<?php
require '../../include/db_conn.php';
page_protect();

if (!isset($_GET['uid']) || !isset($_GET['trainer_id'])) {
    echo "<script>alert('Invalid parameters.'); window.location='view_pt_clients.php';</script>";
    exit;
}

$uid = mysqli_real_escape_string($con, $_GET['uid']);
$trainer_id = mysqli_real_escape_string($con, $_GET['trainer_id']);

// Get member details
$q_mem = mysqli_query($con, "SELECT username FROM users WHERE userid='$uid'");
$member_name = mysqli_num_rows($q_mem) > 0 ? mysqli_fetch_assoc($q_mem)['username'] : 'Unknown';

// Get trainer details
$q_tr = mysqli_query($con, "SELECT Full_name FROM admin WHERE username='$trainer_id'");
$trainer_name = mysqli_num_rows($q_tr) > 0 ? mysqli_fetch_assoc($q_tr)['Full_name'] : 'Unknown';

if (isset($_POST['submit'])) {
    $session_date = mysqli_real_escape_string($con, $_POST['session_date']);
    
    // Check if a session is already logged for this date
    $check = mysqli_query($con, "SELECT id FROM pt_attendance WHERE member_id='$uid' AND session_date='$session_date'");
    if (mysqli_num_rows($check) > 0) {
        echo "<script>alert('A session is already logged for this date!'); window.location='view_pt_clients.php';</script>";
    } else {
        $insert = "INSERT INTO pt_attendance (member_id, trainer_id, session_date) VALUES ('$uid', '$trainer_id', '$session_date')";
        if (mysqli_query($con, $insert)) {
            echo "<script>alert('PT Session logged successfully!'); window.location='view_pt_clients.php';</script>";
        } else {
            echo "<div class='alert alert-danger'>Error: " . mysqli_error($con) . "</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>SUDARSHAN FITNESS | Log PT Session</title>
    <link rel="stylesheet" href="../../css/style.css" id="style-resource-5">
    <script type="text/javascript" src="../../js/Script.js"></script>
    <link rel="stylesheet" href="../../css/dashMain.css">
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
    <link href="a1style.css" rel="stylesheet" type="text/css">
    <style>
        .form-card {
            background: #2b303a;
            border-radius: 12px;
            padding: 30px;
            max-width: 600px;
            margin: 0 auto;
            border: 1px solid rgba(255,107,0,0.2);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .form-group label {
            color: #fff;
            font-weight: 500;
        }
        .form-control {
            background: #1e2229;
            border: 1px solid #444;
            color: #fff;
        }
        .form-control[readonly] {
            background: #1a1d24;
            color: #888;
        }
    </style>
</head>
<body class="page-body page-fade" onload="collapseSidebar()">
    <div class="page-container sidebar-collapsed" id="navbarcollapse">	
        <div class="sidebar-menu">
            <header class="logo-env">
                <div class="logo">
                    <a href="main.php">
                        <?php $sidebar_logo = $gym_settings_data["gym_logo"] ?? "../../images/logo.png"; ?>
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
                <div class="col-md-6 col-sm-8 clearfix"></div>
                <div class="col-md-6 col-sm-4 clearfix hidden-xs">
                    <ul class="list-inline links-list pull-right">
                        <li>Welcome <?php echo $_SESSION['full_name']; ?></li>                            
                        <li>
                            <a href="logout.php">
                                Log Out <i class="entypo-logout right"></i>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3>Log PT Session</h3>
                <a href="view_pt_clients.php" class="a1-btn" style="background: rgba(255,255,255,0.08) !important; color: #fff !important; text-decoration: none;">&larr; Back</a>
            </div>
            <hr/>

            <div class="form-card">
                <form action="" method="POST">
                    <div class="form-group">
                        <label>Member Name</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($member_name); ?>" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label>Assigned Trainer</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($trainer_name); ?>" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label>Session Date</label>
                        <input type="date" name="session_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div style="margin-top: 25px; text-align: center;">
                        <button type="submit" name="submit" class="a1-btn a1-blue" style="width: 100%; padding: 12px; font-size: 16px;">Record Session</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
