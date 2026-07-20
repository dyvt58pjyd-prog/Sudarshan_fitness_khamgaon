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

// Calculate Leaderboard (Top attendees this month)
// This query groups attendance by user for the current month and sorts them
date_default_timezone_set("Asia/Calcutta");
$current_month = date('m');
$current_year = date('Y');

$q_leaderboard = mysqli_query($con, "
    SELECT u.username, COUNT(a.id) as total_scans
    FROM attendance a
    JOIN users u ON a.uid = u.userid
    WHERE MONTH(a.date) = '$current_month' AND YEAR(a.date) = '$current_year'
    GROUP BY a.uid
    ORDER BY total_scans DESC
    LIMIT 10
");

$leaderboard = [];
$rank = 1;
while ($row = mysqli_fetch_assoc($q_leaderboard)) {
    $row['rank'] = $rank++;
    $leaderboard[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Sudarshan Gamification</title>
    <meta name="theme-color" content="#0f172a">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body { background: #0f172a; color: #f8fafc; min-height: 100vh; padding-bottom: 80px; background-image: radial-gradient(circle at 50% 0%, rgba(139,92,246,0.15) 0%, transparent 60%); }
        .header { padding: 25px 20px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .header-title { font-size: 20px; font-weight: 800; color: #fff; }
        .header-title span { color: #8b5cf6; }
        
        .content { padding: 20px; }
        .title-box { text-align: center; margin-bottom: 30px; }
        .title-box h1 { font-size: 24px; font-weight: 800; color: #fff; margin-bottom: 5px; }
        .title-box p { color: #94a3b8; font-size: 13px; }

        .leaderboard-list { display: flex; flex-direction: column; gap: 12px; }
        .leaderboard-item { background: linear-gradient(145deg, rgba(30, 41, 59, 0.7) 0%, rgba(15, 23, 42, 0.8) 100%); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 16px; padding: 15px 20px; display: flex; align-items: center; box-shadow: 0 4px 15px rgba(0,0,0,0.2); transition: transform 0.2s; }
        .leaderboard-item.rank-1 { border-color: rgba(250, 204, 21, 0.4); background: linear-gradient(145deg, rgba(250, 204, 21, 0.1) 0%, rgba(15, 23, 42, 0.9) 100%); }
        .leaderboard-item.rank-2 { border-color: rgba(148, 163, 184, 0.4); background: linear-gradient(145deg, rgba(148, 163, 184, 0.1) 0%, rgba(15, 23, 42, 0.9) 100%); }
        .leaderboard-item.rank-3 { border-color: rgba(217, 119, 6, 0.4); background: linear-gradient(145deg, rgba(217, 119, 6, 0.1) 0%, rgba(15, 23, 42, 0.9) 100%); }
        
        .rank-badge { width: 35px; height: 35px; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-weight: 800; font-size: 16px; margin-right: 15px; background: rgba(255,255,255,0.05); color: #fff; }
        .rank-1 .rank-badge { background: #facc15; color: #000; box-shadow: 0 0 15px rgba(250, 204, 21, 0.5); }
        .rank-2 .rank-badge { background: #94a3b8; color: #000; }
        .rank-3 .rank-badge { background: #d97706; color: #fff; }
        
        .member-info { flex: 1; }
        .member-name { font-weight: 700; font-size: 15px; color: #fff; margin-bottom: 2px; }
        .member-scans { font-size: 12px; color: #10b981; font-weight: 600; display: flex; align-items: center; gap: 4px; }
        
        .bottom-nav { position: fixed; bottom: 0; left: 0; right: 0; background: rgba(15, 23, 42, 0.95); backdrop-filter: blur(10px); display: flex; justify-content: space-around; padding: 15px 10px; border-top: 1px solid rgba(255,255,255,0.05); padding-bottom: calc(15px + env(safe-area-inset-bottom)); }
        .nav-item { color: #64748b; text-decoration: none; font-size: 11px; font-weight: 700; text-transform: uppercase; display: flex; flex-direction: column; align-items: center; gap: 6px; }
        .nav-item.active { color: #8b5cf6; }
        .nav-icon { font-size: 22px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-title">Global <span>Rankings</span></div>
    </div>

    <div class="content">
        <div class="title-box">
            <h1>🏆 Top Warriors</h1>
            <p>Most dedicated members this month</p>
        </div>

        <div class="leaderboard-list">
            <?php if (empty($leaderboard)): ?>
                <div style="text-align: center; color: #94a3b8; padding: 20px;">No workouts logged this month yet. Be the first!</div>
            <?php else: ?>
                <?php foreach ($leaderboard as $lb): ?>
                    <div class="leaderboard-item rank-<?php echo $lb['rank']; ?>">
                        <div class="rank-badge"><?php echo $lb['rank']; ?></div>
                        <div class="member-info">
                            <div class="member-name"><?php echo htmlspecialchars(explode(' ', $lb['username'])[0]); ?></div>
                            <div class="member-scans">🔥 <?php echo $lb['total_scans']; ?> Workouts</div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="bottom-nav">
        <a href="dashboard.php" class="nav-item">
            <span class="nav-icon">🏠</span>
            <span>Home</span>
        </a>
        <a href="leaderboard.php" class="nav-item active">
            <span class="nav-icon">🏆</span>
            <span>Rank</span>
        </a>
        <a href="ai_scanner.php" class="nav-item">
            <span class="nav-icon">📸</span>
            <span>Food AI</span>
        </a>
        <a href="profile.php" class="nav-item">
            <span class="nav-icon">👤</span>
            <span>Me</span>
        </a>
    </div>
</body>
</html>
