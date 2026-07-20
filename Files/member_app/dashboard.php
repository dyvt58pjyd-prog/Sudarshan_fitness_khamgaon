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

// Fetch active plan & payments
$q_plan = mysqli_query($con, "SELECT p.planName, e.expire, e.paid_date, e.paid FROM enrolls_to e INNER JOIN plan p ON e.pid = p.pid WHERE e.uid='$uid' AND e.renewal='yes' ORDER BY e.expire DESC LIMIT 5");
$plan_name = "No Active Plan";
$expire_date = "N/A";
$payments = [];
if (mysqli_num_rows($q_plan) > 0) {
    $first = true;
    while ($row = mysqli_fetch_assoc($q_plan)) {
        if ($first) {
            $plan_name = $row['planName'];
            $expire_date = date('d M Y', strtotime($row['expire']));
            $first = false;
        }
        $payments[] = $row;
    }
}

// Fetch Diet & Routine
$q_routine = mysqli_query($con, "SELECT * FROM member_routines WHERE uid='$uid'");
$diet_plan = "No diet plan assigned yet.";
$workout_plan = "No workout routine assigned yet.";
if (mysqli_num_rows($q_routine) > 0) {
    $routine = mysqli_fetch_assoc($q_routine);
    if (!empty($routine['diet_plan'])) $diet_plan = $routine['diet_plan'];
    if (!empty($routine['workout_plan'])) $workout_plan = $routine['workout_plan'];
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
    <title>Sudarshan Member App</title>
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#0f172a">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body { background: #0f172a; color: #f8fafc; min-height: 100vh; padding-bottom: 80px; background-image: radial-gradient(circle at 100% 0%, rgba(255,107,0,0.1) 0%, transparent 50%); }
        .header { padding: 25px 20px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .header-title { font-size: 20px; font-weight: 800; color: #fff; }
        .header-title span { color: #ff6b00; }
        .logout-btn { color: #ef4444; text-decoration: none; font-size: 13px; font-weight: 700; text-transform: uppercase; background: rgba(239, 68, 68, 0.1); padding: 6px 12px; border-radius: 8px; }
        
        .content { padding: 20px; }
        .card { background: linear-gradient(145deg, rgba(30, 41, 59, 0.7) 0%, rgba(15, 23, 42, 0.8) 100%); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 20px; padding: 25px; margin-bottom: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); position: relative; overflow: hidden; }
        .card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; background: linear-gradient(90deg, #ff6b00, #f59e0b); }
        .card-title { color: #ff6b00; font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 15px; }
        .card-value { font-size: 22px; font-weight: 800; color: #fff; line-height: 1.4; }
        
        .timeline { margin-top: 15px; }
        .timeline-item { padding: 12px 0; border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: space-between; }
        .timeline-item:last-child { border-bottom: none; padding-bottom: 0; }
        .timeline-date { color: #94a3b8; font-size: 13px; font-weight: 500; }
        .timeline-amount { color: #10b981; font-weight: 700; font-size: 15px; }
        
        .routine-box { background: rgba(0,0,0,0.2); padding: 15px; border-radius: 12px; margin-bottom: 15px; border: 1px solid rgba(255,255,255,0.02); }
        .routine-label { color: #38bdf8; font-size: 12px; font-weight: 700; text-transform: uppercase; margin-bottom: 8px; }
        .routine-text { color: #cbd5e1; font-size: 14px; line-height: 1.6; white-space: pre-wrap; }
        
        .bottom-nav { position: fixed; bottom: 0; left: 0; right: 0; background: rgba(15, 23, 42, 0.95); backdrop-filter: blur(10px); display: flex; justify-content: space-around; padding: 15px 10px; border-top: 1px solid rgba(255,255,255,0.05); padding-bottom: calc(15px + env(safe-area-inset-bottom)); }
        .nav-item { color: #64748b; text-decoration: none; font-size: 11px; font-weight: 700; text-transform: uppercase; display: flex; flex-direction: column; align-items: center; gap: 6px; }
        .nav-item.active { color: #ff6b00; }
        .nav-icon { font-size: 22px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-title">Hello, <span><?php echo htmlspecialchars(explode(' ', $user['username'])[0]); ?></span></div>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>

    <div class="content">
        <div class="card">
            <div class="card-title">Membership Status</div>
            <div class="card-value"><?php echo htmlspecialchars($plan_name); ?></div>
            <div style="margin-top: 12px; font-size: 14px; font-weight: 600; color: #10b981; background: rgba(16, 185, 129, 0.1); padding: 8px 12px; border-radius: 8px; display: inline-block;">
                Valid till: <?php echo htmlspecialchars($expire_date); ?>
            </div>
        </div>

        <div class="card">
            <div class="card-title">My Diet & Routine</div>
            <div class="routine-box">
                <div class="routine-label">Workout Plan</div>
                <div class="routine-text"><?php echo htmlspecialchars($workout_plan); ?></div>
            </div>
            <div class="routine-box" style="margin-bottom: 0;">
                <div class="routine-label" style="color: #10b981;">Diet Plan</div>
                <div class="routine-text"><?php echo htmlspecialchars($diet_plan); ?></div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-title">Payment History</div>
            <div class="timeline">
                <?php 
                if (count($payments) > 0) {
                    foreach ($payments as $pay) {
                        echo '<div class="timeline-item">';
                        echo '<div class="timeline-date">' . date('d M Y', strtotime($pay['paid_date'])) . ' (' . htmlspecialchars($pay['planName']) . ')</div>';
                        echo '<div class="timeline-amount">₹' . htmlspecialchars($pay['paid']) . '</div>';
                        echo '</div>';
                    }
                } else {
                    echo '<div class="timeline-item"><div class="timeline-date">No payment history found.</div></div>';
                }
                ?>
            </div>
        </div>

        <div class="card">
            <div class="card-title">Today's Attendance</div>
            <div class="card-value" style="font-size: 18px; color: #38bdf8;"><?php echo htmlspecialchars($att_status); ?></div>
        </div>
    </div>

    <div class="bottom-nav">
        <a href="dashboard.php" class="nav-item active">
            <span class="nav-icon">🏠</span>
            Home
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
