<?php
require '../../include/db_conn.php';
page_protect();
$gym = get_gym_details($con);
$uid = null;
if (isset($_POST['userID'])) {
    $uid = $_POST['userID'];
} elseif (isset($_GET['userID'])) {
    $uid = $_GET['userID'];
} elseif (isset($_GET['id'])) {
    $uid = $_GET['id'];
}

if ($uid) {
    $planid = isset($_POST['planID']) ? $_POST['planID'] : (isset($_GET['planID']) ? $_GET['planID'] : '');
    $query1 = "select * from users WHERE userid='$uid'";
    $result1 = mysqli_query($con, $query1);
    
    $name = "";
    $mobile = "";
    $planName = "No Active Plan";
    if ($result1 && mysqli_num_rows($result1) > 0) {
        $row1 = mysqli_fetch_assoc($result1);
        $name = $row1['username'];
        $mobile = $row1['mobile'];
    }
    
    // Fetch latest membership enrollment details
    $prev_expire = "";
    $prev_paid_date = "";
    $prev_plan_id = "";
    $query_enroll = "SELECT e.*, p.planName as p_name, p.validity 
                     FROM enrolls_to e 
                     LEFT JOIN plan p ON e.pid = p.pid 
                     WHERE e.uid = '$uid' 
                     ORDER BY e.expire DESC, e.et_id DESC LIMIT 1";
    $res_enroll = mysqli_query($con, $query_enroll);
    if ($res_enroll && mysqli_num_rows($res_enroll) > 0) {
        $row_enroll = mysqli_fetch_assoc($res_enroll);
        $prev_expire = $row_enroll['expire'];
        $prev_paid_date = $row_enroll['paid_date'];
        $prev_plan_id = $row_enroll['pid'];
        if (!empty($row_enroll['p_name'])) {
            $planName = $row_enroll['p_name'];
        }
    }

    if (!empty($planid) && $planName == "No Active Plan") {
        $query2 = "select * from plan where pid='$planid'";
        $result2 = mysqli_query($con, $query2);
        if ($result2 && mysqli_num_rows($result2) > 0) {
            $planValue = mysqli_fetch_assoc($result2);
            $planName = $planValue['planName'];
        }
    }

    // Expiry analysis
    $today = date('Y-m-d');
    $today_time = strtotime($today);
    $is_expired = false;
    $days_overdue = 0;
    $days_remaining = 0;
    $attendance_after_expiry = 0;

    if (!empty($prev_expire)) {
        $expire_time = strtotime($prev_expire);
        $diff_days = round(($expire_time - $today_time) / 86400);
        if ($diff_days < 0) {
            $is_expired = true;
            $days_overdue = abs($diff_days);
            // Count gym visits since expiration date
            $q_att = mysqli_query($con, "SELECT COUNT(*) as att_count FROM attendance WHERE uid = '$uid' AND date > '$prev_expire'");
            if ($q_att && $att_row = mysqli_fetch_assoc($q_att)) {
                $attendance_after_expiry = intval($att_row['att_count']);
            }
        } elseif ($diff_days >= 0) {
            $days_remaining = $diff_days;
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>

    <title>SUDARSHAN FITNESS | Renew Memberships</title>
     <link rel="stylesheet" href="../../css/style.css"  id="style-resource-5">
    <script type="text/javascript" src="../../js/Script.js"></script>
    <link rel="stylesheet" href="../../css/dashMain.css">
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
    <link href="a1style.css" type="text/css" rel="stylesheet">
    <link rel="stylesheet" href="../../css/premium.css?v=<?php echo time(); ?>">
    <style>
    	.page-container .sidebar-menu #main-menu li#paymnt > a {
    	background-color: rgba(0, 122, 255, 0.1);
    	color: #007aff;
		}
	#boxx
	{
		width:240px;
	}
	.a1-container table td {
		padding: 9px 0 !important;
		vertical-align: middle !important;
		color: var(--text-main) !important;
		font-weight: 600;
		font-size: 13px;
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

		<h3>SUDARSHAN FITNESS</h3>

		<hr />

		
		
		
		
		
		<div class="a1-container a1-small a1-padding-32" style="margin-top:2px; margin-bottom:2px;">
        <div class="a1-card-8" style="width:580px; max-width:96%; margin:0 auto; border-radius: 16px; overflow: hidden; background: var(--glass-bg); border: 1px solid rgba(0, 0, 0, 0.08); box-shadow: 0 10px 30px rgba(0, 0, 0, 0.06), 0 1px 3px rgba(0, 0, 0, 0.03);">
		<div class="a1-container a1-center" style="background: #f8fafc; border-bottom: 1px solid rgba(0, 0, 0, 0.06); padding: 14px 20px;">
        	<h6 style="margin: 0; font-weight: 700; color: var(--text-main); font-size: 14px; letter-spacing: 0.5px;">RENEW MEMBERSHIP</h6>
        </div>

        <?php if ($is_expired): ?>
        <!-- OVERDUE / EXPIRED ALERT BANNER -->
        <div style="background: rgba(239, 68, 68, 0.08); border-left: 4px solid #ef4444; margin: 15px 20px 5px 20px; padding: 14px 16px; border-radius: 8px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                <span style="font-weight: 800; color: #ef4444; font-size: 13px; display: inline-flex; align-items: center; gap: 6px;">
                    🚨 MEMBERSHIP EXPIRED (<?php echo $days_overdue; ?> DAYS OVERDUE)
                </span>
                <span style="background: #ef4444; color: #ffffff; padding: 2px 8px; border-radius: 12px; font-weight: 700; font-size: 11px;">
                    Expired On <?php echo htmlspecialchars($prev_expire); ?>
                </span>
            </div>
            <div style="font-size: 12px; color: var(--text-muted); line-height: 1.5;">
                Previous Plan: <strong style="color: var(--text-main);"><?php echo htmlspecialchars($planName); ?></strong>
            </div>
            <?php if ($attendance_after_expiry > 0): ?>
            <div style="margin-top: 8px; background: rgba(239, 68, 68, 0.12); padding: 8px 10px; border-radius: 6px; font-size: 11.5px; color: #dc2626; font-weight: 600;">
                ⚠️ <strong>Unpaid Attendance Detected:</strong> Member attended gym <strong><?php echo $attendance_after_expiry; ?> time<?php echo $attendance_after_expiry > 1 ? 's' : ''; ?></strong> after expiring! Reissuing from previous expiry date ensures you recover these unpaid days.
            </div>
            <?php else: ?>
            <div style="margin-top: 6px; font-size: 11px; color: #64748b;">
                ℹ️ No attendance logged after expiry (Member was absent or took a break).
            </div>
            <?php endif; ?>
        </div>
        <?php elseif (!empty($prev_expire) && $days_remaining == 0): ?>
        <!-- EXPIRES TODAY BANNER -->
        <div style="background: rgba(245, 158, 11, 0.08); border-left: 4px solid #f59e0b; margin: 15px 20px 5px 20px; padding: 12px 16px; border-radius: 8px;">
            <div style="font-weight: 800; color: #f59e0b; font-size: 13px;">
                ⚠️ MEMBERSHIP EXPIRES TODAY (<?php echo htmlspecialchars($prev_expire); ?>)
            </div>
            <div style="font-size: 12px; color: var(--text-muted); margin-top: 3px;">
                Current Plan: <strong style="color: var(--text-main);"><?php echo htmlspecialchars($planName); ?></strong>
            </div>
        </div>
        <?php elseif (!empty($prev_expire) && $days_remaining > 0): ?>
        <!-- ACTIVE MEMBERSHIP BANNER -->
        <div style="background: rgba(16, 185, 129, 0.08); border-left: 4px solid #10b981; margin: 15px 20px 5px 20px; padding: 12px 16px; border-radius: 8px;">
            <div style="font-weight: 800; color: #10b981; font-size: 13px;">
                🟢 ACTIVE MEMBERSHIP (Valid until <?php echo htmlspecialchars($prev_expire); ?> — <?php echo $days_remaining; ?> days left)
            </div>
            <div style="font-size: 12px; color: var(--text-muted); margin-top: 3px;">
                Current Plan: <strong style="color: var(--text-main);"><?php echo htmlspecialchars($planName); ?></strong>
            </div>
        </div>
        <?php endif; ?>

       <form id="form1" name="form1" method="post" class="a1-container" action="submit_payments.php">
         <table width="100%" border="0" align="center">
         <tr>
           <td height="35"><table width="100%" border="0" align="center">
           	 <tr>
           	   <td height="35">MEMBERSHIP ID:</td>
           	   <td height="35"><input type="text" name="m_id" id="boxx" value="<?php echo $uid; ?>" readonly/></td>
         	   </tr>
			   
			   <tr>
               <td height="35">NAME:</td>
               <td height="35"><input type="text" name="u_name" id="boxx" value="<?php echo $name; ?>" placeholder="Member Name" maxlength="30" readonly/></td>
             </tr>
             <tr>
               <td height="35">CURRENT PLAN:</td>
               <td height="35">
                 <input type="text" name="prevPlan" id="boxx" value="<?php echo htmlspecialchars($planName) . (!empty($prev_expire) ? ' (Exp: ' . htmlspecialchars($prev_expire) . ')' : ''); ?>" readonly>
               </td>
             </tr>
             <tr>
               <td height="35">SELECT NEW PLAN:</td>
               <td height="35">
                 <select name="plan" id="plan_select" required onchange="myplandetail(this.value); validateDiscount(); recalculateExpiryPreview();">
					<option value="">-- Please select --</option>
					<?php
					    $query = "select * from plan where active='yes'";
					    $result = mysqli_query($con, $query);
					    if ($result && mysqli_num_rows($result) > 0) {
					        while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
					            echo "<option value='" . $row['pid'] . "' data-discount-lock='" . intval($row['discount_lock']) . "' data-validity='" . htmlspecialchars($row['validity']) . "' data-amount='" . htmlspecialchars($row['amount']) . "'>" . htmlspecialchars($row['planName']) . " (" . htmlspecialchars($row['validity']) . ")</option>";
					        }
					    }
					?>
				 </select>
               </td>
             </tr>

             <!-- RENEWAL START DATE OPTIONS -->
             <tr>
               <td height="35" style="vertical-align: top; padding-top: 10px;">START FROM DATE:</td>
               <td style="padding: 10px 0;">
                 <div style="background: rgba(255, 255, 255, 0.03); border: 1px solid rgba(255, 107, 0, 0.25); border-radius: 10px; padding: 12px; margin-bottom: 5px;">
                   <?php if (!empty($prev_expire)): ?>
                   <label style="display: flex; align-items: flex-start; gap: 8px; margin-bottom: 8px; cursor: pointer; font-size: 12px; color: var(--text-main);">
                     <input type="radio" name="start_mode" id="start_mode_continuous" value="continuous" checked onchange="updateStartDateSelection()" style="margin-top: 2px;">
                     <div>
                       <strong style="color: #ff6b00;">Continue from Expiry Date (<?php echo htmlspecialchars($prev_expire); ?>)</strong>
                       <div style="font-size: 11px; color: var(--text-muted);">Recommended if member continued coming to gym (Recovers unpaid days)</div>
                     </div>
                   </label>
                   
                   <label style="display: flex; align-items: flex-start; gap: 8px; margin-bottom: 8px; cursor: pointer; font-size: 12px; color: var(--text-main);">
                     <input type="radio" name="start_mode" id="start_mode_today" value="today" onchange="updateStartDateSelection()" style="margin-top: 2px;">
                     <div>
                       <strong>Start Fresh from Today (<?php echo date('Y-m-d'); ?>)</strong>
                       <div style="font-size: 11px; color: var(--text-muted);">Use if member was absent or took a genuine break</div>
                     </div>
                   </label>
                   <?php else: ?>
                   <label style="display: flex; align-items: flex-start; gap: 8px; margin-bottom: 8px; cursor: pointer; font-size: 12px; color: var(--text-main);">
                     <input type="radio" name="start_mode" id="start_mode_today" value="today" checked onchange="updateStartDateSelection()" style="margin-top: 2px;">
                     <div>
                       <strong>Start from Today (<?php echo date('Y-m-d'); ?>)</strong>
                     </div>
                   </label>
                   <?php endif; ?>
                   
                   <label style="display: flex; align-items: flex-start; gap: 8px; margin-bottom: 4px; cursor: pointer; font-size: 12px; color: var(--text-main);">
                     <input type="radio" name="start_mode" id="start_mode_custom" value="custom" onchange="updateStartDateSelection()" style="margin-top: 2px;">
                     <div>
                       <strong>Custom Start Date</strong>
                     </div>
                   </label>
                   
                   <div id="custom_date_container" style="display: none; margin-top: 8px; padding-left: 20px;">
                     <input type="date" name="custom_start_date" id="custom_start_date" value="<?php echo date('Y-m-d'); ?>" onchange="recalculateExpiryPreview()" style="padding: 6px 10px; border-radius: 6px; border: 1px solid rgba(255, 107, 0, 0.4);">
                   </div>

                   <!-- Hidden effective start date sent to submit_payments.php -->
                   <input type="hidden" name="start_date" id="effective_start_date" value="<?php echo !empty($prev_expire) ? htmlspecialchars($prev_expire) : date('Y-m-d'); ?>">
                 </div>
               </td>
             </tr>

             <!-- LIVE NEW EXPIRY PREVIEW -->
             <tr>
               <td height="35">NEW EXPIRY DATE:</td>
               <td height="35">
                 <div id="live_expiry_preview_box" style="background: rgba(16, 185, 129, 0.08); border: 1px solid #10b981; border-radius: 8px; padding: 8px 12px; font-size: 12.5px; font-weight: 700; color: #10b981;">
                   👉 Select a plan above to view new calculated expiry date
                 </div>
               </td>
             </tr>
               <tr>
                 <td height="35">TRANSACTION DATE:</td>
                 <td height="35"><input type="date" name="transaction_date" id="boxx" value="<?php echo date('Y-m-d'); ?>" required size="30"></td>
               </tr>
               <tr>
                 <td height="35">PAYMENT MODE:</td>
                 <td height="35"><select name="payment_mode" id="payment_mode_select" required onchange="toggleUPIQR()">
                     <option value="Cash" selected>Cash</option>
                     <option value="UPI">UPI</option>
                 </select></td>
               </tr>
               <tr id="upi_qr_container" style="display: none;">
                 <td colspan="2" style="text-align: center; padding: 15px 0;">
                   <div style="background: rgba(15, 7, 18, 0.95); border: 2px solid #ff6b00; border-radius: 16px; padding: 20px; max-width: 280px; margin: 0 auto; box-shadow: 0 0 25px rgba(255, 107, 0, 0.35);">
                     <div style="font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif, sans-serif; color: #ff6b00; font-weight: 800; font-size: 13px; margin-bottom: 8px;">📱 SCAN &amp; PAY VIA UPI</div>
                      <div style="background: var(--glass-bg); padding: 10px; border-radius: 12px; display: inline-block; position: relative;">
                        <img id="upi_qr_image" src="" alt="UPI QR Code" style="width: 180px; height: 180px; display: block;" />
                        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #2c1b18; padding: 3px; border-radius: 50%; border: 2px solid #ff6b00; box-shadow: 0 0 15px rgba(255,107,0,0.8); display: flex; align-items: center; justify-content: center; width: 42px; height: 42px;">
                          <img src="<?php echo htmlspecialchars($gym_settings_data['gym_logo'] ?? '../../images/logo.png'); ?>" alt="Gym Logo" style="width: 32px; height: 32px; border-radius: 50%; object-fit: contain;" />
                        </div>
                      </div>
                     <div id="upi_amount_display" style="font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif, sans-serif; font-size: 20px; font-weight: 900; color: #10b981; margin-top: 10px;">₹0</div>
                     <div style="font-size: 11px; color: #94a3b8; margin-top: 4px;">GPay • PhonePe • Paytm • BHIM</div>
                   </div>
                 </td>
               </tr>
               <tr>
                 <td height="35">AMOUNT PAID NOW (₹):</td>
                 <td height="35"><input type="number" name="paid_amount" id="paid_amount" placeholder="Leave empty if fully paid" size="40" onkeyup="checkBalance(); toggleUPIQR();" onchange="toggleUPIQR();"></td>
               </tr>
               <tr id="balance_due_row" style="display:none;">
                 <td height="35">BALANCE DUE DATE:</td>
                 <td height="35"><input type="date" name="balance_due_date" id="balance_due_date"></td>
               </tr>
               <script>
                 function toggleUPIQR() {
                     var mode = document.getElementById('payment_mode_select') ? document.getElementById('payment_mode_select').value : '';
                     var qrContainer = document.getElementById('upi_qr_container');
                     if (mode === 'UPI') {
                         var amount = document.getElementById('paid_amount').value;
                         if (!amount || parseFloat(amount) <= 0) {
                             var totalText = document.getElementById('price') ? document.getElementById('price').value : '1000';
                             amount = parseFloat(totalText.replace(/[^0-9.]/g, '')) || 1000;
                             var discount = parseFloat(document.getElementById('discount_input').value) || 0;
                             amount = amount - discount;
                         }
                         var upiId = '<?php echo addslashes(!empty($gym['upi_id']) ? $gym['upi_id'] : "anuragbawaskar4326@sbi"); ?>';
                         var name = 'Member';
                         var upiUrl = 'upi://pay?pa=' + encodeURIComponent(upiId) + '&pn=Sudarshan%20Fitness&am=' + encodeURIComponent(amount) + '&cu=INR&tn=' + encodeURIComponent('Membership Payment ' + name);
                         var qrImgUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' + encodeURIComponent(upiUrl);
                         
                         document.getElementById('upi_qr_image').src = qrImgUrl;
                         document.getElementById('upi_amount_display').innerText = '₹' + amount;
                         qrContainer.style.display = 'table-row';
                     } else {
                         qrContainer.style.display = 'none';
                     }
                 }

                 function checkBalance() {
                     var totalText = document.getElementById('price') ? document.getElementById('price').value : '0';
                     var total = parseFloat(totalText.replace(/[^0-9.]/g, '')) || 0;
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
                 setInterval(checkBalance, 1000);
               </script>
               </tr>
               <tr>
                 <td height="35">DISCOUNT AMOUNT (₹):</td>
                 <td height="35">
                     <input type="number" name="discount" id="discount_input" value="0" min="0" placeholder="Enter discount amount" required oninput="validateDiscount()"/>
                     <span id="discount_warning" style="color: #ef4444; font-size: 12px; display: none; margin-top: 5px; font-weight: bold; display: block;"></span>
                 </td>
               </tr>
             
		   
            
             <tr>
			  <table id="plandetls">
             </table>
			 
            
           </table></td>
		   
         </tr>
		  <tr>
               <td height="35">&nbsp;</td>
               <td height="35">&ensp;&ensp;&ensp;&ensp;&ensp;&ensp; &ensp;&ensp;&ensp;&ensp;&ensp;&ensp;&ensp;&ensp; &ensp;&ensp;&ensp;&ensp;&ensp; &ensp;&ensp;&ensp;&ensp;&ensp;&ensp; <input class="a1-btn a1-blue" type="submit" name="submit" id="submit" value="RENEW MEMBERSHIP" >
                 <input class="a1-btn a1-blue" type="reset" name="reset" id="reset" value="Reset"></td>
             </tr>
         </table>
       </form>
    </div>
    </div>   
		
		
		
		

		<?php include('footer.php'); ?>

		</div>


    </body>
</html>


 <script>
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
               		 if (typeof toggleUPIQR === 'function') toggleUPIQR();
             			}
        			};
        			
       				 xmlhttp.open("GET","plandetail.php?q="+str,true);
       				 xmlhttp.send();	
        		}
        		
        	}

            function updateStartDateSelection() {
                var checkedRadio = document.querySelector('input[name="start_mode"]:checked');
                var mode = checkedRadio ? checkedRadio.value : 'today';
                var customContainer = document.getElementById('custom_date_container');
                var effectiveInput = document.getElementById('effective_start_date');
                var prevExpire = '<?php echo addslashes($prev_expire); ?>';
                var todayStr = '<?php echo date('Y-m-d'); ?>';
                
                if (mode === 'continuous') {
                    if (customContainer) customContainer.style.display = 'none';
                    effectiveInput.value = prevExpire || todayStr;
                } else if (mode === 'today') {
                    if (customContainer) customContainer.style.display = 'none';
                    effectiveInput.value = todayStr;
                } else if (mode === 'custom') {
                    if (customContainer) customContainer.style.display = 'block';
                    var customVal = document.getElementById('custom_start_date') ? document.getElementById('custom_start_date').value : todayStr;
                    effectiveInput.value = customVal || todayStr;
                }
                recalculateExpiryPreview();
            }

            function recalculateExpiryPreview() {
                var effectiveInput = document.getElementById('effective_start_date');
                var startDateStr = effectiveInput ? effectiveInput.value : '<?php echo date('Y-m-d'); ?>';
                if (!startDateStr) startDateStr = '<?php echo date('Y-m-d'); ?>';

                var planSelect = document.getElementById('plan_select');
                var previewBox = document.getElementById('live_expiry_preview_box');
                if (!previewBox) return;

                if (!planSelect || !planSelect.value) {
                    previewBox.innerHTML = '<span style="color: var(--text-muted); font-weight: 500;">👉 Select a plan above to view new calculated expiry date</span>';
                    return;
                }

                var selectedOpt = planSelect.options[planSelect.selectedIndex];
                var validity = selectedOpt.getAttribute('data-validity') || '1';
                validity = validity.toLowerCase().trim();

                var parts = startDateStr.split('-');
                if (parts.length !== 3) {
                    previewBox.innerHTML = '<span style="color: #ef4444;">Invalid start date.</span>';
                    return;
                }

                var startYear = parseInt(parts[0], 10);
                var startMonth = parseInt(parts[1], 10) - 1;
                var startDay = parseInt(parts[2], 10);

                var expDate = new Date(startYear, startMonth, startDay);
                var validityDesc = '';

                if (validity.indexOf('d') !== -1) {
                    var days = parseInt(validity.replace('d', ''), 10) || 30;
                    expDate.setDate(expDate.getDate() + days);
                    validityDesc = days + ' Days';
                } else {
                    var months = parseInt(validity.replace('m', ''), 10) || 1;
                    expDate.setMonth(expDate.getMonth() + months);
                    validityDesc = months + (months > 1 ? ' Months' : ' Month');
                }

                var yyyy = expDate.getFullYear();
                var mm = String(expDate.getMonth() + 1).padStart(2, '0');
                var dd = String(expDate.getDate()).padStart(2, '0');
                var formattedExp = yyyy + '-' + mm + '-' + dd;

                previewBox.innerHTML = '<div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:6px;">' +
                    '<span>📅 <strong>New Expiry: ' + formattedExp + '</strong> (' + validityDesc + ')</span>' +
                    '<span style="font-size:11px; color:#10b981; opacity:0.85;">Effective from: ' + startDateStr + '</span>' +
                    '</div>';
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

            document.addEventListener('DOMContentLoaded', function() {
                updateStartDateSelection();
            });
        </script>

<?php
} else {
    
}
?>
