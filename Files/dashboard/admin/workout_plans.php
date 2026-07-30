<?php
require '../../include/db_conn.php';
page_protect();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Workout Plans &amp; Exercise Library | Sudarshan Fitness v2.0</title>
    <link rel="stylesheet" href="../../css/premium.css">
    <link rel="stylesheet" href="../../css/entypo.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@600;800;900&family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { background: var(--bg-dark); color: #fff; padding: 25px; }
        .card { background: rgba(9, 14, 28, 0.9); border: 1px solid var(--glass-border); border-radius: 20px; padding: 25px; margin-bottom: 25px; box-shadow: var(--glass-shadow); }
        .ex-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 20px; margin-top: 20px; }
        .ex-card { background: rgba(3,7,18,0.8); border: 1px solid rgba(0,240,255,0.3); border-radius: 16px; padding: 20px; }
        .ex-title { font-family: 'Orbitron'; font-size: 15px; color: var(--accent-primary); font-weight: 800; margin-bottom: 5px; }
        .tag { display: inline-block; background: rgba(112,0,255,0.2); color: #a78bfa; border: 1px solid #7000ff; padding: 2px 8px; border-radius: 8px; font-size: 10px; font-weight: bold; font-family: 'Orbitron'; margin-right: 5px; margin-bottom: 5px; }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>

    <div style="max-width: 1300px; margin: 0 auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <div>
                <h2 style="font-family: 'Orbitron'; color: var(--accent-primary); margin: 0;">🏋️ WORKOUT PLANS &amp; EXERCISE LIBRARY</h2>
                <div style="color: var(--text-muted); font-size: 13px; font-family: 'Orbitron';">SUDARSHAN FITNESS v2.0 • CUSTOMIZABLE WORKOUT ROUTINES</div>
            </div>
            <a href="index.php" style="background: rgba(0,240,255,0.1); color: var(--accent-primary); border: 1px solid var(--glass-border); padding: 8px 18px; border-radius: 12px; text-decoration: none; font-family: 'Orbitron'; font-weight: 800; font-size: 12px;">← DASHBOARD</a>
        </div>

        <div class="card">
            <h3 style="font-family: 'Orbitron'; color: #fff; margin-top: 0;">💪 Master Exercise Database</h3>
            <div class="ex-grid">
                <div class="ex-card">
                    <div class="ex-title">Barbell Bench Press</div>
                    <div><span class="tag">CHEST</span><span class="tag">TRICEPS</span><span class="tag">STRENGTH</span></div>
                    <p style="font-size: 12px; color: var(--text-muted); margin-top: 8px;">4 Sets • 8-12 Reps • Flat Bench Press for Chest Volume &amp; Power.</p>
                </div>
                <div class="ex-card">
                    <div class="ex-title">Lat Pulldown / Pullups</div>
                    <div><span class="tag">BACK</span><span class="tag">BICEPS</span><span class="tag">WIDTH</span></div>
                    <p style="font-size: 12px; color: var(--text-muted); margin-top: 8px;">4 Sets • 10-12 Reps • Wide Grip Lat Pulldown for V-Taper back development.</p>
                </div>
                <div class="ex-card">
                    <div class="ex-title">Barbell Squats</div>
                    <div><span class="tag">LEGS</span><span class="tag">QUADRICEPS</span><span class="tag">CORE</span></div>
                    <p style="font-size: 12px; color: var(--text-muted); margin-top: 8px;">4 Sets • 10-15 Reps • Full range motion for lower body strength stats.</p>
                </div>
                <div class="ex-card">
                    <div class="ex-title">Dumbbell Shoulder Press</div>
                    <div><span class="tag">SHOULDERS</span><span class="tag">DELTOIDS</span></div>
                    <p style="font-size: 12px; color: var(--text-muted); margin-top: 8px;">4 Sets • 10-12 Reps • Seated DB press for shoulder width &amp; stability.</p>
                </div>
                <div class="ex-card">
                    <div class="ex-title">Incline Dumbbell Curl</div>
                    <div><span class="tag">ARMS</span><span class="tag">BICEPS</span></div>
                    <p style="font-size: 12px; color: var(--text-muted); margin-top: 8px;">3 Sets • 12 Reps • Strict form peak biceps curl.</p>
                </div>
                <div class="ex-card">
                    <div class="ex-title">Tricep Rope Pushdowns</div>
                    <div><span class="tag">ARMS</span><span class="tag">TRICEPS</span></div>
                    <p style="font-size: 12px; color: var(--text-muted); margin-top: 8px;">4 Sets • 15 Reps • Constant tension triceps lockout extension.</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
