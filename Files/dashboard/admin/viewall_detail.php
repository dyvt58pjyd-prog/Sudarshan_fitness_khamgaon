<?php
require '../../include/db_conn.php';
page_protect();

if (isset($_POST['name'])) {
    $memid = $_POST['name'];
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

		<hr />

			<?php
                    $query  = "SELECT u.*, a.streetName, a.city, a.state, a.zipcode, 
                                      h.calorie, h.height, h.weight, h.fat, h.remarks, 
                                      e.paid_date, e.expire, p.planName, p.amount, p.validity, p.description
                               FROM users u 
                               LEFT JOIN address a ON u.userid=a.id
                               LEFT JOIN health_status h ON u.userid=h.uid
                               LEFT JOIN enrolls_to e ON u.userid=e.uid
                               LEFT JOIN plan p ON e.pid=p.pid
                               WHERE u.userid='$memid'
                               ORDER BY e.expire DESC LIMIT 1";
				    $result = mysqli_query($con, $query);
				    
				    $name="";
				    $gender="";
				    $mobile = "";
				    $email   = "";
				    $dob	 = "";         
				    $jdate    = "";
				    $streetname="";
				    $state="";
				    $city="";  
				    $zipcode="";
				    $calorie="";
				    $height="";
				    $weight="";
				    $fat="";
				    $planname="";
				    $pamount="";
				    $pvalidity="";
				    $pdescription="";
				    $paiddate="";
				    $expire="";
				    $remarks="";
				    $bmi_val = "--";
				    $bmi_category = "";

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
				            $planname = isset($row['planName']) ? $row['planName'] : '';
				            $pamount  = isset($row['amount']) ? $row['amount'] : '';
				            $pvalidity = isset($row['validity']) ? $row['validity'] : '';
				            $pdescription = isset($row['description']) ? $row['description'] : '';
				            $paiddate = isset($row['paid_date']) ? $row['paid_date'] : '';
				            $expire  = isset($row['expire']) ? $row['expire'] : '';
				            $remarks = isset($row['remarks']) ? $row['remarks'] : '';

                            if (!empty($height) && !empty($weight) && floatval($height) > 0 && floatval($weight) > 0) {
                                $height_m = floatval($height) / 100;
                                $bmi_val = round(floatval($weight) / ($height_m * $height_m), 1);
                                
                                if ($bmi_val < 18.5) {
                                    $bmi_category = " (Underweight)";
                                } elseif ($bmi_val < 25) {
                                    $bmi_category = " (Normal)";
                                } elseif ($bmi_val < 30) {
                                    $bmi_category = " (Overweight)";
                                } else {
                                    $bmi_category = " (Obese)";
                                }
                            }
				        }
				    }
				    else{
				    	 echo "<html><head><script>alert('Change Unsuccessful');</script></head></html>";
				    	 echo mysqli_error($con);
				    }


				?>
			
			
			
			
			<div class="a1-container a1-small a1-padding-32" style="margin-top:2px; margin-bottom:2px;">
        <div class="a1-card-8 a1-light-gray" style="width:600px; margin:0 auto;">
		<div class="a1-container a1-dark-gray a1-center">
        	<h6>Edit Member Details</h3>
        </div>
       <form id="form1" name="form1" method="post" class="a1-container" action="edit_member.php">
         <table width="100%" border="0" align="center">
         <tbody><tr>
           <td height="35">
           	 </td></tr><tr>
           	   <td height="35">USER ID:</td>
           	   <td height="35"><input type="text" name="name" id="boxxe" readonly="" required="" value='<?php echo $memid?>'></td>
         	   </tr>
             <tr>
               <td height="35">NAME:</td>
               <td height="35"><input type="text" id="boxxe" readonly="" value='<?php echo $name?>'></td>
             </tr>
             <tr>
               <td height="35">GENDER:</td>
               <td height="35"><input type="text" id="boxxe" readonly="" value='<?php echo $gender?>'></td>
             </tr>
			 <tr>
               <td height="35">MOBILE:</td>
               <td height="35"><input type="text" id="boxxe" readonly="" maxlength="10" value='<?php echo $mobile ?>'></td>
             </tr>
			 <tr>
               <td height="35">EMAIL:</td>
               <td height="35"><input type="email" id="boxxe" readonly="" required="" value='<?php echo $email?>'></td>
             </tr>
			 <tr>
               <td height="35">DATE OF BIRTH</td>
               <td height="35"><input type="text" id="boxxe" readonly="" value='<?php echo $dob?>'></td>
             </tr>
			 <tr>
               <td height="35">JOINING DATE:</td>
               <td height="35"><input type="text" id="boxxe" readonly="" value='<?php echo $jdate?>'></td>
             </tr>
			 <tr>
               <td height="35">STREET NAME:</td>
               <td height="35"><input type="text" id="boxxe" readonly="" value='<?php echo $streetname?>'></td>
             </tr>
			 <tr>
               <td height="35">STATE:</td>
               <td height="35"><input type="text" id="boxxe" readonly="" name="state" value='<?php echo $state?>'></td>
             </tr>
			 <tr>
               <td height="35">CITY:</td>
               <td height="35"><input type="text" id="boxxe" readonly="" value='<?php echo $city?>'></td>
             </tr>
              <tr>
               <td height="35">ZIPCODE:</td>
               <td height="35"><input type="text" id="boxxe" readonly="" value='<?php echo $zipcode?>'></td>
             </tr>

			 <tr>
               <td height="35">PLAN NAME:</td>
               <td height="35"><input type="text" readonly="" id="boxxe" value='<?php echo $planname?>'></td>
             </tr>
			 <tr>
               <td height="35">PLAN AMOUNT:</td>
               <td height="35"><input type="text" readonly="" id="boxxe" value='<?php echo $pamount?>'></td>
             </tr>
			  <tr>
               <td height="35">PLAN VALIDITY:</td>
               <td height="35"><input type="text" readonly="" id="boxxe" value='<?php echo $pvalidity.' Month'?>'></td>
             </tr>
			  <tr>
               <td height="35">PLAN DESCRIPTION:</td>
               <td height="35"><input type="text" readonly="" id="boxxe" value='<?php echo $pdescription?>'></td>
             </tr>
			  <tr>
               <td height="35">PAID DATE:</td>
               <td height="35"><input type="text" readonly="" id="boxxe" value='<?php echo $paiddate?>'></td>
             </tr>
			 <tr>
                <td height="35">EXPIRED DATE:</td>
                <td height="35"><input type="text" readonly="" id="boxxe" value='<?php echo $expire?>'></td>
              </tr>
			 <tr>
                <td height="35">HEIGHT:</td>
                <td height="35"><input type="text" readonly="" id="boxxe" value='<?php echo !empty($height) ? htmlspecialchars($height).' cm' : 'Not Recorded'?>'></td>
              </tr>
			 <tr>
                <td height="35">WEIGHT:</td>
                <td height="35"><input type="text" readonly="" id="boxxe" value='<?php echo !empty($weight) ? htmlspecialchars($weight).' kg' : 'Not Recorded'?>'></td>
              </tr>
			 <tr>
                <td height="35">BMI:</td>
                <td height="35"><input type="text" readonly="" id="boxxe" value='<?php echo $bmi_val . $bmi_category?>'></td>
              </tr>
			 <tr>
                <td height="35">CALORIE TARGET:</td>
                <td height="35"><input type="text" readonly="" id="boxxe" value='<?php echo !empty($calorie) ? htmlspecialchars($calorie).' kcal' : 'Not Recorded'?>'></td>
              </tr>
			 <tr>
                <td height="35">BODY FAT:</td>
                <td height="35"><input type="text" readonly="" id="boxxe" value='<?php echo !empty($fat) ? htmlspecialchars($fat).'%' : 'Not Recorded'?>'></td>
              </tr>
			 <tr>
                <td height="35">HEALTH REMARKS:</td>
                <td height="35"><input type="text" readonly="" id="boxxe" value='<?php echo !empty($remarks) ? htmlspecialchars($remarks) : 'None'?>'></td>
              </tr>
              <?php
              require_once '../../include/bmi_helper.php';
              $bmi_sug = get_bmi_suggestions($height, $weight);
              if ($bmi_sug['category'] !== 'No Data'):
              ?>
              <tr>
                <td height="35" valign="top" style="padding-top: 10px;">BMI SUGGESTION GOAL:</td>
                <td height="35" style="padding-top: 10px;"><input type="text" readonly="" id="boxxe" value='<?php echo htmlspecialchars($bmi_sug['goal'])?>'></td>
              </tr>
              <tr>
                <td height="35" valign="top" style="padding-top: 10px;">RECOMMENDED WORKOUTS:</td>
                <td height="35" style="padding-top: 10px;">
                    <textarea readonly="" id="boxxe" rows="4" style="height: auto; width: 100%; box-sizing: border-box; background: rgba(0,0,0,0.05); color: #ffffff; padding: 8px; border: 1px solid #ccc; border-radius: 4px;"><?php echo htmlspecialchars($bmi_sug['workouts'])?></textarea>
                </td>
              </tr>
              <tr>
                <td height="35" valign="top" style="padding-top: 10px;">VEGETARIAN DIET:</td>
                <td height="35" style="padding-top: 10px;">
                    <textarea readonly="" id="boxxe" rows="4" style="height: auto; width: 100%; box-sizing: border-box; background: rgba(0,0,0,0.05); color: #ffffff; padding: 8px; border: 1px solid #ccc; border-radius: 4px;"><?php echo htmlspecialchars($bmi_sug['veg_diet'])?></textarea>
                </td>
              </tr>
              <tr>
                <td height="35" valign="top" style="padding-top: 10px;">NON-VEGETARIAN DIET:</td>
                <td height="35" style="padding-top: 10px;">
                    <textarea readonly="" id="boxxe" rows="4" style="height: auto; width: 100%; box-sizing: border-box; background: rgba(0,0,0,0.05); color: #ffffff; padding: 8px; border: 1px solid #ccc; border-radius: 4px;"><?php echo htmlspecialchars($bmi_sug['nonveg_diet'])?></textarea>
                </td>
              </tr>
              <?php endif; ?>

            
             
            
             <tr>
             </tr><tr>
               <td height="35">&nbsp;</td>
               <td height="35"><input class="a1-btn a1-blue" type="submit" name="submit" id="submit" value="EDIT">
                 <a href="table_view"><input class="a1-btn a1-blue" id="" value="BACK"></a></td>
             </tr>
           
         
         </tbody></table>
       
    </div>
    </div>   
			
			
					

			<?php include('footer.php'); ?>
    	</div>

  
</body>
</html>	

<?php
} else {
    
}
?>
