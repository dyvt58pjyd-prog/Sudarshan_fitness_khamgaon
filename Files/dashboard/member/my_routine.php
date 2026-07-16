<?php
require '../../include/db_conn.php';
page_protect();

if ($_SESSION['role'] !== 'member') {
    die("Access Denied");
}

$gym = get_gym_details($con);
$uid = $_SESSION['user_data'];

$workout = "No workout plan assigned yet.";
$diet = "No diet plan assigned yet.";
$trainer_name = "Not Assigned";
$last_updated = "";

$rq = mysqli_query($con, "SELECT r.*, a.Full_name as trainer_name FROM member_routines r LEFT JOIN admin a ON r.trainer_id = a.username WHERE r.uid = '$uid'");
if ($rq && mysqli_num_rows($rq) > 0) {
    $row = mysqli_fetch_assoc($rq);
    if (!empty($row['workout_plan'])) $workout = $row['workout_plan'];
    if (!empty($row['diet_plan'])) $diet = $row['diet_plan'];
    if (!empty($row['trainer_name'])) $trainer_name = $row['trainer_name'];
    $last_updated = $row['updated_at'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo htmlspecialchars($gym['gym_name']); ?> | My Routine</title>
    <link rel="stylesheet" href="../../css/style.css">
    <script src="../../js/Script.js"></script>
    <link rel="stylesheet" href="../../css/dashMain.css">
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
    <link rel="stylesheet" href="../../css/premium.css">
    <style>
        .page-container .sidebar-menu #main-menu li#my_routine > a {
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
            margin-bottom: 20px;
        }
        .routine-text {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            padding: 20px;
            color: var(--text-main);
            white-space: pre-wrap;
            font-size: 15px;
            line-height: 1.6;
        }
    </style>
</head>
<body class="page-body page-fade" onload="collapseSidebar()">
    <div class="page-container sidebar-collapsed" id="navbarcollapse">
        <div class="sidebar-menu">
            <header class="logo-env">
                <div class="logo">
                    <a href="index.php">
                        <img src="<?php echo htmlspecialchars($gym['gym_logo']); ?>" alt="" style="max-height: 60px;" />
                    </a>
                </div>
            </header>
            <?php include('nav.php'); ?>
        </div>

        <div class="main-content">
            <h2>My Workout & Diet Routine</h2>
            <hr />

            <div class="routine-card">
                <h4 style="color: var(--text-muted); margin-bottom: 20px;">
                    Assigned Trainer: <strong style="color: #fff;"><?php echo htmlspecialchars($trainer_name); ?></strong>
                    <?php if($last_updated): ?>
                        <br><small style="font-size: 12px;">Last Updated: <?php echo date('M d, Y h:i A', strtotime($last_updated)); ?></small>
                    <?php endif; ?>
                </h4>

                <label style="color: var(--accent-primary); font-weight: bold; font-size: 18px;"><i class="entypo-list"></i> Weekly Workout Plan</label>
                <div class="routine-text" style="margin-bottom: 30px;">
                    <?php echo htmlspecialchars($workout); ?>
                </div>
                
                <label style="color: var(--success); font-weight: bold; font-size: 18px;"><i class="entypo-leaf"></i> Weekly Diet Plan</label>
                <div class="routine-text">
                    <?php echo htmlspecialchars($diet); ?>
                </div>
            </div>

            <?php include('../admin/footer.php'); ?>
        </div>
    </div>
</body>
</html>
