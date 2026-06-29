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
    $smtp_username = mysqli_real_escape_string($con, $_POST['smtp_username']);
    $smtp_password = mysqli_real_escape_string($con, $_POST['smtp_password']);
    $smtp_from_name = mysqli_real_escape_string($con, $_POST['smtp_from_name']);
    $smtp_from_email = mysqli_real_escape_string($con, $_POST['smtp_from_email']);

    $update_query = "UPDATE smtp_settings SET 
        smtp_host = '$smtp_host', 
        smtp_port = $smtp_port, 
        smtp_secure = '$smtp_secure', 
        smtp_username = '$smtp_username', 
        smtp_password = '$smtp_password', 
        smtp_from_name = '$smtp_from_name', 
        smtp_from_email = '$smtp_from_email' 
        WHERE id = 1";

    if (mysqli_query($con, $update_query)) {
        echo "<head><script>alert('SMTP configurations saved successfully!');</script></head></html>";
        echo "<meta http-equiv='refresh' content='0; url=smtp_settings.php'>";
        exit();
    } else {
        echo "<head><script>alert('Update failed, check details.');</script></head></html>";
    }
}

// Handle sending test email
$test_sent = false;
$test_error = '';
if (isset($_POST['send_test_email'])) {
    // Save settings first, then attempt to send
    $smtp_host = mysqli_real_escape_string($con, $_POST['smtp_host']);
    $smtp_port = intval($_POST['smtp_port']);
    $smtp_secure = mysqli_real_escape_string($con, $_POST['smtp_secure']);
    $smtp_username = mysqli_real_escape_string($con, $_POST['smtp_username']);
    $smtp_password = mysqli_real_escape_string($con, $_POST['smtp_password']);
    $smtp_from_name = mysqli_real_escape_string($con, $_POST['smtp_from_name']);
    $smtp_from_email = mysqli_real_escape_string($con, $_POST['smtp_from_email']);

    $update_query = "UPDATE smtp_settings SET 
        smtp_host = '$smtp_host', 
        smtp_port = $smtp_port, 
        smtp_secure = '$smtp_secure', 
        smtp_username = '$smtp_username', 
        smtp_password = '$smtp_password', 
        smtp_from_name = '$smtp_from_name', 
        smtp_from_email = '$smtp_from_email' 
        WHERE id = 1";
    mysqli_query($con, $update_query);

    // Refresh configurations local variables
    $smtp = [
        'smtp_host' => $smtp_host,
        'smtp_port' => $smtp_port,
        'smtp_secure' => $smtp_secure,
        'smtp_username' => $smtp_username,
        'smtp_password' => $smtp_password,
        'smtp_from_name' => $smtp_from_name,
        'smtp_from_email' => $smtp_from_email
    ];

    $test_email = mysqli_real_escape_string($con, $_POST['test_email_address']);
    require_once '../../include/smtp_mailer.php';
    
    $test_body = "<h3>SMTP Test Connection Successful</h3><p>This email confirms that your Google/SMTP account has been successfully configured and is sending emails from the SUDARSHAN FITNESS gym system.</p>";
    if (send_smtp_email($test_email, "Test Recipient", "SMTP Test Connection", $test_body)) {
        $test_sent = true;
    } else {
        $test_error = "Failed to dispatch test email. Please check your credentials, App Password, SMTP host, and port settings.";
        // Read errors from log
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
    <title><?php echo htmlspecialchars($gym['gym_name']); ?> | SMTP Settings</title>
    <link rel="stylesheet" href="../../css/style.css" id="style-resource-5">
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
            max-width: 700px;
            margin: 0 auto 30px auto;
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
                            <a href="logout.php">
                                Log Out <i class="entypo-logout right"></i>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <h2>SMTP Server Configuration</h2>
            <hr />

            <div class="settings-card">
                <form id="smtpForm" name="smtpForm" method="post" action="">
                    <h4 style="color: var(--accent-primary); border-bottom: 1px solid rgba(255,107,0,0.2); padding-bottom: 10px; margin-bottom: 20px;">Connection Settings</h4>
                    <div class="row">
                        <div class="col-md-8">
                            <label>SMTP Host Address</label>
                            <input class="form-control-premium" type="text" name="smtp_host" value="<?php echo htmlspecialchars($smtp['smtp_host']); ?>" required placeholder="e.g. smtp.gmail.com">
                        </div>
                        <div class="col-md-4">
                            <label>SMTP Port</label>
                            <input class="form-control-premium" type="number" name="smtp_port" value="<?php echo htmlspecialchars($smtp['smtp_port']); ?>" required placeholder="e.g. 465 or 587">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <label>Encryption Security</label>
                            <select class="form-control-premium" name="smtp_secure" required>
                                <option value="ssl" <?php echo $smtp['smtp_secure'] === 'ssl' ? 'selected' : ''; ?>>SSL (Port 465 Recommended)</option>
                                <option value="tls" <?php echo $smtp['smtp_secure'] === 'tls' ? 'selected' : ''; ?>>TLS (Port 587 Recommended)</option>
                                <option value="none" <?php echo $smtp['smtp_secure'] === 'none' ? 'selected' : ''; ?>>None</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label>Sender Full Name</label>
                            <input class="form-control-premium" type="text" name="smtp_from_name" value="<?php echo htmlspecialchars($smtp['smtp_from_name']); ?>" required placeholder="e.g. Sudarshan Fitness Khamgaon">
                        </div>
                    </div>

                    <h4 style="color: var(--accent-primary); border-bottom: 1px solid rgba(255,107,0,0.2); padding-bottom: 10px; margin-top: 20px; margin-bottom: 20px;">Authentication Credentials (Google Account Info)</h4>
                    
                    <div style="background: rgba(255,107,0,0.05); border: 1px dashed rgba(255,107,0,0.2); border-radius: 8px; padding: 12px; margin-bottom: 20px; font-size: 12px; color: var(--text-muted);">
                        <strong>Gmail Setup Guide:</strong> To use a personal Google Account, you must enable Multi-Factor Authentication (MFA) on your Google account settings, and generate a 16-character <strong>App Password</strong>. Paste the app password directly in the Password field below (do not use your regular account password).
                    </div>

                    <label>Sender / Account Username (Email address)</label>
                    <input class="form-control-premium" type="email" name="smtp_username" value="<?php echo htmlspecialchars($smtp['smtp_username']); ?>" required placeholder="e.g. address@gmail.com">

                    <label>Account Password / Google App Password</label>
                    <input class="form-control-premium" type="password" name="smtp_password" value="<?php echo htmlspecialchars($smtp['smtp_password']); ?>" placeholder="Google App Password">

                    <label>Sender From Email Address (Optional, defaults to Username)</label>
                    <input class="form-control-premium" type="email" name="smtp_from_email" value="<?php echo htmlspecialchars($smtp['smtp_from_email']); ?>" placeholder="e.g. support@sudarshanfitness.com">

                    <div style="text-align: right; margin-top: 25px;">
                        <input class="btn btn-primary" type="submit" name="submit_smtp" value="Save SMTP Configurations" style="width: auto !important; display: inline-block;">
                    </div>
                </form>
            </div>

            <!-- Test Connection Section -->
            <div class="settings-card">
                <h4 style="color: var(--accent-primary); border-bottom: 1px solid rgba(255,107,0,0.2); padding-bottom: 10px; margin-bottom: 20px;">Test SMTP Dispatch</h4>
                
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
                    <!-- Inject current form fields to save them before testing -->
                    <input type="hidden" name="smtp_host" id="test_host">
                    <input type="hidden" name="smtp_port" id="test_port">
                    <input type="hidden" name="smtp_secure" id="test_secure">
                    <input type="hidden" name="smtp_from_name" id="test_from_name">
                    <input type="hidden" name="smtp_username" id="test_username">
                    <input type="hidden" name="smtp_password" id="test_password">
                    <input type="hidden" name="smtp_from_email" id="test_from_email">

                    <label>Send Test Email To</label>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <input class="form-control-premium" style="margin-bottom: 0;" type="email" name="test_email_address" required placeholder="e.g. target@gmail.com">
                        <button type="submit" name="send_test_email" class="a1-btn a1-blue" onclick="syncTestFields()" style="white-space: nowrap; height: 42px;">Send Test Email</button>
                    </div>
                </form>
            </div>

            <script>
                function syncTestFields() {
                    document.getElementById('test_host').value = document.getElementsByName('smtp_host')[0].value;
                    document.getElementById('test_port').value = document.getElementsByName('smtp_port')[0].value;
                    document.getElementById('test_secure').value = document.getElementsByName('smtp_secure')[0].value;
                    document.getElementById('test_from_name').value = document.getElementsByName('smtp_from_name')[0].value;
                    document.getElementById('test_username').value = document.getElementsByName('smtp_username')[0].value;
                    document.getElementById('test_password').value = document.getElementsByName('smtp_password')[0].value;
                    document.getElementById('test_from_email').value = document.getElementsByName('smtp_from_email')[0].value;
                }
            </script>

            <?php include('footer.php'); ?>
        </div>
    </div>
</body>
</html>
