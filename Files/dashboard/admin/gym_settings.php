<?php
require '../../include/db_conn.php';
page_protect();

if ($_SESSION['role'] !== 'super_admin' && $_SESSION['role'] !== 'owner') {
    echo "<head><script>alert('Access Denied');</script></head></html>";
    echo "<meta http-equiv='refresh' content='0; url=index.php'>";
    exit();
}

$gym = get_gym_details($con);

if (isset($_POST['submit'])) {
    $gym_name = mysqli_real_escape_string($con, $_POST['gym_name']);
    $gym_address = mysqli_real_escape_string($con, $_POST['gym_address']);
    $gym_contact = mysqli_real_escape_string($con, $_POST['gym_contact']);
    $gym_email = mysqli_real_escape_string($con, $_POST['gym_email']);
    $logo_path = $gym['gym_logo']; // keep existing as default
    $qr_path = $gym['payment_qr']; // keep existing as default

    // Handle Logo File Upload
    if (isset($_FILES['gym_logo']) && $_FILES['gym_logo']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['gym_logo']['tmp_name'];
        $file_name = $_FILES['gym_logo']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_exts = array('jpg', 'jpeg', 'png', 'gif', 'svg');

        if (in_array($file_ext, $allowed_exts)) {
            // Target the root directory
            $target_dir = "../../../Sudarshan Data Folder/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $new_file_name = "gym_logo_" . time() . "." . $file_ext;
            $target_file = $target_dir . $new_file_name;

            if (move_uploaded_file($file_tmp, $target_file)) {
                $logo_path = "/Sudarshan Data Folder/" . $new_file_name;
            } else {
                echo "<script>alert('Failed to upload logo.');</script>";
            }
        } else {
            echo "<script>alert('Invalid file format. Only JPG, JPEG, PNG, GIF, SVG are allowed.');</script>";
        }
    }

    // Handle Payment QR Code File Upload
    if (isset($_FILES['payment_qr']) && $_FILES['payment_qr']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['payment_qr']['tmp_name'];
        $file_name = $_FILES['payment_qr']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_exts = array('jpg', 'jpeg', 'png', 'gif', 'svg');

        if (in_array($file_ext, $allowed_exts)) {
            // Target the root directory
            $target_dir = "../../../Sudarshan Data Folder/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $new_file_name = "payment_qr_" . time() . "." . $file_ext;
            $target_file = $target_dir . $new_file_name;

            if (move_uploaded_file($file_tmp, $target_file)) {
                $qr_path = "/Sudarshan Data Folder/" . $new_file_name;
            } else {
                echo "<script>alert('Failed to upload QR Code.');</script>";
            }
        } else {
            echo "<script>alert('Invalid file format. Only JPG, JPEG, PNG, GIF, SVG are allowed.');</script>";
        }
    }

    $upi_id = mysqli_real_escape_string($con, $_POST['upi_id']);
    $women_batch_enabled = isset($_POST['women_batch_enabled']) ? 1 : 0;
    $women_batch_start = mysqli_real_escape_string($con, $_POST['women_batch_start']);
    $women_batch_end = mysqli_real_escape_string($con, $_POST['women_batch_end']);

    $update_query = "UPDATE gym_details SET 
        gym_name = '$gym_name', 
        gym_address = '$gym_address', 
        gym_contact = '$gym_contact', 
        gym_email = '$gym_email', 
        gym_logo = '$logo_path',
        payment_qr = '$qr_path',
        upi_id = '$upi_id',
        women_batch_enabled = '$women_batch_enabled',
        women_batch_start = '$women_batch_start',
        women_batch_end = '$women_batch_end'
        WHERE id = 1";

    if (mysqli_query($con, $update_query)) {
        echo "<head><script>alert('Branding settings updated successfully!');</script></head></html>";
        echo "<meta http-equiv='refresh' content='0; url=gym_settings.php'>";
        exit();
    } else {
        echo "<head><script>alert('Update failed, check details.');</script></head></html>";
        echo "Error: " . mysqli_error($con);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo htmlspecialchars($gym['gym_name']); ?> | Gym Settings</title>
    <link rel="stylesheet" href="../../css/style.css" id="style-resource-5">
    <script type="text/javascript" src="../../js/Script.js"></script>
    <link rel="stylesheet" href="../../css/dashMain.css">
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
    <link rel="stylesheet" href="../../css/premium.css">
    <link href="a1style.css" rel="stylesheet" type="text/css">
    <style>
        .page-container .sidebar-menu #main-menu li#gymsettings > a {
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
        .settings-card {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 30px;
            max-width: 700px;
            margin: 0 auto;
            box-shadow: var(--glass-shadow);
        }
        .logo-preview {
            max-height: 100px;
            border-radius: 8px;
            border: 1px dashed var(--glass-border);
            padding: 10px;
            background: rgba(0,0,0,0.2);
            margin-bottom: 15px;
            display: inline-block;
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

            <h2>Gym / Institution Settings</h2>
            <hr />

            <div class="settings-card">
                <form id="form1" name="form1" method="post" action="" enctype="multipart/form-data">
                    <div class="row">
                        <!-- Logo is locked -->
                        <div class="col-md-6" style="margin-bottom: 20px;">
                            <div style="background: rgba(15, 23, 42, 0.4); padding: 15px; border-radius: 12px; border: 1px solid var(--glass-border); text-align: center;">
                                <img class="logo-preview" src="../../images/logo.jpg" alt="Gym Logo" style="max-height: 80px;">
                                <div style="margin-top: 10px; color: var(--success); font-size: 11px; font-weight: bold;">
                                    <i class="entypo-lock"></i> LOGO PERMANENTLY LOCKED
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6" style="text-align: center;">
                            <label>Current Payment QR Code</label>
                            <div>
                                <?php if (!empty($gym['payment_qr'])): ?>
                                    <img class="logo-preview" src="<?php echo htmlspecialchars($gym['payment_qr']); ?>" alt="Payment QR Code" style="max-height: 80px;">
                                <?php else: ?>
                                    <div style="color: var(--text-muted); font-size: 11px; height: 80px; line-height: 80px; text-align: center; border: 1px dashed var(--glass-border); border-radius: 8px;">No QR uploaded</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <label>Gym Name</label>
                    <input class="form-control-premium" type="text" name="gym_name" value="<?php echo htmlspecialchars($gym['gym_name']); ?>" required>

                    <label>Gym UPI ID (For Free Instant Payments)</label>
                    <input class="form-control-premium" type="text" name="upi_id" value="<?php echo isset($gym['upi_id']) ? htmlspecialchars($gym['upi_id']) : ''; ?>" placeholder="e.g. merchant@upi">

                    <label>Gym Email</label>
                    <input class="form-control-premium" type="email" name="gym_email" value="<?php echo htmlspecialchars($gym['gym_email']); ?>" required>

                    <label>Gym Contact Phone</label>
                    <input class="form-control-premium" type="text" name="gym_contact" value="<?php echo htmlspecialchars($gym['gym_contact']); ?>" required>

                    <label>Gym Address</label>
                    <textarea class="form-control-premium" name="gym_address" rows="3" required><?php echo htmlspecialchars($gym['gym_address']); ?></textarea>

                    <div style="background: rgba(255, 107, 0, 0.05); padding: 20px; border-radius: 12px; border: 1px solid rgba(255, 107, 0, 0.2); margin-top: 15px; margin-bottom: 25px;">
                        <h4 style="margin-top: 0; color: var(--accent-primary); font-weight: 700;">
                            <i class="entypo-attention"></i> Women-Only Batch Settings (Biometric Lock)
                        </h4>
                        <p style="font-size: 12px; color: var(--text-muted); line-height: 1.4; margin-bottom: 15px;">
                            If enabled, when a woman check-in is currently active in the gym during this scheduled time window, all other member check-ins (e.g. men) will be blocked automatically at the door.
                        </p>
                        
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                            <input type="checkbox" id="women_batch_enabled" name="women_batch_enabled" value="1" <?php echo (isset($gym['women_batch_enabled']) && $gym['women_batch_enabled'] == 1) ? 'checked' : ''; ?> style="width: 18px; height: 18px; cursor: pointer;">
                            <label for="women_batch_enabled" style="margin-bottom: 0; cursor: pointer; color: #fff; font-weight: 600;">Enable Women-Only Exclusive Batch Security</label>
                        </div>
                        
                        <div class="row">
                            <div class="col-sm-6">
                                <label>Batch Start Time</label>
                                <input type="time" name="women_batch_start" class="form-control-premium" value="<?php echo isset($gym['women_batch_start']) ? htmlspecialchars($gym['women_batch_start']) : '11:00'; ?>">
                            </div>
                            <div class="col-sm-6">
                                <label>Batch End Time</label>
                                <input type="time" name="women_batch_end" class="form-control-premium" value="<?php echo isset($gym['women_batch_end']) ? htmlspecialchars($gym['women_batch_end']) : '13:00'; ?>">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div style="padding: 15px; color: var(--text-muted); font-size: 11.5px; border: 1px dashed var(--glass-border); border-radius: 8px; text-align: center;">
                                <i class="entypo-lock"></i> Logo upload disabled by administrator
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label>Upload Payment QR Code</label>
                            <input class="form-control-premium" type="file" name="payment_qr" accept="image/*">
                            <span class="help-block" style="color: var(--text-muted); font-size: 11px; margin-bottom: 20px; display: block;">*Scan code for members to make dynamic payments.</span>
                        </div>
                    </div>

                    <div style="text-align: right; margin-top: 20px;">
                        <input class="btn btn-primary" type="submit" name="submit" id="submit" value="Save Branding Settings" style="width: auto !important; display: inline-block;">
                    </div>
                </form>
            </div>
            
            <?php include('footer.php'); ?>
        </div>
    </div>
</body>
</html>
