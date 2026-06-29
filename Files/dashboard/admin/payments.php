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
            background: rgba(255, 255, 255, 0.03);
            color: #a3a3a3;
            border: 1px solid rgba(255, 107, 0, 0.15);
            padding: 10px 22px;
            border-radius: 8px;
            text-decoration: none !important;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }
        .tab-btn:hover {
            background: rgba(255, 107, 0, 0.08);
            color: #ffffff;
            border-color: rgba(255, 107, 0, 0.4);
            box-shadow: 0 0 12px rgba(255, 107, 0, 0.2);
        }
        .tab-btn.active-tab {
            background: linear-gradient(135deg, rgba(255, 107, 0, 0.25), rgba(255, 107, 0, 0.08));
            color: #ffffff;
            border-color: #ff6b00;
            box-shadow: 0 0 15px rgba(255, 107, 0, 0.3);
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
                                
                                echo "<tr><td>".$sno."</td>";
                                echo "<td>" . htmlspecialchars($expire) . "</td>";
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


