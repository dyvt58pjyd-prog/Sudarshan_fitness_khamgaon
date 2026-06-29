<?php
require '../../include/db_conn.php';
page_protect();

if ($_SESSION['role'] !== 'super_admin' && $_SESSION['role'] !== 'owner') {
    echo "<head><script>alert('Access Denied');</script></head></html>";
    echo "<meta http-equiv='refresh' content='0; url=/index.php'>";
    exit();
}

$gym = get_gym_details($con);
$today = date('Y-m-d');

// Handle WhatsApp Nudge action
if (isset($_GET['nudge_uid'])) {
    $nudge_uid = mysqli_real_escape_string($con, $_GET['nudge_uid']);
    
    // Fetch member and attendance details
    $q_rem = "SELECT u.username, u.mobile, u.joining_date, MAX(a.date) as last_attendance 
              FROM users u 
              LEFT JOIN attendance a ON u.userid = a.uid 
              WHERE u.userid = '$nudge_uid' 
              GROUP BY u.userid";
    $res_rem = mysqli_query($con, $q_rem);
    
    if ($res_rem && mysqli_num_rows($res_rem) > 0) {
        $row = mysqli_fetch_assoc($res_rem);
        $name = $row['username'];
        $mobile = $row['mobile'];
        
        $last_att = $row['last_attendance'];
        if (empty($last_att)) {
            $last_att = !empty($row['joining_date']) ? $row['joining_date'] : date('Y-m-d', strtotime('-11 days'));
        }
        
        $days_absent = round((strtotime($today) - strtotime($last_att)) / (60 * 60 * 24));
        if ($days_absent < 0) $days_absent = 0;
        
        if (!empty($mobile)) {
            $wa_mobile = preg_replace('/[^0-9]/', '', $mobile);
            if (strlen($wa_mobile) === 10) {
                $wa_mobile = '91' . $wa_mobile;
            }
            
            $gym_name = $gym['gym_name'];
            $message = "🏋️ *We Miss You at {$gym_name}!* 🏋️\n\n" .
                       "Hello *{$name}*,\n\n" .
                       "We noticed you haven't been to the gym in *{$days_absent}* days! Consistency is key to reaching your fitness goals.\n\n" .
                       "Is there anything we can help you with? We have new workout slots and trainer assistance ready to help you get back on track.\n\n" .
                       "Hope to see you back on the gym floor soon! 💪";
            
            if (enqueue_whatsapp_message($con, $wa_mobile, $message)) {
                $_SESSION['nudge_status'] = "success";
                $_SESSION['nudge_msg'] = "Nudge message sent successfully to {$name} via WhatsApp.";
            } else {
                $_SESSION['nudge_status'] = "warning";
                $_SESSION['nudge_msg'] = "WhatsApp gateway offline. Nudge message has been queued in outbox for {$name}.";
            }
        } else {
            $_SESSION['nudge_status'] = "danger";
            $_SESSION['nudge_msg'] = "Member has no mobile number registered.";
        }
    } else {
        $_SESSION['nudge_status'] = "danger";
        $_SESSION['nudge_msg'] = "Member not found.";
    }
    header("Location: churn_analytics.php");
    exit();
}

// Fetch active members and calculate days absent
$q_members = "SELECT u.userid, u.username, u.mobile, u.email, u.joining_date, MAX(a.date) as last_attendance, MAX(e.expire) as expire
              FROM users u
              INNER JOIN enrolls_to e ON u.userid = e.uid AND e.renewal = 'yes' AND e.expire >= '$today'
              LEFT JOIN attendance a ON u.userid = a.uid
              GROUP BY u.userid, u.username, u.mobile, u.email, u.joining_date";
$res_members = mysqli_query($con, $q_members);
$members = [];
$low_risk_cnt = 0;
$med_risk_cnt = 0;
$high_risk_cnt = 0;

if ($res_members) {
    while ($row = mysqli_fetch_assoc($res_members)) {
        $last_att = $row['last_attendance'];
        if (empty($last_att)) {
            $last_att = !empty($row['joining_date']) ? $row['joining_date'] : '';
        }
        
        if (!empty($last_att)) {
            $diff_seconds = strtotime($today) - strtotime($last_att);
            $days_absent = round($diff_seconds / (60 * 60 * 24));
            if ($days_absent < 0) $days_absent = 0;
        } else {
            $days_absent = 30; // treat as high risk fallback if never checked in
        }
        
        $row['days_absent'] = $days_absent;
        $row['last_att_display'] = !empty($row['last_attendance']) ? date('d-M-Y', strtotime($row['last_attendance'])) : 'Never';
        
        if ($days_absent <= 3) {
            $row['risk'] = 'Low';
            $low_risk_cnt++;
        } elseif ($days_absent <= 10) {
            $row['risk'] = 'Medium';
            $med_risk_cnt++;
        } else {
            $row['risk'] = 'High';
            $high_risk_cnt++;
        }
        
        $members[] = $row;
    }
}

// Sort highest absent first
usort($members, function($a, $b) {
    return $b['days_absent'] - $a['days_absent'];
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo htmlspecialchars($gym['gym_name']); ?> | Churn Risk Analytics</title>
    <link rel="stylesheet" href="../../css/style.css" id="style-resource-5">
    <script type="text/javascript" src="../../js/Script.js"></script>
    <link rel="stylesheet" href="../../css/dashMain.css">
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
    <link rel="stylesheet" href="../../css/premium.css">
    <link href="a1style.css" rel="stylesheet" type="text/css">
    <style>
        .page-container .sidebar-menu #main-menu li#churn_analytics > a {
            background-color: rgba(59, 130, 246, 0.1) !important;
            color: var(--accent-primary) !important;
            font-weight: 600 !important;
            box-shadow: inset 3px 0 0 var(--accent-primary);
        }
        .metric-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: var(--glass-shadow);
            text-align: center;
        }
        .metric-val {
            font-size: 36px;
            font-weight: 800;
            margin-top: 5px;
            margin-bottom: 5px;
        }
        .risk-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .risk-low {
            background: rgba(16, 185, 129, 0.15);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        .risk-medium {
            background: rgba(245, 158, 11, 0.15);
            color: #f59e0b;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }
        .risk-high {
            background: rgba(239, 68, 68, 0.15);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        .table-premium {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        .table-premium th, .table-premium td {
            padding: 15px 20px;
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

            <h2>Churn Risk &amp; Retention Analytics</h2>
            <hr />

            <?php if (isset($_SESSION['nudge_msg'])): ?>
                <div class="alert alert-<?php echo $_SESSION['nudge_status']; ?>" style="border-radius: 10px; padding: 15px; margin-bottom: 25px;">
                    <strong><?php echo $_SESSION['nudge_msg']; ?></strong>
                </div>
                <?php 
                unset($_SESSION['nudge_msg']);
                unset($_SESSION['nudge_status']);
                ?>
            <?php endif; ?>

            <!-- Metrics Overview Cards -->
            <div class="row">
                <div class="col-md-4">
                    <div class="metric-card" style="border-top: 4px solid var(--success);">
                        <div style="color: var(--text-muted); font-size: 12px; font-weight: 600; text-transform: uppercase;">Low Risk (≤3 Days Absent)</div>
                        <div class="metric-val" style="color: var(--success);"><?php echo $low_risk_cnt; ?></div>
                        <div style="color: var(--text-muted); font-size: 11px;">Active &amp; Consistent Members</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="metric-card" style="border-top: 4px solid #f59e0b;">
                        <div style="color: var(--text-muted); font-size: 12px; font-weight: 600; text-transform: uppercase;">Medium Risk (4-10 Days Absent)</div>
                        <div class="metric-val" style="color: #f59e0b;"><?php echo $med_risk_cnt; ?></div>
                        <div style="color: var(--text-muted); font-size: 11px;">Needs Attention / Attendance Slump</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="metric-card" style="border-top: 4px solid #ef4444;">
                        <div style="color: var(--text-muted); font-size: 12px; font-weight: 600; text-transform: uppercase;">High Risk (>10 Days Absent)</div>
                        <div class="metric-val" style="color: #ef4444;"><?php echo $high_risk_cnt; ?></div>
                        <div style="color: var(--text-muted); font-size: 11px;">Highly Likely to Churn</div>
                    </div>
                </div>
            </div>

            <!-- Members List -->
            <div class="row">
                <div class="col-md-12">
                    <div style="background: var(--glass-bg); backdrop-filter: blur(16px); border: 1px solid var(--glass-border); border-radius: 20px; padding: 30px; box-shadow: var(--glass-shadow);">
                        <h3 style="margin-top: 0; color: #ffffff; font-weight: 700; margin-bottom: 20px;">Retention &amp; Nudge Console</h3>
                        
                        <div class="table-responsive" style="border: 1px solid var(--glass-border); border-radius: 12px; background: rgba(0,0,0,0.15); overflow: hidden;">
                            <table class="table-premium">
                                <thead>
                                    <tr style="background: rgba(0,0,0,0.25);">
                                        <th>Member ID</th>
                                        <th>Name</th>
                                        <th>Contact</th>
                                        <th>Last Check-In</th>
                                        <th>Days Absent</th>
                                        <th>Risk Category</th>
                                        <th style="text-align: right;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($members) > 0): ?>
                                        <?php foreach ($members as $m): ?>
                                            <tr>
                                                <td><span style="font-family: monospace; font-weight: bold; color: var(--text-muted);"><?php echo htmlspecialchars($m['userid']); ?></span></td>
                                                <td><strong style="color: #ffffff;"><?php echo htmlspecialchars($m['username']); ?></strong></td>
                                                <td>
                                                    <span style="color: #ffffff;"><?php echo htmlspecialchars($m['mobile']); ?></span>
                                                    <div style="color: var(--text-muted); font-size: 11px;"><?php echo htmlspecialchars($m['email']); ?></div>
                                                </td>
                                                <td><span style="font-weight: 500; color: #ffffff;"><?php echo $m['last_att_display']; ?></span></td>
                                                <td>
                                                    <span style="font-weight: bold; color: <?php echo $m['days_absent'] > 10 ? '#ef4444' : ($m['days_absent'] > 3 ? '#f59e0b' : 'var(--success)'); ?>;">
                                                        <?php echo $m['days_absent']; ?> day<?php echo $m['days_absent'] == 1 ? '' : 's'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="risk-badge risk-<?php echo strtolower($m['risk']); ?>">
                                                        <?php echo $m['risk']; ?> Risk
                                                    </span>
                                                </td>
                                                <td style="text-align: right;">
                                                    <?php if ($m['days_absent'] > 3): ?>
                                                        <a href="?nudge_uid=<?php echo urlencode($m['userid']); ?>" class="btn btn-orange btn-xs" style="margin: 0; padding: 6px 12px; font-weight: 600; border-radius: 6px; display: inline-flex; align-items: center; gap: 5px;">
                                                            <i class="entypo-phone"></i> WhatsApp Nudge
                                                        </a>
                                                    <?php else: ?>
                                                        <span style="color: var(--text-muted); font-size: 12px;">Active 👍</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                                No active members found in records.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
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
