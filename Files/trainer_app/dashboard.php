<?php
session_start();
require '../include/db_conn.php';

if (!isset($_SESSION['trainer_id'])) {
    header("Location: index.php");
    exit;
}

$trainer_id = $_SESSION['trainer_id'];
$trainer_name = $_SESSION['trainer_name'];

// Handle quick log
$msg = "";
if (isset($_POST['log_session'])) {
    $uid = mysqli_real_escape_string($con, $_POST['client_uid']);
    $session_date = date('Y-m-d');
    
    $check = mysqli_query($con, "SELECT id FROM pt_attendance WHERE member_id='$uid' AND session_date='$session_date'");
    if (mysqli_num_rows($check) > 0) {
        $msg = "Session already logged today for this client.";
    } else {
        $insert = "INSERT INTO pt_attendance (member_id, trainer_id, session_date) VALUES ('$uid', '$trainer_id', '$session_date')";
        if (mysqli_query($con, $insert)) {
            $msg = "Session logged successfully!";
        } else {
            $msg = "Error logging session.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Trainer Dashboard</title>
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#10b981">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body { background: #0f172a; color: #fff; min-height: 100vh; padding-bottom: 80px; }
        .header { background: #1e293b; padding: 20px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #334155; }
        .header-title { font-size: 18px; font-weight: 700; color: #10b981; }
        .logout-btn { color: #ef4444; text-decoration: none; font-size: 14px; font-weight: 600; }
        
        .content { padding: 20px; }
        .card { background: #1e293b; border-radius: 20px; padding: 20px; margin-bottom: 15px; border: 1px solid #334155; display: flex; justify-content: space-between; align-items: center; }
        .client-name { font-size: 16px; font-weight: 700; color: #fff; margin-bottom: 5px; }
        .client-meta { font-size: 12px; color: #8ba3cb; }
        .log-btn { background: #10b981; color: #fff; border: none; padding: 10px 15px; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer; }
        .log-btn:active { transform: scale(0.95); }
        
        .alert { padding: 15px; border-radius: 10px; margin-bottom: 20px; font-size: 14px; text-align: center; background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16,185,129,0.3); }
        
        /* Bottom Navigation */
        .bottom-nav { position: fixed; bottom: 0; left: 0; right: 0; background: rgba(30, 41, 59, 0.9); backdrop-filter: blur(10px); display: flex; justify-content: space-around; padding: 15px 10px; border-top: 1px solid #334155; padding-bottom: calc(15px + env(safe-area-inset-bottom)); }
        .nav-item { color: #8ba3cb; text-decoration: none; font-size: 12px; font-weight: 600; display: flex; flex-direction: column; align-items: center; gap: 5px; }
        .nav-item.active { color: #10b981; }
        .nav-icon { font-size: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-title">Hello, <?php echo htmlspecialchars(explode(' ', $trainer_name)[0]); ?>!</div>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>

    <div class="content">
        <?php if ($msg != "") { echo "<div class='alert'>$msg</div>"; } ?>
        
        <h3 style="margin-bottom: 15px; font-size: 16px;">My Active Clients</h3>
        
        <?php
        date_default_timezone_set("Asia/Calcutta");
        $today = date('Y-m-d');
        
        $query = "SELECT p.*, u.username, u.mobile,
                         (SELECT COUNT(*) FROM pt_attendance a WHERE a.member_id = p.uid) as sessions_logged
                  FROM pt_enrollments p 
                  INNER JOIN users u ON p.uid = u.userid 
                  WHERE p.trainer_id = '$trainer_id' AND p.expire_date >= '$today'
                  ORDER BY p.expire_date ASC";
        $result = mysqli_query($con, $query);

        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                ?>
                <div class="card">
                    <div>
                        <div class="client-name"><?php echo htmlspecialchars($row['username']); ?></div>
                        <div class="client-meta">Sessions Logged: <strong style="color:#ff6b00;"><?php echo $row['sessions_logged']; ?></strong></div>
                        <div class="client-meta">Valid till: <?php echo date('d M', strtotime($row['expire_date'])); ?></div>
                    </div>
                    <form method="POST" action="">
                        <input type="hidden" name="client_uid" value="<?php echo $row['uid']; ?>">
                        <button type="submit" name="log_session" class="log-btn">+ Log Today</button>
                    </form>
                </div>
                <?php
            }
        } else {
            echo "<p style='color: #8ba3cb; text-align: center; margin-top: 30px;'>No active PT clients assigned.</p>";
        }
        ?>
    </div>

    <div class="bottom-nav">
        <a href="dashboard.php" class="nav-item active">
            <span class="nav-icon">👥</span>
            <span>Clients</span>
        </a>
        <a href="#" class="nav-item" onclick="alert('Feature coming soon!')">
            <span class="nav-icon">📅</span>
            <span>Schedule</span>
        </a>
        <a href="#" class="nav-item" onclick="alert('Feature coming soon!')">
            <span class="nav-icon">👤</span>
            <span>Profile</span>
        </a>
    </div>
</body>
</html>
