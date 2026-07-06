<?php
require '../../include/db_conn.php';
page_protect();

// Auto daily membership expiry warning trigger
$lock_file = __DIR__ . '/../../include/last_expiry_check.txt';
$today_str = date('Y-m-d');
$last_check = file_exists($lock_file) ? trim(@file_get_contents($lock_file)) : '';

if ($last_check !== $today_str) {
    @file_put_contents($lock_file, $today_str);
    ob_start();
    include __DIR__ . '/check_expiring_members.php';
    include __DIR__ . '/../../api/inactivity_check.php';
    include __DIR__ . '/../../api/daily_celebration_check.php';
    ob_end_clean();
}

if (isset($_GET['send_reminder']) && isset($_GET['uid'])) {
    $rem_uid = mysqli_real_escape_string($con, $_GET['uid']);
    // Fetch member details
    $q_rem = "SELECT u.username, u.email, e.expire, p.planName FROM users u INNER JOIN enrolls_to e ON u.userid = e.uid INNER JOIN plan p ON e.pid = p.pid WHERE u.userid = '$rem_uid' AND e.renewal = 'yes'";
    $res_rem = mysqli_query($con, $q_rem);
    if ($res_rem && mysqli_num_rows($res_rem) > 0) {
        $rem_row = mysqli_fetch_assoc($res_rem);
        
        $email = $rem_row['email'];
        $name = $rem_row['username'];
        $plan = $rem_row['planName'];
        $expire = $rem_row['expire'];
        
        // Compute days left
        $today = new DateTime(date('Y-m-d'));
        $expire_dt = new DateTime($expire);
        $diff = $today->diff($expire_dt);
        $days = (int)$diff->format('%r%a');
        
        $gym = get_gym_details($con);
        $gym_name = $gym['gym_name'];
        $gym_email = $gym['gym_email'];
        
        $subject = "Membership Expiry Reminder - $gym_name";
        
        if ($days < 0) {
            $msg_days = "expired on $expire (" . abs($days) . " days ago)";
        } elseif ($days == 0) {
            $msg_days = "expires today ($expire)";
        } else {
            $msg_days = "is expiring soon on $expire ($days days remaining)";
        }
        
        $mail_body = "
        <html>
        <head>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8fafc; color: #1e293b; padding: 30px; margin: 0; }
                .container { background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 40px; max-width: 600px; margin: 0 auto; box-shadow: 0 4px 20px rgba(0,0,0,0.05); position: relative; overflow: hidden; }
                .top-line { position: absolute; top: 0; left: 0; right: 0; height: 5px; background: linear-gradient(90deg, #ff6b00, #ff8c00); }
                h2 { color: #ff6b00; font-size: 22px; font-weight: 700; margin-top: 10px; margin-bottom: 20px; }
                p { font-size: 14px; line-height: 1.6; color: #475569; }
                .login-box { background-color: rgba(255, 107, 0, 0.05); border: 1px dashed rgba(255, 107, 0, 0.3); padding: 20px; margin: 25px 0; border-radius: 10px; font-size: 14px; line-height: 1.6; }
                .login-box strong { color: #ff6b00; }
                .footer { margin-top: 35px; font-size: 12px; color: #94a3b8; text-align: center; border-top: 1px solid #e2e8f0; padding-top: 20px; line-height: 1.5; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='top-line'></div>
                <h2>Membership Expiry Reminder</h2>
                <p>Dear <strong>$name</strong>,</p>
                <p>This is a friendly reminder from <strong>$gym_name</strong> regarding your active membership subscription.</p>
                
                <div class='login-box'>
                    <strong>Subscription Details:</strong><br>
                    Plan: <code>$plan</code><br>
                    Status: Your plan <strong>$msg_days</strong>.
                </div>
                
                <p>To ensure uninterrupted access to the gym facilities, trainer support, and member portal features, please visit the reception desk or contact us to renew your membership package.</p>
                
                <div class='footer'>
                    This is an automated notification from $gym_name.<br>
                    Need assistance? Reply to this email or reach us at <a href='mailto:$gym_email' style='color: #ff6b00; text-decoration: none;'>$gym_email</a>
                </div>
            </div>
        </body>
        </html>";
        
        require_once '../../include/smtp_mailer.php';
        $sent = send_smtp_email($email, $name, $subject, $mail_body);
        
        if (!$sent) {
            $headers  = "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "From: $gym_email\r\n";
            $headers .= "Reply-To: $gym_email\r\n";
            $sent = @mail($email, $subject, $mail_body, $headers);
        }
        
        if ($sent) {
            $_SESSION['rem_status'] = "success";
            $_SESSION['rem_msg'] = "Reminder email sent successfully to $name ($email).";
        } else {
            $_SESSION['rem_status'] = "error";
            $_SESSION['rem_msg'] = "Failed to send email. Please check your SMTP settings.";
        }
    } else {
        $_SESSION['rem_status'] = "error";
        $_SESSION['rem_msg'] = "Member not found or has no active subscription.";
    }
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head> 

    
    <title>SUDARSHAN FITNESS | Dashboard </title>

    <link rel="stylesheet" href="../../css/style.css"  id="style-resource-5">
    <script type="text/javascript" src="../../js/Script.js"></script>
    <link rel="stylesheet" href="../../css/dashMain.css">
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
    <link rel="stylesheet" href="../../css/premium.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
     <style>
    	.page-container .sidebar-menu #main-menu li#dash > a {
    	background-color: #2b303a;
    	color: #ffffff;
		}

        /* Glassmorphism Metric Widgets */
        .tile-stats {
            background: rgba(255, 255, 255, 0.03) !important;
            backdrop-filter: blur(20px) !important;
            -webkit-backdrop-filter: blur(20px) !important;
            border: 1px solid rgba(255, 255, 255, 0.05) !important;
            border-radius: 24px !important;
            padding: 30px 20px !important;
            margin-bottom: 30px !important;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2) !important;
            position: relative;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
        }
        .tile-stats:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.4) !important;
        }
        .tile-stats .icon {
            color: rgba(255,255,255,0.1) !important;
            bottom: 20px !important;
            right: 20px !important;
            font-size: 80px !important;
        }
        .tile-stats h2 {
            font-size: 14px !important;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: var(--text-muted) !important;
            margin-top: 0 !important;
            font-weight: 700 !important;
        }
        .tile-stats .num {
            font-size: 42px !important;
            font-weight: 800 !important;
            color: #ffffff !important;
            text-shadow: 0 0 20px rgba(255,255,255,0.2);
            margin-top: 15px;
        }
        
        /* Colored Glowing Borders based on tile type */
        .tile-red { border-bottom: 4px solid #ef4444 !important; box-shadow: inset 0 -15px 30px -20px rgba(239,68,68,0.5) !important; }
        .tile-green { border-bottom: 4px solid #10b981 !important; box-shadow: inset 0 -15px 30px -20px rgba(16,185,129,0.5) !important; }
        .tile-aqua { border-bottom: 4px solid #06b6d4 !important; box-shadow: inset 0 -15px 30px -20px rgba(6,182,212,0.5) !important; }
        .tile-blue { border-bottom: 4px solid #3b82f6 !important; box-shadow: inset 0 -15px 30px -20px rgba(59,130,246,0.5) !important; }
        
        .tile-red:hover { border-color: #ef4444 !important; }
        .tile-green:hover { border-color: #10b981 !important; }
        .tile-aqua:hover { border-color: #06b6d4 !important; }
        .tile-blue:hover { border-color: #3b82f6 !important; }
        
        /* Darker panel override */
        .panel {
            background: rgba(255, 255, 255, 0.02) !important;
            backdrop-filter: blur(15px) !important;
            border: 1px solid rgba(255,255,255,0.05) !important;
            border-radius: 20px !important;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3) !important;
        }
        .panel-heading {
            background: transparent !important;
            border-bottom: 1px solid rgba(255,255,255,0.05) !important;
        }
        .panel-title { color: #fff !important; font-weight: 700 !important; }
        
        /* Table overrides */
        .table > tbody > tr {
            transition: background 0.3s ease, box-shadow 0.3s ease !important;
        }
        .table > tbody > tr:hover {
            background: rgba(255, 107, 0, 0.05) !important;
            box-shadow: inset 0 0 15px rgba(255, 107, 0, 0.2) !important;
        }

    </style>

</head>
    <body class="page-body  page-fade" onload="collapseSidebar()" style="background-color: #0b0c10;">

        <!-- Particle HUD Background -->
        <div id="particles-js" style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; z-index: -1;"></div>

    	<div class="page-container sidebar-collapsed" id="navbarcollapse">	
	
		<div class="sidebar-menu">
	
			<header class="logo-env">
			
			<!-- logo -->
			<div class="logo">
				<a href="main.php">
					<?php 
                        $sidebar_logo = $gym_settings_data["gym_logo"] ?? "../../images/logo.png";
                        ?>
                        <img src="<?php echo htmlspecialchars($sidebar_logo); ?>" alt="Gym Logo" style="max-height: 80px; max-width: 192px;" />
				</a>
			</div>
			
					<!-- logo collapse icon -->
					<div class="sidebar-collapse" onclick="collapseSidebar()">
				<a href="#" class="sidebar-collapse-icon with-animation"><!-- add class "with-animation" if you want sidebar to have animation during expanding/collapsing transition -->
					<i class="entypo-menu"></i>
				</a>
			</div>
							
			
		
			</header>
    		<?php include('nav.php'); ?>
    	</div>

    		<div class="main-content">
		
				<div class="row">
					
					<!-- Profile Info and Notifications -->
					<div class="col-md-6 col-sm-8 clearfix">	
							
					</div>
					
					
					<!-- Raw Links -->
					<div class="col-md-6 col-sm-4 clearfix hidden-xs">
						
						<ul class="list-inline links-list pull-right">

							<li>Welcome <?php echo $_SESSION['full_name']; ?> 
							</li>					
						
							<li>
								<a href="logout.php">
									Log Out <i class="entypo-logout right"></i>
								</a>
							</li>
						</ul>
						
					</div>
					
				</div>

			<div id="gate-alert-container" style="margin-top: 15px; margin-bottom: 15px;"></div>
			<h2>SUDARSHAN FITNESS</h2>

			<?php
			$q_pending = mysqli_query($con, "SELECT COUNT(*) as cnt FROM whatsapp_outbox WHERE status IN ('pending', 'failed') AND attempts < 3");
			$pending_count = 0;
			if ($q_pending && $row_pending = mysqli_fetch_assoc($q_pending)) {
			    $pending_count = intval($row_pending['cnt']);
			}
			if ($pending_count > 0):
			?>
			<div class="row" id="wa-outbox-alert-row" style="margin-bottom: 20px; margin-left: 0; margin-right: 0;">
			    <div class="col-md-12" style="padding: 0;">
			        <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 12px; padding: 15px 20px; display: flex; align-items: center; justify-content: space-between; color: #ffffff;">
			            <div style="display: flex; align-items: center; gap: 10px;">
			                <span style="font-size: 20px;">⚠️</span>
			                <span><strong>WhatsApp Notification Service is offline:</strong> <span id="wa-pending-count-text"><?php echo $pending_count; ?></span> alerts are currently pending in the outbox retry queue.</span>
			            </div>
			            <button class="btn btn-xs btn-danger" onclick="triggerOutboxRetry()" id="btn-outbox-retry" style="background: var(--danger); border-color: var(--danger); font-weight: bold; padding: 6px 15px; border-radius: 6px;">
			                Resend Pending Messages
			            </button>
			        </div>
			    </div>
			</div>
			<?php endif; ?>

			<hr>

			<!-- Big Boxed Simple Quick Actions (Designed for uneducated / elderly staff usability) -->
			<div class="row" style="margin-bottom: 30px; margin-left: 0; margin-right: 0;">
				<div class="col-md-12" style="padding: 0;">
					<h3 style="color: #ffffff; font-weight: 700; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
						<span style="font-size: 22px;">⚡</span> Quick Work Buttons (Tap any button to start)
					</h3>
					<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px;">
						
						<!-- Register Member -->
						<a href="new_entry.php" style="text-decoration: none;">
							<div style="background: linear-gradient(135deg, rgba(255, 107, 0, 0.15) 0%, rgba(255, 107, 0, 0.05) 100%); border: 2px solid #ff6b00; border-radius: 18px; padding: 25px; text-align: center; cursor: pointer; transition: all 0.2s ease-in-out; box-shadow: 0 10px 20px rgba(0,0,0,0.15);" 
							     onmouseover="this.style.transform='scale(1.03)'; this.style.background='linear-gradient(135deg, rgba(255,107,0,0.25) 0%, rgba(255,107,0,0.1) 100%)';" 
							     onmouseout="this.style.transform='scale(1)'; this.style.background='linear-gradient(135deg, rgba(255,107,0,0.15) 0%, rgba(255,107,0,0.05) 100%)';">
								<div style="font-size: 50px; margin-bottom: 12px; filter: drop-shadow(0 0 10px rgba(255,107,0,0.3));">👤➕</div>
								<h4 style="color: #ffffff; font-weight: 800; font-size: 17px; margin: 0 0 5px 0; text-transform: uppercase; letter-spacing: 0.5px;">Register Member</h4>
								<span style="color: rgba(255,255,255,0.6); font-size: 12.5px; font-weight: 500;">Add new gym admission</span>
							</div>
						</a>

						<!-- Manage Biometrics -->
						<a href="biometric_management.php" style="text-decoration: none;">
							<div style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.15) 0%, rgba(16, 185, 129, 0.05) 100%); border: 2px solid #10b981; border-radius: 18px; padding: 25px; text-align: center; cursor: pointer; transition: all 0.2s ease-in-out; box-shadow: 0 10px 20px rgba(0,0,0,0.15);" 
							     onmouseover="this.style.transform='scale(1.03)'; this.style.background='linear-gradient(135deg, rgba(16,185,129,0.25) 0%, rgba(16,185,129,0.1) 100%)';" 
							     onmouseout="this.style.transform='scale(1)'; this.style.background='linear-gradient(135deg, rgba(16,185,129,0.15) 0%, rgba(16,185,129,0.05) 100%)';">
								<div style="font-size: 50px; margin-bottom: 12px; filter: drop-shadow(0 0 10px rgba(16,185,129,0.3));">🔑🚪</div>
								<h4 style="color: #ffffff; font-weight: 800; font-size: 17px; margin: 0 0 5px 0; text-transform: uppercase; letter-spacing: 0.5px;">Biometric Gate</h4>
								<span style="color: rgba(255,255,255,0.6); font-size: 12.5px; font-weight: 500;">Enable/Disable finger locks</span>
							</div>
						</a>

						<!-- View Members List -->
						<a href="view_mem.php" style="text-decoration: none;">
							<div style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.15) 0%, rgba(59, 130, 246, 0.05) 100%); border: 2px solid #3b82f6; border-radius: 18px; padding: 25px; text-align: center; cursor: pointer; transition: all 0.2s ease-in-out; box-shadow: 0 10px 20px rgba(0,0,0,0.15);" 
							     onmouseover="this.style.transform='scale(1.03)'; this.style.background='linear-gradient(135deg, rgba(59,130,246,0.25) 0%, rgba(59,130,246,0.1) 100%)';" 
							     onmouseout="this.style.transform='scale(1)'; this.style.background='linear-gradient(135deg, rgba(59,130,246,0.15) 0%, rgba(59,130,246,0.05) 100%)';">
								<div style="font-size: 50px; margin-bottom: 12px; filter: drop-shadow(0 0 10px rgba(59,130,246,0.3));">📋🔍</div>
								<h4 style="color: #ffffff; font-weight: 800; font-size: 17px; margin: 0 0 5px 0; text-transform: uppercase; letter-spacing: 0.5px;">View Members</h4>
								<span style="color: rgba(255,255,255,0.6); font-size: 12.5px; font-weight: 500;">Search profiles & update plans</span>
							</div>
						</a>

						<!-- Payment Requests -->
						<a href="payment_requests.php" style="text-decoration: none;">
							<div style="background: linear-gradient(135deg, rgba(168, 85, 247, 0.15) 0%, rgba(168, 85, 247, 0.05) 100%); border: 2px solid #a855f7; border-radius: 18px; padding: 25px; text-align: center; cursor: pointer; transition: all 0.2s ease-in-out; box-shadow: 0 10px 20px rgba(0,0,0,0.15);" 
							     onmouseover="this.style.transform='scale(1.03)'; this.style.background='linear-gradient(135deg, rgba(168,85,247,0.25) 0%, rgba(168,85,247,0.1) 100%)';" 
							     onmouseout="this.style.transform='scale(1)'; this.style.background='linear-gradient(135deg, rgba(168,85,247,0.15) 0%, rgba(168,85,247,0.05) 100%)';">
								<div style="font-size: 50px; margin-bottom: 12px; filter: drop-shadow(0 0 10px rgba(168,85,247,0.3));">💰✓</div>
								<h4 style="color: #ffffff; font-weight: 800; font-size: 17px; margin: 0 0 5px 0; text-transform: uppercase; letter-spacing: 0.5px;">Verify Payments</h4>
								<span style="color: rgba(255,255,255,0.6); font-size: 12.5px; font-weight: 500;">Approve online UPI transfers</span>
							</div>
						</a>

					</div>
				</div>
			</div>

			<hr>

			<!-- Premium Live Analogue Clock & Smart Automation Services Panel -->
			<div class="row" style="margin-bottom: 25px; margin-left: 0; margin-right: 0;">
				<div class="col-md-12" style="padding: 0;">
					<div class="portal-card" style="background: var(--glass-bg); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); border: 1px solid var(--glass-border); border-radius: 20px; padding: 25px; box-shadow: var(--glass-shadow); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 20px; color: #ffffff;">
						
						<!-- Clock and Greeting Block -->
						<div style="display: flex; align-items: center; gap: 25px; flex-wrap: wrap;">
							<!-- Analogue Clock Face -->
							<div class="analogue-clock-container" style="position: relative; width: 130px; height: 130px; background: rgba(255, 255, 255, 0.02); border: 2px solid rgba(255, 255, 255, 0.08); border-radius: 50%; box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37); backdrop-filter: blur(8px); display: flex; align-items: center; justify-content: center;">
								<!-- Clock Center Pin -->
								<div style="position: absolute; width: 8px; height: 8px; background: #ff6b00; border: 2px solid #ffffff; border-radius: 50%; z-index: 10; box-shadow: 0 0 8px rgba(255,107,0,0.8);"></div>
								
								<!-- Hour Hand -->
								<div id="hour-hand" style="position: absolute; width: 4px; height: 32px; background: #ffffff; border-radius: 4px; bottom: 50%; left: calc(50% - 2px); transform-origin: bottom center; z-index: 5; transition: transform 0.1s cubic-bezier(0.4, 2.08, 0.55, 0.44);"></div>
								
								<!-- Minute Hand -->
								<div id="min-hand" style="position: absolute; width: 3px; height: 44px; background: #ff6b00; border-radius: 3px; bottom: 50%; left: calc(50% - 1.5px); transform-origin: bottom center; z-index: 6; transition: transform 0.1s cubic-bezier(0.4, 2.08, 0.55, 0.44);"></div>
								
								<!-- Second Hand -->
								<div id="sec-hand" style="position: absolute; width: 1.5px; height: 48px; background: #ef4444; bottom: 50%; left: calc(50% - 0.75px); transform-origin: bottom center; z-index: 7; transition: transform 0.05s linear;"></div>
								
								<!-- Dial Markers -->
								<div style="position: absolute; width: 4px; height: 4px; background: rgba(255,255,255,0.8); border-radius: 50%; top: 8px; left: calc(50% - 2px);"></div>
								<div style="position: absolute; width: 4px; height: 4px; background: rgba(255,255,255,0.4); border-radius: 50%; right: 8px; top: calc(50% - 2px);"></div>
								<div style="position: absolute; width: 4px; height: 4px; background: rgba(255,255,255,0.4); border-radius: 50%; bottom: 8px; left: calc(50% - 2px);"></div>
								<div style="position: absolute; width: 4px; height: 4px; background: rgba(255,255,255,0.4); border-radius: 50%; left: 8px; top: calc(50% - 2px);"></div>
							</div>
							
							<!-- Greeting & Digital Time info -->
							<div>
								<h3 id="smart-greeting" style="margin: 0 0 5px 0; font-weight: 700; color: #ffffff; font-size: 20px;">System Loading...</h3>
								<div style="font-size: 26px; font-weight: 800; color: #ff6b00; font-family: monospace; display: flex; align-items: center; gap: 8px; margin-bottom: 2px;">
									<span id="digital-clock">00:00:00</span>
									<span style="font-size: 13px; background: rgba(255, 107, 0, 0.15); padding: 1px 6px; border-radius: 4px; border: 1px solid rgba(255,107,0,0.3);" id="time-ampm">PM</span>
								</div>
								<div style="color: var(--text-muted); font-size: 13px; font-weight: 500;" id="date-string">Friday, 19 June 2026</div>
							</div>
						</div>
						
						<!-- Smart AI System Automation Status block -->
						<div style="border-left: 1px dashed rgba(255,255,255,0.1); padding-left: 25px; min-width: 320px; display: flex; flex-direction: column; gap: 6px;">
							<div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted); letter-spacing: 1px; margin-bottom: 3px; display: flex; justify-content: space-between; align-items: center;">
								<span>🧠 Automated Smart Services</span>
								<span id="services-sync-indicator" style="font-size: 9px; color: var(--text-muted); text-transform: none; font-weight: normal;">polling...</span>
							</div>
							<div style="font-size: 13px; color: #ffffff; display: flex; align-items: center; justify-content: space-between; gap: 10px;">
								<span>Gate Controller Node:</span>
								<span id="gate-status" style="color: #3b82f6; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;"><span style="display:inline-block; width:6px; height:6px; background:#3b82f6; border-radius:50%;"></span> checking...</span>
							</div>
							<div style="font-size: 13px; color: #ffffff; display: flex; align-items: center; justify-content: space-between; gap: 10px;">
								<span>WhatsApp AI Daemon:</span>
								<span style="display: inline-flex; align-items: center; gap: 6px;">
									<span id="whatsapp-restart-container"></span>
									<span id="whatsapp-status" style="color: #3b82f6; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;"><span style="display:inline-block; width:6px; height:6px; background:#3b82f6; border-radius:50%;"></span> checking...</span>
								</span>
							</div>
							<div style="font-size: 13px; color: #ffffff; display: flex; align-items: center; justify-content: space-between; gap: 10px;">
								<span>Biometric Sync Gateway:</span>
								<span style="display: inline-flex; align-items: center; gap: 6px;">
									<span id="biometric-restart-container"></span>
									<span id="biometric-status" style="color: #3b82f6; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;"><span style="display:inline-block; width:6px; height:6px; background:#3b82f6; border-radius:50%;"></span> checking...</span>
								</span>
							</div>
							<div style="font-size: 13px; color: #ffffff; display: flex; align-items: center; justify-content: space-between; gap: 10px;">
								<span>Auto Expiry Auditing:</span>
								<span id="expiry-status" style="color: #3b82f6; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;"><span style="display:inline-block; width:6px; height:6px; background:#3b82f6; border-radius:50%;"></span> checking...</span>
							</div>
						</div>
						
					</div>
				</div>
			</div>

			<script>
			document.addEventListener("DOMContentLoaded", function() {
				const hrHand = document.getElementById("hour-hand");
				const minHand = document.getElementById("min-hand");
				const secHand = document.getElementById("sec-hand");
				const greetingEl = document.getElementById("smart-greeting");
				const digitalEl = document.getElementById("digital-clock");
				const ampmEl = document.getElementById("time-ampm");
				const dateEl = document.getElementById("date-string");
				
				function updateClock() {
					const now = new Date();
					const hrs = now.getHours();
					const mins = now.getMinutes();
					const secs = now.getSeconds();
					
					const hrRot = (hrs % 12) * 30 + mins / 2;
					const minRot = mins * 6 + secs / 10;
					const secRot = secs * 6;
					
					if (hrHand) hrHand.style.transform = `rotate(${hrRot}deg)`;
					if (minHand) minHand.style.transform = `rotate(${minRot}deg)`;
					if (secHand) secHand.style.transform = `rotate(${secRot}deg)`;
					
					const displayHrs = hrs % 12 === 0 ? 12 : hrs % 12;
					const formattedHrs = displayHrs.toString().padStart(2, '0');
					const formattedMins = mins.toString().padStart(2, '0');
					const formattedSecs = secs.toString().padStart(2, '0');
					
					if (digitalEl) digitalEl.textContent = `${formattedHrs}:${formattedMins}:${formattedSecs}`;
					if (ampmEl) ampmEl.textContent = hrs >= 12 ? 'PM' : 'AM';
					
					let greeting = "";
					const roleName = "<?php echo htmlspecialchars($_SESSION['full_name']); ?>";
					if (hrs >= 5 && hrs < 12) {
						greeting = `Good Morning, ${roleName}!`;
					} else if (hrs >= 12 && hrs < 17) {
						greeting = `Good Afternoon, ${roleName}!`;
					} else if (hrs >= 17 && hrs < 22) {
						greeting = `Good Evening, ${roleName}!`;
					} else {
						greeting = `Welcome Back, ${roleName}!`;
					}
					if (greetingEl) greetingEl.textContent = greeting;
					
					const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
					if (dateEl) dateEl.textContent = now.toLocaleDateString('en-US', options);
				}
				
				updateClock();
				setInterval(updateClock, 1000);

				// Global service restart handler
				window.restartService = function(service) {
					const container = document.getElementById(service + '-restart-container');
					const statusEl = document.getElementById(service + '-status');
					if (container) {
						container.innerHTML = `<span style="font-size: 11px; color: var(--warning); display: inline-flex; align-items: center; gap: 4px;"><i class="entypo-arrows-ccw" style="animation: spin 1s infinite linear;"></i> starting...</span>`;
					}
					fetch('../../api/restart_service.php?service=' + service)
					.then(r => r.json())
					.then(res => {
						if (res.success) {
							showToastNotification(res.message);
						} else {
							alert('Failed to start service: ' + res.message);
						}
						pollServicesStatus();
					})
					.catch(err => {
						alert('Network error trying to restart service.');
						pollServicesStatus();
					});
				};

				// Trigger daily backup silently on page load
				fetch('../../api/auto_backup.php')
				.then(r => r.json())
				.then(res => {
					updateBackupUI(res.details || null);
				})
				.catch(err => {
					console.error('Failed to trigger daily auto backup:', err);
				});

				window.triggerManualBackup = function() {
					const btn = document.getElementById('btn-manual-backup');
					const lastBackupInfo = document.getElementById('last-backup-info');
					if (btn) {
						btn.disabled = true;
						btn.innerHTML = `<i class="entypo-arrows-ccw" style="animation: spin 1s infinite linear;"></i> Exporting SQL Database & Sending Email...`;
					}
					if (lastBackupInfo) {
						lastBackupInfo.textContent = 'Generating database dump...';
					}

					fetch('../../api/auto_backup.php?force=1')
					.then(r => r.json())
					.then(res => {
						if (btn) {
							btn.disabled = false;
							btn.innerHTML = `<i class="entypo-arrows-ccw"></i> Generate Manual SQL Backup Now`;
						}
						if (res.success) {
							showToastNotification('Backup generated and sent to owner email!');
							updateBackupUI(res.details || null);
						} else {
							alert('Backup failed: ' + res.message);
						}
						pollServicesStatus();
					})
					.catch(err => {
						if (btn) {
							btn.disabled = false;
							btn.innerHTML = `<i class="entypo-arrows-ccw"></i> Generate Manual SQL Backup Now`;
						}
						alert('Network error executing backup.');
					});
				};

				function updateBackupUI(details) {
					const lastBackupInfo = document.getElementById('last-backup-info');
					if (!lastBackupInfo) return;

					if (details && details.status === 'success') {
						const emailSentText = details.email_sent ? '📧 Sent to Owner' : '❌ Email Failed';
						lastBackupInfo.innerHTML = `
							<span style="color: var(--success); font-weight: bold;">✔ Active (Safe)</span><br>
							<span style="font-size:12px; color:#cbd5e1; font-weight: normal;">
								Last: ${details.last_backup_date} ${details.time}<br>
								File: ${details.file} (${(details.size/1024).toFixed(1)} KB)<br>
								Status: ${emailSentText}
							</span>
						`;
					} else {
						lastBackupInfo.innerHTML = `
							<span style="color: var(--danger); font-weight: bold;">⚠ Failed or Pending</span><br>
							<span style="font-size:11px; color:#cbd5e1;">Please generate a backup dump manually.</span>
						`;
					}
				}

				function pollServicesStatus() {
					const syncInd = document.getElementById('services-sync-indicator');
					if (syncInd) syncInd.textContent = 'syncing...';

					fetch('../../api/get_services_status.php')
					.then(r => r.json())
					.then(res => {
						if (syncInd) syncInd.textContent = 'active';
						if (!res.success || !res.services) return;

						const s = res.services;

						// Gate
						const gateDot = `<span style="display:inline-block; width:6px; height:6px; background:${s.gate_controller.status === 'OFFLINE' ? '#ef4444' : '#10b981'}; border-radius:50%;"></span>`;
						document.getElementById('gate-status').innerHTML = `${gateDot} ${s.gate_controller.label}`;
						document.getElementById('gate-status').style.color = s.gate_controller.status === 'OFFLINE' ? '#ef4444' : '#10b981';

						// WhatsApp
						const waDot = `<span style="display:inline-block; width:6px; height:6px; background:${s.whatsapp.status === 'OFFLINE' ? '#ef4444' : (s.whatsapp.status === 'CONNECTED' ? '#10b981' : '#f59e0b')}; border-radius:50%;"></span>`;
						let waText = `${waDot} ${s.whatsapp.label}`;
						if (s.whatsapp.status === 'CONNECTED' && s.whatsapp.user) {
							waText += ` (Linked: ${s.whatsapp.user})`;
						}
						document.getElementById('whatsapp-status').innerHTML = waText;
						document.getElementById('whatsapp-status').style.color = s.whatsapp.status === 'OFFLINE' ? '#ef4444' : (s.whatsapp.status === 'CONNECTED' ? '#10b981' : '#f59e0b');
						
						const waBtnEl = document.getElementById('whatsapp-restart-container');
						if (s.whatsapp.status === 'OFFLINE') {
							waBtnEl.innerHTML = `<button onclick="restartService('whatsapp')" class="btn btn-xs btn-warning" style="font-size:9px; padding: 1px 4px; border-radius:3px; background:rgba(245,158,11,0.2); border:1px solid #f59e0b; color:#f59e0b; cursor:pointer;"><i class="entypo-arrows-ccw"></i> Start Daemon</button>`;
						} else if (s.whatsapp.status === 'QR_READY') {
							waBtnEl.innerHTML = `<a href="whatsapp_setup.php" class="btn btn-xs btn-info" style="font-size:9px; padding: 1px 4px; border-radius:3px; background:rgba(59,130,246,0.2); border:1px solid #3b82f6; color:#3b82f6;"><i class="entypo-popup"></i> Scan QR</a>`;
						} else {
							waBtnEl.innerHTML = '';
						}

						// Biometric
						const bioDot = `<span style="display:inline-block; width:6px; height:6px; background:${s.biometric_sync.status === 'OFFLINE' ? '#ef4444' : '#10b981'}; border-radius:50%;"></span>`;
						document.getElementById('biometric-status').innerHTML = `${bioDot} ${s.biometric_sync.label}`;
						document.getElementById('biometric-status').style.color = s.biometric_sync.status === 'OFFLINE' ? '#ef4444' : '#10b981';

						const bioBtnEl = document.getElementById('biometric-restart-container');
						if (s.biometric_sync.status === 'OFFLINE') {
							bioBtnEl.innerHTML = `<button onclick="restartService('biometric')" class="btn btn-xs btn-warning" style="font-size:9px; padding: 1px 4px; border-radius:3px; background:rgba(245,158,11,0.2); border:1px solid #f59e0b; color:#f59e0b; cursor:pointer;"><i class="entypo-arrows-ccw"></i> Start Gateway</button>`;
						} else {
							bioBtnEl.innerHTML = '';
						}

						// Expiry Audit
						const expDot = `<span style="display:inline-block; width:6px; height:6px; background:#10b981; border-radius:50%;"></span>`;
						document.getElementById('expiry-status').innerHTML = `${expDot} ${s.expiry_audit.label}`;
						document.getElementById('expiry-status').style.color = '#10b981';

						// Database backup summary
						updateBackupUI(s.database_backup);
					})
					.catch(err => {
						if (syncInd) syncInd.textContent = 'error';
						console.error('Failed to poll services status:', err);
					});
				}

				let audioCtx = null;
				let lastSeenLogId = null;

				function playBeep(type) {
					try {
						if (!audioCtx) {
							audioCtx = new (window.AudioContext || window.webkitAudioContext)();
						}
						if (audioCtx.state === 'suspended') {
							audioCtx.resume();
						}
						
						if (type === 'success') {
							// Double high beep
							let osc1 = audioCtx.createOscillator();
							let gain1 = audioCtx.createGain();
							osc1.type = 'sine';
							osc1.frequency.setValueAtTime(880, audioCtx.currentTime);
							gain1.gain.setValueAtTime(0, audioCtx.currentTime);
							gain1.gain.linearRampToValueAtTime(0.15, audioCtx.currentTime + 0.02);
							gain1.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.15);
							
							osc1.connect(gain1);
							gain1.connect(audioCtx.destination);
							osc1.start();
							osc1.stop(audioCtx.currentTime + 0.15);
							
							setTimeout(() => {
								let osc2 = audioCtx.createOscillator();
								let gain2 = audioCtx.createGain();
								osc2.type = 'sine';
								osc2.frequency.setValueAtTime(1046.5, audioCtx.currentTime);
								gain2.gain.setValueAtTime(0, audioCtx.currentTime);
								gain2.gain.linearRampToValueAtTime(0.15, audioCtx.currentTime + 0.02);
								gain2.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.2);
								
								osc2.connect(gain2);
								gain2.connect(audioCtx.destination);
								osc2.start();
								osc2.stop(audioCtx.currentTime + 0.2);
							}, 120);
						} else if (type === 'failed') {
							// Buzz sound
							let osc = audioCtx.createOscillator();
							let gain = audioCtx.createGain();
							osc.type = 'sawtooth';
							osc.frequency.setValueAtTime(120, audioCtx.currentTime);
							gain.gain.setValueAtTime(0, audioCtx.currentTime);
							gain.gain.linearRampToValueAtTime(0.2, audioCtx.currentTime + 0.05);
							gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.6);
							
							osc.connect(gain);
							gain.connect(audioCtx.destination);
							osc.start();
							osc.stop(audioCtx.currentTime + 0.6);
						}
					} catch (e) {
						console.error("Web Audio API warning/error:", e);
					}
				}

				function showGateSuccessBanner(name, type) {
					const container = document.getElementById('gate-alert-container');
					if (!container) return;
					
					const banner = document.createElement('div');
					banner.style.cssText = `
						background: rgba(16, 185, 129, 0.12);
						backdrop-filter: blur(16px);
						-webkit-backdrop-filter: blur(16px);
						border: 1px solid rgba(16, 185, 129, 0.35);
						border-left: 6px solid #10b981;
						border-radius: 12px;
						padding: 16px 20px;
						margin-bottom: 12px;
						box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3), 0 8px 10px -6px rgba(0, 0, 0, 0.3);
						display: flex;
						align-items: center;
						justify-content: space-between;
						color: #ffffff;
						animation: slideInRight 0.4s cubic-bezier(0.16, 1, 0.3, 1);
						position: relative;
						overflow: hidden;
					`;
					
					banner.innerHTML = `
						<div style="display: flex; align-items: center; gap: 15px;">
							<div style="background: rgba(16, 185, 129, 0.2); width: 42px; height: 42px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 1px solid rgba(16, 185, 129, 0.4);">
								<span style="font-size: 20px; color: #10b981;">✓</span>
							</div>
							<div>
								<div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #10b981; letter-spacing: 1px;">Access Granted</div>
								<div style="font-size: 16px; font-weight: 700; color: #ffffff; margin-top: 2px;">${name}</div>
								<div style="font-size: 12px; color: #94a3b8; margin-top: 2px;">Action: <span style="color: #ffffff; font-weight: 600;">${type === 'check-in' || type === 'Check-In' ? 'Check-In' : 'Check-Out'}</span></div>
							</div>
						</div>
						<button onclick="this.parentElement.remove()" style="background: none; border: none; color: #94a3b8; font-size: 20px; cursor: pointer; padding: 0 5px; transition: color 0.2s;">&times;</button>
					`;
					
					const closeBtn = banner.querySelector('button');
					closeBtn.onmouseover = () => closeBtn.style.color = '#ffffff';
					closeBtn.onmouseout = () => closeBtn.style.color = '#94a3b8';
					
					container.appendChild(banner);
					
					setTimeout(() => {
						banner.style.animation = 'fadeOutUp 0.4s forwards';
						setTimeout(() => banner.remove(), 400);
					}, 6000);
				}

				function showGateAlertBanner(name, reason) {
					const container = document.getElementById('gate-alert-container');
					if (!container) return;
					
					const banner = document.createElement('div');
					banner.style.cssText = `
						background: rgba(239, 68, 68, 0.15);
						backdrop-filter: blur(16px);
						-webkit-backdrop-filter: blur(16px);
						border: 1px solid rgba(239, 68, 68, 0.4);
						border-left: 6px solid #ef4444;
						border-radius: 12px;
						padding: 16px 20px;
						margin-bottom: 12px;
						box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3), 0 8px 10px -6px rgba(0, 0, 0, 0.3);
						display: flex;
						align-items: center;
						justify-content: space-between;
						color: #ffffff;
						animation: shakeSlideIn 0.5s ease-out;
						position: relative;
						overflow: hidden;
					`;
					
					banner.innerHTML = `
						<div style="display: flex; align-items: center; gap: 15px;">
							<div style="background: rgba(239, 68, 68, 0.25); width: 42px; height: 42px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 1px solid rgba(239, 68, 68, 0.5); animation: pulseAlert 1.5s infinite alternate;">
								<span style="font-size: 20px; color: #ef4444;">⚠️</span>
							</div>
							<div>
								<div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #ef4444; letter-spacing: 1px;">Access Denied / Biometric Alert</div>
								<div style="font-size: 16px; font-weight: 700; color: #ffffff; margin-top: 2px;">${name}</div>
								<div style="font-size: 12px; color: #f87171; margin-top: 2px; font-weight: 600;">Reason: ${reason}</div>
							</div>
						</div>
						<button onclick="this.parentElement.remove()" style="background: none; border: none; color: #94a3b8; font-size: 20px; cursor: pointer; padding: 0 5px; transition: color 0.2s;">&times;</button>
					`;
					
					const closeBtn = banner.querySelector('button');
					closeBtn.onmouseover = () => closeBtn.style.color = '#ffffff';
					closeBtn.onmouseout = () => closeBtn.style.color = '#94a3b8';
					
					container.appendChild(banner);
					
					setTimeout(() => {
						banner.style.animation = 'fadeOutUp 0.4s forwards';
						setTimeout(() => banner.remove(), 400);
					}, 8000);
				}

				// Live Punch Feed fetcher
				function pollLiveAttendance() {
					fetch('../../api/get_latest_attendance.php')
					.then(r => r.json())
					.then(res => {
						if (!res.success || !res.logs) return;
						const feed = document.getElementById('live-attendance-feed');
						if (!feed) return;

						if (res.logs.length === 0) {
							feed.innerHTML = `<tr><td colspan="5" style="padding: 20px; text-align: center; color: var(--text-muted);">No attendance scans logged in database.</td></tr>`;
							lastSeenLogId = 0;
							return;
						}

						// Detect new logs if we already have a baseline
						if (lastSeenLogId !== null) {
							const newLogs = res.logs
								.filter(log => log.log_id > lastSeenLogId)
								.sort((a, b) => a.log_id - b.log_id);
							
							newLogs.forEach(log => {
								if (log.status === 'success') {
									playBeep('success');
									showGateSuccessBanner(log.name, log.type);
								} else {
									playBeep('failed');
									showGateAlertBanner(log.name, log.error_reason || 'Access Blocked');
								}
							});
						}

						// Update highest seen log ID
						const maxId = Math.max(...res.logs.map(l => l.log_id));
						lastSeenLogId = maxId;

						let html = '';
						res.logs.forEach(log => {
							let typeBadge = '';
							if (log.status === 'failed') {
								typeBadge = `<span style="background: rgba(239, 68, 68, 0.15); border: 1px solid var(--danger); color: var(--danger); font-size:10px; font-weight:bold; padding: 2px 8px; border-radius:10px;">Blocked: ${log.error_reason}</span>`;
							} else {
								typeBadge = log.type === 'check-in' || log.type === 'Check-In' 
									? `<span style="background: rgba(16, 185, 129, 0.15); border: 1px solid var(--success); color: var(--success); font-size:10px; font-weight:bold; padding: 2px 8px; border-radius:10px;">Check-In</span>`
									: `<span style="background: rgba(255, 107, 0, 0.15); border: 1px solid var(--accent-primary); color: var(--accent-primary); font-size:10px; font-weight:bold; padding: 2px 8px; border-radius:10px;">Check-Out</span>`;
							}
							
							html += `
								<tr style="border-bottom: 1px solid rgba(255,255,255,0.04); font-size: 13px;">
									<td style="padding: 10px 12px; font-weight: 600; color: #ffffff;">${log.name}</td>
									<td style="padding: 10px 12px; font-family: monospace; font-size:12px;">${log.biometric_id}</td>
									<td style="padding: 10px 12px; color: var(--text-muted);">${log.date}</td>
									<td style="padding: 10px 12px; color: #ff6b00; font-family: monospace; font-weight:bold;">${log.time}</td>
									<td style="padding: 10px 12px;">${typeBadge}</td>
								</tr>
							`;
						});
						feed.innerHTML = html;
					})
					.catch(err => {
						console.error('Failed to retrieve live attendance punches:', err);
					});
				}

				// Toast Notification element creator
				function showToastNotification(message) {
					let toast = document.getElementById('custom_toast_alert');
					if (!toast) {
						toast = document.createElement('div');
						toast.id = 'custom_toast_alert';
						toast.style.cssText = 'position: fixed; bottom: 25px; right: 25px; background: rgba(255, 107, 0, 0.95); border: 1px solid #ff6b00; color: white; padding: 12px 24px; border-radius: 10px; font-weight: 600; box-shadow: 0 4px 15px rgba(0,0,0,0.35); z-index: 9999; transition: all 0.3s ease; opacity: 0; transform: translateY(10px);';
						document.body.appendChild(toast);
					}
					toast.innerText = message;
					toast.style.opacity = '1';
					toast.style.transform = 'translateY(0)';
					setTimeout(() => {
						toast.style.opacity = '0';
						toast.style.transform = 'translateY(10px)';
					}, 3000);
				}

				// CSS Spin Keyframes & Slide in alerts inject dynamically
				const style = document.createElement('style');
				style.innerHTML = `
					@keyframes spin {
						0% { transform: rotate(0deg); }
						100% { transform: rotate(360deg); }
					}
					@keyframes slideInRight {
						from { opacity: 0; transform: translateX(120px); }
						to { opacity: 1; transform: translateX(0); }
					}
					@keyframes fadeOutUp {
						from { opacity: 1; transform: translateY(0); }
						to { opacity: 0; transform: translateY(-20px); }
					}
					@keyframes shakeSlideIn {
						0% { opacity: 0; transform: translateX(120px); }
						50% { opacity: 0.8; transform: translateX(-10px); }
						75% { transform: translateX(5px); }
						100% { opacity: 1; transform: translateX(0); }
					}
					@keyframes pulseAlert {
						from { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.5); }
						to { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
					}
				`;
				document.head.appendChild(style);

				// Start timers
				pollServicesStatus();
				pollLiveAttendance();
				setInterval(pollServicesStatus, 10000); // Poll services status every 10s
				setInterval(pollLiveAttendance, 3000);   // Poll live attendance logs every 3s
			});
			</script>

			<?php if (isset($_SESSION['rem_msg'])): ?>
				<div class="alert alert-<?php echo $_SESSION['rem_status'] === 'success' ? 'success' : 'danger'; ?>" style="padding: 15px; border-radius: 8px; margin-bottom: 20px; background: <?php echo $_SESSION['rem_status'] === 'success' ? 'rgba(16, 185, 129, 0.15)' : 'rgba(239, 68, 68, 0.15)'; ?>; border: 1px solid <?php echo $_SESSION['rem_status'] === 'success' ? 'var(--success)' : 'var(--danger)'; ?>; color: #ffffff;">
					<strong><?php echo $_SESSION['rem_status'] === 'success' ? 'Success!' : 'Error!'; ?></strong> <?php echo $_SESSION['rem_msg']; ?>
				</div>
				<?php 
				unset($_SESSION['rem_msg']);
				unset($_SESSION['rem_status']);
				?>
			<?php endif; ?>

			<!-- Live Capacity Speedometer -->
			<?php include 'speedometer_widget.php'; ?>
			
			<div class="row">
				<div class="col-sm-3"><a href="revenue_month.php">			
				<div class="tile-stats tile-red">
					<div class="icon"><i class="entypo-credit-card"></i></div>
						<div class="num" data-postfix="" data-duration="1500" data-delay="0">
						<h2>Paid Income This Month</h2><br>	
						<?php
							date_default_timezone_set("Asia/Calcutta"); 
							$date  = $_SESSION['working_year'] . '-' . date('m');
							$query = "select * from enrolls_to WHERE  paid_date LIKE '$date%'";

							//echo $query;
							$result  = mysqli_query($con, $query);
							$revenue = 0;
							if ($result && mysqli_num_rows($result) > 0) {
							    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
							        if (isset($row['paid_amount']) && $row['paid_amount'] !== null && $row['paid_amount'] !== '') {
							            $revenue += intval($row['paid_amount']);
							        } else {
							            // Fallback for old records without paid_amount
							            $query1="select * from plan where pid='".$row['pid']."'";
							            $result1=mysqli_query($con,$query1);
							            if($result1 && mysqli_num_rows($result1) > 0){
							                $value=mysqli_fetch_row($result1);
							                $discount = isset($row['discount_amount']) ? intval($row['discount_amount']) : 0;
							                $revenue += (intval($value[4]) - $discount);
							            }
							        }
							    }
							}
							echo "₹".$revenue;
							?>
						</div>
				</div></a>
			</div>
			

			<div class="col-sm-3"><a href="table_view.php">			
				<div class="tile-stats tile-green">
					<div class="icon"><i class="entypo-users"></i></div>
					<div class="num" data-postfix="" data-duration="1500" data-delay="0">
						<h2>Total <br>Members</h2><br>	
							<?php
							$query = "select COUNT(*) from users WHERE YEAR(joining_date) <= " . $_SESSION['working_year'];

							$result = mysqli_query($con, $query);
							$i      = 1;
							if ($result && mysqli_num_rows($result) > 0) {
							    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
							        echo $row['COUNT(*)'];
							    }
							}
							$i = 1;
							?>
						</div>
				</div></a>
			</div>	
				
			<div class="col-sm-3"><a href="over_members_month.php">			
				<div class="tile-stats tile-aqua">
					<div class="icon"><i class="entypo-user-add"></i></div>
					<div class="num" data-postfix="" data-duration="1500" data-delay="0">
						<h2>Joined This Month</h2><br>	
							<?php
							date_default_timezone_set("Asia/Calcutta"); 
							$date  = $_SESSION['working_year'] . '-' . date('m');
							$query = "select COUNT(*) from users WHERE joining_date LIKE '$date%'";

							//echo $query;
							$result = mysqli_query($con, $query);
							$i      = 1;
							if ($result && mysqli_num_rows($result) > 0) {
							    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
							        echo $row['COUNT(*)'];
							    }
							}
							$i = 1;
							?>
						</div>
				</div></a>			
			</div>

			<div class="col-sm-3"><a href="view_plan.php">			
				<div class="tile-stats tile-blue">
					<div class="icon"><i class="entypo-clipboard"></i></div>
						<div class="num" data-postfix="" data-duration="1500" data-delay="0">
						<h2>Total Plan Available</h2><br>	
							<?php
							$query = "select COUNT(*) from plan where active='yes'";

							//echo $query;
							$result  = mysqli_query($con, $query);
							$i = 1;
							if ($result && mysqli_num_rows($result) > 0) {
							    while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
							        echo $row['COUNT(*)'];
							    }
							}
							$i = 1;
							?>
						</div>
				</div></a>
			</div>
			
			</div>
			<!-- End of Metric Tiles Row -->
			
			<!-- Monthly Leaderboard Section -->
			<?php include 'leaderboard_widget.php'; ?>
			
			<!-- Monthly Profitability Report Section -->
			<?php
			$current_month_str = $_SESSION['working_year'] . '-' . date('m');

			// 1. Membership Revenue
			$mem_rev_query = "SELECT e.paid_amount, e.discount_amount, p.amount FROM enrolls_to e 
			                  INNER JOIN plan p ON e.pid = p.pid 
			                  WHERE e.paid_date LIKE '$current_month_str%'";
			$mem_rev_res = mysqli_query($con, $mem_rev_query);
			$membership_revenue = 0;
			if ($mem_rev_res) {
				while ($m_row = mysqli_fetch_assoc($mem_rev_res)) {
					if ($m_row['paid_amount'] !== null) {
						$membership_revenue += intval($m_row['paid_amount']);
					} else {
						$membership_revenue += (intval($m_row['amount']) - intval($m_row['discount_amount']));
					}
				}
			}

			// 2. PT Revenue
			$pt_rev_query = "SELECT SUM(amount) AS total FROM pt_enrollments WHERE enroll_date LIKE '$current_month_str%'";
			$pt_rev_res = mysqli_query($con, $pt_rev_query);
			$pt_revenue = 0;
			if ($pt_rev_res && $pt_row = mysqli_fetch_assoc($pt_rev_res)) {
				$pt_revenue = isset($pt_row['total']) ? intval($pt_row['total']) : 0;
			}

			// 3. Expenses
			$exp_query = "SELECT SUM(amount) AS total FROM expenses WHERE expense_date LIKE '$current_month_str%'";
			$exp_res = mysqli_query($con, $exp_query);
			$expenses_total = 0;
			if ($exp_res && $e_row = mysqli_fetch_assoc($exp_res)) {
				$expenses_total = isset($e_row['total']) ? intval($e_row['total']) : 0;
			}

			$total_collections = $membership_revenue + $pt_revenue;
			$net_profit = $total_collections - $expenses_total;
			$is_profitable = $net_profit >= 0;

			// Attendance over the last 7 days
			$att_trend_labels = [];
			$att_trend_data = [];
			for ($i = 6; $i >= 0; $i--) {
				$d_str = date('Y-m-d', strtotime("-$i days"));
				$d_label = date('M d', strtotime("-$i days"));
				$att_q = mysqli_query($con, "SELECT COUNT(*) as count FROM attendance WHERE date = '$d_str'");
				$att_count = 0;
				if ($att_q && $att_row = mysqli_fetch_assoc($att_q)) {
					$att_count = intval($att_row['count']);
				}
				$att_trend_labels[] = $d_label;
				$att_trend_data[] = $att_count;
			}

			// 6-Month Collections Breakdown
			$rev_months_labels = [];
			$membership_rev_trend = [];
			$pt_rev_trend = [];
			for ($i = 5; $i >= 0; $i--) {
				$target_month = date('Y-m', strtotime("-$i months"));
				$month_label = date('F', strtotime("-$i months"));
				
				// Membership
				$m_rev_q = mysqli_query($con, "SELECT e.paid_amount, e.discount_amount, p.amount FROM enrolls_to e 
											   INNER JOIN plan p ON e.pid = p.pid 
											   WHERE e.paid_date LIKE '$target_month%'");
				$m_rev = 0;
				if ($m_rev_q) {
					while ($m_row = mysqli_fetch_assoc($m_rev_q)) {
						if ($m_row['paid_amount'] !== null) {
							$m_rev += intval($m_row['paid_amount']);
						} else {
							$m_rev += (intval($m_row['amount']) - intval($m_row['discount_amount']));
						}
					}
				}
				
				// PT
				$p_rev_q = mysqli_query($con, "SELECT SUM(amount) AS total FROM pt_enrollments WHERE enroll_date LIKE '$target_month%'");
				$p_rev = 0;
				if ($p_rev_q && $p_row = mysqli_fetch_assoc($p_rev_q)) {
					$p_rev = isset($p_row['total']) ? intval($p_row['total']) : 0;
				}
				
				$rev_months_labels[] = $month_label;
				$membership_rev_trend[] = $m_rev;
				$pt_rev_trend[] = $p_rev;
			}
			?>

			<!-- Visual Analytics Section -->
			<div class="row" style="margin-top: 30px; margin-left: 0; margin-right: 0;">
				<div class="col-md-12" style="padding: 0;">
					<div class="portal-card" style="background: var(--glass-bg); border: 1px solid var(--glass-border); border-radius: 20px; padding: 25px; box-shadow: var(--glass-shadow); color: #ffffff;">
						<h3 style="margin: 0 0 20px 0; color: #ffffff; font-weight: 700; display: flex; align-items: center; gap: 10px;">
							<i class="entypo-chart-bar" style="color: var(--accent-primary);"></i> Gym Analytics &amp; Visual Insights
						</h3>
						
						<div class="row">
							<!-- Daily Attendance Chart -->
							<div class="col-md-6" style="margin-bottom: 20px;">
								<div style="background: rgba(255, 255, 255, 0.02); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 12px; padding: 15px;">
									<h4 style="margin: 0 0 15px 0; font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase;">Daily Attendance Scan Frequencies (Last 7 Days)</h4>
									<div style="height: 220px; position: relative;">
										<canvas id="attendanceChart"></canvas>
									</div>
								</div>
							</div>
							
							<!-- Revenue Trend Chart -->
							<div class="col-md-6" style="margin-bottom: 20px;">
								<div style="background: rgba(255, 255, 255, 0.02); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 12px; padding: 15px;">
									<h4 style="margin: 0 0 15px 0; font-size: 11px; font-weight: 600; color: var(--text-muted); text-transform: uppercase;">Monthly Revenue Splits: Membership vs PT (Last 6 Months)</h4>
									<div style="height: 220px; position: relative;">
										<canvas id="revenueChart"></canvas>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>

			<script>
			document.addEventListener("DOMContentLoaded", function() {
				// Chart data from PHP
				const attLabels = <?php echo json_encode($att_trend_labels); ?>;
				const attData = <?php echo json_encode($att_trend_data); ?>;
				const revLabels = <?php echo json_encode($rev_months_labels); ?>;
				const memRevData = <?php echo json_encode($membership_rev_trend); ?>;
				const ptRevData = <?php echo json_encode($pt_rev_trend); ?>;

				// 1. Attendance Chart
				const ctxAtt = document.getElementById('attendanceChart').getContext('2d');
				
				// Create neon gradient
				const gradientAtt = ctxAtt.createLinearGradient(0, 0, 0, 400);
				gradientAtt.addColorStop(0, 'rgba(6, 182, 212, 0.5)'); // Neon Cyan
				gradientAtt.addColorStop(1, 'rgba(6, 182, 212, 0.0)');

				new Chart(ctxAtt, {
					type: 'line',
					data: {
						labels: attLabels,
						datasets: [{
							label: 'Check-ins',
							data: attData,
							borderColor: '#06b6d4',
							backgroundColor: gradientAtt,
							borderWidth: 3,
							fill: true,
							tension: 0.4,
							pointBackgroundColor: '#0b0c10',
							pointBorderColor: '#06b6d4',
							pointHoverBackgroundColor: '#06b6d4',
							pointHoverBorderColor: '#ffffff',
							pointRadius: 5,
							pointHoverRadius: 8,
                            pointBorderWidth: 2
						}]
					},
					options: {
						responsive: true,
						maintainAspectRatio: false,
						plugins: {
							legend: { display: false }
						},
						scales: {
							x: {
								grid: { display: false },
								ticks: { color: '#94a3b8' }
							},
							y: {
								grid: { color: 'rgba(255, 255, 255, 0.05)' },
								ticks: { 
									color: '#94a3b8',
									stepSize: 1,
									precision: 0
								},
								beginAtZero: true
							}
						}
					}
				});

				// 2. Revenue Splits Chart
				const ctxRev = document.getElementById('revenueChart').getContext('2d');
				
				// Create neon gradients
				const gradientMem = ctxRev.createLinearGradient(0, 0, 0, 400);
				gradientMem.addColorStop(0, 'rgba(255, 107, 0, 0.8)'); // Neon Orange
				gradientMem.addColorStop(1, 'rgba(255, 107, 0, 0.2)');

				const gradientPt = ctxRev.createLinearGradient(0, 0, 0, 400);
				gradientPt.addColorStop(0, 'rgba(16, 185, 129, 0.8)'); // Neon Green
				gradientPt.addColorStop(1, 'rgba(16, 185, 129, 0.2)');

				new Chart(ctxRev, {
					type: 'bar',
					data: {
						labels: revLabels,
						datasets: [
							{
								label: 'Membership Plans',
								data: memRevData,
								backgroundColor: gradientMem,
								borderColor: '#ff6b00',
								borderWidth: 1,
								borderRadius: 6
							},
							{
								label: 'Personal Training (PT)',
								data: ptRevData,
								backgroundColor: gradientPt,
								borderColor: '#10b981',
								borderWidth: 1,
								borderRadius: 6
							}
						]
					},
					options: {
						responsive: true,
						maintainAspectRatio: false,
						plugins: {
							legend: {
								position: 'top',
								labels: { color: '#ffffff', boxWidth: 12 }
							}
						},
						scales: {
							x: {
								grid: { display: false },
								ticks: { color: '#94a3b8' }
							},
							y: {
								grid: { color: 'rgba(255, 255, 255, 0.05)' },
								ticks: { 
									color: '#94a3b8',
									callback: function(value) { return '₹' + value.toLocaleString(); }
								},
								beginAtZero: true
							}
						}
					}
				});
			});
			</script>

			<div class="row" style="margin-top: 30px; clear: both; margin-left: 0; margin-right: 0;">
				<div class="col-md-12" style="padding: 0;">
					<div class="portal-card" style="background: var(--glass-bg); border: 1px solid var(--glass-border); border-radius: 20px; padding: 25px; box-shadow: var(--glass-shadow); margin-bottom: 30px; color: #ffffff;">
						<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
							<h3 style="margin: 0; color: #ffffff; font-weight: 700; display: flex; align-items: center; gap: 10px;">
								<i class="entypo-chart-line" style="color: var(--accent-primary);"></i> Monthly Profitability Report (<?php echo date('F Y'); ?>)
							</h3>
							<a href="expenses.php" class="btn btn-xs btn-info" style="background: rgba(59, 130, 246, 0.15); border: 1px solid var(--info); color: var(--info); border-radius: 4px; padding: 5px 12px; font-weight: 600; text-decoration: none;">
								<i class="entypo-book-open"></i> Manage Expenses
							</a>
						</div>

						<div class="row" style="display: flex; flex-wrap: wrap; gap: 20px; justify-content: space-between;">
							<!-- Membership Revenue Card -->
							<div style="flex: 1; min-width: 200px; background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 12px; padding: 15px; text-align: center;">
								<span style="font-size: 11px; text-transform: uppercase; color: var(--text-muted); font-weight: 700; letter-spacing: 0.5px;">Membership Plan Revenue</span>
								<h4 style="margin: 8px 0 0 0; font-size: 22px; font-weight: 700; color: #10b981;">+₹<?php echo number_format($membership_revenue); ?></h4>
							</div>

							<!-- PT Collections Card -->
							<div style="flex: 1; min-width: 200px; background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 12px; padding: 15px; text-align: center;">
								<span style="font-size: 11px; text-transform: uppercase; color: var(--text-muted); font-weight: 700; letter-spacing: 0.5px;">Personal Training (PT)</span>
								<h4 style="margin: 8px 0 0 0; font-size: 22px; font-weight: 700; color: #10b981;">+₹<?php echo number_format($pt_revenue); ?></h4>
							</div>

							<!-- Total Expenses Card -->
							<div style="flex: 1; min-width: 200px; background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 12px; padding: 15px; text-align: center;">
								<span style="font-size: 11px; text-transform: uppercase; color: var(--text-muted); font-weight: 700; letter-spacing: 0.5px;">Logged Gym Expenses</span>
								<h4 style="margin: 8px 0 0 0; font-size: 22px; font-weight: 700; color: #ef4444;">-₹<?php echo number_format($expenses_total); ?></h4>
							</div>

							<!-- Net Profitability Card -->
							<div style="flex: 1.2; min-width: 240px; background: <?php echo $is_profitable ? 'linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(16, 185, 129, 0.02) 100%)' : 'linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(239, 68, 68, 0.02) 100%)'; ?>; border: 1px solid <?php echo $is_profitable ? 'rgba(16, 185, 129, 0.25)' : 'rgba(239, 68, 68, 0.25)'; ?>; border-radius: 12px; padding: 15px; text-align: center;">
								<span style="font-size: 11px; text-transform: uppercase; color: <?php echo $is_profitable ? '#10b981' : '#ef4444'; ?>; font-weight: 700; letter-spacing: 0.5px;">
									<?php echo $is_profitable ? 'Net Monthly Profit' : 'Net Monthly Loss'; ?>
								</span>
								<h4 style="margin: 8px 0 0 0; font-size: 24px; font-weight: 800; color: <?php echo $is_profitable ? '#10b981' : '#ef4444'; ?>;">
									<?php echo ($is_profitable ? '₹' : '-₹') . number_format(abs($net_profit)); ?>
								</h4>
							</div>
						</div>
					</div>
				</div>
			</div>

			<div class="row" style="margin-top: 30px; clear: both;">
				<div class="col-md-12">
					<div class="portal-card" style="background: var(--glass-bg); border: 1px solid var(--glass-border); border-radius: 20px; padding: 25px; box-shadow: var(--glass-shadow); margin-bottom: 30px;">
						<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
							<h3 style="margin: 0; color: #ffffff; font-weight: 700; display: flex; align-items: center; gap: 10px;">
								<i class="entypo-alert" style="color: var(--accent-primary);"></i> Membership Expiry &amp; Alerts
							</h3>
							<span class="status-badge" style="background: rgba(255, 107, 0, 0.15); color: var(--accent-primary); border-color: var(--accent-primary); padding: 2px 10px; border: 1px solid var(--accent-primary); border-radius: 20px; font-size: 12px; font-weight: 600;">
								Alert Threshold: &le; 5 Days
							</span>
						</div>
						
						<div class="table-responsive">
							<table class="table" style="width: 100%; border-collapse: collapse; color: var(--text-main);">
								<thead>
									<tr style="border-bottom: 2px solid rgba(255,255,255,0.08); text-align: left;">
										<th style="padding: 12px 15px; color: var(--text-muted); font-weight: 600;">Member ID</th>
										<th style="padding: 12px 15px; color: var(--text-muted); font-weight: 600;">Member Name</th>
										<th style="padding: 12px 15px; color: var(--text-muted); font-weight: 600;">Plan</th>
										<th style="padding: 12px 15px; color: var(--text-muted); font-weight: 600;">Expiry Date</th>
										<th style="padding: 12px 15px; color: var(--text-muted); font-weight: 600;">Days Left</th>
										<th style="padding: 12px 15px; color: var(--text-muted); font-weight: 600; text-align: right;">Actions</th>
									</tr>
								</thead>
								<tbody>
									<?php
									$alert_count = 0;
									$q_alerts = "SELECT e.*, u.username, u.mobile, u.email, p.planName 
												 FROM enrolls_to e 
												 INNER JOIN users u ON e.uid = u.userid 
												 INNER JOIN plan p ON e.pid = p.pid 
												 WHERE e.renewal = 'yes'
												 ORDER BY e.expire ASC";
									$res_alerts = mysqli_query($con, $q_alerts);
									if ($res_alerts && mysqli_num_rows($res_alerts) > 0) {
										$today = new DateTime(date('Y-m-d'));
										while ($row = mysqli_fetch_assoc($res_alerts)) {
											$expire_dt = new DateTime($row['expire']);
											$diff = $today->diff($expire_dt);
											$days = (int)$diff->format('%r%a');
											
											// Show only if expired or expiring in <= 5 days
											if ($days <= 5) {
												$alert_count++;
												
												// Status Badge styling
												if ($days < 0) {
													$badge_bg = 'rgba(239, 68, 68, 0.15)';
													$badge_color = 'var(--danger)';
													$badge_border = 'var(--danger)';
													$days_text = 'Expired (' . abs($days) . ' days ago)';
												} elseif ($days == 0) {
													$badge_bg = 'rgba(239, 68, 68, 0.15)';
													$badge_color = 'var(--danger)';
													$badge_border = 'var(--danger)';
													$days_text = 'Expires Today';
												} elseif ($days == 1) {
													$badge_bg = 'rgba(255, 107, 0, 0.15)';
													$badge_color = 'var(--accent-primary)';
													$badge_border = 'var(--accent-primary)';
													$days_text = '1 day left';
												} else {
													$badge_bg = 'rgba(245, 158, 11, 0.15)';
													$badge_color = 'var(--warning)';
													$badge_border = 'var(--warning)';
													$days_text = $days . ' days left';
												}
												
												// WhatsApp formatting
												$wa_mobile = preg_replace('/[^0-9]/', '', $row['mobile']);
												if (strlen($wa_mobile) === 10) {
													$wa_mobile = '91' . $wa_mobile;
												}
												$wa_msg = rawurlencode("Hi " . $row['username'] . ",\nThis is a friendly reminder from Sudarshan Fitness Khamgaon. Your membership (" . $row['planName'] . ") " . ($days < 0 ? "expired on " . $row['expire'] : "is expiring on " . $row['expire']) . ".\n\nPlease visit the reception to renew your membership.\nThank you!");
												$wa_link = "https://wa.me/" . $wa_mobile . "?text=" . $wa_msg;
												?>
												<tr style="border-bottom: 1px solid rgba(255,255,255,0.05); transition: background 0.2s;">
													<td style="padding: 12px 15px; font-family: monospace;"><?php echo htmlspecialchars($row['uid']); ?></td>
													<td style="padding: 12px 15px; font-weight: 600; color: #ffffff;"><?php echo htmlspecialchars($row['username']); ?></td>
													<td style="padding: 12px 15px;"><?php echo htmlspecialchars($row['planName']); ?></td>
													<td style="padding: 12px 15px; color: var(--text-muted);"><?php echo htmlspecialchars($row['expire']); ?></td>
													<td style="padding: 12px 15px;">
														<span style="display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: bold; border: 1px solid <?php echo $badge_border; ?>; background: <?php echo $badge_bg; ?>; color: <?php echo $badge_color; ?>;">
															<?php echo $days_text; ?>
														</span>
													</td>
													<td style="padding: 12px 15px; text-align: right;">
														<div style="display: inline-flex; gap: 8px;">
															<!-- Send Email -->
															<a href="?send_reminder=1&amp;uid=<?php echo urlencode($row['uid']); ?>" 
															   class="btn btn-xs btn-info" 
															   title="Send Email Reminder"
															   style="background: rgba(59, 130, 246, 0.15); border: 1px solid var(--info); color: var(--info); border-radius: 4px; padding: 4px 8px; display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s; text-decoration: none;">
																<i class="entypo-mail" style="margin: 0; font-size: 14px;"></i>
															</a>
															<!-- Send WhatsApp -->
															<a href="<?php echo $wa_link; ?>" 
															   target="_blank" 
															   class="btn btn-xs btn-success" 
															   title="Send WhatsApp Reminder"
															   style="background: rgba(16, 185, 129, 0.15); border: 1px solid var(--success); color: var(--success); border-radius: 4px; padding: 4px 8px; display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s; text-decoration: none;">
																<i class="entypo-phone" style="margin: 0; font-size: 14px;"></i>
															</a>
															<!-- Renew -->
															<a href="make_payments.php?id=<?php echo urlencode($row['uid']); ?>" 
															   class="btn btn-xs btn-warning" 
															   title="Process Renewal Payment"
															   style="background: rgba(255, 107, 0, 0.15); border: 1px solid var(--accent-primary); color: var(--accent-primary); border-radius: 4px; padding: 4px 8px; display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s; text-decoration: none;">
																<i class="entypo-star" style="margin: 0; font-size: 14px;"></i>
															</a>
														</div>
													</td>
												</tr>
												<?php
											}
										}
									}
									if ($alert_count === 0) {
										?>
										<tr>
											<td colspan="6" style="padding: 25px; text-align: center; color: var(--text-muted);">
												<i class="entypo-check" style="font-size: 24px; color: var(--success); display: block; margin-bottom: 10px;"></i>
												All memberships are currently active and healthy. No expiries in the next 5 days!
											</td>
										</tr>
										<?php
									}
									?>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>

			<!-- Personal Training Dashboard Sections -->
			<div class="row" style="margin-top: 20px;">
				<div class="col-md-12">
					<?php if ($_SESSION['role'] === 'trainer'): ?>
						<!-- Trainer View: My Assigned Clients -->
						<div class="portal-card" style="background: var(--glass-bg); border: 1px solid var(--glass-border); border-radius: 20px; padding: 25px; box-shadow: var(--glass-shadow); margin-bottom: 30px;">
							<h3 style="margin: 0 0 20px 0; color: #ffffff; font-weight: 700; display: flex; align-items: center; gap: 10px;">
								<i class="entypo-users" style="color: var(--accent-primary);"></i> My Assigned Active PT Clients
							</h3>
							<div class="table-responsive">
								<table class="table" style="width: 100%; border-collapse: collapse; color: var(--text-main);">
									<thead>
										<tr style="border-bottom: 2px solid rgba(255,255,255,0.08); text-align: left;">
											<th style="padding: 12px 15px; color: var(--text-muted); font-weight: 600;">Client ID</th>
											<th style="padding: 12px 15px; color: var(--text-muted); font-weight: 600;">Client Name</th>
											<th style="padding: 12px 15px; color: var(--text-muted); font-weight: 600;">Mobile</th>
											<th style="padding: 12px 15px; color: var(--text-muted); font-weight: 600;">Email</th>
											<th style="padding: 12px 15px; color: var(--text-muted); font-weight: 600;">PT Expiry Date</th>
											<th style="padding: 12px 15px; color: var(--text-muted); font-weight: 600; text-align: right;">Action</th>
										</tr>
									</thead>
									<tbody>
										<?php
										$tr_username = mysqli_real_escape_string($con, $_SESSION['username']);
										$q_tr_clients = "SELECT u.userid, u.username, u.mobile, u.email, pe.expire_date 
														 FROM users u 
														 INNER JOIN (
															 SELECT uid, MAX(expire_date) AS expire_date 
															 FROM pt_enrollments 
															 WHERE trainer_id = '$tr_username' 
															 GROUP BY uid
														 ) pe ON u.userid = pe.uid
														 WHERE u.trainer_id = '$tr_username' 
														   AND pe.expire_date >= CURRENT_DATE()
														 ORDER BY u.username ASC";
										$res_tr_clients = mysqli_query($con, $q_tr_clients);
										$client_sno = 0;
										if ($res_tr_clients && mysqli_num_rows($res_tr_clients) > 0) {
											while ($c_row = mysqli_fetch_assoc($res_tr_clients)) {
												$client_sno++;
												?>
												<tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
													<td style="padding: 12px 15px; font-family: monospace;"><?php echo htmlspecialchars($c_row['userid']); ?></td>
													<td style="padding: 12px 15px; font-weight: 600; color: #ffffff;"><?php echo htmlspecialchars($c_row['username']); ?></td>
													<td style="padding: 12px 15px;"><?php echo htmlspecialchars($c_row['mobile']); ?></td>
													<td style="padding: 12px 15px;"><?php echo htmlspecialchars($c_row['email']); ?></td>
													<td style="padding: 12px 15px; color: #ff6b00; font-weight: 600;"><?php echo htmlspecialchars($c_row['expire_date']); ?></td>
													<td style="padding: 12px 15px; text-align: right;">
														<a href="read_member.php?name=<?php echo urlencode($c_row['userid']); ?>" class="a1-btn a1-blue" style="font-size: 11px; padding: 4px 10px !important; text-decoration: none;">View Profile</a>
													</td>
												</tr>
												<?php
											}
										}
										if ($client_sno === 0) {
											?>
											<tr>
												<td colspan="6" style="padding: 25px; text-align: center; color: var(--text-muted);">
													No active personal training clients currently assigned.
												</td>
											</tr>
											<?php
										}
										?>
									</tbody>
								</table>
							</div>
						</div>
					<?php elseif ($_SESSION['role'] === 'super_admin' || $_SESSION['role'] === 'owner'): ?>
						<!-- Admin/Owner View: Personal Trainers Client Counts -->
						<div class="portal-card" style="background: var(--glass-bg); border: 1px solid var(--glass-border); border-radius: 20px; padding: 25px; box-shadow: var(--glass-shadow); margin-bottom: 30px;">
							<h3 style="margin: 0 0 20px 0; color: #ffffff; font-weight: 700; display: flex; align-items: center; gap: 10px;">
								<i class="entypo-users" style="color: var(--accent-primary);"></i> Personal Trainer Active Client Allocation
							</h3>
							<div class="table-responsive">
								<table class="table" style="width: 100%; border-collapse: collapse; color: var(--text-main);">
									<thead>
										<tr style="border-bottom: 2px solid rgba(255,255,255,0.08); text-align: left;">
											<th style="padding: 12px 15px; color: var(--text-muted); font-weight: 600;">Trainer Username</th>
											<th style="padding: 12px 15px; color: var(--text-muted); font-weight: 600;">Trainer Full Name</th>
											<th style="padding: 12px 15px; color: var(--text-muted); font-weight: 600; text-align: center;">Active Clients Count</th>
										</tr>
									</thead>
									<tbody>
										<?php
										$q_trainers_count = "SELECT a.username, a.Full_name, 
															   (SELECT COUNT(*) 
																FROM users u 
																INNER JOIN (
																	SELECT uid, MAX(expire_date) AS max_expire 
																	FROM pt_enrollments 
																	GROUP BY uid
																) pe ON u.userid = pe.uid
																WHERE u.trainer_id = a.username 
																  AND pe.max_expire >= CURRENT_DATE()
															   ) AS client_count
														FROM admin a
														WHERE a.role = 'trainer'
														ORDER BY a.Full_name ASC";
										$res_trainers_count = mysqli_query($con, $q_trainers_count);
										$trainer_row_count = 0;
										if ($res_trainers_count && mysqli_num_rows($res_trainers_count) > 0) {
											while ($t_row = mysqli_fetch_assoc($res_trainers_count)) {
												$trainer_row_count++;
												?>
												<tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
													<td style="padding: 12px 15px; font-family: monospace;"><?php echo htmlspecialchars($t_row['username']); ?></td>
													<td style="padding: 12px 15px; font-weight: 600; color: #ffffff;"><?php echo htmlspecialchars($t_row['Full_name']); ?></td>
													<td style="padding: 12px 15px; text-align: center;">
														<span style="display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; background: <?php echo $t_row['client_count'] > 0 ? 'rgba(16, 185, 129, 0.15)' : 'rgba(255,255,255,0.05)'; ?>; color: <?php echo $t_row['client_count'] > 0 ? 'var(--success)' : 'var(--text-muted)'; ?>; border: 1px solid <?php echo $t_row['client_count'] > 0 ? 'var(--success)' : 'rgba(255,255,255,0.1)'; ?>;">
															<?php echo $t_row['client_count']; ?> Client(s)
														</span>
													</td>
												</tr>
												<?php
											}
										}
										if ($trainer_row_count === 0) {
											?>
											<tr>
												<td colspan="3" style="padding: 25px; text-align: center; color: var(--text-muted);">
													No personal trainers registered in the system.
												</td>
											</tr>
											<?php
										}
										?>
									</tbody>
								</table>
							</div>
						</div>
					<?php endif; ?>
				</div>
			</div>

			<!-- Today's Member Celebrations Card -->
			<div class="row" style="margin-top: 20px;">
				<div class="col-md-12">
					<div class="portal-card" style="background: var(--glass-bg); border: 1px solid var(--glass-border); border-radius: 20px; padding: 25px; box-shadow: var(--glass-shadow); margin-bottom: 20px; color: #ffffff;">
						<h3 style="margin: 0 0 20px 0; color: #ffffff; font-weight: 700; display: flex; align-items: center; gap: 10px;">
							<i class="entypo-gift" style="color: #ff6b00;"></i> Today's Member Celebrations
							<?php
							$today_md = date('m-d');
							
							// Birthdays query
							$q_bday_dash = mysqli_query($con, "
								SELECT userid, username, mobile, dob 
								FROM users 
								WHERE DATE_FORMAT(dob, '%m-%d') = '$today_md'
								ORDER BY username ASC
							");
							$birthdays_today = [];
							if ($q_bday_dash && mysqli_num_rows($q_bday_dash) > 0) {
								while ($row = mysqli_fetch_assoc($q_bday_dash)) {
									$birthdays_today[] = $row;
								}
							}
							
							// Anniversaries query
							$q_ann_dash = mysqli_query($con, "
								SELECT userid, username, mobile, joining_date, (YEAR(CURRENT_DATE()) - YEAR(joining_date)) as years 
								FROM users 
								WHERE DATE_FORMAT(joining_date, '%m-%d') = '$today_md'
								  AND YEAR(joining_date) < YEAR(CURRENT_DATE())
								ORDER BY username ASC
							");
							$anniversaries_today = [];
							if ($q_ann_dash && mysqli_num_rows($q_ann_dash) > 0) {
								while ($row = mysqli_fetch_assoc($q_ann_dash)) {
									$anniversaries_today[] = $row;
								}
							}
							
							$total_celebrations = count($birthdays_today) + count($anniversaries_today);
							if ($total_celebrations > 0):
							?>
								<span style="background: #ff6b00; color: #ffffff; padding: 2px 8px; border-radius: 12px; font-size: 13px; font-weight: bold; margin-left: 8px;">
									<?php echo $total_celebrations; ?>
								</span>
							<?php endif; ?>
						</h3>
						
						<?php if ($total_celebrations === 0): ?>
							<div style="padding: 20px; text-align: center; color: var(--text-muted); font-size: 14px;">
								✨ No member birthdays or gym anniversaries today.
							</div>
						<?php else: ?>
							<div class="row">
								<!-- Birthdays Column -->
								<div class="col-md-6" style="border-right: 1px solid rgba(255,255,255,0.05); min-height: 100px;">
									<h4 style="color: #ff6b00; font-weight: 600; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
										🎂 Birthdays Today <span style="font-size: 12px; background: rgba(255, 107, 0, 0.1); padding: 2px 6px; border-radius: 6px; color: #ff6b00; font-weight: bold;"><?php echo count($birthdays_today); ?></span>
									</h4>
									<?php if (empty($birthdays_today)): ?>
										<p style="color: var(--text-muted); font-size: 13px; padding-left: 5px;">None today</p>
									<?php else: ?>
										<div class="table-responsive">
											<table class="table" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
												<tbody>
													<?php foreach ($birthdays_today as $b_member): ?>
														<tr style="border-bottom: 1px solid rgba(255,255,255,0.03);">
															<td style="padding: 8px 5px; font-weight: 600; color: #ffffff;"><?php echo htmlspecialchars($b_member['username']); ?></td>
															<td style="padding: 8px 5px; font-family: monospace; color: var(--text-muted); font-size: 12px;"><?php echo htmlspecialchars($b_member['userid']); ?></td>
															<td style="padding: 8px 5px; text-align: right;">
																<span style="display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; background: rgba(255, 107, 0, 0.15); color: #ff6b00; border: 1px solid rgba(255, 107, 0, 0.3);">
																	Birthday Today 🎈
																</span>
															</td>
														</tr>
													<?php endforeach; ?>
												</tbody>
											</table>
										</div>
									<?php endif; ?>
								</div>
								
								<!-- Anniversaries Column -->
								<div class="col-md-6" style="min-height: 100px;">
									<h4 style="color: var(--success); font-weight: 600; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
										🏆 Gym Anniversaries <span style="font-size: 12px; background: rgba(16, 185, 129, 0.1); padding: 2px 6px; border-radius: 6px; color: var(--success); font-weight: bold;"><?php echo count($anniversaries_today); ?></span>
									</h4>
									<?php if (empty($anniversaries_today)): ?>
										<p style="color: var(--text-muted); font-size: 13px; padding-left: 5px;">None today</p>
									<?php else: ?>
										<div class="table-responsive">
											<table class="table" style="width: 100%; border-collapse: collapse; margin-bottom: 0;">
												<tbody>
													<?php foreach ($anniversaries_today as $a_member): 
														$years = intval($a_member['years']);
														$badge_text = $years . ' ' . ($years === 1 ? 'Year' : 'Years');
													?>
														<tr style="border-bottom: 1px solid rgba(255,255,255,0.03);">
															<td style="padding: 8px 5px; font-weight: 600; color: #ffffff;"><?php echo htmlspecialchars($a_member['username']); ?></td>
															<td style="padding: 8px 5px; font-family: monospace; color: var(--text-muted); font-size: 12px;"><?php echo htmlspecialchars($a_member['userid']); ?></td>
															<td style="padding: 8px 5px; text-align: right;">
																<span style="display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; background: rgba(16, 185, 129, 0.15); color: var(--success); border: 1px solid rgba(16, 185, 129, 0.3);">
																	<?php echo $badge_text; ?> Anniversary 🏆
																</span>
															</td>
														</tr>
													<?php endforeach; ?>
												</tbody>
											</table>
										</div>
									<?php endif; ?>
								</div>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</div>

			<!-- Live Attendance Punch Feed & Auto Backup Actions -->
			<div class="row" style="margin-top: 20px;">
				<div class="col-md-7">
					<div class="portal-card" style="background: var(--glass-bg); border: 1px solid var(--glass-border); border-radius: 20px; padding: 25px; box-shadow: var(--glass-shadow); margin-bottom: 30px;">
						<h3 style="margin: 0 0 20px 0; color: #ffffff; font-weight: 700; display: flex; align-items: center; gap: 10px;">
							<i class="entypo-clock" style="color: var(--accent-primary);"></i> Live Attendance Punch Feed
						</h3>
						<div class="table-responsive">
							<table class="table" style="width: 100%; border-collapse: collapse; color: var(--text-main);">
								<thead>
									<tr style="border-bottom: 2px solid rgba(255,255,255,0.08); text-align: left;">
										<th style="padding: 10px 12px; color: var(--text-muted); font-weight: 600; font-size: 12px;">Member</th>
										<th style="padding: 10px 12px; color: var(--text-muted); font-weight: 600; font-size: 12px;">Biometric ID</th>
										<th style="padding: 10px 12px; color: var(--text-muted); font-weight: 600; font-size: 12px;">Date</th>
										<th style="padding: 10px 12px; color: var(--text-muted); font-weight: 600; font-size: 12px;">Time</th>
										<th style="padding: 10px 12px; color: var(--text-muted); font-weight: 600; font-size: 12px;">Action</th>
									</tr>
								</thead>
								<tbody id="live-attendance-feed">
									<tr>
										<td colspan="5" style="padding: 25px; text-align: center; color: var(--text-muted);">
											Loading live feed...
										</td>
									</tr>
								</tbody>
							</table>
						</div>
					</div>
				</div>
				<div class="col-md-5">
					<div class="portal-card" style="background: var(--glass-bg); border: 1px solid var(--glass-border); border-radius: 20px; padding: 25px; box-shadow: var(--glass-shadow); margin-bottom: 30px;">
						<h3 style="margin: 0 0 20px 0; color: #ffffff; font-weight: 700; display: flex; align-items: center; gap: 10px;">
							<i class="entypo-drive" style="color: var(--accent-primary);"></i> Database Integrity &amp; SQL Backups
						</h3>
						<p style="font-size: 13px; color: var(--text-muted); line-height: 1.5; margin-bottom: 15px;">
							The system automatically backs up all member profiles, plans, payments, and attendance punches daily, dispatching it securely to the owner's email address.
						</p>
						<div id="backup-summary-box" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); padding: 15px; border-radius: 12px; margin-bottom: 20px;">
							<div style="font-size: 12px; color: var(--text-muted); margin-bottom: 5px;">Last Auto Backup Status:</div>
							<div id="last-backup-info" style="font-size: 14px; font-weight: 600; color: #ffffff;">Checking status...</div>
						</div>
						<button onclick="triggerManualBackup()" id="btn-manual-backup" class="a1-btn a1-blue" style="width: 100%; border-radius: 8px; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 10px !important;">
							<i class="entypo-arrows-ccw"></i> Generate Manual SQL Backup Now
						</button>
					</div>
				</div>
			</div>
   
    	<?php include('footer.php'); ?>
</div>

<script>
function triggerOutboxRetry() {
    const btn = document.getElementById('btn-outbox-retry');
    if (!btn) return;
    const originalText = btn.innerText;
    btn.disabled = true;
    btn.innerText = 'Processing...';
    
    fetch('../../api/trigger_outbox_retry.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Processed ' + data.processed + ' messages.\nSent: ' + data.sent + '\nFailed: ' + data.failed);
                window.location.reload();
            } else {
                alert('Outbox retry failed: ' + data.message);
                btn.disabled = false;
                btn.innerText = originalText;
            }
        })
        .catch(err => {
            console.error('Error retrying outbox:', err);
            alert('Failed to connect to trigger outbox API.');
            btn.disabled = false;
            btn.innerText = originalText;
        });
}
</script>
    </body>
</html>
