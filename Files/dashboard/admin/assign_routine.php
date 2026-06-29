<?php
require '../../include/db_conn.php';
page_protect();

if ($_SESSION['role'] !== 'super_admin' && $_SESSION['role'] !== 'owner' && $_SESSION['role'] !== 'trainer') {
    die("Access Denied");
}

$gym = get_gym_details($con);
$trainer_id = $_SESSION['user_data'];
$is_trainer = ($_SESSION['role'] === 'trainer');

// Fetch members assigned to this trainer (or all if admin)
if ($is_trainer) {
    $q = "SELECT userid, username FROM users WHERE trainer_id = '$trainer_id' ORDER BY username ASC";
} else {
    $q = "SELECT userid, username FROM users ORDER BY username ASC";
}
$members = mysqli_query($con, $q);

$selected_uid = isset($_GET['uid']) ? $_GET['uid'] : '';
$current_workout = "";
$current_diet = "";

if ($selected_uid) {
    $rq = mysqli_query($con, "SELECT workout_plan, diet_plan FROM member_routines WHERE uid = '$selected_uid'");
    if ($rq && mysqli_num_rows($rq) > 0) {
        $row = mysqli_fetch_assoc($rq);
        $current_workout = $row['workout_plan'];
        $current_diet = $row['diet_plan'];
    }
}

if (isset($_POST['save_routine'])) {
    $uid = mysqli_real_escape_string($con, $_POST['uid']);
    $workout = mysqli_real_escape_string($con, $_POST['workout_plan']);
    $diet = mysqli_real_escape_string($con, $_POST['diet_plan']);
    
    // Check if exists
    $chk = mysqli_query($con, "SELECT id FROM member_routines WHERE uid = '$uid'");
    if ($chk && mysqli_num_rows($chk) > 0) {
        mysqli_query($con, "UPDATE member_routines SET workout_plan = '$workout', diet_plan = '$diet', trainer_id = '$trainer_id' WHERE uid = '$uid'");
    } else {
        mysqli_query($con, "INSERT INTO member_routines (uid, trainer_id, workout_plan, diet_plan) VALUES ('$uid', '$trainer_id', '$workout', '$diet')");
    }
    
    echo "<script>alert('Routine saved successfully!'); window.location.href='assign_routine.php?uid=$uid';</script>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo htmlspecialchars($gym['gym_name']); ?> | Assign Routines</title>
    <link rel="stylesheet" href="../../css/style.css">
    <script src="../../js/Script.js"></script>
    <link rel="stylesheet" href="../../css/dashMain.css">
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
    <link rel="stylesheet" href="../../css/premium.css">
    <style>
        .page-container .sidebar-menu #main-menu li#assign_routine > a {
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
        .form-control-premium {
            background: rgba(15, 23, 42, 0.6) !important;
            border: 1px solid var(--glass-border) !important;
            border-radius: 10px !important;
            color: var(--text-main) !important;
            padding: 12px !important;
            width: 100%;
            margin-bottom: 20px;
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
            <h2>Assign Diet & Workout Routines</h2>
            <hr />

            <div class="routine-card">
                <form method="GET" action="">
                    <label>Select Member</label>
                    <select name="uid" class="form-control-premium" onchange="this.form.submit()" required>
                        <option value="">-- Choose Member --</option>
                        <?php while ($m = mysqli_fetch_assoc($members)): ?>
                            <option value="<?php echo $m['userid']; ?>" <?php if($selected_uid == $m['userid']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($m['username']) . ' (ID: ' . $m['userid'] . ')'; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </form>

                <?php if ($selected_uid): ?>
                <form method="POST" action="">
                    <input type="hidden" name="uid" value="<?php echo htmlspecialchars($selected_uid); ?>">
                    
                    <label style="color: var(--accent-primary); font-weight: bold;"><i class="entypo-list"></i> Weekly Workout Plan</label>
                    <textarea name="workout_plan" class="form-control-premium" rows="10" placeholder="e.g. Monday: Chest & Triceps..."><?php echo htmlspecialchars($current_workout); ?></textarea>
                    
                    <label style="color: var(--success); font-weight: bold;"><i class="entypo-leaf"></i> Weekly Diet Plan</label>
                    <textarea name="diet_plan" class="form-control-premium" rows="10" placeholder="e.g. Breakfast: 4 Egg Whites..."><?php echo htmlspecialchars($current_diet); ?></textarea>
                    
                    <div style="text-align: right;">
                        <button type="submit" name="save_routine" class="btn btn-primary"><i class="entypo-floppy"></i> Save Routine</button>
                    </div>
                </form>
                <?php endif; ?>
            </div>

            <?php include('footer.php'); ?>
        </div>
    </div>
</body>
</html>
