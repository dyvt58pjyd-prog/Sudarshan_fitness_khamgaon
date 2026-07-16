<?php
require '../../include/db_conn.php';
page_protect();

if ($_SESSION['role'] !== 'super_admin' && $_SESSION['role'] !== 'owner') {
    echo "<head><script>alert('Access Denied');</script></head></html>";
    echo "<meta http-equiv='refresh' content='0; url=index.php'>";
    exit();
}

$gym = get_gym_details($con);

// Get current year context
$working_year = isset($_SESSION['working_year']) ? intval($_SESSION['working_year']) : intval(date('Y'));
$current_real_year = intval(date('Y'));

if ($working_year === $current_real_year) {
    $today_str = date('Y-m-d');
} else {
    $today_str = $working_year . '-' . date('m-d');
}

$today_time = strtotime($today_str);
$date_today = date('Y-m-d', $today_time);
$date_minus_15 = date('Y-m-d', strtotime('-15 days', $today_time));
$date_plus_15 = date('Y-m-d', strtotime('+15 days', $today_time));
$date_plus_5 = date('Y-m-d', strtotime('+5 days', $today_time));

// Handle Single Nudge
if (isset($_GET['nudge_uid'])) {
    $nudge_uid = mysqli_real_escape_string($con, $_GET['nudge_uid']);
    
    $q_m = "SELECT u.username, u.mobile, e.expire, p.planName, p.amount
            FROM users u
            INNER JOIN (
                SELECT uid, MAX(et_id) as max_et_id
                FROM enrolls_to
                GROUP BY uid
            ) latest_e ON u.userid = latest_e.uid
            INNER JOIN enrolls_to e ON latest_e.max_et_id = e.et_id
            INNER JOIN plan p ON e.pid = p.pid
            WHERE u.userid = '$nudge_uid'";
    $res_m = mysqli_query($con, $q_m);
    
    if ($res_m && mysqli_num_rows($res_m) > 0) {
        $row = mysqli_fetch_assoc($res_m);
        $name = $row['username'];
        $mobile = $row['mobile'];
        $plan = $row['planName'];
        $expire_date = $row['expire'];
        
        $days = (strtotime($expire_date) - strtotime($date_today)) / 86400;
        $gym_name = $gym['gym_name'];
        
        if ($days < 0) {
            $days_abs = abs($days);
            $day_word = ($days_abs == 1) ? "day" : "days";
            $message = "🏋️ *{$gym_name} Membership Alert* 🏋️\n\n" .
                       "Hello *{$name}*,\n\n" .
                       "Your membership for plan *{$plan}* expired on *{$expire_date}* (*{$days_abs} {$day_word} ago*).\n\n" .
                       "To ensure uninterrupted gym access and continue your fitness journey, please renew your membership at the reception desk.\n\n" .
                       "Hope to see you soon! 💪";
        } elseif ($days == 0) {
            $message = "🏋️ *{$gym_name} Membership Renewal* 🏋️\n\n" .
                       "Hello *{$name}*,\n\n" .
                       "Your membership for plan *{$plan}* is expiring *TODAY*.\n\n" .
                       "To ensure uninterrupted gym access, please renew your membership at the reception desk.\n\n" .
                       "Stay fit and strong! 💪";
        } else {
            $day_word = ($days == 1) ? "day" : "days";
            $message = "🏋️ *{$gym_name} Membership Renewal* 🏋️\n\n" .
                       "Hello *{$name}*,\n\n" .
                       "Your membership for plan *{$plan}* is expiring on *{$expire_date}* (in *{$days} {$day_word}*).\n\n" .
                       "To ensure uninterrupted gym access, please renew your membership before the expiry date at the reception desk.\n\n" .
                       "Stay fit and strong! 💪";
        }
        
        if (!empty($mobile)) {
            $wa_mobile = preg_replace('/[^0-9]/', '', $mobile);
            if (strlen($wa_mobile) === 10) $wa_mobile = '91' . $wa_mobile;
            
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
        $_SESSION['nudge_msg'] = "Member subscription details not found.";
    }
    header("Location: renewal_pipeline.php");
    exit();
}

// Handle Bulk Nudge
if (isset($_POST['bulk_nudge'])) {
    $nudge_count = 0;
    $queued_count = 0;
    
    $q_pipeline_bulk = "SELECT u.username, u.mobile, e.expire, p.planName
                        FROM users u
                        INNER JOIN (
                            SELECT uid, MAX(et_id) as max_et_id
                            FROM enrolls_to
                            GROUP BY uid
                        ) latest_e ON u.userid = latest_e.uid
                        INNER JOIN enrolls_to e ON latest_e.max_et_id = e.et_id
                        INNER JOIN plan p ON e.pid = p.pid
                        WHERE e.expire >= '$date_minus_15' AND e.expire <= '$date_plus_15'";
    $res_bulk = mysqli_query($con, $q_pipeline_bulk);
    
    if ($res_bulk && mysqli_num_rows($res_bulk) > 0) {
        while ($row = mysqli_fetch_assoc($res_bulk)) {
            $name = $row['username'];
            $mobile = $row['mobile'];
            $plan = $row['planName'];
            $expire_date = $row['expire'];
            
            if (empty($mobile)) continue;
            
            $days = (strtotime($expire_date) - strtotime($date_today)) / 86400;
            $gym_name = $gym['gym_name'];
            
            if ($days < 0) {
                $days_abs = abs($days);
                $day_word = ($days_abs == 1) ? "day" : "days";
                $message = "🏋️ *{$gym_name} Membership Alert* 🏋️\n\n" .
                           "Hello *{$name}*,\n\n" .
                           "Your membership for plan *{$plan}* expired on *{$expire_date}* (*{$days_abs} {$day_word} ago*).\n\n" .
                           "To ensure uninterrupted gym access and continue your fitness journey, please renew your membership at the reception desk.\n\n" .
                           "Hope to see you soon! 💪";
            } elseif ($days == 0) {
                $message = "🏋️ *{$gym_name} Membership Renewal* 🏋️\n\n" .
                           "Hello *{$name}*,\n\n" .
                           "Your membership for plan *{$plan}* is expiring *TODAY*.\n\n" .
                           "To ensure uninterrupted gym access, please renew your membership at the reception desk.\n\n" .
                           "Stay fit and strong! 💪";
            } else {
                $day_word = ($days == 1) ? "day" : "days";
                $message = "🏋️ *{$gym_name} Membership Renewal* 🏋️\n\n" .
                           "Hello *{$name}*,\n\n" .
                           "Your membership for plan *{$plan}* is expiring on *{$expire_date}* (in *{$days} {$day_word}*).\n\n" .
                           "To ensure uninterrupted gym access, please renew your membership before the expiry date at the reception desk.\n\n" .
                           "Stay fit and strong! 💪";
            }
            
            $wa_mobile = preg_replace('/[^0-9]/', '', $mobile);
            if (strlen($wa_mobile) === 10) $wa_mobile = '91' . $wa_mobile;
            
            if (enqueue_whatsapp_message($con, $wa_mobile, $message)) {
                $nudge_count++;
            } else {
                $queued_count++;
            }
        }
        
        $_SESSION['nudge_status'] = "success";
        $_SESSION['nudge_msg'] = "Bulk reminder campaign finished. Sent {$nudge_count} alert(s) immediately; {$queued_count} alert(s) queued in database.";
    } else {
        $_SESSION['nudge_status'] = "warning";
        $_SESSION['nudge_msg'] = "No members found in the current 15-day renewal pipeline.";
    }
    
    header("Location: renewal_pipeline.php");
    exit();
}

// Fetch all pipeline members
$members = [];
$counts = [
    'expired' => 0,
    'today' => 0,
    'soon' => 0,
    'later' => 0,
    'total_expected' => 0
];

$q_pipeline = "SELECT u.userid, u.username, u.mobile, u.email, e.expire, e.pid, p.planName, p.amount
               FROM users u
               INNER JOIN (
                   SELECT uid, MAX(et_id) as max_et_id
                   FROM enrolls_to
                   GROUP BY uid
               ) latest_e ON u.userid = latest_e.uid
               INNER JOIN enrolls_to e ON latest_e.max_et_id = e.et_id
               INNER JOIN plan p ON e.pid = p.pid
               WHERE e.expire >= '$date_minus_15' AND e.expire <= '$date_plus_15'";
$res = mysqli_query($con, $q_pipeline);

if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $expire_date = $row['expire'];
        $amount = intval($row['amount']);
        
        $days = round((strtotime($expire_date) - strtotime($date_today)) / 86400);
        $row['days_diff'] = $days;
        
        if ($days < 0) {
            $row['status_cat'] = 'expired';
            $counts['expired']++;
        } elseif ($days == 0) {
            $row['status_cat'] = 'today';
            $counts['today']++;
        } elseif ($days <= 5) {
            $row['status_cat'] = 'soon';
            $counts['soon']++;
        } else {
            $row['status_cat'] = 'later';
            $counts['later']++;
        }
        
        $counts['total_expected'] += $amount;
        $members[] = $row;
    }
}

// Sort: Expired first, then Today, then Soon, then Later
usort($members, function($a, $b) {
    return $a['days_diff'] - $b['days_diff'];
});

// Prepare 15-day forward cash flow projection data
$projection_data = [];
for ($i = 0; $i <= 15; $i++) {
    $proj_date = date('Y-m-d', strtotime("+$i days", $today_time));
    $projection_data[$proj_date] = 0;
}

foreach ($members as $m) {
    $exp = $m['expire'];
    if (isset($projection_data[$exp])) {
        $projection_data[$exp] += intval($m['amount']);
    }
}

$proj_labels = [];
$proj_values = [];
foreach ($projection_data as $date => $val) {
    $proj_labels[] = date('d M', strtotime($date));
    $proj_values[] = $val;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo htmlspecialchars($gym['gym_name']); ?> | Renewal Pipeline</title>
    <link rel="stylesheet" href="../../css/style.css" id="style-resource-5">
    <script type="text/javascript" src="../../js/Script.js"></script>
    <link rel="stylesheet" href="../../css/dashMain.css">
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
    <link rel="stylesheet" href="../../css/premium.css">
    <link href="a1style.css" rel="stylesheet" type="text/css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .page-container .sidebar-menu #main-menu li#renewal_pipeline > a {
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
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .metric-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
        }
        .metric-val {
            font-size: 32px;
            font-weight: 800;
            margin-top: 5px;
            margin-bottom: 5px;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .status-expired {
            background: rgba(239, 68, 68, 0.15);
            color: #ef4444;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        .status-today {
            background: rgba(245, 158, 11, 0.15);
            color: #f59e0b;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }
        .status-soon {
            background: rgba(255, 107, 0, 0.15);
            color: var(--accent-primary);
            border: 1px solid rgba(255, 107, 0, 0.3);
        }
        .status-later {
            background: rgba(59, 130, 246, 0.15);
            color: #3b82f6;
            border: 1px solid rgba(59, 130, 246, 0.3);
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
        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            color: #ffffff;
            text-decoration: none;
            transition: all 0.2s;
            margin-left: 5px;
        }
        .action-btn:hover {
            background: var(--accent-primary);
            border-color: var(--accent-primary);
            color: #ffffff;
            transform: scale(1.05);
        }
        .action-btn.pay-btn:hover {
            background: var(--success);
            border-color: var(--success);
        }
        .action-btn.email-btn:hover {
            background: #3b82f6;
            border-color: #3b82f6;
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

            <h2>Renewal Pipeline &amp; Projections</h2>
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

            <!-- Metrics Grid -->
            <div class="row">
                <div class="col-md-25" style="width: 20%; float: left; padding: 0 15px;">
                    <div class="metric-card" style="border-top: 4px solid #ef4444;">
                        <div style="color: var(--text-muted); font-size: 11px; font-weight: 600; text-transform: uppercase;">Expired (1-15d ago)</div>
                        <div class="metric-val" style="color: #ef4444;"><?php echo $counts['expired']; ?></div>
                        <div style="color: var(--text-muted); font-size: 10px;">Lapsed Members</div>
                    </div>
                </div>
                <div class="col-md-25" style="width: 20%; float: left; padding: 0 15px;">
                    <div class="metric-card" style="border-top: 4px solid #f59e0b;">
                        <div style="color: var(--text-muted); font-size: 11px; font-weight: 600; text-transform: uppercase;">Expires Today</div>
                        <div class="metric-val" style="color: #f59e0b;"><?php echo $counts['today']; ?></div>
                        <div style="color: var(--text-muted); font-size: 10px;">Critical Attention</div>
                    </div>
                </div>
                <div class="col-md-25" style="width: 20%; float: left; padding: 0 15px;">
                    <div class="metric-card" style="border-top: 4px solid var(--accent-primary);">
                        <div style="color: var(--text-muted); font-size: 11px; font-weight: 600; text-transform: uppercase;">Expires 1-5 Days</div>
                        <div class="metric-val" style="color: var(--accent-primary);"><?php echo $counts['soon']; ?></div>
                        <div style="color: var(--text-muted); font-size: 10px;">High Priority Nudge</div>
                    </div>
                </div>
                <div class="col-md-25" style="width: 20%; float: left; padding: 0 15px;">
                    <div class="metric-card" style="border-top: 4px solid #3b82f6;">
                        <div style="color: var(--text-muted); font-size: 11px; font-weight: 600; text-transform: uppercase;">Expires 6-15 Days</div>
                        <div class="metric-val" style="color: #3b82f6;"><?php echo $counts['later']; ?></div>
                        <div style="color: var(--text-muted); font-size: 10px;">Standard Pipeline</div>
                    </div>
                </div>
                <div class="col-md-25" style="width: 20%; float: left; padding: 0 15px;">
                    <div class="metric-card" style="border-top: 4px solid var(--success); background: rgba(16, 185, 129, 0.05);">
                        <div style="color: var(--text-muted); font-size: 11px; font-weight: 600; text-transform: uppercase;">Expected Inflows</div>
                        <div class="metric-val" style="color: var(--success);">₹<?php echo number_format($counts['total_expected']); ?></div>
                        <div style="color: var(--text-muted); font-size: 10px;">Total Pipeline Value</div>
                    </div>
                </div>
            </div>
            <div style="clear: both;"></div>

            <!-- Projection Chart and Campaign Card -->
            <div class="row" style="margin-top: 15px;">
                <div class="col-md-7">
                    <div style="background: var(--glass-bg); backdrop-filter: blur(16px); border: 1px solid var(--glass-border); border-radius: 20px; padding: 25px; box-shadow: var(--glass-shadow); height: 350px; display: flex; flex-direction: column;">
                        <h4 style="margin-top: 0; color: #ffffff; font-weight: 700; margin-bottom: 15px;">15-Day Renewal Cash Flow Projection</h4>
                        <div style="flex-grow: 1; position: relative;">
                            <canvas id="projectionChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-5">
                    <div style="background: var(--glass-bg); backdrop-filter: blur(16px); border: 1px solid var(--glass-border); border-radius: 20px; padding: 25px; box-shadow: var(--glass-shadow); height: 350px; display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <h4 style="margin-top: 0; color: #ffffff; font-weight: 700; margin-bottom: 10px;">Bulk WhatsApp Campaign</h4>
                            <p style="color: var(--text-muted); font-size: 13px; line-height: 1.6; margin-bottom: 15px;">
                                Queue personalized WhatsApp renewal reminders to all <strong><?php echo count($members); ?> members</strong> currently in the 15-day pipeline. The system will adapt the template for expired versus expiring members dynamically.
                            </p>
                            
                            <div style="background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.08); border-radius: 10px; padding: 15px; font-size: 12px; font-family: monospace; color: #e2e8f0; max-height: 120px; overflow-y: auto;">
                                <span style="color: var(--accent-primary); font-weight: bold;">[SAMPLE REMINDER TEXT]</span><br>
                                🏋️ Sudarshan Fitness Renewal<br>
                                Hello [Name], your plan [PlanName] is expiring in [X] days (on [ExpiryDate]). Please renew before expiry to prevent scan lockages. 💪
                            </div>
                        </div>
                        
                        <form action="renewal_pipeline.php" method="POST" style="margin: 0;">
                            <button type="submit" name="bulk_nudge" value="1" class="btn btn-primary btn-block" style="display: flex; align-items: center; justify-content: center; gap: 8px; font-weight: bold; padding: 12px; border-radius: 10px;">
                                <i class="entypo-megaphone" style="font-size: 16px;"></i>
                                Send Bulk WhatsApp Reminders
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Detailed Table List -->
            <div class="row" style="margin-top: 30px; margin-bottom: 50px;">
                <div class="col-md-12">
                    <div style="background: var(--glass-bg); backdrop-filter: blur(16px); border: 1px solid var(--glass-border); border-radius: 20px; padding: 30px; box-shadow: var(--glass-shadow);">
                        <h3 style="margin-top: 0; color: #ffffff; font-weight: 700; margin-bottom: 20px;">Renewal Pipeline Members</h3>
                        
                        <div class="table-responsive" style="border: 1px solid var(--glass-border); border-radius: 12px; background: rgba(0,0,0,0.15); overflow: hidden;">
                            <table class="table-premium">
                                <thead>
                                    <tr style="background: rgba(0,0,0,0.25);">
                                        <th>Member ID</th>
                                        <th>Name</th>
                                        <th>Contact Details</th>
                                        <th>Expiry Date</th>
                                        <th>Plan Name</th>
                                        <th>Status Category</th>
                                        <th>Renewal Fee</th>
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
                                                <td><span style="font-weight: 500; color: #ffffff;"><?php echo date('d-M-Y', strtotime($m['expire'])); ?></span></td>
                                                <td><span style="color: #ffffff; font-weight: 500;"><?php echo htmlspecialchars($m['planName']); ?></span></td>
                                                <td>
                                                    <?php if ($m['status_cat'] === 'expired'): ?>
                                                        <span class="status-badge status-expired">Expired <?php echo abs($m['days_diff']); ?>d ago</span>
                                                    <?php elseif ($m['status_cat'] === 'today'): ?>
                                                        <span class="status-badge status-today">Expires Today</span>
                                                    <?php elseif ($m['status_cat'] === 'soon'): ?>
                                                        <span class="status-badge status-soon">Expires in <?php echo $m['days_diff']; ?>d</span>
                                                    <?php else: ?>
                                                        <span class="status-badge status-later">Expires in <?php echo $m['days_diff']; ?>d</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><strong style="color: var(--success);">₹<?php echo number_format($m['amount']); ?></strong></td>
                                                <td style="text-align: right;">
                                                    <a href="renewal_pipeline.php?nudge_uid=<?php echo urlencode($m['userid']); ?>" class="action-btn" title="Send WhatsApp Nudge">
                                                        <i class="entypo-chat"></i>
                                                    </a>
                                                    <a href="mailto:<?php echo htmlspecialchars($m['email']); ?>?subject=Membership Expiry Notice&body=Dear <?php echo urlencode($m['username']); ?>, your subscription is expiring on <?php echo $m['expire']; ?>. Please renew at the reception desk." class="action-btn email-btn" title="Send Email Alert">
                                                        <i class="entypo-mail"></i>
                                                    </a>
                                                    <a href="make_payments.php?userID=<?php echo urlencode($m['userid']); ?>" class="action-btn pay-btn" title="Record Renewal Payment">
                                                        <i class="entypo-star"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 30px;">
                                                No members are expiring or expired in the current 15-day range.
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

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const ctx = document.getElementById('projectionChart').getContext('2d');
            const labels = <?php echo json_encode($proj_labels); ?>;
            const values = <?php echo json_encode($proj_values); ?>;

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Projected Revenue (₹)',
                        data: values,
                        backgroundColor: 'rgba(255, 107, 0, 0.1)',
                        borderColor: '#ff6b00',
                        borderWidth: 2,
                        pointBackgroundColor: '#ff6b00',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 1,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Potential Revenue: ₹' + context.raw.toLocaleString();
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: '#94a3b8',
                                font: {
                                    size: 11
                                }
                            }
                        },
                        y: {
                            grid: {
                                color: 'rgba(255, 255, 255, 0.05)'
                            },
                            ticks: {
                                color: '#94a3b8',
                                font: {
                                    size: 11
                                },
                                callback: function(value) {
                                    return '₹' + value;
                                }
                            },
                            beginAtZero: true
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>
