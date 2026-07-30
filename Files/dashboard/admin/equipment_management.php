<?php
require '../../include/db_conn.php';
page_protect();

if ($_SESSION['role'] !== 'super_admin' && $_SESSION['role'] !== 'owner') {
    echo "<head><script>alert('Access Denied');</script></head></html>";
    echo "<meta http-equiv='refresh' content='0; url=index.php'>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Equipment Maintenance Tracker | Sudarshan Fitness v2.0</title>
    <link rel="stylesheet" href="../../css/premium.css">
    <link rel="stylesheet" href="../../css/entypo.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@600;800;900&family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { background: var(--bg-dark); color: #fff; padding: 25px; }
        .card { background: rgba(15, 7, 18, 0.94); border: 1px solid var(--glass-border); border-radius: 20px; padding: 25px; margin-bottom: 25px; box-shadow: var(--glass-shadow); }
        .table-custom { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .table-custom th, .table-custom td { padding: 12px; text-align: left; border-bottom: 1px solid rgba(255,0,60,0.15); font-size: 13px; }
        .table-custom th { color: var(--accent-primary); font-family: 'Orbitron'; text-transform: uppercase; }
        .status-badge { padding: 3px 10px; border-radius: 8px; font-size: 11px; font-weight: bold; font-family: 'Orbitron'; }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>

    <div style="max-width: 1200px; margin: 0 auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <div>
                <h2 style="font-family: 'Orbitron'; color: var(--accent-primary); margin: 0;">🛠️ EQUIPMENT &amp; MAINTENANCE TRACKER</h2>
                <div style="color: var(--text-muted); font-size: 13px; font-family: 'Orbitron';">SUDARSHAN FITNESS v2.0 • ASSET &amp; SERVICE REPAIR AUDIT</div>
            </div>
            <a href="index.php" style="background: rgba(255,0,60,0.15); color: var(--accent-primary); border: 1px solid var(--glass-border); padding: 8px 18px; border-radius: 12px; text-decoration: none; font-family: 'Orbitron'; font-weight: 800; font-size: 12px;">← DASHBOARD</a>
        </div>

        <div class="card">
            <h3 style="font-family: 'Orbitron'; color: #fff; margin-top: 0;">🏋️ Gym Floor Asset Inventory</h3>
            
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>Asset ID</th>
                        <th>Equipment Name</th>
                        <th>Category</th>
                        <th>Last Serviced</th>
                        <th>Next Due</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="font-family: 'Orbitron'; color: var(--accent-primary);">EQ-101</td>
                        <td style="font-weight: bold;">Commercial Treadmill Pro X</td>
                        <td>Cardio Zone</td>
                        <td>15-Jun-2026</td>
                        <td>15-Aug-2026</td>
                        <td><span class="status-badge" style="background: rgba(16,185,129,0.2); color: #10b981; border: 1px solid #10b981;">🟢 WORKING</span></td>
                    </tr>
                    <tr>
                        <td style="font-family: 'Orbitron'; color: var(--accent-primary);">EQ-102</td>
                        <td style="font-weight: bold;">Heavy Duty Smith Machine</td>
                        <td>Strength Zone</td>
                        <td>02-May-2026</td>
                        <td>02-Aug-2026</td>
                        <td><span class="status-badge" style="background: rgba(255,183,3,0.2); color: #ffb703; border: 1px solid #ffb703;">🟡 NEEDS SERVICE</span></td>
                    </tr>
                    <tr>
                        <td style="font-family: 'Orbitron'; color: var(--accent-primary);">EQ-103</td>
                        <td style="font-weight: bold;">Dual Pulley Cable Crossover</td>
                        <td>Cable Station</td>
                        <td>10-Jan-2026</td>
                        <td>10-Jul-2026</td>
                        <td><span class="status-badge" style="background: rgba(255,0,60,0.2); color: #ff003c; border: 1px solid #ff003c;">🔴 UNDER REPAIR</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
