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

// Fetch user profile and credentials
$sql = "SELECT u.*, a.streetName, a.state, a.city, a.zipcode, ad.pass_key 
        FROM users u 
        LEFT JOIN address a ON u.userid = a.id 
        LEFT JOIN admin ad ON u.userid = ad.username 
        WHERE u.userid = '$userid'";
$result = mysqli_query($con, $sql);
$user_info = mysqli_fetch_assoc($result);

if (isset($_POST['submit'])) {
    $mobile = mysqli_real_escape_string($con, $_POST['mobile']);
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $streetName = mysqli_real_escape_string($con, $_POST['streetName']);
    $state = mysqli_real_escape_string($con, $_POST['state']);
    $city = mysqli_real_escape_string($con, $_POST['city']);
    $zipcode = mysqli_real_escape_string($con, $_POST['zipcode']);
    $password = mysqli_real_escape_string($con, $_POST['password']);

    // Update users table
    $up_users = "UPDATE users SET mobile = '$mobile', email = '$email' WHERE userid = '$userid'";
    // Update address table
    $up_address = "UPDATE address SET streetName = '$streetName', state = '$state', city = '$city', zipcode = '$zipcode' WHERE id = '$userid'";
    // Update admin table password
    $up_admin = "UPDATE admin SET pass_key = '$password' WHERE username = '$userid'";

    if (mysqli_query($con, $up_users) && mysqli_query($con, $up_address) && mysqli_query($con, $up_admin)) {
        echo "<head><script>alert('Profile settings updated successfully! Please login again.');</script></head></html>";
        // Force relogin if password changed or profile updated
        echo "<meta http-equiv='refresh' content='0; url=../admin/logout.php'>";
        exit();
    } else {
        echo "<head><script>alert('Update failed, check details.');</script></head></html>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo htmlspecialchars($gym['gym_name']); ?> | My Profile</title>
    <link rel="stylesheet" href="../../css/style.css" id="style-resource-5">
    <script type="text/javascript" src="../../js/Script.js"></script>
    <link rel="stylesheet" href="../../css/dashMain.css">
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
    <link rel="stylesheet" href="../../css/premium.css">
    <link href="a1style.css" rel="stylesheet" type="text/css">
    <style>
        .page-container .sidebar-menu #main-menu li#profile > a {
            background-color: rgba(59, 130, 246, 0.1) !important;
            color: var(--accent-primary) !important;
            font-weight: 600 !important;
            box-shadow: inset 3px 0 0 var(--accent-primary);
        }
        .form-control-premium {
            background: rgba(15, 23, 42, 0.6) !important;
            border: 1px solid var(--glass-border) !important;
            border-radius: 10px !important;
            color: var(--text-main) !important;
            padding: 10px !important;
            width: 100%;
            margin-bottom: 15px;
        }
        .form-control-premium:focus {
            border-color: var(--accent-primary) !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2) !important;
        }
        .profile-card {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 30px;
            max-width: 700px;
            margin: 0 auto;
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
                        <li>Welcome <?php echo htmlspecialchars($user_info['username']); ?></li>
                        <li>
                            <a href="../admin/logout.php">
                                Log Out <i class="entypo-logout right"></i>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <h2>Edit My Profile</h2>
            <hr />

            <div class="profile-card">
                <form method="post" action="">
                    <div class="row">
                        <div class="col-md-6">
                            <label>Membership ID</label>
                            <input class="form-control-premium" type="text" value="<?php echo htmlspecialchars($user_info['userid']); ?>" readonly style="background: rgba(0,0,0,0.2) !important;">
                        </div>
                        <div class="col-md-6">
                            <label>Full Name</label>
                            <input class="form-control-premium" type="text" value="<?php echo htmlspecialchars($user_info['username']); ?>" readonly style="background: rgba(0,0,0,0.2) !important;">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <label>Date of Birth</label>
                            <input class="form-control-premium" type="text" value="<?php echo htmlspecialchars($user_info['dob']); ?>" readonly style="background: rgba(0,0,0,0.2) !important;">
                        </div>
                        <div class="col-md-6">
                            <label>Joining Date</label>
                            <input class="form-control-premium" type="text" value="<?php echo htmlspecialchars($user_info['joining_date']); ?>" readonly style="background: rgba(0,0,0,0.2) !important;">
                        </div>
                    </div>

                    <label>Mobile Contact</label>
                    <input class="form-control-premium" type="number" name="mobile" value="<?php echo htmlspecialchars($user_info['mobile']); ?>" required>

                    <label>Email Address</label>
                    <input class="form-control-premium" type="email" name="email" value="<?php echo htmlspecialchars($user_info['email']); ?>" required>

                    <label>Street Name</label>
                    <input class="form-control-premium" type="text" name="streetName" value="<?php echo htmlspecialchars($user_info['streetName']); ?>" required>

                    <div class="row">
                        <div class="col-md-4">
                            <label>City</label>
                            <input class="form-control-premium" type="text" name="city" value="<?php echo htmlspecialchars($user_info['city']); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label>State</label>
                            <input class="form-control-premium" type="text" name="state" value="<?php echo htmlspecialchars($user_info['state']); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label>Zipcode</label>
                            <input class="form-control-premium" type="text" name="zipcode" value="<?php echo htmlspecialchars($user_info['zipcode']); ?>" required>
                        </div>
                    </div>

                    <label>My Login Password</label>
                    <input class="form-control-premium" type="password" name="password" value="<?php echo htmlspecialchars($user_info['pass_key']); ?>" required>

                    <div style="text-align: right; margin-top: 20px;">
                        <input class="btn btn-primary" type="submit" name="submit" value="Save Profile Details" style="width: auto !important; display: inline-block;">
                    </div>
                </form>
            </div>


</body>
</html>
