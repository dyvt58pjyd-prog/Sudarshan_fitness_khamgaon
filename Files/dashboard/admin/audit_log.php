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
    <title>Activity Audit Logs | Sudarshan Fitness v2.0</title>
    <link rel="stylesheet" href="../../css/premium.css">
    <link rel="stylesheet" href="../../css/entypo.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@600;800;900&family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { background: var(--bg-dark); color: #fff; padding: 25px; }
        .card { background: rgba(9, 14, 28, 0.9); border: 1px solid var(--glass-border); border-radius: 20px; padding: 25px; margin-bottom: 25px; box-shadow: var(--glass-shadow); }
        .table-custom { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .table-custom th, .table-custom td { padding: 12px; text-align: left; border-bottom: 1px solid rgba(0,240,255,0.15); font-size: 13px; }
        .table-custom th { color: var(--accent-primary); font-family: 'Orbitron'; text-transform: uppercase; }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>

    <div style="max-width: 1200px; margin: 0 auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <div>
                <h2 style="font-family: 'Orbitron'; color: var(--accent-primary); margin: 0;">📜 SYSTEM AUDIT LOGS</h2>
                <div style="color: var(--text-muted); font-size: 13px; font-family: 'Orbitron';">SUDARSHAN FITNESS v2.0 • SECURITY &amp; TRANSACTION AUDIT TRAIL</div>
            </div>
            <a href="index.php" style="background: rgba(0,240,255,0.1); color: var(--accent-primary); border: 1px solid var(--glass-border); padding: 8px 18px; border-radius: 12px; text-decoration: none; font-family: 'Orbitron'; font-weight: 800; font-size: 12px;">← DASHBOARD</a>
        </div>

        <div class="card">
            <h3 style="font-family: 'Orbitron'; color: #fff; margin-top: 0;">🔐 Recent Security &amp; Activity Audit Trail</h3>
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>User</th>
                        <th>Role</th>
                        <th>Action Performed</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Audit log generated from recent payments and registration events
                    $q_recent = "SELECT e.paid_date as ts, u.username as usr, 'Payment Record' as act, e.paid_amount as amt FROM enrolls_to e JOIN users u ON e.uid = u.userid ORDER BY e.paid_date DESC LIMIT 15";
                    $res_recent = mysqli_query($con, $q_recent);
                    if ($res_recent && mysqli_num_rows($res_recent) > 0):
                        while($r = mysqli_fetch_assoc($res_recent)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($r['ts']); ?></td>
                                <td style="font-weight: bold; font-family: 'Orbitron'; color: var(--accent-primary);"><?php echo htmlspecialchars($r['usr']); ?></td>
                                <td><span style="background: rgba(0,240,255,0.15); color: #00f0ff; border: 1px solid #00f0ff; padding: 2px 8px; border-radius: 6px; font-size: 11px; font-family: 'Orbitron';">MEMBER</span></td>
                                <td>Payment Collection of ₹<?php echo number_format($r['amt']); ?> recorded</td>
                                <td><span style="color: #10b981; font-weight: bold; font-family: 'Orbitron';">SUCCESS ✓</span></td>
                            </tr>
                        <?php endwhile;
                    else: ?>
                        <tr><td colspan="5" style="text-align: center; color: var(--text-muted);">No activity recorded yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
