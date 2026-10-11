<?php
require '../../include/db_conn.php';
page_protect();
?>


<!DOCTYPE html>
<html lang="en">
<head>

    <title>SUDARSHAN FITNESS | Payments</title>
    <link rel="stylesheet" href="../../css/style.css"  id="style-resource-5">
    <script type="text/javascript" src="../../js/Script.js"></script>
    <link rel="stylesheet" href="../../css/dashMain.css">
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
    <link rel="stylesheet" href="../../css/premium.css?v=<?php echo time(); ?>">
    <link href="a1style.css" type="text/css" rel="stylesheet">
    <style>
    	.page-container .sidebar-menu #main-menu li#paymnt > a {
            background-color: #2b303a;
            color: #ffffff;
		}
        .member-tabs-container {
            display: flex;
            gap: 12px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }
        .tab-btn {
            background: var(--bg-card, #ffffff);
            color: var(--text-muted, #64748b);
            border: 1px solid var(--border-color, rgba(255, 107, 0, 0.2));
            padding: 10px 22px;
            border-radius: 8px;
            text-decoration: none !important;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.04);
        }
        .tab-btn:hover {
            background: rgba(255, 107, 0, 0.08);
            color: #ff6b00;
            border-color: rgba(255, 107, 0, 0.4);
        }
        .tab-btn.active-tab {
            background: #ff6b00;
            color: #ffffff !important;
            border-color: #ff6b00;
            box-shadow: 0 4px 12px rgba(255, 107, 0, 0.3);
        }
    </style>

</head>
      <body class="page-body  page-fade" onload="collapseSidebar()">

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

		<h2>Payments</h2>

		<hr />
		
        <?php
        $current_tab = isset($_GET['tab']) ? $_GET['tab'] : 'plan';
        ?>

        <div class="member-tabs-container">
            <a href="?tab=plan" class="tab-btn <?php echo $current_tab === 'plan' ? 'active-tab' : ''; ?>">
                Membership Plan Payments
            </a>
            <a href="?tab=pt" class="tab-btn <?php echo $current_tab === 'pt' ? 'active-tab' : ''; ?>">
                Personal Training Payments
            </a>
        </div>

        <?php if ($current_tab === 'plan'): ?>
            <?php
            $current_filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
            ?>
            <!-- Status Filter Shortcuts -->
            <div style="display: flex; gap: 8px; margin-bottom: 18px; flex-wrap: wrap;">
                <a href="?tab=plan&filter=all" class="a1-btn" style="padding: 7px 16px; font-size: 12px; border-radius: 8px; text-decoration: none; font-weight: 600; <?php echo $current_filter === 'all' ? 'background: #2563eb; color: #fff;' : 'background: rgba(255,255,255,0.06); color: var(--text-muted); border: 1px solid rgba(255,255,255,0.1);'; ?>">
                    All Members
                </a>
                <a href="?tab=plan&filter=expired" class="a1-btn" style="padding: 7px 16px; font-size: 12px; border-radius: 8px; text-decoration: none; font-weight: 700; <?php echo $current_filter === 'expired' ? 'background: #dc2626; color: #fff;' : 'background: rgba(239,68,68,0.1); color: #ef4444; border: 1px solid rgba(239,68,68,0.3);'; ?>">
                    🚨 Expired Members (Overdue)
                </a>
                <a href="?tab=plan&filter=expiring" class="a1-btn" style="padding: 7px 16px; font-size: 12px; border-radius: 8px; text-decoration: none; font-weight: 600; <?php echo $current_filter === 'expiring' ? 'background: #d97706; color: #fff;' : 'background: rgba(245,158,11,0.1); color: #f59e0b; border: 1px solid rgba(245,158,11,0.3);'; ?>">
                    ⏳ Expiring Soon (≤ 5 Days)
                </a>
                <a href="?tab=plan&filter=active" class="a1-btn" style="padding: 7px 16px; font-size: 12px; border-radius: 8px; text-decoration: none; font-weight: 600; <?php echo $current_filter === 'active' ? 'background: #059669; color: #fff;' : 'background: rgba(16,185,129,0.1); color: #10b981; border: 1px solid rgba(16,185,129,0.3);'; ?>">
                    🟢 Active Members
                </a>
            </div>

            <!-- Membership Plan Payments Tab -->
            <table class="table table-bordered datatable" id="table-1" border=1>
                <thead>
                    <tr>
                        <th>Sl.No</th>
                        <th>Membership Expiry</th>
                        <th>Name</th>
                        <th>Member ID</th>
                        <th>Phone</th>
                        <th>E-Mail</th>
                        <th>Gender</th>
                        <th style="width: 250px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        $query  = "select * from users WHERE YEAR(joining_date) <= " . $_SESSION['working_year'] . " ORDER BY username ASC";
                        $result = mysqli_query($con, $query);
                        $sno    = 1;
                        $today  = date('Y-m-d');
                        $today_ts = strtotime($today);

                        if ($result && mysqli_num_rows($result) > 0) {
                            while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                                $uid   = $row['userid'];
                                $query1  = "select * from enrolls_to WHERE uid='$uid' AND YEAR(paid_date) <= " . $_SESSION['working_year'] . " ORDER BY expire DESC LIMIT 1";
                                $result1 = mysqli_query($con, $query1);
                                
                                $expire = "No Active Plan";
                                $planid = "";
                                if ($result1 && mysqli_num_rows($result1) > 0) {
                                    $row1 = mysqli_fetch_array($result1, MYSQLI_ASSOC);
                                    $expire = $row1['expire'];
                                    $planid = $row1['pid'];
                                }

                                $status_key = 'no_plan';
                                $badge_html = '';

                                if ($expire === "No Active Plan" || empty($expire)) {
                                    $status_key = 'no_plan';
                                    $badge_html = '<span style="background: rgba(148, 163, 184, 0.15); color: #94a3b8; border: 1px solid rgba(148, 163, 184, 0.3); padding: 3px 8px; border-radius: 6px; font-weight: 600; font-size: 11px;">No Active Plan</span>';
                                } else {
                                    $expire_ts = strtotime($expire);
                                    $diff_days = round(($expire_ts - $today_ts) / 86400);
                                    if ($diff_days < 0) {
                                        $status_key = 'expired';
                                        $days_ago = abs($diff_days);
                                        $badge_html = '<div style="display: flex; flex-direction: column; gap: 3px;">
                                            <span style="font-weight: 700; color: #ef4444; font-size: 13px;">' . htmlspecialchars($expire) . '</span>
                                            <span style="background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.4); padding: 2px 7px; border-radius: 5px; font-weight: 800; font-size: 10.5px; width: fit-content;">
                                                🚨 Expired (' . $days_ago . 'd ago)
                                            </span>
                                        </div>';
                                    } elseif ($diff_days == 0) {
                                        $status_key = 'expiring';
                                        $badge_html = '<div style="display: flex; flex-direction: column; gap: 3px;">
                                            <span style="font-weight: 700; color: #f59e0b; font-size: 13px;">' . htmlspecialchars($expire) . '</span>
                                            <span style="background: rgba(245, 158, 11, 0.15); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.4); padding: 2px 7px; border-radius: 5px; font-weight: 800; font-size: 10.5px; width: fit-content;">
                                                ⚠️ Expires Today
                                            </span>
                                        </div>';
                                    } elseif ($diff_days <= 5) {
                                        $status_key = 'expiring';
                                        $badge_html = '<div style="display: flex; flex-direction: column; gap: 3px;">
                                            <span style="font-weight: 700; color: #f59e0b; font-size: 13px;">' . htmlspecialchars($expire) . '</span>
                                            <span style="background: rgba(245, 158, 11, 0.15); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.4); padding: 2px 7px; border-radius: 5px; font-weight: 800; font-size: 10.5px; width: fit-content;">
                                                ⏳ ' . $diff_days . ' days left
                                            </span>
                                        </div>';
                                    } else {
                                        $status_key = 'active';
                                        $badge_html = '<div style="display: flex; flex-direction: column; gap: 3px;">
                                            <span style="font-weight: 700; color: #10b981; font-size: 13px;">' . htmlspecialchars($expire) . '</span>
                                            <span style="background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.4); padding: 2px 7px; border-radius: 5px; font-weight: 800; font-size: 10.5px; width: fit-content;">
                                                🟢 Active (' . $diff_days . 'd left)
                                            </span>
                                        </div>';
                                    }
                                }

                                if ($current_filter === 'expired' && $status_key !== 'expired') continue;
                                if ($current_filter === 'expiring' && $status_key !== 'expiring') continue;
                                if ($current_filter === 'active' && $status_key !== 'active') continue;
                                
                                echo "<tr><td>".$sno."</td>";
                                echo "<td>" . $badge_html . "</td>";
                                echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['userid']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['mobile']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['gender']) . "</td>";
                                
                                $sno++;
                                
                                echo "<td>
                                    <div style='display: flex; gap: 8px;'>
                                        <form action='make_payments.php' method='post' style='margin: 0; display: inline;'>
                                            <input type='hidden' name='userID' value='" . $uid . "'/>
                                            <input type='hidden' name='planID' value='" . $planid . "'/>
                                            <input type='submit' class='a1-btn a1-blue' style='padding: 6px 10px !important; font-size: 11px;' value='Plan Payment'/>
                                        </form>
                                        <form action='enroll_pt.php' method='post' style='margin: 0; display: inline;'>
                                            <input type='hidden' name='userID' value='" . $uid . "'/>
                                            <input type='submit' class='a1-btn' style='padding: 6px 10px !important; font-size: 11px; background-color: #ff6b00; border-color: #ff6b00; color: #ffffff;' value='PT Payment'/>
                                        </form>
                                    </div>
                                </td></tr>";
                            }
                        }
                    ?>									
                </tbody>
            </table>
        <?php else: ?>
            <!-- Personal Training Payments Tab -->
            <table class="table table-bordered datatable" id="table-1" border=1>
                <thead>
                    <tr>
                        <th>Sl.No</th>
                        <th>Member Name</th>
                        <th>Member ID</th>
                        <th>Trainer Assigned</th>
                        <th>Start Date</th>
                        <th>Expiry Date</th>
                        <th>Amount Paid</th>
                        <th>Payment Mode</th>
                        <th>Handled By</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        $pt_query = "SELECT p.*, u.username, t.Full_name AS trainer_name 
                                     FROM pt_enrollments p 
                                     INNER JOIN users u ON p.uid = u.userid 
                                     INNER JOIN admin t ON p.trainer_id = t.username 
                                     ORDER BY p.enroll_date DESC";
                        $pt_result = mysqli_query($con, $pt_query);
                        $sno = 1;

                        if ($pt_result && mysqli_num_rows($pt_result) > 0) {
                            while ($pt_row = mysqli_fetch_array($pt_result, MYSQLI_ASSOC)) {
                                echo "<tr>";
                                echo "<td>" . $sno . "</td>";
                                echo "<td>" . htmlspecialchars($pt_row['username']) . "</td>";
                                echo "<td>" . htmlspecialchars($pt_row['uid']) . "</td>";
                                echo "<td>" . htmlspecialchars($pt_row['trainer_name']) . "</td>";
                                echo "<td>" . htmlspecialchars($pt_row['enroll_date']) . "</td>";
                                echo "<td>" . htmlspecialchars($pt_row['expire_date']) . "</td>";
                                echo "<td>₹" . htmlspecialchars($pt_row['amount']) . "</td>";
                                echo "<td>" . htmlspecialchars($pt_row['payment_mode']) . "</td>";
                                echo "<td>" . htmlspecialchars($pt_row['received_by']) . "</td>";
                                echo "<td><a href='gen_pt_invoice.php?ptid=" . urlencode($pt_row['pt_id']) . "' target='_blank' class='a1-btn a1-blue' style='padding: 6px 12px !important; font-size: 11px; text-decoration: none;'>Print Memo</a></td>";
                                echo "</tr>";
                                $sno++;
                            }
                        } else {
                            echo "<tr><td colspan='10' style='text-align: center; color: #a3a3a3; padding: 20px;'>No personal training enrollment records found.</td></tr>";
                        }
                    ?>
                </tbody>
            </table>
        <?php endif; ?>

			<?php include('footer.php'); ?>
    	</div>

    </body>
</html>


