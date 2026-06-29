<?php
require '../../include/db_conn.php';
page_protect();

if ($_SESSION['role'] !== 'super_admin') {
    echo "<head><script>alert('Access Denied. Only Super Admins (Application Developer) can configure SMTP details.');</script></head></html>";
    echo "<meta http-equiv='refresh' content='0; url=index.php'>";
    exit();
}

$gym = get_gym_details($con);

// Fetch current SMTP Settings
$res = mysqli_query($con, "SELECT * FROM smtp_settings WHERE id = 1");
$smtp = mysqli_fetch_assoc($res);

if (isset($_POST['submit_smtp'])) {
    $smtp_host = mysqli_real_escape_string($con, $_POST['smtp_host']);
    $smtp_port = intval($_POST['smtp_port']);
    $smtp_secure = mysqli_real_escape_string($con, $_POST['smtp_secure']);
    
    // System Admin (Default)
    $smtp_username = mysqli_real_escape_string($con, $_POST['smtp_username']);
    $smtp_password = mysqli_real_escape_string($con, $_POST['smtp_password']);
    $smtp_from_name = mysqli_real_escape_string($con, $_POST['smtp_from_name']);
    
    // Payments
    $smtp_user_payments = mysqli_real_escape_string($con, $_POST['smtp_user_payments']);
    $smtp_pass_payments = mysqli_real_escape_string($con, $_POST['smtp_pass_payments']);
    $smtp_name_payments = mysqli_real_escape_string($con, $_POST['smtp_name_payments']);
    
    // Recovery
    $smtp_user_recovery = mysqli_real_escape_string($con, $_POST['smtp_user_recovery']);
    $smtp_pass_recovery = mysqli_real_escape_string($con, $_POST['smtp_pass_recovery']);
    $smtp_name_recovery = mysqli_real_escape_string($con, $_POST['smtp_name_recovery']);
    
    // Cyber / Intruder
    $smtp_user_cyber = mysqli_real_escape_string($con, $_POST['smtp_user_cyber']);
    $smtp_pass_cyber = mysqli_real_escape_string($con, $_POST['smtp_pass_cyber']);
    $smtp_name_cyber = mysqli_real_escape_string($con, $_POST['smtp_name_cyber']);

    $update_query = "UPDATE smtp_settings SET 
        smtp_host = '$smtp_host', 
        smtp_port = $smtp_port, 
        smtp_secure = '$smtp_secure', 
        smtp_username = '$smtp_username', 
        smtp_password = '$smtp_password', 
        smtp_from_name = '$smtp_from_name',
        smtp_user_payments = '$smtp_user_payments',
        smtp_pass_payments = '$smtp_pass_payments',
        smtp_name_payments = '$smtp_name_payments',
        smtp_user_recovery = '$smtp_user_recovery',
        smtp_pass_recovery = '$smtp_pass_recovery',
        smtp_name_recovery = '$smtp_name_recovery',
        smtp_user_cyber = '$smtp_user_cyber',
        smtp_pass_cyber = '$smtp_pass_cyber',
        smtp_name_cyber = '$smtp_name_cyber'
        WHERE id = 1";

    // Since users might not have run the db_upgrade script yet, we check if it succeeds
    if (mysqli_query($con, $update_query)) {
        echo "<head><script>alert('Multi-Role SMTP configurations saved successfully!');</script></head></html>";
        echo "<meta http-equiv='refresh' content='0; url=smtp_settings.php'>";
        exit();
    } else {
        $err = mysqli_error($con);
        if (strpos($err, "Unknown column") !== false) {
            echo "<head><script>alert('Error: You must run db_upgrade.php first to upgrade your database schema!');</script></head></html>";
        } else {
            echo "<head><script>alert('Update failed: $err');</script></head></html>";
        }
    }
}

// Handle sending test email (uses default/Admin config for testing)
$test_sent = false;
$test_error = '';
if (isset($_POST['send_test_email'])) {
    $test_email = mysqli_real_escape_string($con, $_POST['test_email_address']);
    require_once '../../include/smtp_mailer.php';
    
    $test_body = "<h3>SMTP Test Connection Successful</h3><p>This email confirms that your SMTP account has been successfully configured and is sending emails from the SUDARSHAN FITNESS gym system.</p>";
    if (send_smtp_email($test_email, "Test Recipient", "SMTP Test Connection", $test_body, null, null, 'admin')) {
        $test_sent = true;
    } else {
        $test_error = "Failed to dispatch test email. Please check your credentials, SMTP host, and port settings.";
        $log_file = '../../include/email_log.txt';
        if (file_exists($log_file)) {
            $log_contents = file($log_file);
            $last_lines = array_slice($log_contents, -5);
            $test_error .= "<br><strong>Last log error:</strong> " . nl2br(htmlspecialchars(implode("", $last_lines)));
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo htmlspecialchars($gym['gym_name']); ?> | Multi-Role SMTP Settings</title>
    <link rel="stylesheet" href="../../css/style.css">
    <script type="text/javascript" src="../../js/Script.js"></script>
    <link rel="stylesheet" href="../../css/dashMain.css">
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
    <link rel="stylesheet" href="../../css/premium.css">
    <link href="a1style.css" rel="stylesheet" type="text/css">
    <style>
        .page-container .sidebar-menu #main-menu li#smtpsettings > a {
            background-color: rgba(255, 107, 0, 0.1) !important;
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
            box-shadow: 0 0 0 3px rgba(255, 107, 0, 0.2) !important;
        }
        .settings-card {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: var(--glass-shadow);
        }
        .role-badge {
            background: rgba(255,107,0,0.1);
            color: var(--accent-primary);
            padding: 3px 8px;
            border-radius: 5px;
            font-size: 11px;
            text-transform: uppercase;
            font-weight: bold;
            margin-left: 10px;
            vertical-align: middle;
        }
    </style>
</head>
<body class="page-body page-fade" onload="collapseSidebar()">
    <div class="page-container sidebar-collapsed" id="navbarcollapse">
        <div class="sidebar-menu">
            <header class="logo-env">
                <div class="logo">
                    <a href="index.php">
                        <img src="../../images/logo.png" alt="" style="max-height: 60px; max-width: 180px;" />
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
                            <a href="logout.php">Log Out <i class="entypo-logout right"></i></a>
                        </li>
                    </ul>
                </div>
            </div>

            <h2>Enterprise Multi-Role SMTP Configuration</h2>
            <p style="color: var(--text-muted); margin-bottom: 30px;">Configure dedicated credentials for each automated email department.</p>

            <form id="smtpForm" name="smtpForm" method="post" action="">
                
                <!-- 1. Master Server Settings -->
                <div class="settings-card">
                    <h4 style="color: var(--accent-primary); border-bottom: 1px solid rgba(255,107,0,0.2); padding-bottom: 10px; margin-bottom: 20px;">
                        <i class="entypo-network"></i> Master Server Details
                    </h4>
                    <p style="font-size: 12px; color: var(--text-muted); margin-bottom: 15px;">These host settings will be used globally by all 4 roles below.</p>
                    <div class="row">
                        <div class="col-md-5">
                            <label>SMTP Host Address</label>
                            <input class="form-control-premium" type="text" name="smtp_host" value="<?php echo htmlspecialchars($smtp['smtp_host']); ?>" required placeholder="e.g. smtp.hostinger.com">
                        </div>
                        <div class="col-md-3">
                            <label>SMTP Port</label>
                            <input class="form-control-premium" type="number" name="smtp_port" value="<?php echo htmlspecialchars($smtp['smtp_port']); ?>" required placeholder="465">
                        </div>
                        <div class="col-md-4">
                            <label>Encryption Security</label>
                            <select class="form-control-premium" name="smtp_secure" required>
                                <option value="ssl" <?php echo $smtp['smtp_secure'] === 'ssl' ? 'selected' : ''; ?>>SSL (Port 465)</option>
                                <option value="tls" <?php echo $smtp['smtp_secure'] === 'tls' ? 'selected' : ''; ?>>TLS (Port 587)</option>
                                <option value="none" <?php echo $smtp['smtp_secure'] === 'none' ? 'selected' : ''; ?>>None</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- 2. System Admin -->
                    <div class="col-md-6">
                        <div class="settings-card">
                            <h4 style="color: #3b82f6; border-bottom: 1px solid rgba(59,130,246,0.2); padding-bottom: 10px; margin-bottom: 20px;">
                                <i class="entypo-cog"></i> System Admin <span class="role-badge" style="background: rgba(59,130,246,0.1); color: #3b82f6;">admin@</span>
                            </h4>
                            <p style="font-size: 11px; color: var(--text-muted); height: 30px;">Used for Automated Backups and Membership Expiry Warnings.</p>
                            
                            <label>Email Username</label>
                            <input class="form-control-premium" type="email" name="smtp_username" value="<?php echo htmlspecialchars($smtp['smtp_username']); ?>" required>
                            
                            <label>Mailbox Password</label>
                            <input class="form-control-premium" type="password" name="smtp_password" value="<?php echo htmlspecialchars($smtp['smtp_password']); ?>">
                            
                            <label>Sender 'From' Name</label>
                            <input class="form-control-premium" type="text" name="smtp_from_name" value="<?php echo htmlspecialchars($smtp['smtp_from_name']); ?>" required>
                        </div>
                    </div>

                    <!-- 3. Payments & Invoices -->
                    <div class="col-md-6">
                        <div class="settings-card">
                            <h4 style="color: #10b981; border-bottom: 1px solid rgba(16,185,129,0.2); padding-bottom: 10px; margin-bottom: 20px;">
                                <i class="entypo-credit-card"></i> Financial Module <span class="role-badge" style="background: rgba(16,185,129,0.1); color: #10b981;">payments@</span>
                            </h4>
                            <p style="font-size: 11px; color: var(--text-muted); height: 30px;">Used for sending PDF Invoices and Payment Receipts.</p>
                            
                            <label>Email Username</label>
                            <input class="form-control-premium" type="email" name="smtp_user_payments" value="<?php echo htmlspecialchars($smtp['smtp_user_payments'] ?? ''); ?>" required>
                            
                            <label>Mailbox Password</label>
                            <input class="form-control-premium" type="password" name="smtp_pass_payments" value="<?php echo htmlspecialchars($smtp['smtp_pass_payments'] ?? ''); ?>">
                            
                            <label>Sender 'From' Name</label>
                            <input class="form-control-premium" type="text" name="smtp_name_payments" value="<?php echo htmlspecialchars($smtp['smtp_name_payments'] ?? ''); ?>" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- 4. Security / Recovery -->
                    <div class="col-md-6">
                        <div class="settings-card">
                            <h4 style="color: #f59e0b; border-bottom: 1px solid rgba(245,158,11,0.2); padding-bottom: 10px; margin-bottom: 20px;">
                                <i class="entypo-key"></i> Recovery Module <span class="role-badge" style="background: rgba(245,158,11,0.1); color: #f59e0b;">recovery@</span>
                            </h4>
                            <p style="font-size: 11px; color: var(--text-muted); height: 30px;">Used exclusively for "Forgot Password" resets.</p>
                            
                            <label>Email Username</label>
                            <input class="form-control-premium" type="email" name="smtp_user_recovery" value="<?php echo htmlspecialchars($smtp['smtp_user_recovery'] ?? ''); ?>" required>
                            
                            <label>Mailbox Password</label>
                            <input class="form-control-premium" type="password" name="smtp_pass_recovery" value="<?php echo htmlspecialchars($smtp['smtp_pass_recovery'] ?? ''); ?>">
                            
                            <label>Sender 'From' Name</label>
                            <input class="form-control-premium" type="text" name="smtp_name_recovery" value="<?php echo htmlspecialchars($smtp['smtp_name_recovery'] ?? ''); ?>" required>
                        </div>
                    </div>

                    <!-- 5. Cyber / Intruder -->
                    <div class="col-md-6">
                        <div class="settings-card">
                            <h4 style="color: #ef4444; border-bottom: 1px solid rgba(239,68,68,0.2); padding-bottom: 10px; margin-bottom: 20px;">
                                <i class="entypo-shield"></i> Security Alerts <span class="role-badge" style="background: rgba(239,68,68,0.1); color: #ef4444;">cyber.officer@</span>
                            </h4>
                            <p style="font-size: 11px; color: var(--text-muted); height: 30px;">Used for Unauthorized Intruder Login Alerts.</p>
                            
                            <label>Email Username</label>
                            <input class="form-control-premium" type="email" name="smtp_user_cyber" value="<?php echo htmlspecialchars($smtp['smtp_user_cyber'] ?? ''); ?>" required>
                            
                            <label>Mailbox Password</label>
                            <input class="form-control-premium" type="password" name="smtp_pass_cyber" value="<?php echo htmlspecialchars($smtp['smtp_pass_cyber'] ?? ''); ?>">
                            
                            <label>Sender 'From' Name</label>
                            <input class="form-control-premium" type="text" name="smtp_name_cyber" value="<?php echo htmlspecialchars($smtp['smtp_name_cyber'] ?? ''); ?>" required>
                        </div>
                    </div>
                </div>

                <div style="text-align: center; margin-top: 10px; margin-bottom: 40px;">
                    <button class="btn btn-primary" type="submit" name="submit_smtp" style="width: 300px; height: 50px; font-size: 16px; font-weight: bold; background: linear-gradient(135deg, var(--accent-primary), #ff4d00) !important; border: none !important;">
                        <i class="entypo-floppy"></i> Save All 4 Configurations
                    </button>
                </div>
            </form>

            <!-- Test Connection Section -->
            <div class="settings-card">
                <h4 style="color: var(--text-main); border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px; margin-bottom: 20px;">Test System Admin Dispatch</h4>
                
                <?php if ($test_sent): ?>
                    <div class="alert alert-success" style="background: rgba(16, 185, 129, 0.1); border: 1px solid var(--success); color: var(--success); border-radius: 10px; padding: 15px; margin-bottom: 20px;">
                        <strong>Success!</strong> Test email dispatched successfully! Please check the recipient inbox.
                    </div>
                <?php endif; ?>

                <?php if ($test_error !== ''): ?>
                    <div class="alert alert-danger" style="background: rgba(239, 68, 68, 0.1); border: 1px solid var(--danger); color: var(--danger); border-radius: 10px; padding: 15px; margin-bottom: 20px; font-size: 13px;">
                        <strong>Error:</strong> <?php echo $test_error; ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="">
                    <label>Send Test Email To</label>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <input class="form-control-premium" style="margin-bottom: 0; max-width: 400px;" type="email" name="test_email_address" required placeholder="e.g. target@gmail.com">
                        <button type="submit" name="send_test_email" class="btn btn-primary" style="white-space: nowrap; height: 42px;">Send Test Email</button>
                    </div>
                </form>
            </div>

            <?php include('footer.php'); ?>
        </div>
    </div>
</body>
</html>
