<?php
session_start();
require_once __DIR__ . '/../../include/db_conn.php';

$raw_uid = $_SESSION['member_uid'] ?? $_SESSION['user_data'] ?? ($_SESSION['userid'] ?? ($_GET['uid'] ?? ''));
if (is_array($raw_uid)) {
    $uid = $raw_uid['userid'] ?? '';
} else {
    $uid = (string)$raw_uid;
}
$uid = mysqli_real_escape_string($con, trim($uid));

if (empty($uid)) {
    header("Location: ../../index.php");
    exit;
}

$gym = get_gym_details($con);

// Fetch Member info
$q_user = mysqli_query($con, "SELECT u.*, a.city, a.state FROM users u LEFT JOIN address a ON u.userid = a.id WHERE u.userid='$uid'");
$user = ($q_user && mysqli_num_rows($q_user) > 0) ? mysqli_fetch_assoc($q_user) : [
    'userid' => $uid,
    'username' => 'Athlete',
    'mobile' => '',
    'joining_date' => date('Y-m-d')
];

// Fetch Active Membership
$q_plan = mysqli_query($con, "SELECT p.planName, p.validity, e.expire, e.paid_date FROM enrolls_to e INNER JOIN plan p ON e.pid = p.pid WHERE e.uid='$uid' ORDER BY e.expire DESC LIMIT 1");
$plan_name = "General Membership";
$expire_date = "Active";
$days_left = 30;
$is_active = true;
if ($q_plan && mysqli_num_rows($q_plan) > 0) {
    $plan_row = mysqli_fetch_assoc($q_plan);
    $plan_name = $plan_row['planName'];
    $expire_date = date('d M Y', strtotime($plan_row['expire']));
    $diff = strtotime($plan_row['expire']) - strtotime(date('Y-m-d'));
    if ($diff >= 0) {
        $is_active = true;
        $days_left = ceil($diff / (60 * 60 * 24));
    } else {
        $is_active = false;
        $days_left = 0;
    }
}

// Fetch Trainer Name if assigned
$trainer_name = "General Floor Coach";
if (!empty($user['trainer_id'])) {
    $tr_id = $user['trainer_id'];
    $q_tr = mysqli_query($con, "SELECT Full_name FROM admin WHERE username='$tr_id'");
    if ($q_tr && $r_tr = mysqli_fetch_assoc($q_tr)) {
        $trainer_name = $r_tr['Full_name'];
    }
}

// Batch Details
$batch_name = "General Batch (06:00 AM - 10:00 PM)";
if (isset($user['biometric_batch'])) {
    $b_id = intval($user['biometric_batch']);
    $q_bt = mysqli_query($con, "SELECT batch_name, start_time, end_time FROM biometric_batches WHERE id=$b_id");
    if ($q_bt && $r_bt = mysqli_fetch_assoc($q_bt)) {
        $batch_name = $r_bt['batch_name'] . " (" . date('h:i A', strtotime($r_bt['start_time'])) . " - " . date('h:i A', strtotime($r_bt['end_time'])) . ")";
    }
}

// Get photo URL or fallback avatar
$photo_url = function_exists('get_member_photo_url') ? get_member_photo_url($user, '../../') : '../../images/logo.png';
$gym_logo = '../../images/logo.png';

// Generate WhatsApp Pass Share Link
$pass_public_url = "https://" . ($_SERVER['HTTP_HOST'] ?? 'sudarshanfitness.de') . "/member_app/wallet_pass.php?uid=" . urlencode($user['userid']);
$wa_text = "🏋️ *SUDARSHAN FITNESS - VIP ATHLETE DIGITAL PASS*\n\n"
         . "👤 *Athlete:* " . $user['username'] . "\n"
         . "🆔 *Member ID:* #" . $user['userid'] . "\n"
         . "📋 *Plan:* " . $plan_name . "\n"
         . "📅 *Valid Till:* " . $expire_date . ($is_active ? " (Active)" : " (Expired)") . "\n"
         . "⏰ *Batch:* " . $batch_name . "\n\n"
         . "📲 *Open My Digital Gate Pass:* " . $pass_public_url;
$wa_url = "https://wa.me/?text=" . urlencode($wa_text);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>VIP Digital Gym Pass | <?php echo htmlspecialchars($gym['gym_name']); ?></title>
    <link rel="manifest" href="../../member_app/manifest.json">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Sudarshan Pass">
    <link rel="apple-touch-icon" href="../../images/logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&family=Orbitron:wght@700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Outfit', sans-serif; }
        body { background: #030712; color: #f8fafc; min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 20px 15px; }
        
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

        .header-logo-wrap {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .header-logo-wrap img {
            max-height: 48px;
            max-width: 90px;
            object-fit: contain;
            filter: drop-shadow(0 2px 8px rgba(0,0,0,0.5));
            border-radius: 50%;
            border: 1px solid rgba(255,215,0,0.4);
        }

        .gym-meta-title { font-family: 'Orbitron', sans-serif; font-size: 13px; font-weight: 900; letter-spacing: 1px; color: #fff; text-transform: uppercase; }
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
            position: relative;
            width: 96px;
            height: 96px;
            border-radius: 50%;
            border: 3px solid #10b981;
            margin: 0 auto 12px auto;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0,0,0,0.6);
            background: #0f172a;
        }
        .member-photo-frame img { width: 100%; height: 100%; object-fit: cover; }

        .member-name { font-size: 22px; font-weight: 900; color: #fff; letter-spacing: 0.5px; margin-bottom: 2px; }
        .member-id-tag { font-family: 'Orbitron', monospace; font-size: 12px; color: #38bdf8; font-weight: 800; letter-spacing: 1px; }

        /* Comprehensive Matrix Grid */
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

        /* Modal styling */
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
        <a href="index.php" style="color: #94a3b8; text-decoration: none; font-size: 13px; font-weight: 700;">← Back to Dashboard</a>
        <span style="font-size: 11px; color: #10b981; font-weight: 800;">🆔 OFFICIAL DIGITAL PASS</span>
    </div>

    <!-- 💳 COMPLETE VIP DIGITAL WALLET GYM PASS -->
    <div class="pass-container" id="digitalPassCard">
        
        <!-- Header with Gym Logo -->
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
            <!-- Member Photo -->
            <div class="member-photo-frame">
                <img src="<?php echo htmlspecialchars($photo_url); ?>" alt="Member Photo">
            </div>

            <!-- Member Identity -->
            <div class="member-name"><?php echo htmlspecialchars($user['username']); ?></div>
            <div class="member-id-tag">MEMBER ID #<?php echo htmlspecialchars($user['userid']); ?></div>

            <!-- Complete Information Matrix -->
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

            <!-- Dynamic Gate Check-in QR Code -->
            <div class="qr-card-wrap" onclick="toggleFullScreenQR()" title="Tap to expand for optical turnstile scanner">
                <canvas id="qrCanvas"></canvas>
            </div>
            
            <div style="font-size: 11px; color: #94a3b8; font-weight: 600; margin-top: 4px;">
                💡 Tap QR code for high-brightness turnstile scanner
            </div>
        </div>

        <!-- Facility Location Footer -->
        <div class="pass-footer-bar">
            <div>📍 <strong>Facility:</strong> <?php echo htmlspecialchars($gym['gym_address']); ?></div>
            <div style="margin-top: 2px;">📞 <strong>Emergency Helpline:</strong> <?php echo htmlspecialchars($gym['gym_contact']); ?></div>
        </div>
    </div>

    <!-- 🎯 100% FOOLPROOF MULTI-CHANNEL PASS SAVING ACTIONS -->
    <div style="width: 100%; max-width: 380px; margin-top: 18px; display: grid; gap: 10px;">
        
        <!-- 🟢 1. Send Pass to WhatsApp (1-Click Instant Sync) -->
        <a href="<?php echo $wa_url; ?>" target="_blank" class="btn-action" style="background: #25D366; color: #ffffff; box-shadow: 0 4px 18px rgba(37, 211, 102, 0.35);">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="#ffffff"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
            🟢 Send Pass to My WhatsApp
        </a>

        <!-- 📲 2. Pin Pass to Phone Home Screen (PWA Native App) -->
        <button onclick="openInstallModal()" class="btn-action" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #ffffff; box-shadow: 0 4px 18px rgba(16, 185, 129, 0.35);">
            📲 Add Pass to iPhone / Android Home Screen
        </button>

        <!-- 🖼️ 3. Save HD Pass to Photos / Apple Photos -->
        <button onclick="downloadPassImage()" class="btn-action" style="background: rgba(56, 189, 248, 0.15); color: #38bdf8; border: 1px solid rgba(56,189,248,0.4);">
            🖼️ Save Pass Image to Photos Gallery
        </button>

        <a href="index.php" class="btn-action" style="background: rgba(255,255,255,0.05); color: #94a3b8; border: 1px solid rgba(255,255,255,0.1);">
            Return to Dashboard
        </a>
    </div>

    <!-- 📱 PWA HOME SCREEN MODAL -->
    <div id="installModal" class="modal-overlay" onclick="closeModals()">
        <div class="modal-card" onclick="event.stopPropagation()">
            <div style="font-size: 38px; margin-bottom: 8px;">📲</div>
            <h3 style="color: #10b981; font-weight: 800; margin-bottom: 10px;">Pin VIP Pass to Phone Screen</h3>
            <p style="font-size: 13px; color: #cbd5e1; line-height: 1.5; margin-bottom: 15px;">
                Instant 1-tap gate access right next to your apps even with zero internet:
            </p>
            <div style="background: rgba(0,0,0,0.4); border-radius: 14px; padding: 14px; text-align: left; font-size: 12.5px; line-height: 1.6; color: #cbd5e1; margin-bottom: 18px; border: 1px solid rgba(255,255,255,0.1);">
                <div style="margin-bottom: 10px;">
                    🍏 <strong>iPhone (Safari):</strong><br>
                    1. Tap the <strong>Share button (⬆️)</strong> at the bottom of Safari.<br>
                    2. Tap <strong>"Add to Home Screen" (➕)</strong>.<br>
                    3. Tap <strong>"Add"</strong> on the top right.
                </div>
                <div style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 8px;">
                    🔵 <strong>Android (Chrome):</strong><br>
                    1. Tap the <strong>3 dots (⋮)</strong> at top right.<br>
                    2. Tap <strong>"Install app"</strong> or <strong>"Add to Home screen"</strong>.
                </div>
            </div>
            <button onclick="closeModals()" class="btn-action" style="background: #10b981; color: #fff;">Got It 👍</button>
        </div>
    </div>

    <!-- 💡 FULLSCREEN QR MODAL -->
    <div id="fullScreenQRModal" class="modal-overlay" onclick="closeModals()">
        <div class="modal-card" style="background: #ffffff; color: #000; border: 3px solid #10b981;" onclick="event.stopPropagation()">
            <div style="font-size: 14px; font-weight: 900; color: #0f172a; margin-bottom: 10px; text-transform: uppercase;">
                SCAN FOR GATE ENTRANCE
            </div>
            <canvas id="largeQrCanvas" style="width: 240px; height: 240px; margin: 0 auto; display: block;"></canvas>
            <div style="font-size: 15px; font-weight: 900; margin-top: 12px; color: #000;">
                #<?php echo htmlspecialchars($user['userid']); ?> • <?php echo htmlspecialchars($user['username']); ?>
            </div>
            <button onclick="closeModals()" class="btn-action" style="background: #0f172a; color: #fff; margin-top: 15px;">Done</button>
        </div>
    </div>

    <script>
        // Cache credentials locally for instant offline gate access
        try {
            localStorage.setItem('sf_pass_uid', '<?php echo $user['userid']; ?>');
            localStorage.setItem('sf_pass_name', '<?php echo addslashes($user['username']); ?>');
            localStorage.setItem('sf_pass_plan', '<?php echo addslashes($plan_name); ?>');
            localStorage.setItem('sf_pass_expire', '<?php echo addslashes($expire_date); ?>');
        } catch(e) {}

        const qr = new QRious({
            element: document.getElementById('qrCanvas'),
            value: 'MEMBER_ID:<?php echo $user['userid']; ?>',
            size: 150,
            background: 'white',
            foreground: 'black',
            level: 'H'
        });

        const largeQr = new QRious({
            element: document.getElementById('largeQrCanvas'),
            value: 'MEMBER_ID:<?php echo $user['userid']; ?>',
            size: 240,
            background: 'white',
            foreground: 'black',
            level: 'H'
        });

        function openInstallModal() { document.getElementById('installModal').style.display = 'flex'; }
        function toggleFullScreenQR() { document.getElementById('fullScreenQRModal').style.display = 'flex'; }
        function closeModals() {
            document.querySelectorAll('.modal-overlay').forEach(m => m.style.display = 'none');
        }

        function downloadPassImage() {
            const passCard = document.getElementById('digitalPassCard');
            html2canvas(passCard, { scale: 3, backgroundColor: null, useCORS: true }).then(canvas => {
                const link = document.createElement('a');
                link.download = 'Sudarshan_VIP_Pass_<?php echo $user['userid']; ?>.png';
                link.href = canvas.toDataURL('image/png');
                link.click();
            });
        }
    </script>
</body>
</html>
