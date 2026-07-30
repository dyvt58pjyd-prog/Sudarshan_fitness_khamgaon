<?php
require_once __DIR__ . '/../../include/db_conn.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_data']) && !isset($_SESSION['userid'])) {
    header("Location: ../../index.php");
    exit();
}

$userid = isset($_SESSION['userid']) ? $_SESSION['userid'] : ($_SESSION['user_data']['userid'] ?? '');
$gym = get_gym_details($con);

// Fetch member details
$user_q = mysqli_query($con, "SELECT u.*, e.expire, p.planName FROM users u LEFT JOIN enrolls_to e ON u.userid = e.uid AND e.renewal = 'yes' LEFT JOIN plan p ON e.pid = p.pid WHERE u.userid = '$userid'");
$user = mysqli_fetch_assoc($user_q);

$name = htmlspecialchars($user['username'] ?? 'Hunter');
$mobile = htmlspecialchars($user['mobile'] ?? '');
$plan = htmlspecialchars($user['planName'] ?? 'General Membership');
$expire = htmlspecialchars($user['expire'] ?? 'Active');
$rank = htmlspecialchars($user['gym_rank'] ?? 'Beginner');
$xp = intval($user['xp_points'] ?? 100);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Access Pass | Sudarshan Fitness v2.0</title>
    <link rel="stylesheet" href="../../css/premium.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@600;800;900&family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { background: var(--bg-dark); color: #fff; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .pass-card { width: 100%; max-width: 420px; background: rgba(15, 7, 18, 0.95); border: 2px solid var(--accent-primary); border-radius: 24px; padding: 30px 25px; box-shadow: 0 0 50px rgba(255, 0, 60, 0.35); position: relative; overflow: hidden; }
        .pass-card::before { content: '[ DIGITAL ACCESS PASS ]'; position: absolute; top: -12px; left: 50%; transform: translateX(-50%); background: #030712; border: 1px solid var(--accent-primary); color: var(--accent-primary); font-family: 'Orbitron'; font-size: 10px; font-weight: 900; padding: 3px 14px; border-radius: 10px; letter-spacing: 2px; }
        .qr-box { background: #fff; padding: 15px; border-radius: 16px; margin: 20px auto; width: 180px; height: 180px; display: flex; align-items: center; justify-content: center; box-shadow: 0 0 25px rgba(255, 0, 60, 0.4); }
    </style>
</head>
<body>

    <div class="pass-card">
        <div style="text-align: center; margin-bottom: 15px;">
            <img src="<?php echo htmlspecialchars($gym['gym_logo']); ?>" alt="Gym Logo" style="max-height: 55px; filter: drop-shadow(0 0 10px rgba(255,0,60,0.5));">
            <h3 style="font-family: 'Orbitron'; color: var(--accent-primary); margin: 8px 0 2px 0;"><?php echo htmlspecialchars($gym['gym_name']); ?></h3>
            <div style="font-size: 11px; color: var(--text-muted); font-family: 'Orbitron'; font-weight: 800;">OFFICIAL MEMBER IDENTIFICATION</div>
        </div>

        <div class="qr-box">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?php echo urlencode($userid); ?>" alt="Member QR Code" style="width: 100%; height: 100%;">
        </div>

        <div style="text-align: center; margin-bottom: 20px;">
            <div style="font-family: 'Orbitron'; font-size: 22px; font-weight: 900; color: #fff; text-shadow: 0 0 15px var(--accent-primary);"><?php echo $name; ?></div>
            <div style="font-family: 'Orbitron'; font-size: 13px; color: var(--accent-primary); font-weight: 800; margin-top: 4px;">ID: <?php echo htmlspecialchars($userid); ?> • <?php echo $rank; ?> RANK</div>
        </div>

        <div style="background: rgba(3,7,18,0.8); border: 1px solid rgba(255,0,60,0.3); padding: 15px; border-radius: 14px;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 12px;">
                <span style="color: var(--text-muted); font-family: 'Orbitron';">Active Plan:</span>
                <strong style="color: #fff; font-family: 'Orbitron';"><?php echo $plan; ?></strong>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 12px;">
                <span style="color: var(--text-muted); font-family: 'Orbitron';">Expiry Date:</span>
                <strong style="color: #ffb703; font-family: 'Orbitron';"><?php echo $expire; ?></strong>
            </div>
        </div>

        <a href="index.php" style="display: block; text-align: center; background: linear-gradient(135deg, var(--accent-primary), #0077ff); color: #030712; padding: 12px; border-radius: 12px; font-weight: 900; font-family: 'Orbitron'; text-decoration: none; margin-top: 20px; box-shadow: 0 0 25px rgba(255,0,60,0.5);">
            ← BACK TO DASHBOARD
        </a>
    </div>

</body>
</html>
