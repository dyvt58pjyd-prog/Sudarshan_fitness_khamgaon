<?php
require '../../include/db_conn.php';
page_protect();

if ($_SESSION['role'] !== 'super_admin' && $_SESSION['role'] !== 'owner') {
    echo "<head><script>alert('Access Denied');</script></head></html>";
    echo "<meta http-equiv='refresh' content='0; url=index.php'>";
    exit();
}

$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_announcement'])) {
    $ann_text = mysqli_real_escape_string($con, $_POST['ann_text']);
    mysqli_query($con, "UPDATE gym_tips SET tip_text = '$ann_text' WHERE id = 1");
    $msg = "Gym announcement posted live across all member dashboards!";
}

$gym = get_gym_details($con);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Gym Announcements | Sudarshan Fitness v2.0</title>
    <link rel="stylesheet" href="../../css/premium.css">
    <link rel="stylesheet" href="../../css/entypo.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@600;800;900&family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { background: var(--bg-dark); color: #fff; padding: 25px; }
        .card { background: rgba(15, 7, 18, 0.94); border: 1px solid var(--glass-border); border-radius: 20px; padding: 25px; margin-bottom: 25px; box-shadow: var(--glass-shadow); }
        .btn-post { background: linear-gradient(135deg, var(--accent-primary), #0077ff); color: #030712; border: none; padding: 12px 24px; border-radius: 12px; font-weight: 800; font-family: 'Orbitron'; cursor: pointer; }
        .form-control { background: rgba(3,7,18,0.8); border: 1px solid rgba(255,0,60,0.3); color: #fff; padding: 12px 18px; border-radius: 12px; width: 100%; font-size: 14px; margin-bottom: 15px; }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>

    <div style="max-width: 1100px; margin: 0 auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <div>
                <h2 style="font-family: 'Orbitron'; color: var(--accent-primary); margin: 0;">📢 GYM ANNOUNCEMENTS &amp; BROADCAST</h2>
                <div style="color: var(--text-muted); font-size: 13px; font-family: 'Orbitron';">SUDARSHAN FITNESS v2.0 • LIVE MEMBER BANNER MESSAGES</div>
            </div>
            <a href="index.php" style="background: rgba(255,0,60,0.15); color: var(--accent-primary); border: 1px solid var(--glass-border); padding: 8px 18px; border-radius: 12px; text-decoration: none; font-family: 'Orbitron'; font-weight: 800; font-size: 12px;">← DASHBOARD</a>
        </div>

        <?php if ($msg): ?>
            <div style="background: rgba(255,0,60,0.15); border: 1px solid var(--accent-primary); color: var(--accent-primary); padding: 14px; border-radius: 12px; margin-bottom: 20px; font-weight: bold;">
                <?php echo htmlspecialchars($msg); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h3 style="font-family: 'Orbitron'; color: #fff; margin-top: 0;">Broadcast Live Message to All Members</h3>
            <form method="POST">
                <textarea name="ann_text" class="form-control" rows="4" placeholder="e.g. 🔥 Special Sunday Deadlift & Squat Workshop at 8:00 AM! Free entry for all members!" required></textarea>
                <button type="submit" name="post_announcement" class="btn-post">PUBLISH ANNOUNCEMENT BANNER ➔</button>
            </form>
        </div>
    </div>
</body>
</html>
