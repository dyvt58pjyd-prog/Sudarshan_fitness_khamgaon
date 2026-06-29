<?php
require '../../include/db_conn.php';
page_protect();

// Access check: only super_admin and owner can view global biometric entry/exit logs
if ($_SESSION['role'] !== 'super_admin' && $_SESSION['role'] !== 'owner') {
    echo "<head><script>alert('Access Denied: You do not have permissions to view biometric entry logs.');</script></head></html>";
    echo "<meta http-equiv='refresh' content='0; url=index.php'>";
    exit();
}

$gym = get_gym_details($con);

// Parse search query if any
$search = isset($_GET['search']) ? mysqli_real_escape_string($con, trim($_GET['search'])) : '';

$where_clause = "";
if (!empty($search)) {
    $where_clause = " WHERE u.username LIKE '%$search%' OR u.userid LIKE '%$search%' ";
}

// Fetch logs
$sql_logs = "SELECT a.id, a.uid, a.date, a.entry_time, a.exit_time, u.username, u.photo 
             FROM attendance a 
             INNER JOIN users u ON a.uid = u.userid
             $where_clause
             ORDER BY a.date DESC, a.entry_time DESC 
             LIMIT 150"; // Limit to top 150 for page speed
$res_logs = mysqli_query($con, $sql_logs);
$logs = [];
if ($res_logs) {
    while ($row = mysqli_fetch_assoc($res_logs)) {
        $logs[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo htmlspecialchars($gym['gym_name']); ?> | Fingerprint Access Logs</title>
    <link rel="stylesheet" href="../../css/style.css" id="style-resource-5">
    <script type="text/javascript" src="../../js/Script.js"></script>
    <link rel="stylesheet" href="../../css/dashMain.css">
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
    <link rel="stylesheet" href="../../css/premium.css">
    <link href="a1style.css" rel="stylesheet" type="text/css">
    <style>
        .page-container .sidebar-menu #main-menu li#biometric_logs_link > a {
            background-color: rgba(59, 130, 246, 0.1) !important;
            color: var(--accent-primary) !important;
            font-weight: 600 !important;
            box-shadow: inset 3px 0 0 var(--accent-primary);
        }
        
        .logs-card {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 25px;
            box-shadow: var(--glass-shadow);
            margin-bottom: 30px;
        }

        .table-premium {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .table-premium th, .table-premium td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            vertical-align: middle;
        }

        .table-premium th {
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            font-size: 11.5px;
            letter-spacing: 0.5px;
        }

        .member-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .badge-premium {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-inside {
            background: rgba(255, 107, 0, 0.12);
            color: var(--accent-primary);
            border: 1px solid rgba(255, 107, 0, 0.25);
        }

        .badge-completed {
            background: rgba(148, 163, 184, 0.08);
            color: var(--text-muted);
            border: 1px solid rgba(148, 163, 184, 0.15);
        }
    </style>
</head>
<body class="page-body page-fade" onload="collapseSidebar()">
    <div class="page-container sidebar-collapsed" id="navbarcollapse">
        <div class="sidebar-menu">
            <header class="logo-env">
                <div class="logo">
                    <a href="index.php">
                        <img src="<?php echo htmlspecialchars($gym['gym_logo']); ?>" alt="" style="max-height: 60px; max-width: 180px;" />
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
                <div class="col-md-6 col-sm-8 clearfix"></div>
                <div class="col-md-6 col-sm-4 clearfix hidden-xs">
                    <ul class="list-inline links-list pull-right">
                        <li>Welcome <?php echo htmlspecialchars($_SESSION['full_name']); ?></li>
                        <li>
                            <a href="logout.php">
                                Log Out <i class="entypo-logout right"></i>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <h2>Fingerprint Access Logs</h2>
            <hr />

            <div class="logs-card">
                <h3 style="margin-top: 0; color: #ffffff; font-weight: 700; display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                    <i class="entypo-list" style="color: var(--accent-primary);"></i> Audit Entries &amp; Exits
                </h3>
                <p style="color: var(--text-muted); font-size: 13.5px; margin-bottom: 25px; line-height: 1.5;">
                    Monitor all door entries and exits. Attendance records get updated instantly when fingerprints are recognized on the physical device.
                </p>

                <!-- Server-side Search Panel -->
                <form method="get" action="" style="margin-bottom: 25px;">
                    <div style="display: flex; gap: 15px;">
                        <div style="flex-grow: 1; position: relative;">
                            <input type="text" name="search" class="form-control-premium" placeholder="Search logs by member name or ID..." value="<?php echo htmlspecialchars($search); ?>" style="padding-left: 15px !important;" />
                        </div>
                        <button type="submit" class="btn btn-primary" style="margin: 0; padding: 10px 25px;">Search Logs</button>
                        <?php if (!empty($search)): ?>
                            <a href="biometric_logs.php" class="btn btn-default" style="margin: 0; padding: 10px 20px; display: inline-flex; align-items: center; justify-content: center;">Clear Filter</a>
                        <?php endif; ?>
                    </div>
                </form>

                <div class="table-responsive" style="border: 1px solid var(--glass-border); border-radius: 12px; background: rgba(0,0,0,0.15); overflow: hidden;">
                    <table class="table-premium">
                        <thead>
                            <tr style="background: rgba(0,0,0,0.25);">
                                <th style="width: 8%;">Photo</th>
                                <th style="width: 25%;">Gym Member</th>
                                <th style="width: 17%;">ID Number</th>
                                <th style="width: 17%;">Date &amp; Day</th>
                                <th style="width: 15%;">Entry Time</th>
                                <th style="width: 18%;">Exit Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($logs) > 0): ?>
                                <?php foreach ($logs as $l): 
                                    $photo_src = !empty($l['photo']) ? htmlspecialchars($l['photo']) : '../../images/default_avatar.jpg';
                                    $day_str = date('l', strtotime($l['date']));
                                    $date_formatted = date('d-M-Y', strtotime($l['date']));
                                ?>
                                    <tr>
                                        <td>
                                            <img src="<?php echo $photo_src; ?>" class="member-avatar" alt="Avatar">
                                        </td>
                                        <td>
                                            <strong style="color: #ffffff; font-size: 14.5px;"><?php echo htmlspecialchars($l['username']); ?></strong>
                                        </td>
                                        <td>
                                            <span style="color: var(--text-muted); font-family: monospace; font-size: 13px; font-weight: bold;">
                                                <?php echo htmlspecialchars($l['uid']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong style="color: #ffffff;"><?php echo $date_formatted; ?></strong>
                                            <div style="color: var(--text-muted); font-size: 11px; margin-top: 1px;"><?php echo $day_str; ?></div>
                                        </td>
                                        <td>
                                            <span style="font-family: monospace; font-weight: bold; color: var(--success); font-size: 13.5px;">
                                                <?php echo date('h:i A', strtotime($l['entry_time'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!empty($l['exit_time'])): ?>
                                                <span style="font-family: monospace; font-weight: bold; color: var(--text-muted); font-size: 13.5px; margin-right: 8px;">
                                                    <?php echo date('h:i A', strtotime($l['exit_time'])); ?>
                                                </span>
                                                <span class="badge-premium badge-completed">Checked Out</span>
                                            <?php else: ?>
                                                <span class="badge-premium badge-inside">Still Inside</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                        No door logs found matching the filter search.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php include('footer.php'); ?>
        </div>
    </div>
</body>
</html>
