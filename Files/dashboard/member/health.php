<?php
require '../../include/db_conn.php';
page_protect();
header("Location: index.php");
exit();

if ($_SESSION['role'] !== 'member') {
    echo "<head><script>alert('Access Denied');</script></head></html>";
    echo "<meta http-equiv='refresh' content='0; url=/index.php'>";
    exit();
}

$gym = get_gym_details($con);
$userid = $_SESSION['user_data'];

// Fetch the member health status details
$sql = "SELECT * FROM health_status WHERE uid = '$userid'";
$result = mysqli_query($con, $sql);
$health = mysqli_fetch_assoc($result);

$calorie = !empty($health['calorie']) ? htmlspecialchars($health['calorie']) : 'N/A';
$height = !empty($health['height']) ? htmlspecialchars($health['height']) . " cm" : 'N/A';
$weight = !empty($health['weight']) ? htmlspecialchars($health['weight']) . " kg" : 'N/A';
$fat = !empty($health['fat']) ? htmlspecialchars($health['fat']) . " %" : 'N/A';
$remarks = !empty($health['remarks']) ? htmlspecialchars($health['remarks']) : 'No remarks recorded yet by your trainer.';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo htmlspecialchars($gym['gym_name']); ?> | My Health Status</title>
    <link rel="stylesheet" href="../../css/style.css" id="style-resource-5">
    <script type="text/javascript" src="../../js/Script.js"></script>
    <link rel="stylesheet" href="../../css/dashMain.css">
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
    <link rel="stylesheet" href="../../css/premium.css">
    <link href="a1style.css" rel="stylesheet" type="text/css">
    <style>
        .page-container .sidebar-menu #main-menu li#health > a {
            background-color: rgba(59, 130, 246, 0.1) !important;
            color: var(--accent-primary) !important;
            font-weight: 600 !important;
            box-shadow: inset 3px 0 0 var(--accent-primary);
        }
        .metric-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .metric-box {
            background: var(--glass-bg);
            backdrop-filter: blur(12px);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 20px;
            box-shadow: var(--glass-shadow);
            text-align: center;
        }
        .metric-val {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-main);
            margin: 10px 0;
        }
        .metric-lbl {
            color: var(--text-muted);
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }
        .remarks-card {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 30px;
            box-shadow: var(--glass-shadow);
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

            <h2>My Health Status</h2>
            <hr />

            <div class="metric-grid">
                <!-- Height -->
                <div class="metric-box" style="border-top: 3px solid var(--accent-primary);">
                    <div class="metric-lbl">Height</div>
                    <div class="metric-val"><?php echo $height; ?></div>
                    <div style="color: var(--text-muted); font-size: 12px;">Last Recorded</div>
                </div>

                <!-- Weight -->
                <div class="metric-box" style="border-top: 3px solid var(--info);">
                    <div class="metric-lbl">Weight</div>
                    <div class="metric-val"><?php echo $weight; ?></div>
                    <div style="color: var(--text-muted); font-size: 12px;">Last Recorded</div>
                </div>

                <!-- Body Fat -->
                <div class="metric-box" style="border-top: 3px solid var(--warning);">
                    <div class="metric-lbl">Body Fat</div>
                    <div class="metric-val"><?php echo $fat; ?></div>
                    <div style="color: var(--text-muted); font-size: 12px;">Last Recorded</div>
                </div>

                <!-- Target Calories -->
                <div class="metric-box" style="border-top: 3px solid var(--success);">
                    <div class="metric-lbl">Target Calories</div>
                    <div class="metric-val"><?php echo $calorie; ?></div>
                    <div style="color: var(--text-muted); font-size: 12px;">Daily Goal</div>
                </div>
            </div>

            <!-- Trainer Remarks -->
            <div class="remarks-card">
                <h3 style="margin-top: 0; color: var(--text-main); font-weight: 600;">Trainer Remarks / Feedbacks</h3>
                <hr style="margin-top: 10px; margin-bottom: 20px; border-color: rgba(255,255,255,0.05);" />
                <p style="font-size: 16px; line-height: 1.6; color: var(--text-main); font-style: italic;">
                    "<?php echo $remarks; ?>"
                </p>
            </div>

            <?php include('../admin/footer.php'); ?>
        </div>
    </div>
</body>
</html>
