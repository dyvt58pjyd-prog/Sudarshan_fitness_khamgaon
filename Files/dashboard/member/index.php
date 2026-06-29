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

// Fetch user subscription details (get the latest subscription)
$sql = "SELECT u.username, u.email, u.trainer_id, e.paid_date, e.expire, p.planName, p.amount 
        FROM users u 
        LEFT JOIN enrolls_to e ON u.userid = e.uid 
        LEFT JOIN plan p ON e.pid = p.pid 
        WHERE u.userid = '$userid'
        ORDER BY e.expire DESC LIMIT 1";
$result = mysqli_query($con, $sql);
$user_info = mysqli_fetch_assoc($result);

$trainer_name = 'None Assigned';
if ($user_info && !empty($user_info['trainer_id'])) {
    $tr_id = mysqli_real_escape_string($con, $user_info['trainer_id']);
    $tr_q = mysqli_query($con, "SELECT Full_name FROM admin WHERE username = '$tr_id'");
    if ($tr_q && mysqli_num_rows($tr_q) > 0) {
        $tr_row = mysqli_fetch_assoc($tr_q);
        $trainer_name = $tr_row['Full_name'];
    }
}

$today_date = date('Y-m-d');
$att_today_q = mysqli_query($con, "SELECT * FROM attendance WHERE uid = '$userid' AND date = '$today_date'");
$checked_in_today = ($att_today_q && mysqli_num_rows($att_today_q) > 0);

$username = isset($user_info['username']) ? $user_info['username'] : $_SESSION['full_name'];
$planName = isset($user_info['planName']) ? $user_info['planName'] : 'No Active Plan';
$amount = isset($user_info['amount']) ? "₹" . $user_info['amount'] : 'N/A';
$paid_date = isset($user_info['paid_date']) ? $user_info['paid_date'] : 'N/A';
$expire = isset($user_info['expire']) ? $user_info['expire'] : 'N/A';

$is_expired = false;
$is_expiring_soon = false;
$days_remaining = 0;

if ($planName === 'No Active Plan' || $expire === 'N/A') {
    $is_expired = true;
} else {
    $expire_time = strtotime($expire);
    $current_time = strtotime(date('Y-m-d'));
    if ($current_time > $expire_time) {
        $is_expired = true;
    } else {
        $diff = $expire_time - $current_time;
        $days_remaining = round($diff / (60 * 60 * 24));
        if ($days_remaining <= 5) {
            $is_expiring_soon = true;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo htmlspecialchars($gym['gym_name']); ?> | My Dashboard</title>
    <link rel="stylesheet" href="../../css/style.css" id="style-resource-5">
    <script type="text/javascript" src="../../js/Script.js"></script>
    <link rel="stylesheet" href="../../css/dashMain.css">
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
    <link rel="stylesheet" href="../../css/premium.css">
    <link href="a1style.css" rel="stylesheet" type="text/css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .page-container .sidebar-menu #main-menu li#dash > a {
            background-color: rgba(59, 130, 246, 0.1) !important;
            color: var(--accent-primary) !important;
            font-weight: 600 !important;
            box-shadow: inset 3px 0 0 var(--accent-primary);
        }
        .portal-card {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: var(--glass-shadow);
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            color: var(--text-muted);
            font-weight: 600;
        }
        .info-value {
            color: var(--text-main);
            font-weight: 500;
        }
        .status-badge {
            background: rgba(16, 185, 129, 0.2);
            color: var(--success);
            border: 1px solid var(--success);
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
    </style>
</head>
<body class="page-body page-fade" onload="collapseSidebar()">
    <!-- Particle HUD Background -->
    <div id="particles-js" style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; z-index: -1;"></div>
    
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
                        <li>Welcome <?php echo htmlspecialchars($username); ?></li>
                        <li>
                            <a href="../admin/logout.php">
                                Log Out <i class="entypo-logout right"></i>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <h2>My Dashboard</h2>
            <hr />

			<!-- Premium Live Analogue Clock & Smart Fitness Info Panel -->
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
						
						<!-- Smart Member Fitness Status block -->
						<div style="border-left: 1px dashed rgba(255,255,255,0.1); padding-left: 25px; min-width: 280px; display: flex; flex-direction: column; gap: 5px;">
							<div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-muted); letter-spacing: 1px; margin-bottom: 3px;">
								🧠 Smart Fitness Status
							</div>
							<div style="font-size: 13px; color: #ffffff; display: flex; align-items: center; justify-content: space-between;">
								<span>Membership Plan:</span>
								<span style="color: <?php echo $is_expired ? '#ef4444' : '#10b981'; ?>; font-weight: 600;">
									<?php echo htmlspecialchars($planName); ?> (<?php echo $is_expired ? 'Expired' : 'Active'; ?>)
								</span>
							</div>
							<div style="font-size: 13px; color: #ffffff; display: flex; align-items: center; justify-content: space-between;">
								<span>Today's Attendance:</span>
								<span style="color: <?php echo $checked_in_today ? '#10b981' : '#ef4444'; ?>; font-weight: 600;">
									<?php echo $checked_in_today ? 'Checked In 🟢' : 'Not Logged Yet 🔴'; ?>
								</span>
							</div>
							<div style="font-size: 13px; color: #ffffff; display: flex; align-items: center; justify-content: space-between;">
								<span>Personal Trainer:</span>
								<span style="color: #ff6b00; font-weight: 600;">
									<?php echo htmlspecialchars($trainer_name); ?>
								</span>
							</div>
							<div style="font-size: 13px; color: #ffffff; display: flex; align-items: center; justify-content: space-between;">
								<span>WhatsApp AI Coach:</span>
								<span style="color: #10b981; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
									<span style="display:inline-block; width:6px; height:6px; background:#10b981; border-radius:50%;"></span> Active
								</span>
							</div>
						</div>
						
					</div>
				</div>
			</div>
            
            <!-- Monthly Leaderboard Section (Gamification) -->
            <div class="row" style="margin-left: 0; margin-right: 0;">
                <?php include '../admin/leaderboard_widget.php'; ?>
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
					const memberName = "<?php echo htmlspecialchars($username); ?>";
					if (hrs >= 5 && hrs < 12) {
						greeting = `Good Morning, ${memberName}!`;
					} else if (hrs >= 12 && hrs < 17) {
						greeting = `Good Afternoon, ${memberName}!`;
					} else if (hrs >= 17 && hrs < 22) {
						greeting = `Good Evening, ${memberName}!`;
					} else {
						greeting = `Welcome Back, ${memberName}!`;
					}
					if (greetingEl) greetingEl.textContent = greeting;
					
					const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
					if (dateEl) dateEl.textContent = now.toLocaleDateString('en-US', options);
				}
				
				updateClock();
				setInterval(updateClock, 1000);
			});
			</script>

            <?php if ($is_expired || $is_expiring_soon): ?>
                <div class="alert alert-warning" style="background: rgba(255, 107, 0, 0.1); border: 1px solid rgba(255, 107, 0, 0.25); border-radius: 12px; padding: 20px; margin-bottom: 30px; backdrop-filter: blur(8px);">
                    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px;">
                        <div>
                            <h4 style="margin: 0 0 5px 0; color: var(--accent-primary); font-weight: 700;">
                                <?php if ($planName === 'No Active Plan' || $is_expired): ?>
                                    ⚠️ Gym Membership Expired
                                <?php else: ?>
                                    ⚠️ Gym Membership Expiring Soon
                                <?php endif; ?>
                            </h4>
                            <p style="margin: 0; color: var(--text-muted); font-size: 14px;">
                                <?php if ($planName === 'No Active Plan' || $is_expired): ?>
                                    Your subscription has expired. Please renew your membership package to continue uninterrupted gym gate access.
                                <?php else: ?>
                                    Your subscription is expiring in <?php echo $days_remaining; ?> day<?php echo $days_remaining > 1 ? 's' : ''; ?> (on <?php echo htmlspecialchars($expire); ?>).
                                <?php endif; ?>
                            </p>
                        </div>
                        <a href="payment.php" class="btn btn-primary" style="margin: 0; padding: 10px 20px; font-weight: 600; background: var(--accent-primary); border-color: var(--accent-primary);">Renew Membership Now</a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Gamification Row (Streak, Levels, Leaderboard) -->
            <div class="row" style="margin-bottom: 20px;">
                <!-- Streak & XP Card -->
                <div class="col-md-6" style="margin-bottom: 20px;">
                    <div class="portal-card" style="min-height: 240px; display: flex; flex-direction: column; justify-content: space-between; margin-bottom: 0;">
                        <div>
                            <h3 style="margin-top: 0;">My Fitness Status</h3>
                            <hr style="margin-top: 10px; margin-bottom: 15px; border-color: rgba(255,255,255,0.05);" />
                            
                            <?php
                            $streak = get_member_streak($con, $userid);
                            $att_q = mysqli_query($con, "SELECT COUNT(*) as cnt FROM attendance WHERE uid = '$userid'");
                            $att_row = mysqli_fetch_assoc($att_q);
                            $total_attendance = intval($att_row['cnt']);
                            $level = floor($total_attendance / 5) + 1;
                            $xp = ($total_attendance % 5) * 20; // 5 checkins per level
                            
                            if ($level <= 2) $badge = "Novice Challenger 🛡️";
                            elseif ($level <= 5) $badge = "Regular Crusader ⚔️";
                            elseif ($level <= 10) $badge = "Iron Warrior 🏋️";
                            else $badge = "Elite Champion 🏆";
                            ?>
                            
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <span style="font-size: 36px;">🔥</span>
                                    <div>
                                        <span style="font-size: 20px; font-weight: 700; color: var(--accent-primary); display: block;"><?php echo $streak; ?>-Day Streak</span>
                                        <span style="font-size: 12px; color: var(--text-muted);">Keep checking in consecutive days!</span>
                                    </div>
                                </div>
                                <div style="text-align: right;">
                                    <span class="status-badge" style="background: rgba(255, 107, 0, 0.15); color: var(--accent-primary); border-color: var(--accent-primary); font-size: 13px; padding: 4px 12px;"><?php echo $badge; ?></span>
                                </div>
                            </div>
                        </div>

                        <div>
                            <div style="display: flex; justify-content: space-between; font-size: 12px; color: var(--text-muted); margin-bottom: 5px;">
                                <span>Level <?php echo $level; ?></span>
                                <span>Level <?php echo $level + 1; ?> (<?php echo $xp; ?>% XP)</span>
                            </div>
                            <div style="background: rgba(255,255,255,0.05); height: 8px; border-radius: 10px; overflow: hidden; border: 1px solid rgba(255,255,255,0.05);">
                                <div style="background: var(--accent-primary); width: <?php echo $xp; ?>%; height: 100%; border-radius: 10px; box-shadow: 0 0 8px var(--accent-primary);"></div>
                            </div>
                            <span style="font-size: 11px; color: var(--text-muted); display: block; margin-top: 5px;">Total check-ins: <strong><?php echo $total_attendance; ?></strong> (5 check-ins to level up!)</span>
                        </div>
                    </div>
                </div>

                <!-- Monthly Leaderboard Card -->
                <div class="col-md-6" style="margin-bottom: 20px;">
                    <div class="portal-card" style="min-height: 240px; margin-bottom: 0;">
                        <h3 style="margin-top: 0;">Monthly Gym Leaderboard</h3>
                        <hr style="margin-top: 10px; margin-bottom: 15px; border-color: rgba(255,255,255,0.05);" />
                        
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <?php
                            $leader_q = mysqli_query($con, "SELECT u.username, COUNT(a.id) as checkins 
                                                            FROM attendance a 
                                                            INNER JOIN users u ON a.uid = u.userid 
                                                            WHERE MONTH(a.date) = MONTH(CURRENT_DATE()) AND YEAR(a.date) = YEAR(CURRENT_DATE())
                                                            GROUP BY a.uid 
                                                            ORDER BY checkins DESC LIMIT 4");
                            $rank = 0;
                            if ($leader_q && mysqli_num_rows($leader_q) > 0) {
                                while ($row_l = mysqli_fetch_assoc($leader_q)) {
                                    $rank++;
                                    $medal = "";
                                    if ($rank === 1) $medal = "🥇";
                                    elseif ($rank === 2) $medal = "🥈";
                                    elseif ($rank === 3) $medal = "🥉";
                                    else $medal = "⭐";
                                    ?>
                                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 6px 12px; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.03); border-radius: 8px;">
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <span style="font-size: 14px; font-weight: bold; width: 20px; text-align: center; color: var(--text-muted);"><?php echo $medal; ?></span>
                                            <span style="font-weight: 600; color: #ffffff;"><?php echo htmlspecialchars($row_l['username']); ?></span>
                                        </div>
                                        <span style="font-size: 12px; font-weight: 700; color: var(--accent-primary);"><?php echo $row_l['checkins']; ?> check-ins</span>
                                    </div>
                                    <?php
                                }
                            } else {
                                echo "<div style='text-align: center; color: var(--text-muted); padding: 30px;'>No check-ins logged this month yet.</div>";
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Subscription Card -->
                <div class="col-md-6" style="margin-bottom: 20px;">
                    <div class="portal-card" style="margin-bottom: 0; min-height: 290px;">
                        <h3>Membership Subscription</h3>
                        <hr style="margin-top: 10px; margin-bottom: 20px; border-color: rgba(255,255,255,0.05);" />
                        
                        <div class="info-row">
                            <span class="info-label">Active Plan</span>
                            <span class="info-value"><?php echo htmlspecialchars($planName); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Plan Amount</span>
                            <span class="info-value"><?php echo htmlspecialchars($amount); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Payment Date</span>
                            <span class="info-value"><?php echo htmlspecialchars($paid_date); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Expiration Date</span>
                            <span class="info-value"><?php echo htmlspecialchars($expire); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Membership ID</span>
                            <span class="info-value" style="font-family: monospace; letter-spacing: 0.5px;"><?php echo htmlspecialchars($userid); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Status</span>
                            <span class="info-value">
                                <?php if ($planName !== 'No Active Plan'): ?>
                                    <span class="status-badge">Active</span>
                                <?php else: ?>
                                    <span class="status-badge" style="background: rgba(239, 68, 68, 0.2); color: var(--danger); border-color: var(--danger);">Inactive</span>
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Navigation Tiles -->
                <div class="col-md-6" style="margin-bottom: 20px;">
                    <div class="row">
                        <div class="col-sm-12" style="margin-bottom: 15px;">
                            <a href="routine.php">
                                <div class="tile-stats tile-blue" style="min-height: 110px; padding: 15px 24px !important;">
                                    <div class="icon"><i class="entypo-alert"></i></div>
                                    <div class="num">
                                        <h2 style="margin-bottom: 5px !important;">My Workout</h2>
                                        <p style="font-size: 13px; color: var(--text-muted); font-weight: normal; margin: 0;">View assigned daily exercise routine.</p>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-12" style="margin-bottom: 15px;">
                            <a href="pt_booking.php">
                                <div class="tile-stats tile-red" style="min-height: 110px; padding: 15px 24px !important; background-color: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.25);">
                                    <div class="icon"><i class="entypo-calendar"></i></div>
                                    <div class="num">
                                        <h2 style="margin-bottom: 5px !important; color: var(--danger);">PT Slot Booking</h2>
                                        <p style="font-size: 13px; color: var(--text-muted); font-weight: normal; margin: 0;">Book a training session with your personal trainer.</p>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- AI Fitness Coach Row -->
            <div class="row" style="margin-top: 10px; margin-bottom: 25px;">
                <div class="col-md-12">
                    <div class="portal-card" style="background: var(--glass-bg); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); border: 1px solid var(--glass-border); border-radius: 20px; padding: 25px; box-shadow: var(--glass-shadow); color: #ffffff;">
                        <h3 style="margin-top: 0; display: flex; align-items: center; gap: 10px; color: #ffffff;">
                            <span>🧠</span> AI Fitness &amp; Nutrition Counselor
                        </h3>
                        <hr style="margin-top: 10px; margin-bottom: 20px; border-color: rgba(255,255,255,0.05);" />
                        
                        <div class="row">
                            <div class="col-md-4" style="border-right: 1px dashed rgba(255,255,255,0.1); padding-right: 25px; margin-bottom: 20px;">
                                <h4 style="color: var(--accent-primary); margin-top: 0; font-weight: 700;">Configure My Plan</h4>
                                <form id="ai-coach-form" onsubmit="generateAIPlan(event)">
                                    <div class="form-group" style="margin-bottom: 15px;">
                                        <label style="color: var(--text-muted); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Fitness Goal</label>
                                        <select class="form-control" id="ai-goal" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); color: #ffffff; border-radius: 8px;">
                                            <option value="Fat Loss" style="background: #1e1e1e; color: #ffffff;">🔥 Fat Loss & Weight Reduction</option>
                                            <option value="Muscle Gain" style="background: #1e1e1e; color: #ffffff;">💪 Muscle Hypertrophy & Bulking</option>
                                            <option value="Lean Builder" style="background: #1e1e1e; color: #ffffff;">⚡ Lean Muscle & Conditioning</option>
                                        </select>
                                    </div>
                                    <div class="form-group" style="margin-bottom: 15px;">
                                        <label style="color: var(--text-muted); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Diet Preference</label>
                                        <select class="form-control" id="ai-diet" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); color: #ffffff; border-radius: 8px;">
                                            <option value="Vegetarian" style="background: #1e1e1e; color: #ffffff;">🥦 Vegetarian</option>
                                            <option value="Vegan" style="background: #1e1e1e; color: #ffffff;">🌱 Vegan</option>
                                            <option value="Non-Vegetarian" style="background: #1e1e1e; color: #ffffff;">🍖 Non-Vegetarian</option>
                                        </select>
                                    </div>
                                    <div class="form-group" style="margin-bottom: 15px;">
                                        <label style="color: var(--text-muted); font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Medical Conditions / Injuries</label>
                                        <input type="text" class="form-control" id="ai-medical" placeholder="e.g. Knee pain, Shoulder strain, None" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.08); color: #ffffff; border-radius: 8px;" value="" />
                                    </div>
                                    <div class="form-group" style="margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
                                        <input type="checkbox" id="ai-send-wa" style="width: 16px; height: 16px; accent-color: #ff6b00;" checked />
                                        <label for="ai-send-wa" style="color: var(--text-muted); font-size: 12px; font-weight: 600; margin: 0; cursor: pointer;">Send generated chart to my WhatsApp</label>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-block" style="background: var(--accent-primary); border-color: var(--accent-primary); font-weight: 700; text-transform: uppercase; border-radius: 8px; padding: 10px;" id="btn-generate-ai">
                                        ⚡ Generate AI Plan
                                    </button>
                                </form>
                            </div>
                            
                            <div class="col-md-8" style="padding-left: 25px;">
                                <h4 style="color: var(--accent-primary); margin-top: 0; font-weight: 700;">My Personalized Chart</h4>
                                <div id="ai-plan-output" style="background: rgba(255, 255, 255, 0.01); border: 1px solid rgba(255,255,255,0.04); border-radius: 12px; padding: 20px; min-height: 250px; color: #e2e8f0; font-size: 13.5px; line-height: 1.6; max-height: 400px; overflow-y: auto;">
                                    <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 200px; color: var(--text-muted);">
                                        <span style="font-size: 40px; margin-bottom: 10px;">🧠</span>
                                        <span>Click "Generate AI Plan" to build a customized diet &amp; workout plan tailored to your body metrics.</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Health Analytics (Chart.js) Row -->
            <div class="row" style="margin-top: 20px; margin-bottom: 30px;">
                <div class="col-md-12">
                    <div class="portal-card" style="margin-bottom: 0;">
                        <h3>My Health & Metrics Tracker</h3>
                        <hr style="margin-top: 10px; margin-bottom: 20px; border-color: rgba(255,255,255,0.05);" />
                        
                        <?php
                        $history_q = mysqli_query($con, "SELECT weight, fat, logged_date FROM health_history WHERE uid = '$userid' ORDER BY logged_date ASC");
                        $history_dates = [];
                        $history_weights = [];
                        $history_fats = [];
                        
                        while ($row_h = mysqli_fetch_assoc($history_q)) {
                            $history_dates[] = date('M d', strtotime($row_h['logged_date']));
                            $history_weights[] = floatval($row_h['weight']);
                            $history_fats[] = floatval($row_h['fat']);
                        }
                        
                        // Seed current values if history is empty to make charts active
                        if (empty($history_weights)) {
                            $curr_h = mysqli_query($con, "SELECT weight, fat FROM health_status WHERE uid = '$userid'");
                            if ($curr_h && $row_c = mysqli_fetch_assoc($curr_h)) {
                                if (!empty($row_c['weight'])) {
                                    $history_dates[] = 'Today';
                                    $history_weights[] = floatval($row_c['weight']);
                                    $history_fats[] = floatval($row_c['fat']);
                                }
                            }
                        }
                        
                        $dates_json = json_encode($history_dates);
                        $weights_json = json_encode($history_weights);
                        $fats_json = json_encode($history_fats);
                        ?>

                        <?php if (!empty($history_weights)): ?>
                            <div class="row">
                                <div class="col-md-6" style="margin-bottom: 20px;">
                                    <h4 style="text-align: center; color: var(--text-muted); font-size: 13px; font-weight: bold; margin-bottom: 15px;">BODY WEIGHT TREND (KG)</h4>
                                    <div style="height: 250px; position: relative;">
                                        <canvas id="weightChart"></canvas>
                                    </div>
                                </div>
                                <div class="col-md-6" style="margin-bottom: 20px;">
                                    <h4 style="text-align: center; color: var(--text-muted); font-size: 13px; font-weight: bold; margin-bottom: 15px;">BODY FAT TREND (%)</h4>
                                    <div style="height: 250px; position: relative;">
                                        <canvas id="fatChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                                <i class="entypo-info-circled" style="font-size: 36px; display: block; margin-bottom: 15px; color: var(--accent-primary);"></i>
                                No body metric logs found. Ask the receptionist to log your weight and body fat during your next visit!
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php include('../admin/footer.php'); ?>
        </div>
    </div>

    <?php if (!empty($history_weights)): ?>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const labels = <?php echo $dates_json; ?>;
            
            // Weight Chart
            const weightCtx = document.getElementById('weightChart').getContext('2d');
            new Chart(weightCtx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Weight (kg)',
                        data: <?php echo $weights_json; ?>,
                        borderColor: '#ff6b00',
                        backgroundColor: 'rgba(255, 107, 0, 0.15)',
                        borderWidth: 3,
                        tension: 0.3,
                        fill: true,
                        pointBackgroundColor: '#ff6b00',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7
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
                            grid: { color: 'rgba(255,255,255,0.03)' },
                            ticks: { color: '#a3a3a3', font: { family: 'Outfit' } }
                        },
                        y: {
                            grid: { color: 'rgba(255,255,255,0.03)' },
                            ticks: { color: '#a3a3a3', font: { family: 'Outfit' } }
                        }
                    }
                }
            });

            // Fat Chart
            const fatCtx = document.getElementById('fatChart').getContext('2d');
            new Chart(fatCtx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Body Fat (%)',
                        data: <?php echo $fats_json; ?>,
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.15)',
                        borderWidth: 3,
                        tension: 0.3,
                        fill: true,
                        pointBackgroundColor: '#3b82f6',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7
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
                            grid: { color: 'rgba(255,255,255,0.03)' },
                            ticks: { color: '#a3a3a3', font: { family: 'Outfit' } }
                        },
                        y: {
                            grid: { color: 'rgba(255,255,255,0.03)' },
                            ticks: { color: '#a3a3a3', font: { family: 'Outfit' } }
                        }
                    }
                }
            });
        });

        function generateAIPlan(event) {
            event.preventDefault();
            const btn = document.getElementById('btn-generate-ai');
            const out = document.getElementById('ai-plan-output');
            const goal = document.getElementById('ai-goal').value;
            const diet = document.getElementById('ai-diet').value;
            const medical = document.getElementById('ai-medical').value;
            const sendWa = document.getElementById('ai-send-wa').checked;
            
            const origText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '⌛ CONSULTING AI...';
            
            out.innerHTML = `
                <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 200px;">
                    <span style="font-size: 40px; margin-bottom: 10px;">⏳</span>
                    <span>Analyzing metrics &amp; generating routines...</span>
                </div>
            `;
            
            fetch('../../api/get_ai_plan.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ goal, diet, medical, send_whatsapp: sendWa })
            })
            .then(res => res.json())
            .then(data => {
                btn.disabled = false;
                btn.innerHTML = origText;
                if (data.success) {
                    let html = `<div style="font-size: 14px;">`;
                    html += `<h4 style="color: #ff6b00; margin-top: 0; font-weight: bold;">📊 Health Analysis Summary</h4>`;
                    html += `<p style="margin-bottom: 15px;">Your BMI is <strong>${data.bmi} (${data.bmi_category})</strong>. To reach your goal of <strong>${goal}</strong>, your custom target is <strong>${data.target_calories} kcal/day</strong>.</p>`;
                    
                    html += `<h4 style="color: #ff6b00; font-weight: bold; margin-top: 20px;">📅 Weekly Workout split</h4>`;
                    html += `<table class="table" style="background: transparent; border-collapse: collapse; width:100%; border:none;"><tbody>`;
                    for (const [day, routine] of Object.entries(data.workout)) {
                        html += `<tr><td style="border:none; padding:6px 0; font-weight:600; width:100px; color:#ffffff;">${day}</td><td style="border:none; padding:6px 0; color:#e2e8f0;">${routine}</td></tr>`;
                    }
                    html += `</tbody></table>`;
                    
                    html += `<h4 style="color: #ff6b00; font-weight: bold; margin-top: 20px;">🥗 Custom Diet Plan (${diet})</h4>`;
                    html += `<table class="table" style="background: transparent; border-collapse: collapse; width:100%; border:none;"><tbody>`;
                    for (const [meal, food] of Object.entries(data.diet)) {
                        html += `<tr><td style="border:none; padding:6px 0; font-weight:600; width:150px; color:#ffffff;">${meal}</td><td style="border:none; padding:6px 0; color:#e2e8f0;">${food}</td></tr>`;
                    }
                    html += `</tbody></table>`;
                    
                    if (data.whatsapp_sent) {
                        html += `<div style="margin-top: 20px; padding: 12px; background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.25); border-radius: 8px; color: #10b981; font-weight: 600; font-size: 13px;">✓ Custom plan sent successfully to your WhatsApp!</div>`;
                    }
                    
                    html += `</div>`;
                    out.innerHTML = html;
                } else {
                    out.innerHTML = `<div style="color:#ef4444; font-weight:600;">Failed to generate AI plan. Error: ${data.message}</div>`;
                }
            })
            .catch(err => {
                btn.disabled = false;
                btn.innerHTML = origText;
                out.innerHTML = `<div style="color:#ef4444; font-weight:600;">Connection failed. Check network or server status.</div>`;
            });
        }
    </script>
    <?php endif; ?>
    
    <!-- Particles HUD Script -->
    <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            if (window.particlesJS) {
                particlesJS("particles-js", {
                    "particles": {
                        "number": { "value": 40, "density": { "enable": true, "value_area": 800 } },
                        "color": { "value": "#ff6b00" },
                        "shape": { "type": "circle" },
                        "opacity": { "value": 0.3, "random": false },
                        "size": { "value": 3, "random": true },
                        "line_linked": { "enable": true, "distance": 150, "color": "#ff6b00", "opacity": 0.2, "width": 1 },
                        "move": { "enable": true, "speed": 1.5, "direction": "none", "random": false, "straight": false, "out_mode": "out", "bounce": false }
                    },
                    "interactivity": {
                        "detect_on": "canvas",
                        "events": { "onhover": { "enable": true, "mode": "grab" }, "onclick": { "enable": true, "mode": "push" }, "resize": true },
                        "modes": { "grab": { "distance": 140, "line_linked": { "opacity": 0.5 } }, "push": { "particles_nb": 4 } }
                    },
                    "retina_detect": true
                });
            }
        });
    </script>
</body>
</html>
