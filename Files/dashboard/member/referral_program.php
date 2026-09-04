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

$user_q = mysqli_query($con, "SELECT username, xp_points FROM users WHERE userid = '$userid'");
$user = mysqli_fetch_assoc($user_q);

$name = htmlspecialchars($user['username'] ?? 'Hunter');
$ref_code = "REF-" . strtoupper(substr($userid, -6));
$ref_link = "https://sudarshanfitness.de/register.php?ref=" . $ref_code;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Referral Program | Sudarshan Fitness v2.0</title>
    <link rel="stylesheet" href="../../css/premium.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@600;800;900&family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { background: var(--bg-dark); color: #fff; padding: 25px; }
        .card { background: rgba(15, 7, 18, 0.94); border: 1px solid var(--glass-border); border-radius: 20px; padding: 25px; margin-bottom: 25px; box-shadow: var(--glass-shadow); }
        .btn-copy { background: linear-gradient(135deg, var(--accent-primary), #0077ff); color: #0f0a05; border: none; padding: 12px 20px; border-radius: 12px; font-weight: 800; font-family: 'Orbitron'; cursor: pointer; }
    </style>
</head>
<body>

    <div style="max-width: 900px; margin: 0 auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <div>
                <h2 style="font-family: 'Orbitron'; color: var(--accent-primary); margin: 0;">🎁 REFERRAL &amp; REWARD PROGRAM</h2>
                <div style="color: var(--text-muted); font-size: 13px; font-family: 'Orbitron';">SUDARSHAN FITNESS v2.0 • EARN +200 EXP &amp; DISCOUNTS</div>
            </div>
            <a href="index.php" style="background: rgba(255,123,0,0.15); color: var(--accent-primary); border: 1px solid var(--glass-border); padding: 8px 18px; border-radius: 12px; text-decoration: none; font-family: 'Orbitron'; font-weight: 800; font-size: 12px;">← DASHBOARD</a>
        </div>

        <div class="card" style="text-align: center; padding: 35px;">
            <div style="font-size: 45px; margin-bottom: 10px;">🤝🎁</div>
            <h3 style="font-family: 'Orbitron'; color: #fff; margin-top: 0; font-size: 24px;">Invite Friends &amp; Train Together!</h3>
            <p style="color: #cbd5e1; font-size: 14px; max-width: 600px; margin: 0 auto 25px auto; line-height: 1.5;">Share your unique Sudarshan Fitness referral code with friends. When they join, you earn **+200 EXP Points** &amp; **10% Renewal Discount** on your next membership package!</p>

            <div style="background: rgba(3,7,18,0.8); border: 1px solid var(--accent-primary); padding: 15px 25px; border-radius: 14px; display: inline-block; margin-bottom: 20px;">
                <div style="font-size: 11px; color: var(--text-muted); font-family: 'Orbitron';">YOUR UNIQUE REFERRAL CODE</div>
                <div style="font-size: 28px; font-weight: 900; color: var(--accent-primary); font-family: 'Orbitron'; letter-spacing: 2px;"><?php echo $ref_code; ?></div>
            </div>

            <div>
                <button onclick="navigator.clipboard.writeText('<?php echo $ref_link; ?>'); alert('📋 Referral link copied to clipboard!');" class="btn-copy">
                    📋 COPY REFERRAL LINK
                </button>
            </div>
        </div>
    </div>

</body>
</html>
