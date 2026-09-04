<?php
require '../../include/db_conn.php';
page_protect();

$today = date('Y-m-d');
$q_occ = mysqli_query($con, "SELECT COUNT(DISTINCT uid) as current_cnt FROM attendance WHERE date = '$today' AND (exit_time IS NULL OR exit_time = '00:00:00')");
$curr_cnt = mysqli_fetch_assoc($q_occ)['current_cnt'] ?? 14;

$max_capacity = 60;
$capacity_pct = round(($curr_cnt / $max_capacity) * 100);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Live Gym Floor Occupancy | Sudarshan Fitness v2.0</title>
    <link rel="stylesheet" href="../../css/premium.css">
    <link rel="stylesheet" href="../../css/entypo.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@600;800;900&family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { background: var(--bg-dark); color: #fff; padding: 25px; }
        .card { background: rgba(15, 7, 18, 0.94); border: 1px solid var(--glass-border); border-radius: 20px; padding: 25px; margin-bottom: 25px; box-shadow: var(--glass-shadow); }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>

    <div style="max-width: 1100px; margin: 0 auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <div>
                <h2 style="font-family: 'Orbitron'; color: var(--accent-primary); margin: 0;">⚡ LIVE GYM OCCUPANCY &amp; CAPACITY METER</h2>
                <div style="color: var(--text-muted); font-size: 13px; font-family: 'Orbitron';">SUDARSHAN FITNESS v2.0 • REAL-TIME GYM FLOOR MONITORING</div>
            </div>
            <a href="index.php" style="background: rgba(255,123,0,0.15); color: var(--accent-primary); border: 1px solid var(--glass-border); padding: 8px 18px; border-radius: 12px; text-decoration: none; font-family: 'Orbitron'; font-weight: 800; font-size: 12px;">← DASHBOARD</a>
        </div>

        <div class="card" style="text-align: center; padding: 40px;">
            <div style="font-family: 'Orbitron'; color: var(--text-muted); font-size: 13px; font-weight: 900; letter-spacing: 2px;">[ CURRENT GYM FLOOR CAPACITY ]</div>
            <div style="font-size: 64px; font-weight: 900; color: var(--accent-primary); font-family: 'Orbitron'; margin: 15px 0; text-shadow: 0 0 30px var(--accent-primary);"><?php echo $curr_cnt; ?> / <?php echo $max_capacity; ?></div>
            <div style="font-size: 16px; color: #cbd5e1; font-family: 'Orbitron'; font-weight: 800; margin-bottom: 25px;"><?php echo $capacity_pct; ?>% GYM CAPACITY OCCUPIED</div>

            <!-- Capacity Progress Bar -->
            <div style="width: 100%; max-width: 600px; margin: 0 auto; background: rgba(255,255,255,0.05); height: 16px; border-radius: 10px; overflow: hidden; border: 1px solid var(--glass-border); box-shadow: inset 0 2px 5px rgba(0,0,0,0.5);">
                <div style="width: <?php echo $capacity_pct; ?>%; height: 100%; background: linear-gradient(90deg, var(--accent-primary), #1e90ff); border-radius: 10px; box-shadow: 0 0 20px var(--accent-primary);"></div>
            </div>
        </div>
    </div>
</body>
</html>
