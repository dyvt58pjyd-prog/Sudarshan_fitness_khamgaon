<?php
session_start();
require '../include/db_conn.php';

if (!isset($_SESSION['member_uid'])) {
    header("Location: index.php");
    exit;
}

$uid = $_SESSION['member_uid'];
$gym = get_gym_details($con);

// Fetch Member info
$q_user = mysqli_query($con, "SELECT * FROM users WHERE userid='$uid'");
$user = mysqli_fetch_assoc($q_user);

// Fetch Active Membership
$q_plan = mysqli_query($con, "SELECT p.planName, e.expire, e.paid_date FROM enrolls_to e INNER JOIN plan p ON e.pid = p.pid WHERE e.uid='$uid' AND e.renewal='yes' ORDER BY e.expire DESC LIMIT 1");
$plan_name = "No Active Plan";
$expire_date = "N/A";
$is_active = false;
if ($q_plan && mysqli_num_rows($q_plan) > 0) {
    $plan_row = mysqli_fetch_assoc($q_plan);
    $plan_name = $plan_row['planName'];
    $expire_date = date('d M Y', strtotime($plan_row['expire']));
    if (strtotime($plan_row['expire']) >= strtotime(date('Y-m-d'))) {
        $is_active = true;
    }
}

// Get photo URL or fallback avatar
$photo_url = get_member_photo_url($user, '../');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Digital Gym Pass | <?php echo htmlspecialchars($gym['gym_name']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800;900&family=Orbitron:wght@700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Outfit', sans-serif; }
        body { background: #030712; color: #f8fafc; min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 20px; }
        
        .pass-container {
            width: 100%;
            max-width: 360px;
            background: linear-gradient(145deg, #1e293b 0%, #0f172a 100%);
            border: 2px solid <?php echo $is_active ? '#10b981' : '#ef4444'; ?>;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 20px 50px rgba(0,0,0,0.6), 0 0 30px <?php echo $is_active ? 'rgba(16,185,129,0.25)' : 'rgba(239,68,68,0.25)'; ?>;
            position: relative;
        }

        .pass-header {
            background: <?php echo $is_active ? 'linear-gradient(135deg, #10b981, #059669)' : 'linear-gradient(135deg, #ef4444, #b91c1c)'; ?>;
            padding: 16px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #fff;
        }

        .pass-title { font-family: 'Orbitron', sans-serif; font-size: 13px; font-weight: 900; letter-spacing: 1.5px; text-transform: uppercase; }
        .pass-status-badge { background: rgba(0,0,0,0.3); font-size: 10px; font-weight: 800; padding: 4px 10px; border-radius: 12px; text-transform: uppercase; }

        .pass-body { padding: 22px; text-align: center; }
        
        .member-avatar-wrap {
            width: 84px;
            height: 84px;
            border-radius: 50%;
            border: 3px solid #fff;
            margin: 0 auto 12px auto;
            overflow: hidden;
            box-shadow: 0 6px 20px rgba(0,0,0,0.4);
            background: #0f172a;
        }
        .member-avatar-wrap img { width: 100%; height: 100%; object-fit: cover; }

        .member-name { font-size: 20px; font-weight: 800; color: #fff; margin-bottom: 2px; }
        .member-uid { font-size: 12px; color: #94a3b8; font-family: 'Orbitron', monospace; font-weight: 700; }

        .pass-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            background: rgba(0,0,0,0.25);
            padding: 12px;
            border-radius: 14px;
            margin: 16px 0;
            text-align: left;
        }
        .info-lbl { font-size: 10px; color: #94a3b8; text-transform: uppercase; font-weight: 700; }
        .info-val { font-size: 13px; font-weight: 800; color: #fff; margin-top: 2px; }

        .qr-card-wrap {
            background: #ffffff;
            padding: 14px;
            border-radius: 16px;
            display: inline-block;
            margin: 8px 0;
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
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
            margin-top: 10px;
            text-decoration: none;
        }
    </style>
</head>
<body>

    <div style="width: 100%; max-width: 360px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center;">
        <a href="dashboard.php" style="color: #94a3b8; text-decoration: none; font-size: 13px; font-weight: 700;">← Back to App</a>
        <span style="font-size: 11px; color: #10b981; font-weight: 800;">📲 DIGITAL SMART PASS</span>
    </div>

    <!-- 💳 DIGITAL WALLET PASS CARD -->
    <div class="pass-container" id="digitalPassCard">
        <div class="pass-header">
            <div class="pass-title"><?php echo htmlspecialchars($gym['gym_name']); ?></div>
            <div class="pass-status-badge"><?php echo $is_active ? 'ACTIVE PASS' : 'EXPIRED'; ?></div>
        </div>

        <div class="pass-body">
            <div class="member-avatar-wrap">
                <img src="<?php echo htmlspecialchars($photo_url); ?>" alt="Member Photo">
            </div>

            <div class="member-name"><?php echo htmlspecialchars($user['username']); ?></div>
            <div class="member-uid">MEMBER ID #<?php echo htmlspecialchars($user['userid']); ?></div>

            <div class="pass-info-grid">
                <div>
                    <div class="info-lbl">Plan</div>
                    <div class="info-val"><?php echo htmlspecialchars($plan_name); ?></div>
                </div>
                <div>
                    <div class="info-lbl">Valid Till</div>
                    <div class="info-val" style="color: <?php echo $is_active ? '#10b981' : '#ef4444'; ?>;"><?php echo htmlspecialchars($expire_date); ?></div>
                </div>
            </div>

            <!-- Dynamic Dynamic Gate Pass QR Code -->
            <div class="qr-card-wrap">
                <canvas id="qrCanvas"></canvas>
            </div>
            
            <div style="font-size: 10px; color: #94a3b8; margin-top: 4px;">
                Scan at entrance terminal for contactless gate access.
            </div>
        </div>
    </div>

    <!-- Actions Buttons -->
    <div style="width: 100%; max-width: 360px; margin-top: 15px;">
        <button onclick="downloadPassImage()" class="btn-action" style="background: linear-gradient(135deg, #38bdf8, #0284c7); color: #fff;">
            📥 Save Pass to Photos / Gallery
        </button>
        <a href="dashboard.php" class="btn-action" style="background: rgba(255,255,255,0.08); color: #fff; border: 1px solid rgba(255,255,255,0.15);">
            Return to Dashboard
        </a>
    </div>

    <script>
        // Generate QR code for member ID
        const qr = new QRious({
            element: document.getElementById('qrCanvas'),
            value: 'MEMBER_ID:<?php echo $user['userid']; ?>',
            size: 150,
            background: 'white',
            foreground: 'black',
            level: 'H'
        });

        // Save card as Image using html2canvas
        function downloadPassImage() {
            const passCard = document.getElementById('digitalPassCard');
            html2canvas(passCard, { scale: 3, backgroundColor: null }).then(canvas => {
                const link = document.createElement('a');
                link.download = 'Sudarshan_Pass_<?php echo $user['userid']; ?>.png';
                link.href = canvas.toDataURL('image/png');
                link.click();
            });
        }
    </script>
</body>
</html>
