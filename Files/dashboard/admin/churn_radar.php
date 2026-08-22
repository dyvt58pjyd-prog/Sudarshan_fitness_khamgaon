<?php
require '../../include/db_conn.php';
page_protect();
$gym = get_gym_details($con);

// Handle manual re-engagement WhatsApp queueing
$msg = '';
if (isset($_POST['send_retention_wa'])) {
    $target_uid = mysqli_real_escape_string($con, $_POST['target_uid']);
    $target_mobile = mysqli_real_escape_string($con, $_POST['target_mobile']);
    $target_name = mysqli_real_escape_string($con, $_POST['target_name']);
    $days_absent = intval($_POST['days_absent']);

    $wa_text = "Hey {$target_name}! 💪 We noticed you haven't visited Sudarshan Fitness in {$days_absent} days. Don't break your fitness momentum! Your gym family is waiting for you. See you on the gym floor today! 🏋️🔥 - Team " . $gym['gym_name'];

    // Queue in whatsapp_outbox
    @mysqli_query($con, "INSERT INTO whatsapp_outbox (receiver_mobile, receiver_name, message, template_id, status) 
                         VALUES ('$target_mobile', '$target_name', '$wa_text', 'MEMBER_CHURN_REMINDER', 'pending')");
    $msg = "✅ Motivational re-engagement message queued for <strong>" . htmlspecialchars($target_name) . "</strong>!";
}

// ── Calculate AI Churn Risk for Active Members ────────────────────────────────
$today = date('Y-m-d');
$query_members = "SELECT u.userid, u.username, u.mobile, u.gender, e.expire, p.planName,
                  (SELECT MAX(a.date) FROM attendance a WHERE a.uid = u.userid) as last_attendance,
                  (SELECT COUNT(*) FROM attendance a WHERE a.uid = u.userid AND a.date >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)) as monthly_visits
                  FROM users u
                  INNER JOIN enrolls_to e ON u.userid = e.uid
                  INNER JOIN plan p ON e.pid = p.pid
                  WHERE e.renewal = 'yes' AND e.expire >= CURRENT_DATE()
                  ORDER BY last_attendance ASC, u.username ASC";

$res_m = mysqli_query($con, $query_members);

$high_risk = [];
$moderate_risk = [];
$consistent = [];

if ($res_m && mysqli_num_rows($res_m) > 0) {
    while ($row = mysqli_fetch_assoc($res_m)) {
        $last_att = $row['last_attendance'];
        if (empty($last_att)) {
            $days_absent = 30; // Never checked in
        } else {
            $diff = abs(strtotime($today) - strtotime($last_att));
            $days_absent = intval(floor($diff / (60 * 60 * 24)));
        }
        $row['days_absent'] = $days_absent;

        if ($days_absent >= 7) {
            $row['risk_level'] = 'HIGH';
            $high_risk[] = $row;
        } elseif ($days_absent >= 4) {
            $row['risk_level'] = 'MODERATE';
            $moderate_risk[] = $row;
        } else {
            $row['risk_level'] = 'LOW';
            $consistent[] = $row;
        }
    }
}

$total_active = count($high_risk) + count($moderate_risk) + count($consistent);
$retention_rate = ($total_active > 0) ? round((count($consistent) / $total_active) * 100, 1) : 100;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo htmlspecialchars($gym['gym_name']); ?> | AI Member Churn &amp; Retention Radar</title>
    <link rel="stylesheet" href="../../css/style.css" id="style-resource-5">
    <script type="text/javascript" src="../../js/Script.js"></script>
    <link rel="stylesheet" href="../../css/dashMain.css">
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
    <link href="a1style.css" rel="stylesheet" type="text/css">
    <style>
        .risk-badge { padding: 4px 10px; border-radius: 8px; font-weight: 800; font-size: 11px; text-transform: uppercase; }
        .risk-high { background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid #ef4444; }
        .risk-mod { background: rgba(245, 158, 11, 0.15); color: #f59e0b; border: 1px solid #f59e0b; }
        .risk-low { background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid #10b981; }
    </style>
</head>
<body class="page-body page-fade" onload="collapseSidebar();">

    <div class="page-container sidebar-collapsed" id="navbarcollapse">	
        <div class="sidebar-menu">
            <header class="logo-env">
                <div class="logo">
                    <a href="main.php">
                        <img src="../../images/logo.png" alt="Gym Logo" style="max-height: 80px; max-width: 192px;" />
                    </a>
                </div>
                <div class="sidebar-collapse" onclick="collapseSidebar()">
                    <a href="#" class="sidebar-collapse-icon with-animation"><i class="entypo-menu"></i></a>
                </div>
            </header>
            <?php include('nav.php'); ?>
        </div>

        <div class="main-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
                <div>
                    <h2 style="margin: 0; font-weight: 800; text-transform: uppercase; color: #fff;">🧠 AI Member Churn Prediction &amp; Retention Radar</h2>
                    <p style="color: #94a3b8; font-size: 13px; margin-top: 4px;">Predictive attendance analysis detecting members at risk of quitting, with automated 1-click re-engagement.</p>
                </div>
                <div>
                    <a href="index.php" class="a1-btn a1-blue" style="font-size: 12px; font-weight: bold; border-radius: 8px;">← Back to Dashboard</a>
                </div>
            </div>

            <?php if (!empty($msg)): ?>
                <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid #10b981; color: #10b981; padding: 12px 18px; border-radius: 12px; margin-bottom: 20px; font-weight: bold;">
                    <?php echo $msg; ?>
                </div>
            <?php endif; ?>

            <!-- Metrics Grid -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; margin-bottom: 25px;">
                <div style="background: rgba(239, 68, 68, 0.12); border: 2px solid #ef4444; border-radius: 16px; padding: 18px; text-align: center;">
                    <div style="font-size: 11px; font-weight: 800; color: #ef4444; text-transform: uppercase;">🔴 High Dropout Risk (Absent 7+ Days)</div>
                    <div style="font-size: 32px; font-weight: 900; color: #ef4444; margin-top: 4px; font-family: 'Orbitron', sans-serif;"><?php echo count($high_risk); ?></div>
                    <div style="font-size: 11px; color: #94a3b8; margin-top: 2px;">Need Immediate Follow-Up</div>
                </div>

                <div style="background: rgba(245, 158, 11, 0.12); border: 2px solid #f59e0b; border-radius: 16px; padding: 18px; text-align: center;">
                    <div style="font-size: 11px; font-weight: 800; color: #f59e0b; text-transform: uppercase;">🟡 Moderate Risk (Absent 4-6 Days)</div>
                    <div style="font-size: 32px; font-weight: 900; color: #f59e0b; margin-top: 4px; font-family: 'Orbitron', sans-serif;"><?php echo count($moderate_risk); ?></div>
                    <div style="font-size: 11px; color: #94a3b8; margin-top: 2px;">Dropping Attendance</div>
                </div>

                <div style="background: rgba(16, 185, 129, 0.12); border: 2px solid #10b981; border-radius: 16px; padding: 18px; text-align: center;">
                    <div style="font-size: 11px; font-weight: 800; color: #10b981; text-transform: uppercase;">🟢 Active &amp; Consistent Athletes</div>
                    <div style="font-size: 32px; font-weight: 900; color: #10b981; margin-top: 4px; font-family: 'Orbitron', sans-serif;"><?php echo count($consistent); ?></div>
                    <div style="font-size: 11px; color: #94a3b8; margin-top: 2px;">Working Out Regularly</div>
                </div>

                <div style="background: rgba(56, 189, 248, 0.12); border: 2px solid #38bdf8; border-radius: 16px; padding: 18px; text-align: center;">
                    <div style="font-size: 11px; font-weight: 800; color: #38bdf8; text-transform: uppercase;">📊 Gym Retention Health Score</div>
                    <div style="font-size: 32px; font-weight: 900; color: #38bdf8; margin-top: 4px; font-family: 'Orbitron', sans-serif;"><?php echo $retention_rate; ?>%</div>
                    <div style="font-size: 11px; color: #94a3b8; margin-top: 2px;">Healthy Active Ratio</div>
                </div>
            </div>

            <!-- At-Risk Action List -->
            <div style="background: rgba(15, 23, 42, 0.85); border: 1px solid rgba(255,255,255,0.1); border-radius: 18px; padding: 22px; margin-bottom: 30px;">
                <h3 style="margin-top: 0; color: #fff; font-size: 16px; font-weight: 800; text-transform: uppercase; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 12px;">
                    🎯 At-Risk Member Re-engagement Table
                </h3>

                <?php
                $at_risk_list = array_merge($high_risk, $moderate_risk);
                if (count($at_risk_list) > 0):
                ?>
                <table class="table table-bordered table-striped" style="font-size: 13.5px; width: 100%;">
                    <thead>
                        <tr style="background: rgba(255,255,255,0.05); color: #94a3b8; text-transform: uppercase; font-size: 11px;">
                            <th>Member ID</th>
                            <th>Name</th>
                            <th>Mobile</th>
                            <th>Active Plan</th>
                            <th>Last Check-In</th>
                            <th>Days Absent</th>
                            <th>Risk Status</th>
                            <th style="text-align: center;">1-Click Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($at_risk_list as $mem): ?>
                        <tr>
                            <td><strong style="color: #38bdf8;">#<?php echo htmlspecialchars($mem['userid']); ?></strong></td>
                            <td><strong><?php echo htmlspecialchars($mem['username']); ?></strong></td>
                            <td><?php echo htmlspecialchars($mem['mobile']); ?></td>
                            <td><?php echo htmlspecialchars($mem['planName']); ?></td>
                            <td><?php echo !empty($mem['last_attendance']) ? date('d M Y', strtotime($mem['last_attendance'])) : '<span style="color:#ef4444;">Never Attended</span>'; ?></td>
                            <td><strong style="color: <?php echo $mem['days_absent'] >= 7 ? '#ef4444' : '#f59e0b'; ?>;"><?php echo $mem['days_absent']; ?> Days</strong></td>
                            <td>
                                <span class="risk-badge <?php echo $mem['risk_level'] === 'HIGH' ? 'risk-high' : 'risk-mod'; ?>">
                                    <?php echo $mem['risk_level']; ?> RISK
                                </span>
                            </td>
                            <td style="text-align: center;">
                                <form method="POST" style="display: inline; margin: 0;">
                                    <input type="hidden" name="target_uid" value="<?php echo htmlspecialchars($mem['userid']); ?>">
                                    <input type="hidden" name="target_mobile" value="<?php echo htmlspecialchars($mem['mobile']); ?>">
                                    <input type="hidden" name="target_name" value="<?php echo htmlspecialchars($mem['username']); ?>">
                                    <input type="hidden" name="days_absent" value="<?php echo $mem['days_absent']; ?>">
                                    <button type="submit" name="send_retention_wa" class="a1-btn a1-green" style="font-size: 11px; font-weight: bold; border-radius: 8px; padding: 5px 12px; cursor: pointer;">
                                        💬 Send WhatsApp Check-in
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <div style="text-align: center; color: #10b981; padding: 30px; font-weight: bold;">
                        🎉 Outstanding! No members are currently at risk of dropping out. Everyone is working out consistently.
                    </div>
                <?php endif; ?>
            </div>

            <?php include('footer.php'); ?>
        </div>
    </div>
</body>
</html>
