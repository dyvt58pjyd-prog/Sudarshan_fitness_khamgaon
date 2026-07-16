<?php
require '../../include/db_conn.php';
page_protect();

if ($_SESSION['role'] !== 'member') {
    echo "<head><script>alert('Access Denied');</script></head></html>";
    echo "<meta http-equiv='refresh' content='0; url=/index.php'>";
    exit();
}

$gym = get_gym_details($con);
$userid = $_SESSION['user_data'];

// Fetch the assigned timetable ID for the user
$sql = "SELECT tid FROM users WHERE userid = '$userid'";
$result = mysqli_query($con, $sql);
$user_row = mysqli_fetch_assoc($result);
$tid = isset($user_row['tid']) ? $user_row['tid'] : null;

$routine_found = false;
$rname = "";
$day1 = ""; $day2 = ""; $day3 = ""; $day4 = ""; $day5 = ""; $day6 = "";

if ($tid) {
    $r_sql = "SELECT * FROM timetable WHERE tid = $tid";
    $r_result = mysqli_query($con, $r_sql);
    if ($r_result && mysqli_num_rows($r_result) > 0) {
        $routine_row = mysqli_fetch_assoc($r_result);
        $routine_found = true;
        $rname = $routine_row['tname'];
        $day1 = $routine_row['day1'];
        $day2 = $routine_row['day2'];
        $day3 = $routine_row['day3'];
        $day4 = $routine_row['day4'];
        $day5 = $routine_row['day5'];
        $day6 = $routine_row['day6'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo htmlspecialchars($gym['gym_name']); ?> | My Workout Routine</title>
    <link rel="stylesheet" href="../../css/style.css" id="style-resource-5">
    <script type="text/javascript" src="../../js/Script.js"></script>
    <link rel="stylesheet" href="../../css/dashMain.css">
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
    <link rel="stylesheet" href="../../css/premium.css">
    <link href="a1style.css" rel="stylesheet" type="text/css">
    <style>
        .page-container .sidebar-menu #main-menu li#routine > a {
            background-color: rgba(59, 130, 246, 0.1) !important;
            color: var(--accent-primary) !important;
            font-weight: 600 !important;
            box-shadow: inset 3px 0 0 var(--accent-primary);
        }
        .routine-card {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 30px;
            box-shadow: var(--glass-shadow);
            max-width: 900px;
            margin: 0 auto;
        }
        .day-box {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        .day-box:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: var(--accent-primary);
            transform: translateX(5px);
        }
        .day-title {
            color: var(--accent-primary);
            font-weight: 600;
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        .day-routine {
            color: var(--text-main);
            font-size: 15px;
            line-height: 1.5;
        }
        .no-routine {
            text-align: center;
            padding: 40px 20px;
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
                            <a href="../admin/logout.php">
                                Log Out <i class="entypo-logout right"></i>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <h2>My Workout Routine</h2>
            <hr />

            <div class="routine-card">
                <?php if ($routine_found): ?>
                    <h3 style="margin-top: 0; color: var(--text-main); font-weight: 600;">Assigned Routine: <?php echo htmlspecialchars($rname); ?></h3>
                    <hr style="margin-top: 10px; margin-bottom: 25px; border-color: rgba(255,255,255,0.05);" />

                    <div class="day-box">
                        <div class="day-title">Monday (Day 1)</div>
                        <div class="day-routine"><?php echo !empty($day1) ? nl2br(htmlspecialchars($day1)) : 'Rest / Cardio'; ?></div>
                    </div>

                    <div class="day-box">
                        <div class="day-title">Tuesday (Day 2)</div>
                        <div class="day-routine"><?php echo !empty($day2) ? nl2br(htmlspecialchars($day2)) : 'Rest / Cardio'; ?></div>
                    </div>

                    <div class="day-box">
                        <div class="day-title">Wednesday (Day 3)</div>
                        <div class="day-routine"><?php echo !empty($day3) ? nl2br(htmlspecialchars($day3)) : 'Rest / Cardio'; ?></div>
                    </div>

                    <div class="day-box">
                        <div class="day-title">Thursday (Day 4)</div>
                        <div class="day-routine"><?php echo !empty($day4) ? nl2br(htmlspecialchars($day4)) : 'Rest / Cardio'; ?></div>
                    </div>

                    <div class="day-box">
                        <div class="day-title">Friday (Day 5)</div>
                        <div class="day-routine"><?php echo !empty($day5) ? nl2br(htmlspecialchars($day5)) : 'Rest / Cardio'; ?></div>
                    </div>

                    <div class="day-box">
                        <div class="day-title">Saturday (Day 6)</div>
                        <div class="day-routine"><?php echo !empty($day6) ? nl2br(htmlspecialchars($day6)) : 'Rest / Cardio'; ?></div>
                    </div>

                    <div class="day-box" style="border-color: transparent; background: transparent; padding-left: 0;">
                        <div class="day-title" style="color: var(--text-muted);">Sunday (Day 7)</div>
                        <div class="day-routine" style="color: var(--text-muted); font-style: italic;">Rest & Recovery Day</div>
                    </div>

                <?php else: ?>
                    <div class="no-routine">
                        <i class="entypo-alert" style="font-size: 64px; color: var(--warning); display: block; margin-bottom: 20px;"></i>
                        <h4>No Workout Routine Assigned</h4>
                        <p style="color: var(--text-muted); margin-top: 10px;">Please contact your trainer or super admin to assign a workout timetable to your membership account.</p>
                    </div>
                <?php endif; ?>
            </div>

            <?php include('../admin/footer.php'); ?>
        </div>
    </div>
</body>
</html>
