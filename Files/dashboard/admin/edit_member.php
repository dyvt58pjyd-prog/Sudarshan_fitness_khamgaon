<?php
require '../../include/db_conn.php';
page_protect();

$memid = null;
if (isset($_POST['name'])) {
    $memid = $_POST['name'];
} elseif (isset($_GET['id'])) {
    $memid = $_GET['id'];
} elseif (isset($_GET['name'])) {
    $memid = $_GET['name'];
}

if ($memid) {
?>

<!DOCTYPE html>
<html lang="en">
<head>

    <title>SUDARSHAN FITNESS | Edit Member</title>
    <link rel="stylesheet" href="../../css/style.css"  id="style-resource-5">
    <script type="text/javascript" src="../../js/Script.js"></script>
    <link rel="stylesheet" href="../../css/dashMain.css">
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
	<link href="a1style.css" rel="stylesheet" type="text/css">
	
	<style>
 	#button1
	{
	width:126px;
	}
	#boxxe
	{
		width:230px;
	}
	.page-container .sidebar-menu #main-menu li#hassubopen > a {
	background-color: #2b303a;
	color: #ffffff;
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
			<h3>Edit Member Details</h3>
			<hr/>
			<?php
	    
				    $query  = "SELECT u.*, a.streetName, a.state, a.city, a.zipcode, 
				                      h.calorie, h.height, h.weight, h.fat, h.remarks
				               FROM users u 
				               LEFT JOIN address a ON u.userid=a.id
				               LEFT JOIN health_status h ON u.userid=h.uid
				               WHERE u.userid='$memid'
				               ORDER BY h.hid DESC LIMIT 1";
				    $result = mysqli_query($con, $query);
				    $sno    = 1;
				    
				    $name="";
				    $gender="";
				    $mobile="";
				    $email="";
				    $dob="";
				    $jdate="";
				    $streetname="";
				    $state="";
				    $city="";
				    $zipcode="";
				    $calorie="";
				    $height="";
				    $weight="";
				    $fat="";
				    $remarks="";
				    $user_tid=null;
				    $biometric_batch="1";

				    if ($result && mysqli_num_rows($result) > 0) {
				        while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
				            $name    = isset($row['username']) ? $row['username'] : '';
				            $gender  = isset($row['gender']) ? $row['gender'] : '';
				            $mobile  = isset($row['mobile']) ? $row['mobile'] : '';
				            $email   = isset($row['email']) ? $row['email'] : '';
				            $dob	 = isset($row['dob']) ? $row['dob'] : '';         
				            $jdate   = isset($row['joining_date']) ? $row['joining_date'] : '';
				            $streetname = isset($row['streetName']) ? $row['streetName'] : '';
				            $state   = isset($row['state']) ? $row['state'] : '';
				            $city    = isset($row['city']) ? $row['city'] : '';  
				            $zipcode = isset($row['zipcode']) ? $row['zipcode'] : '';
				            $calorie = isset($row['calorie']) ? $row['calorie'] : '';
				            $height  = isset($row['height']) ? $row['height'] : '';
				            $weight  = isset($row['weight']) ? $row['weight'] : '';
				            $fat     = isset($row['fat']) ? $row['fat'] : '';
				            $remarks = isset($row['remarks']) ? $row['remarks'] : '';
				            $user_tid = isset($row['tid']) ? $row['tid'] : null;
				            $biometric_batch = isset($row['biometric_batch']) ? $row['biometric_batch'] : '1';
				        }
				    }
				    else{
				    	 echo "<html><head><script>alert('Change Unsuccessful');</script></head></html>";
				    	 echo mysqli_error($con);
				    }

				    $pass_res = mysqli_query($con, "SELECT pass_key FROM admin WHERE username='$memid'");
				    $user_pass = "";
				    if ($pass_res && mysqli_num_rows($pass_res) > 0) {
				        $pass_row = mysqli_fetch_assoc($pass_res);
				        $user_pass = $pass_row['pass_key'];
				    }
				?>

            <style>
                .form-wrapper {
                    max-width: 800px;
                    margin: 0 auto;
                    padding: 10px;
                }
                .form-card {
                    background: var(--glass-bg);
                    backdrop-filter: blur(16px);
                    border: 1px solid var(--glass-border);
                    border-radius: 20px;
                    box-shadow: var(--glass-shadow);
                    overflow: hidden;
                    margin-bottom: 30px;
                }
                .form-header {
                    background: rgba(255, 107, 0, 0.15);
                    border-bottom: 1px solid rgba(255, 107, 0, 0.2);
                    padding: 20px;
                    text-align: center;
                }
                .form-header h3 {
                    margin: 0;
                    color: #fff;
                    font-weight: 700;
                    font-size: 18px;
                    letter-spacing: 0.5px;
                }
                .form-body {
                    padding: 30px 20px;
                }
                .form-section-title {
                    color: var(--accent-primary);
                    font-size: 14px;
                    font-weight: 700;
                    text-transform: uppercase;
                    margin-top: 25px;
                    margin-bottom: 15px;
                    border-bottom: 1px solid rgba(255, 107, 0, 0.15);
                    padding-bottom: 6px;
                    letter-spacing: 0.5px;
                }
                .form-section-title:first-of-type {
                    margin-top: 0;
                }
                .form-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
                    gap: 20px;
                }
                .form-group {
                    display: flex;
                    flex-direction: column;
                    gap: 6px;
                }
                .form-group.full-width {
                    grid-column: 1 / -1;
                }
                .form-label {
                    color: var(--text-muted);
                    font-size: 13px;
                    font-weight: 500;
                }
                .form-control-custom {
                    background: rgba(0, 0, 0, 0.3) !important;
                    border: 1px solid rgba(255, 255, 255, 0.15) !important;
                    color: #fff !important;
                    padding: 10px 14px !important;
                    border-radius: 8px !important;
                    font-size: 14px !important;
                    width: 100% !important;
                    box-sizing: border-box !important;
                    transition: border-color 0.2s, box-shadow 0.2s;
                }
                .form-control-custom:focus {
                    border-color: var(--accent-primary) !important;
                    box-shadow: 0 0 0 3px rgba(255, 107, 0, 0.2) !important;
                    outline: none !important;
                }
                .form-control-custom[readonly] {
                    background: rgba(255, 255, 255, 0.05) !important;
                    color: var(--text-muted) !important;
                    border-color: rgba(255, 255, 255, 0.08) !important;
                    cursor: not-allowed;
                }
                .form-actions {
                    display: flex;
                    gap: 12px;
                    justify-content: flex-end;
                    margin-top: 30px;
                    border-top: 1px solid rgba(255, 255, 255, 0.05);
                    padding-top: 20px;
                }
                .form-btn {
                    padding: 10px 24px !important;
                    border-radius: 8px !important;
                    font-size: 14px !important;
                    font-weight: 600 !important;
                    cursor: pointer;
                    transition: all 0.2s;
                }
            </style>
        </head>
        <body class="page-body page-fade" onload="collapseSidebar()">
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
                            <a href="#" class="sidebar-collapse-icon with-animation">
                                <i class="entypo-menu"></i>
                            </a>
                        </div>
                    </header>
                    <?php include('nav.php'); ?>
                </div>

                <div class="main-content">
                    <div class="row">
                        <!-- Profile Info and Notifications -->
                        <div class="col-md-6 col-sm-8 clearfix"></div>
                        <!-- Raw Links -->
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
                        <h3>Edit Member Profiles</h3>
                        <a href="view_mem.php" class="a1-btn" style="background: rgba(255,255,255,0.08) !important; color: #fff !important; text-decoration: none;">&larr; Back to List</a>
                    </div>
                    <hr/>

                    <div class="form-wrapper">
                        <div class="form-card">
                            <div class="form-header">
                                <h3>EDIT MEMBER DETAILS</h3>
                            </div>
                            <form id="form1" name="form1" method="post" action="edit_mem_submit.php">
                                <input type="hidden" name="uid" value="<?php echo htmlspecialchars($memid); ?>">
                                
                                <div class="form-body">
                                    <div class="form-section-title">Personal & Account Details</div>
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label class="form-label">User ID (Read Only):</label>
                                            <input class="form-control-custom" type="text" readonly value="<?php echo htmlspecialchars($memid); ?>">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Full Name:</label>
                                            <input class="form-control-custom" type="text" name="uname" value="<?php echo htmlspecialchars($name); ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Gender:</label>
                                            <select class="form-control-custom" name="gender" required>
                                                <option <?php if($gender == 'Male'){echo("selected");}?> value="Male">Male</option>
                                                <option <?php if($gender == 'Female'){echo("selected");}?> value="Female">Female</option>
                                                <option <?php if($gender == 'Transgender'){echo("selected");}?> value="Transgender">Transgender</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Mobile Number:</label>
                                            <input class="form-control-custom" type="text" name="phone" maxlength="10" value="<?php echo htmlspecialchars($mobile); ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Email Address:</label>
                                            <input class="form-control-custom" type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Date of Birth:</label>
                                            <input class="form-control-custom" type="date" name="dob" value="<?php echo htmlspecialchars($dob); ?>">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Joining Date:</label>
                                            <input class="form-control-custom" type="date" name="jdate" value="<?php echo htmlspecialchars($jdate); ?>">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Login Password:</label>
                                            <input class="form-control-custom" type="password" name="password" value="<?php echo htmlspecialchars($user_pass); ?>" required>
                                        </div>
                                    </div>

                                    <div class="form-section-title">Assigned Routine & Membership Plans</div>
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label class="form-label">Assigned Workout Routine:</label>
                                            <select class="form-control-custom" name="routine">
                                                <option value="">--No Routine Assigned--</option>
                                                <?php
                                                    $q_routine = "SELECT * FROM timetable";
                                                    $res_routine = mysqli_query($con, $q_routine);
                                                    if ($res_routine && mysqli_num_rows($res_routine) > 0) {
                                                        while ($row_r = mysqli_fetch_assoc($res_routine)) {
                                                            $selected = ($user_tid == $row_r['tid']) ? "selected" : "";
                                                            echo "<option value='".$row_r['tid']."' $selected>".htmlspecialchars($row_r['tname'])."</option>";
                                                        }
                                                    }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Update Membership Plan:</label>
                                            <select class="form-control-custom" name="plan">
                                                <option value="">--No Plan Assigned / Keep Current--</option>
                                                <?php
                                                    $q_plans = "SELECT * FROM plan WHERE active='yes'";
                                                    $res_plans = mysqli_query($con, $q_plans);
                                                    
                                                    $current_pid = "";
                                                    $q_curr = "SELECT pid FROM enrolls_to WHERE uid='$memid' AND renewal='yes' ORDER BY expire DESC LIMIT 1";
                                                    $res_curr = mysqli_query($con, $q_curr);
                                                    if ($res_curr && mysqli_num_rows($res_curr) > 0) {
                                                        $row_curr = mysqli_fetch_assoc($res_curr);
                                                        $current_pid = $row_curr['pid'];
                                                    }
                                                    
                                                    if ($res_plans && mysqli_num_rows($res_plans) > 0) {
                                                        while ($row_p = mysqli_fetch_assoc($res_plans)) {
                                                            $selected = ($current_pid == $row_p['pid']) ? "selected" : "";
                                                            echo "<option value='".$row_p['pid']."' $selected>".htmlspecialchars($row_p['planName'])."</option>";
                                                        }
                                                    }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Plan Discount Amount (₹):</label>
                                            <input class="form-control-custom" type="number" name="discount" value="0" min="0" placeholder="0">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Biometric Shift / Batch Option:</label>
                                            <select class="form-control-custom" name="biometric_batch" required>
                                                <option value="1" <?php if($biometric_batch == '1'){echo 'selected';} ?>>Batch 1 (General: 6 AM - 11 AM)</option>
                                                <option value="2" <?php if($biometric_batch == '2'){echo 'selected';} ?>>Batch 2 (Women Only: 4 PM - 5 PM)</option>
                                                <option value="3" <?php if($biometric_batch == '3'){echo 'selected';} ?>>Batch 3 (Evening General: 5 PM - 10 PM)</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="form-section-title">Address Information</div>
                                    <div class="form-grid">
                                        <div class="form-group full-width">
                                            <label class="form-label">Street Address:</label>
                                            <input class="form-control-custom" type="text" name="stname" value="<?php echo htmlspecialchars($streetname); ?>">
                                        </div>
                                        <div class="form-grid full-width" style="grid-template-columns: repeat(3, 1fr); gap: 15px;">
                                            <div class="form-group">
                                                <label class="form-label">City:</label>
                                                <input class="form-control-custom" type="text" name="city" value="<?php echo htmlspecialchars($city); ?>">
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label">State:</label>
                                                <input class="form-control-custom" type="text" name="state" value="<?php echo htmlspecialchars($state); ?>">
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label">Zip Code:</label>
                                                <input class="form-control-custom" type="text" name="zipcode" value="<?php echo htmlspecialchars($zipcode); ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-section-title">Health Metrics & Goal Logs</div>
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label class="form-label">Height (cm):</label>
                                            <input class="form-control-custom" type="number" name="height" value="<?php echo htmlspecialchars($height); ?>" placeholder="e.g. 175">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Weight (kg):</label>
                                            <input class="form-control-custom" type="number" name="weight" value="<?php echo htmlspecialchars($weight); ?>" placeholder="e.g. 70" step="0.1">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Target Calorie Intake (kcal):</label>
                                            <input class="form-control-custom" type="number" name="calorie" value="<?php echo htmlspecialchars($calorie); ?>" placeholder="e.g. 2000">
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">Body Fat Ratio (%):</label>
                                            <input class="form-control-custom" type="number" name="fat" value="<?php echo htmlspecialchars($fat); ?>" placeholder="e.g. 15" step="0.1">
                                        </div>
                                        <div class="form-group full-width">
                                            <label class="form-label">General Health Goal / Trainer Notes:</label>
                                            <textarea class="form-control-custom" name="remarks" rows="3" style="resize: vertical;" placeholder="Type goals, allergies, medical notes..."><?php echo htmlspecialchars($remarks); ?></textarea>
                                        </div>
                                    </div>

                                    <div class="form-actions">
                                        <input class="form-btn a1-btn a1-blue" type="submit" name="submit" value="SAVE CHANGES">
                                        <input class="form-btn a1-btn a1-orange" type="reset" value="RESET">
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>   
			
			
			
			
					


</body>
</html>	

<?php
} else {
    
}
?>
