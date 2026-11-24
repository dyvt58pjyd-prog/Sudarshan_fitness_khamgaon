<?php
/**
 * Shared VIP Digital Wallet Pass View
 * Expected context variables from entrypoints:
 *   $pass_assets_prefix (e.g. '../' or '../../')
 *   $pass_dashboard_url (e.g. 'dashboard.php' or 'index.php')
 *   $pass_login_url     (e.g. 'index.php' or '../../index.php')
 *   $pass_api_prefix    (e.g. '../api/' or '../../api/')
 *   $pass_renew_url     (e.g. '../dashboard/member/payment.php' or 'payment.php')
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/db_conn.php';

$pass_assets_prefix = $pass_assets_prefix ?? '../';
$pass_dashboard_url = $pass_dashboard_url ?? 'dashboard.php';
$pass_login_url     = $pass_login_url ?? 'index.php';
$pass_api_prefix    = $pass_api_prefix ?? '../api/';
$pass_renew_url     = $pass_renew_url ?? '../dashboard/member/payment.php';

$req_uid = $_GET['uid'] ?? '';
$req_sig = $_GET['sig'] ?? '';
$req_exp = $_GET['exp'] ?? 0;

$auth = authorize_wallet_pass_request($con, $req_uid, $req_sig, $req_exp);
if (!$auth['authorized']) {
    if (in_array($auth['reason'] ?? '', ['invalid_signature', 'unsigned_url', 'forbidden_uid'], true)) {
        http_response_code(403);
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Unauthorized Pass Link | Sudarshan Fitness</title>
            <style>
                body { margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center; background:#0f172a; color:#f8fafc; font-family:-apple-system,BlinkMacSystemFont,'Inter',sans-serif; padding:20px; }
                .box { max-width:380px; width:100%; background:#1e293b; border:1px solid rgba(239,68,68,0.4); border-radius:20px; padding:28px; text-align:center; }
                .btn { display:inline-block; margin-top:16px; background:#ff6b00; color:#fff; text-decoration:none; padding:11px 20px; border-radius:12px; font-weight:800; font-size:13px; }
            </style>
        </head>
        <body>
            <div class="box">
                <div style="font-size:38px; margin-bottom:10px;">🔒</div>
                <h2 style="margin:0 0 8px; font-size:19px;">Signed Pass Link Required</h2>
                <p style="margin:0; font-size:13px; color:#94a3b8; line-height:1.5;">
                    For member privacy, direct pass links require a valid cryptographic signature or active member sign-in.
                </p>
                <a class="btn" href="<?php echo htmlspecialchars($pass_login_url); ?>">Sign In to Member App</a>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
    header("Location: " . $pass_login_url);
    exit;
}

$uid_raw = $auth['uid'];
$pass_ctx = fetch_wallet_pass_member_context($con, $uid_raw, $pass_assets_prefix);
$gym = $pass_ctx ? $pass_ctx['gym'] : get_gym_details($con);
$user = $pass_ctx ? $pass_ctx['user'] : [
    'userid' => $uid_raw,
    'username' => 'Athlete',
    'mobile' => '',
    'joining_date' => date('Y-m-d')
];
$uid = mysqli_real_escape_string($con, $uid_raw);

date_default_timezone_set("Asia/Calcutta");
$today_str = date('Y-m-d');
$plan_name = $pass_ctx ? $pass_ctx['plan_name'] : "No Active Plan";
$expire_date = $pass_ctx ? $pass_ctx['expire_date'] : "N/A";
$days_left = $pass_ctx ? $pass_ctx['days_left'] : 0;
$is_active = $pass_ctx ? $pass_ctx['is_active'] : false;
$trainer_name = $pass_ctx ? $pass_ctx['trainer_name'] : "General Floor Coach";
$batch_name = $pass_ctx ? $pass_ctx['batch_name'] : "General Batch (06:00 AM - 10:00 PM)";
$photo_url = $pass_ctx ? $pass_ctx['photo_url'] : ($pass_assets_prefix . 'images/default_avatar.png');
$gym_logo = $pass_ctx ? $pass_ctx['gym_logo'] : resolve_gym_logo_url($gym, $pass_assets_prefix, false);

// Target platform hint from pass issuer (?platform=apple|android|universal)
$target_platform = strtolower(trim((string)($_GET['platform'] ?? 'universal')));
if (!in_array($target_platform, ['apple', 'android'], true)) {
    $target_platform = 'universal';
}
$platform_label = $target_platform === 'apple'
    ? '🍎 APPLE iPHONE PASS'
    : ($target_platform === 'android' ? '🤖 ANDROID SMART PASS' : '🔒 SIGNED SMART PASS');

// Today's Attendance State (for 1-Tap PWA Check-In button)
$att_state = 'none';
$att_time_label = 'Not Checked In Today';
$q_att = mysqli_query($con, "SELECT entry_time, exit_time FROM attendance WHERE uid='$uid' AND date='$today_str' ORDER BY id DESC LIMIT 1");
if ($q_att && mysqli_num_rows($q_att) > 0) {
    $att_row = mysqli_fetch_assoc($q_att);
    if (empty($att_row['exit_time']) || $att_row['exit_time'] === '00:00:00') {
        $att_state = 'checked_in';
        $att_time_label = 'Checked In at ' . date('h:i A', strtotime($att_row['entry_time']));
    } else {
        $att_state = 'completed';
        $att_time_label = 'Workout Completed (' . date('h:i A', strtotime($att_row['entry_time'])) . ' - ' . date('h:i A', strtotime($att_row['exit_time'])) . ')';
    }
}

// Cryptographically signed shareable pass URL & 60s rotating gate QR payload
$pass_exp_used    = ($req_sig !== '' && intval($req_exp) > 0) ? intval($req_exp) : 0;
$pass_sig         = sign_pass_url_signature($con, $user['userid'], $pass_exp_used);
$pass_public_url  = build_signed_wallet_pass_url($con, $user['userid'], 0, false, $target_platform);
$initial_qr_token = generate_rotating_qr_payload($con, $user['userid']);
$initial_qr_rem   = get_rotating_qr_remaining_seconds();
$manifest_href    = ($pass_assets_prefix === '../') ? 'manifest.json' : '../../member_app/manifest.json';

$wa_text = $pass_ctx
    ? build_wallet_pass_whatsapp_text($pass_ctx, $pass_public_url, $target_platform)
    : ("🏋️ *" . $gym['gym_name'] . "*\n📲 *Open Signed Gate Pass:* " . $pass_public_url);
$wa_url = "https://wa.me/?text=" . urlencode($wa_text);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>VIP Digital Gym Pass | <?php echo htmlspecialchars($gym['gym_name']); ?></title>
    <link rel="manifest" href="<?php echo htmlspecialchars($manifest_href); ?>">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="<?php echo htmlspecialchars($gym['gym_name'] ?? 'Sudarshan Pass'); ?>">
    <link rel="apple-touch-icon" href="<?php echo htmlspecialchars($gym_logo); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
        body { background: #2c1b18; color: #f8fafc; min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 20px 15px; }

        .pass-container {
            width: 100%;
            max-width: 380px;
            background: linear-gradient(160deg, #1e293b 0%, #0f172a 60%, #020617 100%);
            border: 2px solid <?php echo $is_active ? '#10b981' : '#ef4444'; ?>;
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 25px 60px rgba(0,0,0,0.8), 0 0 35px <?php echo $is_active ? 'rgba(16,185,129,0.3)' : 'rgba(239,68,68,0.3)'; ?>;
            position: relative;
        }

        .pass-header {
            background: <?php echo $is_active ? 'linear-gradient(135deg, #065f46 0%, #047857 100%)' : 'linear-gradient(135deg, #991b1b 0%, #dc2626 100%)'; ?>;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid rgba(255,255,255,0.15);
        }

        .header-logo-wrap { display: flex; align-items: center; gap: 12px; }
        .header-logo-wrap img {
            max-height: 48px;
            max-width: 90px;
            object-fit: contain;
            filter: drop-shadow(0 2px 8px rgba(0,0,0,0.5));
            border-radius: 50%;
            border: 1px solid rgba(255,215,0,0.4);
        }

        .gym-meta-title { font-size: 13px; font-weight: 900; letter-spacing: 1px; color: #fff; text-transform: uppercase; }
        .gym-meta-sub { font-size: 9.5px; color: rgba(255,255,255,0.8); letter-spacing: 0.5px; text-transform: uppercase; font-weight: 700; }

        .status-pill {
            background: rgba(0,0,0,0.4);
            border: 1px solid rgba(255,255,255,0.25);
            font-size: 10px;
            font-weight: 800;
            padding: 4px 10px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #fff;
            white-space: nowrap;
        }

        .pass-body { padding: 22px 20px; text-align: center; }

        .member-photo-frame {
            width: 96px;
            height: 96px;
            border-radius: 50%;
            border: 3px solid <?php echo $is_active ? '#10b981' : '#ef4444'; ?>;
            margin: 0 auto 12px auto;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0,0,0,0.6);
            background: #0f172a;
        }
        .member-photo-frame img { width: 100%; height: 100%; object-fit: cover; }

        .member-name { font-size: 22px; font-weight: 900; color: #fff; letter-spacing: 0.5px; margin-bottom: 2px; }
        .member-id-tag { font-size: 12px; color: #38bdf8; font-weight: 800; letter-spacing: 1px; }

        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            background: rgba(0,0,0,0.35);
            border: 1px solid rgba(255,255,255,0.06);
            padding: 14px;
            border-radius: 16px;
            margin: 16px 0;
            text-align: left;
        }
        .detail-item { display: flex; flex-direction: column; }
        .detail-lbl { font-size: 9.5px; color: #94a3b8; text-transform: uppercase; font-weight: 800; letter-spacing: 0.5px; }
        .detail-val { font-size: 13px; font-weight: 800; color: #fff; margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        .qr-card-wrap {
            background: #ffffff;
            padding: 12px;
            border-radius: 18px;
            display: inline-block;
            margin: 8px 0;
            box-shadow: 0 10px 30px rgba(0,0,0,0.4);
            cursor: pointer;
            transition: transform 0.2s ease;
        }
        .qr-card-wrap:hover { transform: scale(1.04); }

        .qr-shield-bar {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 6px;
            padding: 5px 12px;
            border-radius: 999px;
            background: rgba(16, 185, 129, 0.12);
            border: 1px solid rgba(16, 185, 129, 0.35);
            font-size: 11px;
            font-weight: 700;
            color: #34d399;
        }

        .checkin-panel {
            margin-top: 14px;
            padding: 12px;
            border-radius: 14px;
            background: rgba(15, 23, 42, 0.75);
            border: 1px solid rgba(255,255,255,0.08);
        }

        .pass-footer-bar {
            background: rgba(0,0,0,0.4);
            border-top: 1px solid rgba(255,255,255,0.06);
            padding: 12px 16px;
            font-size: 10.5px;
            color: #94a3b8;
            line-height: 1.4;
        }

        .btn-action {
            width: 100%;
            padding: 13px;
            border-radius: 14px;
            font-size: 13px;
            font-weight: 800;
            cursor: pointer;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .btn-action:hover { transform: translateY(-2px); }
        .btn-action:disabled { opacity: 0.65; cursor: wait; transform: none; }

        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.85);
            backdrop-filter: blur(8px);
            z-index: 99999;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .modal-card {
            background: #1e293b;
            border: 2px solid #38bdf8;
            border-radius: 24px;
            padding: 25px 20px;
            max-width: 380px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 50px rgba(0,0,0,0.8);
        }
    </style>
</head>
<body>

    <div style="width: 100%; max-width: 380px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center;">
        <a href="<?php echo htmlspecialchars($pass_dashboard_url); ?>" style="color: #94a3b8; text-decoration: none; font-size: 13px; font-weight: 700;">← Back to Dashboard</a>
        <span style="font-size: 11px; color: #10b981; font-weight: 800;"><?php echo htmlspecialchars($platform_label); ?></span>
    </div>

    <!-- COMPLETE VIP DIGITAL WALLET GYM PASS -->
    <div class="pass-container" id="digitalPassCard">
        <div class="pass-header">
            <div class="header-logo-wrap">
                <img src="<?php echo htmlspecialchars($gym_logo); ?>" alt="Gym Logo">
                <div>
                    <div class="gym-meta-title"><?php echo htmlspecialchars($gym['gym_name']); ?></div>
                    <div class="gym-meta-sub">Official Athlete Pass</div>
                </div>
            </div>
            <div class="status-pill">
                <?php echo $is_active ? '🟢 ACTIVE' : '🔴 EXPIRED'; ?>
            </div>
        </div>

        <div class="pass-body">
            <div class="member-photo-frame">
                <img src="<?php echo htmlspecialchars($photo_url); ?>" alt="Member Photo">
            </div>

            <div class="member-name"><?php echo htmlspecialchars($user['username']); ?></div>
            <div class="member-id-tag">MEMBER ID #<?php echo htmlspecialchars($user['userid']); ?></div>

            <div class="details-grid">
                <div class="detail-item">
                    <span class="detail-lbl">Membership Plan</span>
                    <span class="detail-val" style="color: #38bdf8;"><?php echo htmlspecialchars($plan_name); ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-lbl">Validity Countdown</span>
                    <span class="detail-val" style="color: <?php echo $is_active ? '#10b981' : '#ef4444'; ?>;">
                        <?php echo htmlspecialchars($expire_date); ?>
                        <?php if ($is_active): ?>
                            <span style="font-size: 10px; opacity: 0.8;">(<?php echo $days_left; ?>d left)</span>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="detail-item">
                    <span class="detail-lbl">Assigned Batch</span>
                    <span class="detail-val" title="<?php echo htmlspecialchars($batch_name); ?>"><?php echo htmlspecialchars($batch_name); ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-lbl">Coach / Trainer</span>
                    <span class="detail-val"><?php echo htmlspecialchars($trainer_name); ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-lbl">Registered Mobile</span>
                    <span class="detail-val"><?php echo htmlspecialchars($user['mobile']); ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-lbl">Member Since</span>
                    <span class="detail-val"><?php echo !empty($user['joining_date']) ? date('d M Y', strtotime($user['joining_date'])) : '2024'; ?></span>
                </div>
            </div>

            <!-- Live Rotating Gate QR Code -->
            <div class="qr-card-wrap" onclick="toggleFullScreenQR()" title="Tap to expand for optical turnstile scanner">
                <canvas id="qrCanvas"></canvas>
            </div>

            <div>
                <span class="qr-shield-bar" id="qrShieldBadge">
                    🔒 Rotating QR • Refreshes in <span id="qrTimerNum"><?php echo intval($initial_qr_rem); ?></span>s
                </span>
            </div>

            <div style="font-size: 11px; color: #94a3b8; font-weight: 600; margin-top: 8px;">
                💡 Tap QR to expand (anti-screenshot dynamic code)
            </div>

            <!-- 1-Tap PWA Check-In Panel -->
            <div class="checkin-panel">
                <div id="attStateText" style="font-size: 11.5px; color: #cbd5e1; font-weight: 700; margin-bottom: 8px;">
                    📍 Today: <?php echo htmlspecialchars($att_time_label); ?>
                </div>
                <?php if ($is_active): ?>
                    <button type="button" id="pwaCheckinBtn" onclick="triggerPwaCheckin()" class="btn-action" style="padding: 11px; background: <?php echo $att_state === 'checked_in' ? 'linear-gradient(135deg, #f59e0b, #d97706)' : ($att_state === 'completed' ? 'rgba(16,185,129,0.2)' : 'linear-gradient(135deg, #ff6b00, #ea580c)'); ?>; color: #fff;">
                        <?php echo $att_state === 'checked_in' ? '👋 1-Tap Check Out' : ($att_state === 'completed' ? '✅ Workout Completed Today' : '⚡ 1-Tap Gym Check-In'); ?>
                    </button>
                <?php else: ?>
                    <a href="<?php echo htmlspecialchars($pass_renew_url); ?>" class="btn-action" style="padding: 11px; background: linear-gradient(135deg, #ef4444, #dc2626); color: #fff;">
                        💳 Renew Membership to Unlock Gate
                    </a>
                <?php endif; ?>
                <div id="pwaCheckinFeedback" style="display: none; margin-top: 8px; font-size: 11.5px; font-weight: 700;"></div>
            </div>
        </div>

        <div class="pass-footer-bar">
            <div>📍 <strong>Facility:</strong> <?php echo htmlspecialchars($gym['gym_address']); ?></div>
            <div style="margin-top: 2px;">📞 <strong>Helpline:</strong> <?php echo htmlspecialchars($gym['gym_contact']); ?></div>
        </div>
    </div>

    <!-- MULTI-CHANNEL PASS ACTIONS -->
    <div style="width: 100%; max-width: 380px; margin-top: 18px; display: grid; gap: 10px;">
        <a href="<?php echo htmlspecialchars($wa_url); ?>" target="_blank" class="btn-action" style="background: #25D366; color: #ffffff; box-shadow: 0 4px 18px rgba(37, 211, 102, 0.35);">
            🟢 Send Signed Pass to WhatsApp
        </a>

        <button type="button" onclick="openInstallModal()" class="btn-action" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #ffffff; box-shadow: 0 4px 18px rgba(16, 185, 129, 0.35);">
            📲 Pin Pass to iPhone / Android Home Screen
        </button>

        <button type="button" onclick="downloadPassImage()" class="btn-action" style="background: rgba(56, 189, 248, 0.15); color: #38bdf8; border: 1px solid rgba(56,189,248,0.4);">
            🖼️ Save ID Card Snapshot
        </button>

        <a href="<?php echo htmlspecialchars($pass_dashboard_url); ?>" class="btn-action" style="background: rgba(255,255,255,0.05); color: #94a3b8; border: 1px solid rgba(255,255,255,0.1);">
            Return to Dashboard
        </a>
    </div>

    <!-- PWA HOME SCREEN MODAL -->
    <div id="installModal" class="modal-overlay" onclick="closeModals()">
        <div class="modal-card" onclick="event.stopPropagation()">
            <div style="font-size: 38px; margin-bottom: 8px;">📲</div>
            <h3 style="color: #10b981; font-weight: 800; margin-bottom: 10px;">Pin Signed Pass to Home Screen</h3>
            <p style="font-size: 13px; color: #cbd5e1; line-height: 1.5; margin-bottom: 15px;">
                Open your rotating gate QR and 1-Tap Check-In right from your home screen:
            </p>
            <div style="background: rgba(0,0,0,0.4); border-radius: 14px; padding: 14px; text-align: left; font-size: 12.5px; line-height: 1.6; color: #cbd5e1; margin-bottom: 18px; border: 1px solid rgba(255,255,255,0.1);">
                <div style="margin-bottom: 10px;">
                    🍏 <strong>iPhone (Safari):</strong><br>
                    1. Tap <strong>Share (⬆️)</strong> in Safari.<br>
                    2. Tap <strong>"Add to Home Screen" (➕)</strong> -> <strong>Add</strong>.
                </div>
                <div style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 8px;">
                    🔵 <strong>Android (Chrome):</strong><br>
                    1. Tap the <strong>3 dots (⋮)</strong> at top right.<br>
                    2. Tap <strong>"Install app"</strong> or <strong>"Add to Home screen"</strong>.
                </div>
            </div>
            <button type="button" onclick="closeModals()" class="btn-action" style="background: #10b981; color: #fff;">Got It 👍</button>
        </div>
    </div>

    <!-- FULLSCREEN QR MODAL -->
    <div id="fullScreenQRModal" class="modal-overlay" onclick="closeModals()">
        <div class="modal-card" style="background: #ffffff; color: #000; border: 3px solid #10b981;" onclick="event.stopPropagation()">
            <div style="font-size: 14px; font-weight: 900; color: #0f172a; margin-bottom: 10px; text-transform: uppercase;">
                SCAN FOR GATE ENTRANCE
            </div>
            <canvas id="largeQrCanvas" style="width: 240px; height: 240px; margin: 0 auto; display: block;"></canvas>
            <div style="font-size: 15px; font-weight: 900; margin-top: 12px; color: #000;">
                #<?php echo htmlspecialchars($user['userid']); ?> • <?php echo htmlspecialchars($user['username']); ?>
            </div>
            <div style="font-size: 12px; font-weight: 700; color: #059669; margin-top: 6px;">
                Live Pass • Refreshes in <span id="largeQrTimerNum"><?php echo intval($initial_qr_rem); ?></span>s
            </div>
            <button type="button" onclick="closeModals()" class="btn-action" style="background: #0f172a; color: #fff; margin-top: 15px;">Done</button>
        </div>
    </div>

    <script>
        const PASS_UID        = <?php echo json_encode((string)$user['userid']); ?>;
        const PASS_SIG        = <?php echo json_encode((string)$pass_sig); ?>;
        const PASS_EXP        = <?php echo intval($pass_exp_used); ?>;
        const API_PREFIX      = <?php echo json_encode((string)$pass_api_prefix); ?>;
        let currentQrToken    = <?php echo json_encode((string)$initial_qr_token); ?>;
        let qrSecondsLeft     = <?php echo intval($initial_qr_rem); ?>;

        try {
            localStorage.setItem('sf_pass_uid', PASS_UID);
            localStorage.setItem('sf_pass_name', <?php echo json_encode((string)$user['username']); ?>);
            localStorage.setItem('sf_pass_plan', <?php echo json_encode((string)$plan_name); ?>);
            localStorage.setItem('sf_pass_expire', <?php echo json_encode((string)$expire_date); ?>);
        } catch (e) {}

        let qr = null;
        let largeQr = null;
        if (typeof QRious !== 'undefined') {
            qr = new QRious({
                element: document.getElementById('qrCanvas'),
                value: currentQrToken,
                size: 150,
                background: 'white',
                foreground: 'black',
                level: 'M'
            });

            largeQr = new QRious({
                element: document.getElementById('largeQrCanvas'),
                value: currentQrToken,
                size: 240,
                background: 'white',
                foreground: 'black',
                level: 'M'
            });
        }

        function renderQrToken(token, secondsLeft) {
            currentQrToken = token;
            qrSecondsLeft = secondsLeft;
            if (qr) qr.value = token;
            if (largeQr) largeQr.value = token;
            const t1 = document.getElementById('qrTimerNum');
            const t2 = document.getElementById('largeQrTimerNum');
            if (t1) t1.textContent = qrSecondsLeft;
            if (t2) t2.textContent = qrSecondsLeft;
        }

        async function refreshLiveQr() {
            try {
                const url = `${API_PREFIX}get_live_qr.php?uid=${encodeURIComponent(PASS_UID)}&sig=${encodeURIComponent(PASS_SIG)}&exp=${PASS_EXP}`;
                const res = await fetch(url, { cache: 'no-store' });
                const data = await res.json();
                if (data.success && data.token) {
                    renderQrToken(data.token, data.expires_in || 60);
                }
            } catch (e) {
                qrSecondsLeft = 15;
            }
        }

        setInterval(() => {
            qrSecondsLeft -= 1;
            if (qrSecondsLeft <= 0) {
                refreshLiveQr();
                return;
            }
            const t1 = document.getElementById('qrTimerNum');
            const t2 = document.getElementById('largeQrTimerNum');
            if (t1) t1.textContent = qrSecondsLeft;
            if (t2) t2.textContent = qrSecondsLeft;
        }, 1000);

        async function triggerPwaCheckin() {
            const btn = document.getElementById('pwaCheckinBtn');
            const feedback = document.getElementById('pwaCheckinFeedback');
            const stateText = document.getElementById('attStateText');
            if (!btn) return;

            btn.disabled = true;
            const prevLabel = btn.textContent;
            btn.textContent = '⏳ Logging Attendance...';
            feedback.style.display = 'none';

            try {
                const res = await fetch(`${API_PREFIX}pwa_checkin.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ uid: PASS_UID, sig: PASS_SIG, exp: PASS_EXP })
                });
                const data = await res.json();

                feedback.style.display = 'block';
                if (data.success) {
                    feedback.style.color = '#34d399';
                    if (data.action === 'check-in') {
                        feedback.textContent = `✓ Checked in at ${data.time} (+${data.xp_earned || 50} XP • Streak: ${data.streak}d)`;
                        stateText.textContent = `📍 Today: Checked In at ${data.time}`;
                        btn.style.background = 'linear-gradient(135deg, #f59e0b, #d97706)';
                        btn.textContent = '👋 1-Tap Check Out';
                    } else if (data.action === 'check-out') {
                        feedback.textContent = `👋 Checked out at ${data.time}. Great workout!`;
                        stateText.textContent = `📍 Today: Workout Completed`;
                        btn.style.background = 'rgba(16,185,129,0.2)';
                        btn.textContent = '✅ Workout Completed Today';
                    } else if (data.action === 'cooldown') {
                        feedback.style.color = '#fbbf24';
                        feedback.textContent = data.message;
                        btn.textContent = prevLabel;
                    } else {
                        feedback.textContent = data.message;
                        btn.textContent = '✅ Workout Completed Today';
                    }
                } else {
                    feedback.style.color = '#f87171';
                    feedback.textContent = data.message || 'Unable to log attendance.';
                    btn.textContent = prevLabel;
                }
            } catch (err) {
                feedback.style.display = 'block';
                feedback.style.color = '#f87171';
                feedback.textContent = 'Network error. Please scan your QR at the desk.';
                btn.textContent = prevLabel;
            } finally {
                btn.disabled = false;
            }
        }

        function openInstallModal() { document.getElementById('installModal').style.display = 'flex'; }
        function toggleFullScreenQR() { document.getElementById('fullScreenQRModal').style.display = 'flex'; }
        function closeModals() {
            document.querySelectorAll('.modal-overlay').forEach(m => m.style.display = 'none');
        }

        function downloadPassImage() {
            const passCard = document.getElementById('digitalPassCard');
            html2canvas(passCard, { scale: 3, backgroundColor: null, useCORS: true }).then(canvas => {
                const link = document.createElement('a');
                link.download = `Sudarshan_VIP_Pass_${PASS_UID}.png`;
                link.href = canvas.toDataURL('image/png');
                link.click();
            });
        }

        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register(<?php echo json_encode($pass_assets_prefix === '../' ? 'sw.js' : '../../member_app/sw.js'); ?>).catch(() => {});
        }
    </script>
</body>
</html>
