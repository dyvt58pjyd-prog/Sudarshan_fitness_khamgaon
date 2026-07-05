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


			
			
			<div class="a1-container a1-small a1-padding-32" style="margin-top:2px; margin-bottom:2px;">
        <div class="a1-card-8 a1-light-gray" style="width:600px; margin:0 auto;">
		<div class="a1-container a1-dark-gray a1-center">
        	<h6>EDIT MEMBER PROFILE</h6>
        </div>
       <form id="form1" name="form1" method="post" class="a1-container" action="edit_mem_submit.php">
         <table width="100%" border="0" align="center">
         <tr>
           <td height="35"><table width="100%" border="0" align="center">
           	 <tr>
           	   <td height="35">User ID:</td>
           	   <td height="35"><input id="boxxe" type="text" name="uid" readonly required value=<?php echo $memid?>></td>
         	   </tr>
             <tr>
               <td height="35">NAME:</td>
               <td height="35"><input id="boxxe" type="text" name="uname" value='<?php echo $name?>'></td>
             </tr>
             <tr>
               <td height="35">GENDER:</td>
               <td height="35"><select id="boxxe" name="gender" id="gender" required>

						<option <?php if($gender == 'Male'){echo("selected");}?> value="Male">Male</option>
						<option <?php if($gender == 'Female'){echo("selected");}?> value="Female">Female</option>
						<option <?php if($gender == 'Transgender'){echo("selected");}?> value="Transgender">Transgender</option>
						</select></td><br>
             </tr>
			  <tr>
               <td height="35">MOBILE:</td>
               <td height="35"><input id="boxxe" type="number" name="phone" maxlength="10" value=<?php echo $mobile?>></td>
             </tr>
             <tr>
               <td height="35">EMAIL:</td>
               <td height="35"><input id="boxxe" type="email" name="email" required value=<?php echo $email?>></td>
             </tr>
			 <tr>
               <td height="35">DATE OF BIRTH:</td>
               <td height="35"><input type="date" id="boxxe" name="dob" value=<?php echo $dob?>></td>
             </tr>
			 <tr>
               <td height="35">JOINING DATE:</td>
               <td height="35"><input type="date" id="boxxe" name="jdate" value=<?php echo $jdate?>></td>
             </tr>
			 <tr>
               <td height="35">LOGIN PASSWORD:</td>
               <td height="35"><input type="password" id="boxxe" name="password" value="<?php echo htmlspecialchars($user_pass); ?>" required></td>
             </tr>
			 <tr>
               <td height="35">ASSIGNED ROUTINE:</td>
               <td height="35"><select id="boxxe" name="routine">
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
               </select></td>
             </tr>

			 <tr>
               <td height="35">MEMBERSHIP PLAN:</td>
               <td height="35"><select id="boxxe" name="plan">
                   <option value="">--No Plan Assigned / Keep Current--</option>
                   <?php
                       $q_plans = "SELECT * FROM plan WHERE active='yes'";
                       $res_plans = mysqli_query($con, $q_plans);
                       
                       // Get the current plan ID for this user if they have one
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
               </select></td>
             </tr>
             <tr>
                <td height="35">DISCOUNT AMOUNT (₹):</td>
                <td height="35"><input type="number" id="boxxe" name="discount" value="0" min="0" placeholder="Enter discount amount"/></td>
              </tr>
              <tr>
                <td height="35">STREET NAME:</td>
                <td height="35"><input type="text" id="boxxe" name="stname" value='<?php echo $streetname?>'></td>
              </tr>
              <tr>
                <td height="35">STATE:</td>
                <td height="35"><input type="text" id="boxxe" name="state" value='<?php echo $state?>'></td>
              </tr>
              <tr>
                <td height="35">CITY:</td>
                <td height="35"><input type="text" id="boxxe" name="city" value='<?php echo $city?>'></td>
              </tr>
              <tr>
                <td height="35">ZIPCODE:</td>
                <td height="35"><input type="text" id="boxxe" name="zipcode" value='<?php echo $zipcode?>'></td>
              </tr>
              <tr>
                <td colspan="2" style="padding: 10px 0; border-top: 1px solid rgba(255,107,0,0.2);"><strong style="color: var(--accent-primary);">HEALTH METRICS (SIMPLE INFO):</strong></td>
              </tr>
              <tr>
                <td height="35">HEIGHT (cm):</td>
                <td height="35"><input type="number" id="boxxe" name="height" value="<?php echo htmlspecialchars($height); ?>" placeholder="e.g. 175"></td>
              </tr>
              <tr>
                <td height="35">WEIGHT (kg):</td>
                <td height="35"><input type="number" id="boxxe" name="weight" value="<?php echo htmlspecialchars($weight); ?>" placeholder="e.g. 70" step="0.1"></td>
              </tr>
              <tr>
                <td height="35">CALORIE TARGET (kcal):</td>
                <td height="35"><input type="number" id="boxxe" name="calorie" value="<?php echo htmlspecialchars($calorie); ?>" placeholder="e.g. 2000"></td>
              </tr>
              <tr>
                <td height="35">BODY FAT (%):</td>
                <td height="35"><input type="number" id="boxxe" name="fat" value="<?php echo htmlspecialchars($fat); ?>" placeholder="e.g. 15" step="0.1"></td>
              </tr>
              <tr>
                <td height="35">REMARKS/HEALTH GOAL:</td>
                <td height="35"><textarea id="boxxe" name="remarks" rows="3" style="resize: vertical; background: rgba(0,0,0,0.3); border: 1px solid rgba(255,255,255,0.15); color: #fff; padding: 6px; border-radius: 4px;"><?php echo htmlspecialchars($remarks); ?></textarea></td>
              </tr>

			 
			 
			 
             <br>
            
             <tr>
             <tr>
               <td height="35">&nbsp;</td>
               <td height="35"><input class="a1-btn a1-blue" type="submit" name="submit" id="submit" value="UPDATE" >
                 <input class="a1-btn a1-blue" type="reset" name="reset" id="reset" value="Reset"></td>
             </tr>
           </table></td>
         </tr>
         </table>
       </form>
    </div>
    </div>   
			
			
			
			
					


</body>
</html>	

<?php
} else {
    
}
?>
