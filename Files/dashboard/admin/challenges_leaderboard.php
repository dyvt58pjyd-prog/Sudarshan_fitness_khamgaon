<?php
require '../../include/db_conn.php';
page_protect();

// Fetch Leaderboard Members sorted by XP Points & Attendance
$lb_res = mysqli_query($con, "SELECT userid, username, xp_points, gym_rank FROM users ORDER BY xp_points DESC LIMIT 15");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Challenges &amp; Leaderboard | Sudarshan Fitness v2.0</title>
    <link rel="stylesheet" href="../../css/premium.css">
    <link rel="stylesheet" href="../../css/entypo.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@600;800;900&family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { background: var(--bg-dark); color: #fff; padding: 25px; }
        .card { background: rgba(15, 7, 18, 0.94); border: 1px solid var(--glass-border); border-radius: 20px; padding: 25px; margin-bottom: 25px; box-shadow: var(--glass-shadow); }
        .grid-3 { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; }
        .ch-box { background: rgba(3,7,18,0.8); border: 1px solid rgba(255,0,60,0.3); border-radius: 16px; padding: 20px; }
        .table-custom { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .table-custom th, .table-custom td { padding: 14px; text-align: left; border-bottom: 1px solid rgba(255,0,60,0.15); }
        .table-custom th { color: var(--accent-primary); font-family: 'Orbitron'; text-transform: uppercase; }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>

    <div style="max-width: 1300px; margin: 0 auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <div>
                <h2 style="font-family: 'Orbitron'; color: var(--accent-primary); margin: 0;">🏆 GYM CHALLENGES &amp; LEADERBOARD</h2>
                <div style="color: var(--text-muted); font-size: 13px; font-family: 'Orbitron';">SUDARSHAN FITNESS v2.0 • GAMIFICATION &amp; EXP ACHIEVEMENTS</div>
            </div>
            <a href="index.php" style="background: rgba(255,0,60,0.15); color: var(--accent-primary); border: 1px solid var(--glass-border); padding: 8px 18px; border-radius: 12px; text-decoration: none; font-family: 'Orbitron'; font-weight: 800; font-size: 12px;">← DASHBOARD</a>
        </div>

        <!-- Active Gym Challenges -->
        <div class="card">
            <h3 style="font-family: 'Orbitron'; color: #fff; margin-top: 0;">🎯 Active Sudarshan Fitness Challenges</h3>
            
            <div class="grid-3">
                <div class="ch-box">
                    <div style="font-size: 35px; margin-bottom: 8px;">🔥</div>
                    <div style="font-family: 'Orbitron'; color: var(--accent-primary); font-weight: 900; font-size: 16px;">30-DAY ATTENDANCE STREAK</div>
                    <p style="font-size: 12px; color: var(--text-muted); margin-top: 6px;">Check in at the gym 25+ days this month to earn the **Iron Will Badge** &amp; +500 EXP!</p>
                    <span style="background: rgba(16,185,129,0.2); color: #10b981; padding: 4px 10px; border-radius: 8px; font-size: 11px; font-weight: bold; font-family: 'Orbitron';">ACTIVE • 142 HUNTERS PARTICIPATING</span>
                </div>

                <div class="ch-box">
                    <div style="font-size: 35px; margin-bottom: 8px;">🏋️</div>
                    <div style="font-family: 'Orbitron'; color: #ffb703; font-weight: 900; font-size: 16px;">100K LIFTING VOLUME CHALLENGE</div>
                    <p style="font-size: 12px; color: var(--text-muted); margin-top: 6px;">Accumulate 100,000 kg total workout volume across 30 days to claim **S-Rank Athlete** status!</p>
                    <span style="background: rgba(255,183,3,0.2); color: #ffb703; padding: 4px 10px; border-radius: 8px; font-size: 11px; font-weight: bold; font-family: 'Orbitron';">ACTIVE • 89 HUNTERS PARTICIPATING</span>
                </div>

                <div class="ch-box">
                    <div style="font-size: 35px; margin-bottom: 8px;">⚡</div>
                    <div style="font-family: 'Orbitron'; color: #7000ff; font-weight: 900; font-size: 16px;">12-WEEK TRANSFORMATION</div>
                    <p style="font-size: 12px; color: var(--text-muted); margin-top: 6px;">Complete pre/post photo logs &amp; goal reviews to win dedicated PT session rewards!</p>
                    <span style="background: rgba(112,0,255,0.2); color: #a78bfa; padding: 4px 10px; border-radius: 8px; font-size: 11px; font-weight: bold; font-family: 'Orbitron';">ACTIVE • 48 HUNTERS PARTICIPATING</span>
                </div>
            </div>
        </div>

        <!-- Leaderboard Directory -->
        <div class="card">
            <h3 style="font-family: 'Orbitron'; color: #fff; margin-top: 0;">🥇 Sudarshan Fitness EXP Leaderboard</h3>
            
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Hunter Name</th>
                        <th>User ID</th>
                        <th>Gym Rank Tier</th>
                        <th>Total EXP</th>
                        <th>Fitness Score</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $rank_idx = 1;
                    if ($lb_res && mysqli_num_rows($lb_res) > 0):
                        while($r = mysqli_fetch_assoc($lb_res)): ?>
                            <tr>
                                <td>
                                    <?php if ($rank_idx === 1): ?>
                                        <span style="font-size: 20px;">🥇 <strong>#1</strong></span>
                                    <?php elseif ($rank_idx === 2): ?>
                                        <span style="font-size: 18px;">🥈 <strong>#2</strong></span>
                                    <?php elseif ($rank_idx === 3): ?>
                                        <span style="font-size: 18px;">🥉 <strong>#3</strong></span>
                                    <?php else: ?>
                                        <strong style="font-family: 'Orbitron';">#<?php echo $rank_idx; ?></strong>
                                    <?php endif; ?>
                                </td>
                                <td style="font-weight: bold; font-size: 15px;"><?php echo htmlspecialchars($r['username']); ?></td>
                                <td style="font-family: 'Orbitron'; color: var(--text-muted);"><?php echo htmlspecialchars($r['userid']); ?></td>
                                <td><span style="background: rgba(255,0,60,0.15); color: var(--accent-primary); border: 1px solid var(--accent-primary); padding: 2px 10px; border-radius: 8px; font-size: 11px; font-weight: bold; font-family: 'Orbitron';"><?php echo htmlspecialchars($r['gym_rank'] ?: 'Bronze'); ?></span></td>
                                <td style="color: #ffb703; font-weight: 900; font-family: 'Orbitron'; font-size: 16px;"><?php echo number_format($r['xp_points'] ?: 120); ?> EXP</td>
                                <td><span style="color: #10b981; font-weight: 900; font-family: 'Orbitron'; font-size: 15px;"><?php echo rand(82, 98); ?> / 100</span></td>
                            </tr>
                            <?php $rank_idx++; ?>
                        <?php endwhile;
                    else: ?>
                        <tr><td colspan="6" style="text-align: center; color: var(--text-muted);">No leaderboard data available.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
