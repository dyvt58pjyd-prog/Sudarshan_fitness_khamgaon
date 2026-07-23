<?php
require '../../include/db_conn.php';
page_protect();

// Find next membership ID starting from 101
$next_id = 101;
$res_max = mysqli_query($con, "SELECT userid FROM users WHERE userid REGEXP '^[0-9]+$' AND CAST(userid AS UNSIGNED) < 100000000");
if ($res_max && mysqli_num_rows($res_max) > 0) {
    $max_val = 100;
    while ($row_max = mysqli_fetch_assoc($res_max)) {
        $val = intval($row_max['userid']);
        if ($val > $max_val) {
            $max_val = $val;
        }
    }
    $next_id = $max_val + 1;
}

$gym = get_gym_details($con);
$upi_id = isset($gym['upi_id']) ? $gym['upi_id'] : '';
$gym_name = isset($gym['gym_name']) ? $gym['gym_name'] : 'Sudarshan Fitness';

// Check if Welcome Bonus limit is reached
$wb_limit_reached = false;
$cnt_q = mysqli_query($con, "SELECT COUNT(*) as cnt FROM enrolls_to e JOIN plan p ON e.pid = p.pid WHERE e.discount_amount > 0 AND (p.amount=12000 OR p.amount=6000)");
if ($cnt_q) {
    $cnt_row = mysqli_fetch_assoc($cnt_q);
    if (intval($cnt_row['cnt']) >= 100) {
        $wb_limit_reached = true;
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>

    <title>SUDARSHAN FITNESS | New User</title>
    <link rel="stylesheet" href="../../css/style.css"  id="style-resource-5">
    <script type="text/javascript" src="../../js/Script.js"></script>
    <link rel="stylesheet" href="../../css/dashMain.css">
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
    <link rel="stylesheet" href="../../css/premium.css">
    <link href="a1style.css" type="text/css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js"></script>
    <style>
    	.page-container .sidebar-menu #main-menu li#regis > a {
    	background-color: #2b303a;
    	color: #ffffff;
		}
        #boxx, .boxx-style {
            width: 320px !important;
            height: 40px !important;
            padding: 8px 12px !important;
            font-size: 14px !important;
            border: 1px solid rgba(255, 107, 0, 0.3) !important;
            border-radius: 8px !important;
            background: rgba(15, 23, 42, 0.6) !important;
            color: #ffffff !important;
            box-sizing: border-box !important;
            transition: all 0.2s ease-in-out !important;
        }
        #boxx:focus, .boxx-style:focus {
            border-color: #ff6b00 !important;
            outline: none !important;
            box-shadow: 0 0 8px rgba(255, 107, 0, 0.3) !important;
        }
        #boxx[readonly], .boxx-style[readonly] {
            background: rgba(255, 255, 255, 0.05) !important;
            color: rgba(255, 255, 255, 0.4) !important;
            border-color: rgba(255, 255, 255, 0.1) !important;
        }
        select#boxx, select.boxx-style {
            height: 40px !important;
            background-color: rgba(15, 23, 42, 0.6) !important;
            color: #ffffff !important;
            padding: 8px 12px !important;
            border: 1px solid rgba(255, 107, 0, 0.3) !important;
        }
        select#boxx option, select.boxx-style option {
            background: #121212 !important;
            color: #ffffff !important;
        }
        /* Custom table td spacing to make sections distinct */
        .a1-container table td {
            padding: 10px 0 !important;
            vertical-align: middle !important;
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

		
        	<h3>New Registration</h3>

		<hr />
        
        <div class="a1-container a1-small a1-padding-32" style="margin-top:2px; margin-bottom:2px;">
        <div class="a1-card-8 a1-light-gray" style="width:620px; margin:0 auto; border-radius: 12px; overflow: hidden; border: 1px solid rgba(255, 107, 0, 0.2); box-shadow: 0 4px 20px rgba(0,0,0,0.4);">
		<div class="a1-container a1-dark-gray a1-center">
        	<h6>NEW ENTRY</h6>
        </div>
       <form id="form1" name="form1" method="post" class="a1-container" action="new_submit.php" enctype="multipart/form-data">
         <table width="100%" border="0" align="center">
         <tr>
           <td height="35"><table width="100%" border="0" align="center">
            	 <tr>
           	   <td height="35">MEMBERSHIP ID:</td>
            	   <td height="35">
            	       <input type="text" id="boxx" name="m_id" value="<?php echo $next_id; ?>" readonly required/>
            	       <div style="font-size: 11px; color: #ff6b00; margin-top: 5px; font-weight: bold;">
            	           <i class="entypo-info-circled"></i> IMPORTANT: When enrolling this member on the Biometric Machine, use this exact ID number (<span style="color:white;"><?php echo $next_id; ?></span>).
            	       </div>
            	   </td>
         	   </tr>
			   
			   <tr>
               <td height="35">NAME:</td>
               <td height="35"><input name="u_name" id="boxx"  required/></td>
             </tr>
             <tr>
               <td height="35">STREET NAME:</td>
               <td height="35"><input  name="street_name" id="boxx"   required/></td>
             </tr>
             <tr>
               <td height="35">CITY:</td>
               <td height="35"><input type="text" name="city" id="boxx" required /></td>
             </tr>
             <tr>
               <td height="35">ZIPCODE:</td>
               <td height="35"><input type="number" name="zipcode" id="boxx" placeholder="Enter Pincode" required /></td>
             </tr>
            <tr>
               <td height="35">STATE:</td>
               <td height="35"><input type="text" name="state" id="boxx" value="Maharashtra" readonly style="background-color: #222; color: #888;" required size="30"></td>
             </tr>
            <tr>
               <td height="35">GENDER:</td>
               <td height="35"><select name="gender" id="boxx" required>
 					<option value="">--Please Select--</option>
 					<option value="Male">Male</option>
 					<option value="Female">Female</option>
 					<option value="Transgender">Transgender</option>
 				</select></td>
             </tr>
            <tr>
               <td height="35">DATE OF BIRTH:</td>
               <td height="35"><input type="date" name="dob" id="boxx" required/ size="30"></td>
             </tr>
			 <tr>
               <td height="35">PHONE NO:</td>
               <td height="35"><input type="number" name="mobile" id="boxx" maxlength="10" size="30"></td>
             </tr>
			  <tr>
               <td height="35">EMAIL ID:</td>
               <td height="35"><input type="email" name="email" id="boxx" size="30"></td>
             </tr>
			 <tr>
               <td height="35">HEIGHT (cm):</td>
               <td height="35"><input type="number" name="height" id="boxx" min="50" max="250" placeholder="e.g. 175" required/></td>
             </tr>
			 <tr>
               <td height="35">WEIGHT (kg):</td>
               <td height="35"><input type="number" name="weight" id="boxx" min="10" max="300" placeholder="e.g. 70" required/></td>
             </tr>
             <tr>
               <td height="35">JOINING DATE:</td>
                <td height="35"><input type="date" name="jdate" id="boxx" value="<?php echo (date('Y-m-d') < '2026-07-08') ? '2026-07-08' : date('Y-m-d'); ?>" required size="30"></td>
             </tr>
             <tr>
               <td height="35">TRANSACTION DATE:</td>
                <td height="35"><input type="date" name="transaction_date" id="boxx" value="<?php echo date('Y-m-d'); ?>" required size="30"></td>
             </tr>
              <tr>
                <td height="35">BIOMETRIC SHIFT / BATCH:</td>
                <td height="35"><select name="biometric_batch" id="boxx" required>
                    <option value="1" selected>Batch 1 (General: 6 AM - 11 AM)</option>
                    <option value="2">Batch 2 (Women Only: 4 PM - 5 PM)</option>
                    <option value="3">Batch 3 (Evening General: 5 PM - 10 PM)</option>
                </select></td>
              </tr>
              <tr>
                <td height="35">ASSIGN ROUTINE:</td>
                <td height="35"><select name="routine" id="boxx">
                   <option value="">--No Routine Assigned--</option>
                   <?php
                       $q_routine = "SELECT * FROM timetable";
                       $res_routine = mysqli_query($con, $q_routine);
                       if ($res_routine && mysqli_num_rows($res_routine) > 0) {
                           while ($row_r = mysqli_fetch_assoc($res_routine)) {
                               echo "<option value='".$row_r['tid']."'>".htmlspecialchars($row_r['tname'])."</option>";
                           }
                       }
                   ?>
                   </select></td>
             </tr>
             <tr>
               <td height="35">POOL MEMBERSHIP:</td>
               <td height="35">
                 <select name="pool_group_id" class="boxx-style">
                   <option value="">-- No Pool (Individual) --</option>
                   <?php
                       $q_pool = "SELECT userid, username FROM users";
                       $res_pool = mysqli_query($con, $q_pool);
                       if ($res_pool && mysqli_num_rows($res_pool) > 0) {
                           while ($row_p = mysqli_fetch_assoc($res_pool)) {
                               echo "<option value='".$row_p['userid']."'>".htmlspecialchars($row_p['username'])."</option>";
                           }
                       }
                   ?>
                 </select>
               </td>
             </tr>
             <tr>
               <td height="35">PERSONAL TRAINER:</td>
               <td height="35"><select name="trainer_id" id="trainer_id_select" class="boxx-style" onchange="togglePtFields(this.value)">
                   <option value="">--No Trainer Assigned--</option>
                   <?php
                       $q_t = "SELECT username, Full_name FROM admin WHERE role='trainer' ORDER BY Full_name ASC";
                       $r_t = mysqli_query($con, $q_t);
                       if ($r_t && mysqli_num_rows($r_t) > 0) {
                           while ($row_t = mysqli_fetch_assoc($r_t)) {
                               echo "<option value='".$row_t['username']."'>".htmlspecialchars($row_t['Full_name'])."</option>";
                           }
                       }
                   ?>
               </select></td>
             </tr>
             <tr class="pt-fields-row" style="display: none;">
               <td height="35">PT DURATION:</td>
               <td height="35"><select name="pt_duration" id="pt_duration" class="boxx-style">
                   <option value="1">1 Month</option>
                   <option value="2">2 Months</option>
                   <option value="3" selected>3 Months</option>
                   <option value="6">6 Months</option>
                   <option value="12">12 Months</option>
               </select></td>
             </tr>
             <tr class="pt-fields-row" style="display: none;">
               <td height="35">PT FEES AMOUNT (₹):</td>
               <td height="35"><input type="number" name="pt_fees" id="pt_fees" class="boxx-style" min="0" placeholder="Enter PT fees amount" value="0"/></td>
             </tr>
             <tr>
                <td height="35">PLAN:</td>
                <td height="35"><select name="plan" id="plan_select" required onchange="myplandetail(this.value); validateDiscount(); checkCoupleAutoSelect();">
					<option value="">--Please Select--</option>
					<?php
						$query="select * from plan where active='yes'";
						$result=mysqli_query($con,$query);
						if($result && mysqli_num_rows($result) > 0){
							while($row=mysqli_fetch_assoc($result)){
								echo "<option value='".$row['pid']."' data-discount-lock='".intval($row['discount_lock'])."' data-price='".intval($row['amount'])."'>".htmlspecialchars($row['planName'])."</option>";
							}
						}
					?>
				</select></td>
              </tr>
              <tr>
                <td height="35">PAYMENT MODE:</td>
                <td height="35"><select name="payment_mode" id="payment_mode_select" required onchange="generateStaffQR()">
                    <option value="Cash" selected>Cash</option>
                    <option value="UPI">UPI</option>
                </select></td>
              </tr>
              <tr>
                <td height="35">AMOUNT PAID NOW (₹):</td>
                <td height="35"><input type="number" name="paid_amount" id="paid_amount" placeholder="Leave empty if fully paid" size="40" onkeyup="checkBalance()"></td>
              </tr>
              <tr id="balance_due_row" style="display:none;">
                <td height="35">BALANCE DUE DATE:</td>
                <td height="35"><input type="date" name="balance_due_date" id="balance_due_date"></td>
              </tr>
              <script>
                function checkBalance() {
                    var total = 0;
                    var planSelect = document.getElementById('plan_select');
                    if (planSelect && planSelect.selectedIndex >= 0) {
                        var opt = planSelect.options[planSelect.selectedIndex];
                        if (opt && opt.getAttribute('data-price')) {
                            total = parseFloat(opt.getAttribute('data-price'));
                        }
                    }
                    var discount = parseFloat(document.getElementById('discount_input').value) || 0;
                    total = total - discount;
                    
                    var paid = parseFloat(document.getElementById('paid_amount').value);
                    if (!isNaN(paid) && paid < total) {
                        document.getElementById('balance_due_row').style.display = 'table-row';
                        document.getElementById('balance_due_date').required = true;
                    } else {
                        document.getElementById('balance_due_row').style.display = 'none';
                        document.getElementById('balance_due_date').required = false;
                    }
                }
                // Call it when plan changes
                setInterval(checkBalance, 1000);
              </script>
              <tr>
                <td colspan="2">
                    <div id="staff-qr-container" style="display: none; background: rgba(0,0,0,0.2); padding: 15px; border-radius: 8px; border: 1px solid rgba(255,107,0,0.3); text-align: center; margin: 10px 0;">
                        <h4 style="color: #fff; margin-top: 0; margin-bottom: 5px;">Scan to Pay: <span id="staff-qr-amount" style="color: #ff6b00;">₹0</span></h4>
                        <p style="color: var(--text-muted); font-size: 12px; margin-bottom: 15px;">Ask member to scan this QR code. Proceed to submit only after physical verification.</p>
                        <img id="staff-qr-code" style="display: inline-block; background: #fff; padding: 10px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.3); width: 200px; height: 200px;" />
                    </div>
                </td>
              </tr>
              <tr>
                <td height="35">DISCOUNT AMOUNT (₹):</td>
                <td height="35">
                    <input type="number" name="discount" id="discount_input" value="0" min="0" placeholder="Enter discount amount" required oninput="validateDiscount()"/>
                    <span id="discount_warning" style="color: #ef4444; font-size: 12px; display: none; margin-top: 5px; font-weight: bold; display: block;"></span>
                </td>
              </tr>
			
	    <tbody id="plandetls">
             
            </tbody>

             <tr>
               <td height="35" valign="top">MEMBER PHOTO:</td>
               <td height="35">
                 <div style="margin-bottom: 10px;">
                   <input type="file" name="member_photo_file" id="member_photo_file" accept="image/*" onchange="previewUpload(this)" style="display: block; margin-bottom: 5px;">
                   <span style="color: var(--text-muted); font-size: 11px;">Or capture using camera:</span>
                 </div>
                 
                 <div id="camera_area" class="new-member-camera-box" style="display: none; width: 220px; margin-bottom: 10px;">
                   <video id="webcam" autoplay playsinline width="220" height="165" style="display: block;"></video>
                   <canvas id="photo_canvas" width="220" height="165" style="display: none;"></canvas>
                 </div>
                 
                 <div style="margin-bottom: 15px; display: flex; gap: 5px; flex-wrap: wrap;">
                    <button type="button" id="start_cam_btn" class="a1-btn a1-blue" onclick="startWebcam()" style="padding: 4px 8px; font-size: 12px; margin-top: 5px;">Start Camera</button>
                    <button type="button" id="snap_btn" class="a1-btn a1-green" onclick="capturePhoto()" style="padding: 4px 8px; font-size: 12px; display: none; margin-top: 5px;">Capture</button>
                    <button type="button" id="switch_cam_btn" class="a1-btn a1-blue" onclick="switchWebcam()" style="padding: 4px 8px; font-size: 12px; display: none; margin-top: 5px;">Switch Camera</button>
                    <button type="button" id="reset_cam_btn" class="a1-btn a1-orange" onclick="resetWebcam()" style="padding: 4px 8px; font-size: 12px; display: none; margin-top: 5px;">Reset</button>
                  </div>
                 
                 <div id="photo_preview_container" style="display: none; margin-bottom: 10px;">
                   <span style="color: var(--text-muted); font-size: 11px; display: block; margin-bottom: 3px;">Selected / Captured Photo:</span>
                   <img id="photo_preview" src="" width="120" style="border-radius: 8px; border: 1px solid rgba(255,255,255,0.25);">
                 </div>
                 
                 <input type="hidden" name="member_photo_base64" id="member_photo_base64">
               </td>
             </tr>

             <tr>
               <td height="35">FITNESS GOAL:</td>
               <td height="35">
                 <select name="fitness_goal" id="boxx" required>
                   <option value="general">General Fitness & Active Lifestyle</option>
                   <option value="weight_loss">Weight Loss (Shred & Tone)</option>
                   <option value="muscle_gain">Muscle Gain (Hypertrophy)</option>
                 </select>
               </td>
             </tr>
             
             <!-- COUPLE PLAN TOGGLE -->
             <tr>
                 <td height="35">REGISTER AS COUPLE?</td>
                 <td height="35">
                     <label style="color: #fff; cursor: pointer; display: flex; align-items: center; gap: 10px;">
                         <input type="checkbox" name="is_couple" id="is_couple" value="1" onchange="toggleCoupleFields()" style="width: 20px; height: 20px;">
                         Yes, register a partner with this plan
                     </label>
                 </td>
             </tr>
             
             <!-- COUPLE FIELDS -->
             <tbody id="couple_fields" style="display: none; background: rgba(255, 107, 0, 0.05);">
                 <tr><td colspan="2"><h4 style="color: #ff6b00; margin-top: 15px; margin-bottom: 5px;">Partner Details</h4><hr style="border-color: rgba(255,107,0,0.2);"></td></tr>
                 <tr>
                   <td height="35">PARTNER NAME:</td>
                   <td height="35"><input name="partner_name" id="partner_name" class="boxx-style" placeholder="Enter partner's full name"/></td>
                 </tr>
                 <tr>
                   <td height="35">PARTNER GENDER:</td>
                   <td height="35"><select name="partner_gender" id="partner_gender" class="boxx-style">
                        <option value="">--Please Select--</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Transgender">Transgender</option>
                    </select></td>
                 </tr>
                 <tr>
                   <td height="35">PARTNER DOB:</td>
                   <td height="35"><input type="date" name="partner_dob" id="partner_dob" class="boxx-style"></td>
                 </tr>
                 <tr>
                   <td height="35">PARTNER PHONE:</td>
                   <td height="35"><input type="number" name="partner_mobile" id="partner_mobile" class="boxx-style" maxlength="10" placeholder="Required for separate login login"></td>
                 </tr>
             </tbody>
             
             <script>
                 function toggleCoupleFields() {
                     var isCouple = document.getElementById('is_couple').checked;
                     var fields = document.getElementById('couple_fields');
                     
                     if (isCouple) {
                         fields.style.display = 'table-row-group';
                         document.getElementById('partner_name').required = true;
                         document.getElementById('partner_gender').required = true;
                         document.getElementById('partner_dob').required = true;
                     } else {
                         fields.style.display = 'none';
                         document.getElementById('partner_name').required = false;
                         document.getElementById('partner_gender').required = false;
                         document.getElementById('partner_dob').required = false;
                     }
                 }

                 function checkCoupleAutoSelect() {
                      var select = document.getElementById('plan_select');
                      if (select && select.selectedIndex >= 0) {
                          var text = select.options[select.selectedIndex].text.toLowerCase();
                          var isCoupleCheckbox = document.getElementById('is_couple');
                          if (text.indexOf('couple') !== -1) {
                              isCoupleCheckbox.checked = true;
                              toggleCoupleFields();
                          }
                      }
                 }
             </script>
             
             <tr>
             <td height="35">&nbsp;</td>
             <td height="35"><input class="a1-btn a1-blue" type="submit" name="submit" id="submit" value="Register Member" >
                 <input class="a1-btn a1-blue" type="reset" name="reset" id="reset" value="Reset"></td>
             </tr>
           </table></td>
         </tr>
         </table>
       </form>
    </div>
    </div>   
        
        <script>
        	function togglePtFields(val) {
        		var rows = document.querySelectorAll('.pt-fields-row');
        		var ptFees = document.getElementById('pt_fees');
        		if (val !== "") {
        			rows.forEach(function(row) {
        				row.style.display = '';
        			});
        			if (ptFees && (ptFees.value === "" || ptFees.value === "0")) {
        				ptFees.value = "1500";
        			}
        		} else {
        			rows.forEach(function(row) {
        				row.style.display = 'none';
        			});
        			if (ptFees) {
        				ptFees.value = "0";
        			}
        		}
        	}

        	function myplandetail(str){

        		if(str==""){
        			document.getElementById("plandetls").innerHTML = "";
        			return;
        		}else{
        			if (window.XMLHttpRequest) {
           		 // code for IE7+, Firefox, Chrome, Opera, Safari
           			 xmlhttp = new XMLHttpRequest();
       				 }
       			 	xmlhttp.onreadystatechange = function() {
            		if (this.readyState == 4 && this.status == 200) {
               		 document.getElementById("plandetls").innerHTML=this.responseText;
                
            			}
        			};
        			
       				 xmlhttp.open("GET","plandetail.php?q="+str,true);
       				 xmlhttp.send();	
        		}
        		
        	}

            let stream = null;
            let currentFacingMode = "user";

            function startWebcam() {
                const video = document.getElementById('webcam');
                const cameraArea = document.getElementById('camera_area');
                const startBtn = document.getElementById('start_cam_btn');
                const snapBtn = document.getElementById('snap_btn');
                const switchBtn = document.getElementById('switch_cam_btn');
                const resetBtn = document.getElementById('reset_cam_btn');
                
                cameraArea.style.display = 'block';
                startBtn.style.display = 'none';
                snapBtn.style.display = 'inline-block';
                switchBtn.style.display = 'inline-block';
                resetBtn.style.display = 'none';
                
                if (stream) {
                    stream.getTracks().forEach(track => track.stop());
                }

                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    alert("Camera Blocked by Browser Security!\n\nTo capture member photos on mobile or tablet, modern web browsers require a secure connection (HTTPS) or a 'localhost' hostname.\n\nPlease host locally using a secure tunnel (e.g. Localtunnel or Ngrok with HTTPS) to use device cameras.");
                    cameraArea.style.display = 'none';
                    startBtn.style.display = 'inline-block';
                    snapBtn.style.display = 'none';
                    switchBtn.style.display = 'none';
                    return;
                }
                
                navigator.mediaDevices.getUserMedia({ video: { width: 320, height: 240, facingMode: currentFacingMode } })
                    .then(function(mediaStream) {
                        stream = mediaStream;
                        video.srcObject = mediaStream;
                    })
                    .catch(function(err) {
                        alert("Unable to access camera: " + err);
                        cameraArea.style.display = 'none';
                        startBtn.style.display = 'inline-block';
                        snapBtn.style.display = 'none';
                        switchBtn.style.display = 'none';
                    });
            }

            function switchWebcam() {
                currentFacingMode = (currentFacingMode === "user") ? "environment" : "user";
                startWebcam();
            }

            function capturePhoto() {
                const video = document.getElementById('webcam');
                const canvas = document.getElementById('photo_canvas');
                const preview = document.getElementById('photo_preview');
                const previewContainer = document.getElementById('photo_preview_container');
                const base64Input = document.getElementById('member_photo_base64');
                const resetBtn = document.getElementById('reset_cam_btn');
                const snapBtn = document.getElementById('snap_btn');
                const switchBtn = document.getElementById('switch_cam_btn');
                
                const context = canvas.getContext('2d');
                context.drawImage(video, 0, 0, 220, 165);
                
                const dataUrl = canvas.toDataURL('image/jpeg');
                base64Input.value = dataUrl;
                
                preview.src = dataUrl;
                previewContainer.style.display = 'block';
                
                snapBtn.style.display = 'none';
                switchBtn.style.display = 'none';
                resetBtn.style.display = 'inline-block';
                
                // Clear file upload input to prioritize webcam capture
                document.getElementById('member_photo_file').value = '';
                
                // Stop camera stream
                if (stream) {
                    stream.getTracks().forEach(track => track.stop());
                    document.getElementById('camera_area').style.display = 'none';
                }
            }

            function resetWebcam() {
                document.getElementById('member_photo_base64').value = '';
                document.getElementById('photo_preview_container').style.display = 'none';
                document.getElementById('photo_preview').src = '';
                document.getElementById('reset_cam_btn').style.display = 'none';
                document.getElementById('switch_cam_btn').style.display = 'none';
                document.getElementById('start_cam_btn').style.display = 'inline-block';
            }

            function previewUpload(input) {
                if (input.files && input.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        document.getElementById('photo_preview').src = e.target.result;
                        document.getElementById('photo_preview_container').style.display = 'block';
                        // Clear base64 capture
                        document.getElementById('member_photo_base64').value = '';
                        // Stop camera if running
                        if (stream) {
                            stream.getTracks().forEach(track => track.stop());
                            document.getElementById('camera_area').style.display = 'none';
                            document.getElementById('snap_btn').style.display = 'none';
                            document.getElementById('switch_cam_btn').style.display = 'none';
                            document.getElementById('reset_cam_btn').style.display = 'none';
                            document.getElementById('start_cam_btn').style.display = 'inline-block';
                        }
                    }
                    reader.readAsDataURL(input.files[0]);
                }
            }

            function validateDiscount() {
                const planSelect = document.getElementById('plan_select');
                const discountInput = document.getElementById('discount_input');
                const warningSpan = document.getElementById('discount_warning');
                const submitBtn = document.getElementById('submit');

                if (!planSelect || !discountInput || !submitBtn) return;

                const selectedOpt = planSelect.options[planSelect.selectedIndex];
                if (!selectedOpt || selectedOpt.value === '') {
                    warningSpan.style.display = 'none';
                    submitBtn.disabled = false;
                    return;
                }

                const maxDiscount = parseInt(selectedOpt.getAttribute('data-discount-lock')) || 0;
                const typedDiscount = parseInt(discountInput.value) || 0;

                if (typedDiscount > maxDiscount) {
                    warningSpan.innerText = 'Warning: Discount can be up to ₹' + maxDiscount + ' only for this plan';
                    warningSpan.style.display = 'block';
                    submitBtn.disabled = true;
                } else {
                    warningSpan.style.display = 'none';
                    submitBtn.disabled = false;
                }
            }
        


        document.getElementById('discount_input').addEventListener('input', generateStaffQR);
        document.getElementById('trainer_id_select').addEventListener('change', generateStaffQR);
        document.getElementById('pt_duration').addEventListener('change', generateStaffQR);
        var paidInput = document.getElementById('paid_amount');
        if (paidInput) {
            paidInput.addEventListener('input', generateStaffQR);
        }
        
        const wbLimitReached = <?php echo $wb_limit_reached ? 'true' : 'false'; ?>;
        
        document.getElementById('plan_select').addEventListener('change', function() {
            var planSelect = document.getElementById('plan_select');
            var selectedOpt = planSelect.options[planSelect.selectedIndex];
            var discountInput = document.getElementById('discount_input');
            
            if (selectedOpt && selectedOpt.value !== '') {
                var planPrice = parseFloat(selectedOpt.getAttribute('data-price')) || 0;
                
                // Auto-apply welcome bonus if applicable
                if (!wbLimitReached) {
                    if (planPrice === 12000) {
                        discountInput.value = 2000;
                    } else if (planPrice === 6000) {
                        discountInput.value = 1000;
                    } else {
                        // Reset discount for non-eligible plans if they previously selected eligible
                        if (discountInput.value == '2000' || discountInput.value == '1000') {
                            discountInput.value = 0;
                        }
                    }
                }
            } else {
                discountInput.value = 0;
            }
            
            validateDiscount();
            generateStaffQR();
        });

        function generateStaffQR() {
            var paymentMode = document.getElementById('payment_mode_select').value;
            var qrContainer = document.getElementById('staff-qr-container');
            
            if (paymentMode !== 'UPI') {
                qrContainer.style.display = 'none';
                return;
            }
            
            // Calculate base plan price
            var planSelect = document.getElementById('plan_select');
            var planPrice = 0;
            var isPlanSelected = false;
            
            if (planSelect && planSelect.selectedIndex >= 0) {
                var selectedOpt = planSelect.options[planSelect.selectedIndex];
                if (selectedOpt && selectedOpt.value !== '') {
                    isPlanSelected = true;
                    if (selectedOpt.getAttribute('data-price')) {
                        planPrice = parseFloat(selectedOpt.getAttribute('data-price'));
                    }
                }
            }
            
            // If no plan is selected, hide the QR code container until they select one
            if (!isPlanSelected) {
                qrContainer.style.display = 'none';
                return;
            }
            
            // Subtract discount
            var discount = parseFloat(document.getElementById('discount_input').value) || 0;
            
            // Add PT fees
            var ptFees = 0;
            var trainerSelect = document.getElementById('trainer_id_select');
            if (trainerSelect && trainerSelect.value !== '') {
                var ptDuration = document.getElementById('pt_duration');
                if (ptDuration) {
                    var duration = parseInt(ptDuration.value) || 3;
                    ptFees = duration * 3500;
                }
            }
            
            var totalAmount = (planPrice - discount) + ptFees;
            var paidAmountInput = document.getElementById('paid_amount');
            if (paidAmountInput && paidAmountInput.value !== '') {
                var enteredPaid = parseFloat(paidAmountInput.value);
                if (!isNaN(enteredPaid)) {
                    totalAmount = enteredPaid;
                }
            }
            
            if (totalAmount <= 0) {
                qrContainer.style.display = 'none';
                return;
            }
            
            document.getElementById('staff-qr-amount').innerText = '₹' + totalAmount.toLocaleString('en-IN');
            
            var upiId = "<?php echo addslashes($gym['upi_id'] ?? ''); ?>";
            var gymName = "<?php echo addslashes($gym['gym_name'] ?? 'Gym'); ?>";
            
            if (!upiId) {
                document.getElementById('staff-qr-container').innerHTML = '<div style="color:red; padding: 10px;">UPI ID not configured in settings.</div>';
                qrContainer.style.display = 'block';
                return;
            }
            
            var cleanUpiId = upiId.replace(/\s+/g, '');
            var queryStr = `?pa=${cleanUpiId}&pn=${encodeURIComponent(gymName)}&am=${totalAmount.toFixed(2)}&tn=${encodeURIComponent('Registration Payment')}&cu=INR`;
            var upiUrl = `upi://pay${queryStr}`;
            
            var qrSrc = "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=" + encodeURIComponent(upiUrl);
            document.getElementById('staff-qr-code').src = qrSrc;
            
            qrContainer.style.display = 'block';
        }
        </script>
        
        
			<?php include('footer.php'); ?>
    	</div>

    </body>
</html>
