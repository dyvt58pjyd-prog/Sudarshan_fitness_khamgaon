<?php
session_start();
require '../include/db_conn.php';

if (!isset($_SESSION['member_uid'])) {
    header("Location: index.php");
    exit;
}

$uid = $_SESSION['member_uid'];

// Fetch member details
$q = mysqli_query($con, "SELECT * FROM users WHERE userid='$uid'");
$user = mysqli_fetch_assoc($q);

// Fetch active plan
$q_plan = mysqli_query($con, "SELECT p.planName, e.expire FROM enrolls_to e INNER JOIN plan p ON e.pid = p.pid WHERE e.uid='$uid' AND e.renewal='yes' ORDER BY e.expire DESC LIMIT 1");
$plan_name = "No Active Plan";
$expire_date = "N/A";
if (mysqli_num_rows($q_plan) > 0) {
    $plan_row = mysqli_fetch_assoc($q_plan);
    $plan_name = $plan_row['planName'];
    $expire_date = date('d M Y', strtotime($plan_row['expire']));
}

// Fetch today's attendance
date_default_timezone_set("Asia/Calcutta");
$today = date('Y-m-d');
$q_att = mysqli_query($con, "SELECT * FROM attendance WHERE uid='$uid' AND date='$today' ORDER BY id DESC LIMIT 1");
$att_status = "Not Checked In";
if (mysqli_num_rows($q_att) > 0) {
    $att_row = mysqli_fetch_assoc($q_att);
    if (empty($att_row['exit_time']) || $att_row['exit_time'] == '00:00:00') {
        $att_status = "Checked In at " . date('h:i A', strtotime($att_row['entry_time']));
    } else {
        $att_status = "Completed (Left at " . date('h:i A', strtotime($att_row['exit_time'])) . ")";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Member Dashboard</title>
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#ff6b00">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body { background: #0f172a; color: #fff; min-height: 100vh; padding-bottom: 80px; }
        .header { background: #1e293b; padding: 20px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #334155; }
        .header-title { font-size: 18px; font-weight: 700; }
        .logout-btn { color: #ef4444; text-decoration: none; font-size: 14px; font-weight: 600; }
        
        .content { padding: 20px; }
        .card { background: #1e293b; border-radius: 20px; padding: 25px; margin-bottom: 20px; border: 1px solid #334155; }
        .card-title { color: #8ba3cb; font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; }
        .card-value { font-size: 22px; font-weight: 700; color: #fff; }
        
        .qr-card { text-align: center; background: linear-gradient(135deg, rgba(255,107,0,0.1) 0%, rgba(255,107,0,0.05) 100%); border-color: rgba(255,107,0,0.3); }
        #qrcode-canvas { background: #fff; padding: 10px; border-radius: 12px; margin: 15px 0; max-width: 200px; width: 100%; height: auto; }
        
        /* Bottom Navigation */
        .bottom-nav { position: fixed; bottom: 0; left: 0; right: 0; background: rgba(30, 41, 59, 0.9); backdrop-filter: blur(10px); display: flex; justify-content: space-around; padding: 15px 10px; border-top: 1px solid #334155; padding-bottom: calc(15px + env(safe-area-inset-bottom)); }
        .nav-item { color: #8ba3cb; text-decoration: none; font-size: 12px; font-weight: 600; display: flex; flex-direction: column; align-items: center; gap: 5px; }
        .nav-item.active { color: #ff6b00; }
        .nav-icon { font-size: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-title">Hello, <?php echo htmlspecialchars(explode(' ', $user['username'])[0]); ?>! 👋</div>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>

    <div class="content">
        <div class="card qr-card">
            <div class="card-title" style="color: #ff6b00;">Your Entry Pass</div>
            <p style="font-size: 13px; color: #cbd5e1; margin-bottom: 10px;">Scan this at the reception to check-in/out</p>
            <canvas id="qrcode-canvas"></canvas>
            <div style="font-size: 14px; font-weight: 600; letter-spacing: 1px;"><?php echo htmlspecialchars($uid); ?></div>
        </div>

        <div class="card">
            <div class="card-title">Membership Status</div>
            <div class="card-value"><?php echo htmlspecialchars($plan_name); ?></div>
            <div style="margin-top: 10px; font-size: 14px; color: #10b981;">Valid till: <?php echo htmlspecialchars($expire_date); ?></div>
        </div>

        <div class="card">
            <div class="card-title">Today's Attendance</div>
            <div class="card-value" style="font-size: 18px;"><?php echo htmlspecialchars($att_status); ?></div>
        </div>
    </div>

    <div class="bottom-nav">
        <a href="dashboard.php" class="nav-item active">
            <span class="nav-icon">🏠</span>
            <span>Home</span>
        </a>
        <a href="#" class="nav-item" onclick="alert('Feature coming soon!')">
            <span class="nav-icon">📊</span>
            <span>Progress</span>
        </a>
        <a href="#" class="nav-item" onclick="alert('Feature coming soon!')">
            <span class="nav-icon">👤</span>
            <span>Profile</span>
        </a>
    </div>

    <script>
        // Generate QR Code
        var qr = new QRious({
            element: document.getElementById('qrcode-canvas'),
            value: '<?php echo $uid; ?>',
            size: 250,
            background: 'white',
            foreground: 'black'
        });
    </script>
</body>
</html>
