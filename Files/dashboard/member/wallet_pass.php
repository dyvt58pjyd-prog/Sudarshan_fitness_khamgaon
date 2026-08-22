<?php
session_start();
require '../../include/db_conn.php';

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

// Fetch Active Membership (order by latest expiration date)
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
$photo_url = get_member_photo_url($user, '../../');

// Gym Logo URL
$gym_logo = !empty($gym_settings_data['gym_logo']) ? $gym_settings_data['gym_logo'] : (!empty($gym['gym_logo']) ? $gym['gym_logo'] : '../../images/logo.png');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>VIP Digital Gym Pass | <?php echo htmlspecialchars($gym['gym_name']); ?></title>
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
            padding: 12px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 800;
            cursor: pointer;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            transition: transform 0.2s ease;
        }
        .btn-action:hover { transform: translateY(-2px); }
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
            <div class="qr-card-wrap">
                <canvas id="qrCanvas"></canvas>
            </div>
            
            <div style="font-size: 11px; color: #94a3b8; font-weight: 600; margin-top: 4px;">
                Scan for Instant Turnstile &amp; Biometric Gate Access
            </div>
        </div>

        <!-- Facility Location Footer -->
        <div class="pass-footer-bar">
            <div>📍 <strong>Facility:</strong> <?php echo htmlspecialchars($gym['gym_address']); ?></div>
            <div style="margin-top: 2px;">📞 <strong>Emergency Helpline:</strong> <?php echo htmlspecialchars($gym['gym_contact']); ?></div>
        </div>
    </div>

    <!-- Direct Apple & Google Wallet Actions -->
    <div style="width: 100%; max-width: 380px; margin-top: 18px; display: grid; gap: 9px;">
        <!-- Apple Wallet Button -->
        <a href="../../api/generate_apple_pass.php" class="btn-action" style="background: #000; color: #fff; border: 1px solid rgba(255,255,255,0.25); box-shadow: 0 4px 15px rgba(0,0,0,0.6);">
            <svg width="20" height="20" viewBox="0 0 170 170" fill="#fff"><path d="M150.37 130.25c-2.45 5.66-5.35 10.87-8.71 15.66-4.58 6.53-8.33 11.05-11.22 13.56-4.48 4.12-9.28 6.23-14.42 6.35-3.69 0-8.14-1.05-13.32-3.18-5.19-2.12-9.97-3.17-14.34-3.17-4.58 0-9.49 1.05-14.75 3.17-5.26 2.13-9.5 3.24-12.74 3.35-4.35.13-9.16-1.9-14.42-6.08-3.69-3.04-7.67-7.81-11.96-14.34-6.42-9.78-11.48-20.9-15.19-33.35-3.71-12.44-5.56-23.77-5.56-33.99 0-14.12 3.65-25.59 10.95-34.42 7.3-8.83 16.32-13.35 27.06-13.56 4.35 0 9.29 1.15 14.82 3.46 5.53 2.31 9.38 3.52 11.55 3.64 1.74 0 5.86-1.33 12.38-3.99 6.51-2.67 11.98-3.83 16.4-3.5 12.18.98 21.73 5.34 28.66 13.08-10.66 6.42-15.88 15.24-15.66 26.46.22 8.71 3.52 16.03 9.91 21.96 6.39 5.94 13.91 9.26 22.56 9.98-2.17 6.42-4.78 12.76-7.83 19.01zM119.22 33.15c0-6.74 2.45-13.15 7.34-19.23 4.9-6.08 10.9-10.15 18-12.22-.44 6.74-2.99 13.1-7.65 19.08-4.66 5.98-10.74 9.93-18.25 11.85-.22-.76-.44-1.59-.44-2.48z"/></svg>
            Add to Apple Wallet
        </a>

        <!-- Google Wallet Button -->
        <a href="../../api/generate_google_pass.php" class="btn-action" style="background: #ffffff; color: #000; box-shadow: 0 4px 15px rgba(255,255,255,0.2);">
            <svg width="20" height="20" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/></svg>
            Add to Google Wallet
        </a>

        <!-- Download Image Button -->
        <button onclick="downloadPassImage()" class="btn-action" style="background: rgba(56, 189, 248, 0.15); color: #38bdf8; border: 1px solid rgba(56,189,248,0.4);">
            📥 Save Pass Image to Photos
        </button>

        <a href="index.php" class="btn-action" style="background: rgba(255,255,255,0.05); color: #94a3b8; border: 1px solid rgba(255,255,255,0.1);">
            Return to Dashboard
        </a>
    </div>

    <script>
        const qr = new QRious({
            element: document.getElementById('qrCanvas'),
            value: 'MEMBER_ID:<?php echo $user['userid']; ?>',
            size: 150,
            background: 'white',
            foreground: 'black',
            level: 'H'
        });

        function downloadPassImage() {
            const passCard = document.getElementById('digitalPassCard');
            html2canvas(passCard, { scale: 3, backgroundColor: null }).then(canvas => {
                const link = document.createElement('a');
                link.download = 'Sudarshan_VIP_Pass_<?php echo $user['userid']; ?>.png';
                link.href = canvas.toDataURL('image/png');
                link.click();
            });
        }
    </script>
</body>
</html>
