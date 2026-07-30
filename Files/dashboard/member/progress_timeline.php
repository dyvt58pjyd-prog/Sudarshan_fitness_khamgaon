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

// Fetch member profile
$user_res = mysqli_query($con, "SELECT username, photo, weight, height FROM users WHERE userid = '$userid'");
$user = mysqli_fetch_assoc($user_res);

// Fetch health history
$health_res = mysqli_query($con, "SELECT * FROM health_status WHERE uid = '$userid' ORDER BY hid DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Progress Photo Timeline | Sudarshan Fitness v2.0</title>
    <link rel="stylesheet" href="../../css/premium.css">
    <link rel="stylesheet" href="../../css/entypo.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@600;800;900&family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { background: var(--bg-dark); color: #fff; padding: 25px; }
        .card { background: rgba(15, 7, 18, 0.94); border: 1px solid var(--glass-border); border-radius: 20px; padding: 25px; margin-bottom: 25px; box-shadow: var(--glass-shadow); }
        .timeline-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-top: 20px; }
        .timeline-box { background: rgba(3,7,18,0.8); border: 1px solid rgba(255,0,60,0.3); border-radius: 16px; padding: 15px; text-align: center; }
        .img-ph { width: 100%; height: 200px; background: rgba(255,255,255,0.05); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 35px; color: var(--text-muted); object-fit: cover; }
        .btn-upload { background: linear-gradient(135deg, var(--accent-primary), #0077ff); color: #030712; border: none; padding: 10px 18px; border-radius: 12px; font-weight: 800; font-family: 'Orbitron'; cursor: pointer; margin-top: 10px; }
    </style>
</head>
<body>

    <div style="max-width: 1200px; margin: 0 auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <div>
                <h2 style="font-family: 'Orbitron'; color: var(--accent-primary); margin: 0;">📸 TRANSFORMATION TIMELINE</h2>
                <div style="color: var(--text-muted); font-size: 13px; font-family: 'Orbitron';">SUDARSHAN FITNESS v2.0 • PRIVATE PROGRESS TRACKER</div>
            </div>
            <a href="index.php" style="background: rgba(255,0,60,0.15); color: var(--accent-primary); border: 1px solid var(--glass-border); padding: 8px 18px; border-radius: 12px; text-decoration: none; font-family: 'Orbitron'; font-weight: 800; font-size: 12px;">← RETURN TO DASHBOARD</a>
        </div>

        <!-- Metric Overview -->
        <div class="card">
            <h3 style="font-family: 'Orbitron'; color: #fff; margin-top: 0;">📊 Body Composition Progress</h3>
            <div style="display: flex; gap: 20px; flex-wrap: wrap; margin-top: 15px;">
                <div style="flex: 1; min-width: 150px; background: rgba(3,7,18,0.8); border: 1px solid rgba(255,0,60,0.3); padding: 15px; border-radius: 14px; text-align: center;">
                    <div style="font-size: 11px; color: var(--text-muted); font-family: 'Orbitron';">CURRENT WEIGHT</div>
                    <div style="font-size: 26px; font-weight: 900; color: var(--accent-primary); font-family: 'Orbitron';"><?php echo htmlspecialchars($user['weight'] ?: '70'); ?> KG</div>
                </div>
                <div style="flex: 1; min-width: 150px; background: rgba(3,7,18,0.8); border: 1px solid rgba(255,0,60,0.3); padding: 15px; border-radius: 14px; text-align: center;">
                    <div style="font-size: 11px; color: var(--text-muted); font-family: 'Orbitron';">HEIGHT</div>
                    <div style="font-size: 26px; font-weight: 900; color: #ffb703; font-family: 'Orbitron';"><?php echo htmlspecialchars($user['height'] ?: '175'); ?> CM</div>
                </div>
                <div style="flex: 1; min-width: 150px; background: rgba(3,7,18,0.8); border: 1px solid rgba(255,0,60,0.3); padding: 15px; border-radius: 14px; text-align: center;">
                    <div style="font-size: 11px; color: var(--text-muted); font-family: 'Orbitron';">ESTIMATED BMI</div>
                    <?php 
                    $h_m = floatval($user['height'] ?: 175) / 100;
                    $bmi = $h_m > 0 ? round(floatval($user['weight'] ?: 70) / ($h_m * $h_m), 1) : 22.5;
                    ?>
                    <div style="font-size: 26px; font-weight: 900; color: #10b981; font-family: 'Orbitron';"><?php echo $bmi; ?></div>
                </div>
            </div>
        </div>

        <!-- Transformation Photo Milestones -->
        <div class="card">
            <h3 style="font-family: 'Orbitron'; color: #fff; margin-top: 0;">🖼️ Private Transformation Timeline</h3>
            
            <div class="timeline-grid">
                <div class="timeline-box">
                    <div style="font-family: 'Orbitron'; color: var(--accent-primary); font-weight: 800; font-size: 14px; margin-bottom: 10px;">DAY 1 (STARTING)</div>
                    <div class="img-ph">📸</div>
                    <button class="btn-upload" onclick="alert('Upload Day 1 Transformation Photo')">UPLOAD PHOTO</button>
                </div>
                <div class="timeline-box">
                    <div style="font-family: 'Orbitron'; color: var(--accent-primary); font-weight: 800; font-size: 14px; margin-bottom: 10px;">DAY 30 MILESTONE</div>
                    <div class="img-ph">📸</div>
                    <button class="btn-upload" onclick="alert('Upload Day 30 Transformation Photo')">UPLOAD PHOTO</button>
                </div>
                <div class="timeline-box">
                    <div style="font-family: 'Orbitron'; color: var(--accent-primary); font-weight: 800; font-size: 14px; margin-bottom: 10px;">DAY 60 MILESTONE</div>
                    <div class="img-ph">📸</div>
                    <button class="btn-upload" onclick="alert('Upload Day 60 Transformation Photo')">UPLOAD PHOTO</button>
                </div>
                <div class="timeline-box">
                    <div style="font-family: 'Orbitron'; color: var(--accent-primary); font-weight: 800; font-size: 14px; margin-bottom: 10px;">DAY 90 MILESTONE</div>
                    <div class="img-ph">📸</div>
                    <button class="btn-upload" onclick="alert('Upload Day 90 Transformation Photo')">UPLOAD PHOTO</button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
