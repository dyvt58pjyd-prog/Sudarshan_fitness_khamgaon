<?php
require '../../include/db_conn.php';
page_protect();

if ($_SESSION['role'] !== 'owner' && $_SESSION['role'] !== 'super_admin') {
    header("Location: index.php");
    exit();
}

// Fetch logs
$audit_query = mysqli_query($con, "SELECT * FROM security_audit_logs ORDER BY created_at DESC LIMIT 200");
$login_query = mysqli_query($con, "SELECT * FROM login_attempts ORDER BY attempt_time DESC LIMIT 200");

// Calculate stats
$stats_q = mysqli_query($con, "SELECT 
    (SELECT COUNT(*) FROM login_attempts WHERE status='failed' AND attempt_time > NOW() - INTERVAL 24 HOUR) as failed_24h,
    (SELECT COUNT(*) FROM blocked_ips WHERE expires_at > NOW() OR expires_at IS NULL) as active_blocks,
    (SELECT COUNT(*) FROM security_audit_logs WHERE severity='critical' AND created_at > NOW() - INTERVAL 7 DAY) as crit_alerts
");
$stats = mysqli_fetch_assoc($stats_q);

// Helper function to colorize severity
function get_severity_badge($sev) {
    switch (strtolower($sev)) {
        case 'critical': return '<span style="background: rgba(239, 68, 68, 0.1); color: var(--danger); padding: 4px 8px; border-radius: 4px; border: 1px solid var(--danger); font-size: 11px; font-weight: bold; text-transform: uppercase;">CRITICAL</span>';
        case 'warning': return '<span style="background: rgba(245, 158, 11, 0.1); color: var(--amber); padding: 4px 8px; border-radius: 4px; border: 1px solid var(--amber); font-size: 11px; font-weight: bold; text-transform: uppercase;">WARNING</span>';
        default: return '<span style="background: rgba(16, 185, 129, 0.1); color: var(--success); padding: 4px 8px; border-radius: 4px; border: 1px solid var(--success); font-size: 11px; font-weight: bold; text-transform: uppercase;">INFO</span>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>SUDARSHAN FITNESS | Security Audit Log</title>
    <link rel="stylesheet" href="../../css/style.css" id="style-resource-5">
    <script type="text/javascript" src="../../js/Script.js"></script>
    <link rel="stylesheet" href="../../css/dashMain.css">
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
    <link rel="stylesheet" href="../../css/premium.css?v=<?php echo time(); ?>">
</head>
<body class="page-body page-fade" onload="collapseSidebar()">
    <div class="page-container sidebar-collapsed" id="navbarcollapse">    
        <div class="sidebar-menu">
            <header class="logo-env">
                <div class="logo">
                    <a href="main.php">
                        <?php 
                        $sidebar_logo = $gym_settings_data["gym_logo"] ?? "../../images/logo.png";
                        ?>
                        <img src="<?php echo htmlspecialchars($sidebar_logo); ?>" alt="Gym Logo" style="max-height: 80px; max-width: 192px;" />
                    </a>
                </div>
                <div class="sidebar-collapse" onclick="collapseSidebar()">
                    <a href="#" class="sidebar-collapse-icon with-animation">
                        <i class="entypo-menu"></i>
                    </a>
                </div>
            </header>
            <?php include('nav.php'); ?>
        </div>

        <div class="main-content">
            <div class="row">
                <div class="col-md-12">
                    <h2><i class="entypo-shield"></i> Security Audit Trail</h2>
                    <p style="color: var(--text-muted); margin-bottom: 20px;">Review all system activity, WAF blocks, and login attempts.</p>
                </div>
            </div>

            <div class="row" style="margin-bottom: 20px;">
                <div class="col-md-4">
                    <div style="background: var(--card-bg); border: 1px solid var(--card-border); padding: 20px; border-radius: 12px;">
                        <div style="color: var(--text-muted); font-size: 12px; font-weight: bold; text-transform: uppercase;">Failed Logins (24h)</div>
                        <div style="font-size: 32px; font-weight: 800; color: var(--danger); font-family: 'Inter';"><?php echo $stats['failed_24h']; ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div style="background: var(--card-bg); border: 1px solid var(--card-border); padding: 20px; border-radius: 12px;">
                        <div style="color: var(--text-muted); font-size: 12px; font-weight: bold; text-transform: uppercase;">Active IP Blocks</div>
                        <div style="font-size: 32px; font-weight: 800; color: var(--amber); font-family: 'Inter';"><?php echo $stats['active_blocks']; ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div style="background: var(--card-bg); border: 1px solid var(--card-border); padding: 20px; border-radius: 12px;">
                        <div style="color: var(--text-muted); font-size: 12px; font-weight: bold; text-transform: uppercase;">Critical Alerts (7d)</div>
                        <div style="font-size: 32px; font-weight: 800; color: var(--danger); font-family: 'Inter';"><?php echo $stats['crit_alerts']; ?></div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <ul class="nav nav-tabs bordered">
                        <li class="active">
                            <a href="#audit" data-toggle="tab">
                                <span class="hidden-xs">General Audit Log</span>
                            </a>
                        </li>
                        <li>
                            <a href="#login" data-toggle="tab">
                                <span class="hidden-xs">Login Attempts (Brute Force)</span>
                            </a>
                        </li>
                    </ul>
                    
                    <div class="tab-content">
                        <!-- General Audit Log -->
                        <div class="tab-pane active" id="audit">
                            <table class="table table-bordered table-striped datatable" style="background: var(--card-bg);">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>Severity</th>
                                        <th>Event Type</th>
                                        <th>User/Target</th>
                                        <th>Description</th>
                                        <th>IP Address</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = mysqli_fetch_assoc($audit_query)): ?>
                                    <tr>
                                        <td style="font-size: 12px; color: var(--text-muted);"><?php echo htmlspecialchars($row['created_at']); ?></td>
                                        <td><?php echo get_severity_badge($row['severity']); ?></td>
                                        <td style="font-weight: bold; font-family: monospace; font-size: 12px;"><?php echo htmlspecialchars($row['event_type']); ?></td>
                                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                                        <td><?php echo htmlspecialchars($row['description']); ?></td>
                                        <td style="font-family: monospace; font-size: 12px;"><?php echo htmlspecialchars($row['ip_address']); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Login Attempts -->
                        <div class="tab-pane" id="login">
                            <table class="table table-bordered table-striped datatable" style="background: var(--card-bg);">
                                <thead>
                                    <tr>
                                        <th>Attempt Time</th>
                                        <th>Status</th>
                                        <th>Target Username</th>
                                        <th>IP Address</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = mysqli_fetch_assoc($login_query)): ?>
                                    <tr>
                                        <td style="font-size: 12px; color: var(--text-muted);"><?php echo htmlspecialchars($row['attempt_time']); ?></td>
                                        <td>
                                            <?php if ($row['status'] === 'failed'): ?>
                                                <span style="color: var(--danger); font-weight: bold;"><i class="entypo-cancel"></i> FAILED</span>
                                            <?php else: ?>
                                                <span style="color: var(--success); font-weight: bold;"><i class="entypo-check"></i> SUCCESS</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="font-weight: bold;"><?php echo htmlspecialchars($row['username']); ?></td>
                                        <td style="font-family: monospace; font-size: 12px; color: <?php echo ($row['status'] === 'failed') ? 'var(--danger)' : 'var(--text-main)'; ?>"><?php echo htmlspecialchars($row['ip_address']); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php include('footer.php'); ?>
        </div>
    </div>
</body>
</html>
