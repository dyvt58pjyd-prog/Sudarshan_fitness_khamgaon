<?php
session_start();
include './include/db_conn.php';
require_once './include/smtp_mailer.php';

$gym = get_gym_details($con);

$msg = '';
$msg_type = '';

if (isset($_POST['btnReset'])) {
    $input = mysqli_real_escape_string($con, trim($_POST['reset_input']));
    
    // Look for the member in users table by email or userid
    $q_user = "SELECT userid, username, email FROM users WHERE email = '$input' OR userid = '$input'";
    $res_user = mysqli_query($con, $q_user);
    
    if ($res_user && mysqli_num_rows($res_user) > 0) {
        $user_row = mysqli_fetch_assoc($res_user);
        $userid = $user_row['userid'];
        $email = $user_row['email'];
        $name = $user_row['username'];
        
        // Fetch their password from the admin table
        $q_admin = "SELECT pass_key FROM admin WHERE username = '$userid'";
        $res_admin = mysqli_query($con, $q_admin);
        
        if ($res_admin && mysqli_num_rows($res_admin) > 0) {
            $admin_row = mysqli_fetch_assoc($res_admin);
            $password = $admin_row['pass_key'];
            
            // Send the email!
            if (!empty($email)) {
                $subject = "Your Password Recovery - " . $gym['gym_name'];
                $body = "<h2>Password Recovery</h2>
                <p>Hello $name,</p>
                <p>We received a request to recover your password for <strong>" . $gym['gym_name'] . "</strong>.</p>
                <p>Your login details are:</p>
                <ul style='background: #f4f4f5; padding: 15px; border-radius: 8px; font-family: monospace;'>
                    <li><strong>User ID:</strong> $userid</li>
                    <li><strong>Password:</strong> $password</li>
                </ul>
                <p>You can now return to the login page and access your account.</p>
                <p>If you did not request this, please contact support immediately.</p>";
                
                if (send_smtp_email($email, $name, $subject, $body, null, null, 'recovery')) {
                    $msg = "Success! Your password has been sent to your registered email ($email).";
                    $msg_type = "success";
                } else {
                    $msg = "Error: We could not send the email. Please check the system SMTP settings or contact the gym administrator.";
                    $msg_type = "error";
                }
            } else {
                $msg = "Error: There is no email address registered with this User ID. Please contact the front desk.";
                $msg_type = "error";
            }
        } else {
            $msg = "Error: Account authentication record not found.";
            $msg_type = "error";
        }
    } else {
        $msg = "Error: We could not find any member with that Email or User ID.";
        $msg_type = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo htmlspecialchars($gym['gym_name']); ?> | Reset Password</title>
    <link rel="stylesheet" href="./css/style.css"/>
	<link rel="stylesheet" type="text/css" href="./css/entypo.css">
	<link rel="stylesheet" href="./css/premium.css"/>
</head>

<body class="page-body login-page login-form-fall">
	<div id="container">
		<div class="login-container">
			<div class="login-header login-caret">
				<div class="login-content">
					<?php
					$logo_path = $gym['gym_logo'] ?? 'images/logo.png';
					if (substr($logo_path, 0, 6) === '../../') {
						$logo_path = './' . substr($logo_path, 6);
					}
					?>
					<a href="index.php" class="logo">
						<img src="<?php echo htmlspecialchars($logo_path); ?>" alt="Gym Logo" style="max-height: 80px;" />
					</a>
                    <p class="description">Recover your account password.</p>
				</div>
			</div>
			
			<div class="login-form">
				<div class="login-content">
                    
                    <?php if (!empty($msg)): ?>
                        <div class="alert alert-<?php echo ($msg_type === 'success') ? 'success' : 'danger'; ?>" style="border-radius: 8px; text-align: left; margin-bottom: 25px;">
                            <?php echo $msg; ?>
                        </div>
                    <?php endif; ?>
                    
					<form action="" method="POST" id="bb">	
						<div class="form-group">					
							<div class="input-group">
								<div class="input-group-addon">
									<i class="entypo-mail"></i>
								</div>
								<input type="text" class="form-control" name="reset_input" placeholder="Enter your Email or User ID" required />
							</div>
						</div>				
						
						<div class="form-group" style="margin-top: 25px;">
							<button type="Submit" name="btnReset" class="btn btn-primary" style="width: 100%;">
								Send My Password
								<i class="entypo-paper-plane"></i>
							</button>
						</div>
                        
                        <div class="form-group" style="margin-top: 15px;">
                            <a href="./index.php" class="btn btn-default" style="width: 100%; background: transparent; border: 1px solid rgba(255,255,255,0.2); color: #fff;">
                                Back to Login
                            </a>
                        </div>
					</form>
				</div>
			</div>
		</div>
	</div>	
</body>
</html>
