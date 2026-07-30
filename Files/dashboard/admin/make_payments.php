<?php
require '../../include/db_conn.php';
page_protect();
$uid = null;
if (isset($_POST['userID'])) {
    $uid = $_POST['userID'];
} elseif (isset($_GET['userID'])) {
    $uid = $_GET['userID'];
}

if ($uid) {
    $planid = isset($_POST['planID']) ? $_POST['planID'] : (isset($_GET['planID']) ? $_GET['planID'] : '');
    $query1 = "select * from users WHERE userid='$uid'";
    $result1 = mysqli_query($con, $query1);
    
    $name = "";
    $planName = "No Active Plan";
    if ($result1 && mysqli_num_rows($result1) > 0) {
        $row1 = mysqli_fetch_assoc($result1);
        $name = $row1['username'];
    }
    
    if (!empty($planid)) {
        $query2 = "select * from plan where pid='$planid'";
        $result2 = mysqli_query($con, $query2);
        if ($result2 && mysqli_num_rows($result2) > 0) {
            $planValue = mysqli_fetch_assoc($result2);
            $planName = $planValue['planName'];
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>

    <title>SUDARSHAN FITNESS | Make Payment</title>
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
	#boxx
	{
		width:220px;
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
        <div class="a1-card-8 a1-light-gray" style="width:500px; margin:0 auto;">
		<div class="a1-container a1-dark-gray a1-center">
        	<h6>MAKE PAYMENT</h6>
        </div>
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
               <td height="35"><input type="text" name="u_name" id="boxx" value="<?php echo $name; ?>" placeholder="Member Name" maxlength="30" readonly/>
                 
             </tr>
             <tr>
               <td height="35">CURRENT PLAN</td>
               <td height="35"><input type="text" name="prevPlan" id="boxx" value="<?php echo $planName; ?>" readonly></td></td>
             </tr>
             <tr>
               <td height="35">SELECT NEW PLAN:</td>
               <td height="35"><select name="plan" id="plan_select" required onchange="myplandetail(this.value); validateDiscount()">
 							<option value="">-- Please select --</option>
 							<?php
 							    $query = "select * from plan where active='yes'";
 							    $result = mysqli_query($con, $query);
 							    if ($result && mysqli_num_rows($result) > 0) {
 							        while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
 							            echo "<option value='" . $row['pid'] . "' data-discount-lock='" . intval($row['discount_lock']) . "'>" . htmlspecialchars($row['planName']) . "</option>";
 							        }
 							    }
 							?>
 						</select></td></td>
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
                   <div style="background: rgba(15, 7, 18, 0.95); border: 2px solid #ff003c; border-radius: 16px; padding: 20px; max-width: 280px; margin: 0 auto; box-shadow: 0 0 25px rgba(255, 0, 60, 0.35);">
                     <div style="font-family: 'Orbitron', sans-serif; color: #ff003c; font-weight: 800; font-size: 13px; margin-bottom: 8px;">📱 SCAN &amp; PAY VIA UPI</div>
                      <div style="background: #fff; padding: 10px; border-radius: 12px; display: inline-block; position: relative;">
                        <img id="upi_qr_image" src="" alt="UPI QR Code" style="width: 180px; height: 180px; display: block;" />
                        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #030712; padding: 3px; border-radius: 50%; border: 2px solid #ff003c; box-shadow: 0 0 15px rgba(255,0,60,0.8); display: flex; align-items: center; justify-content: center; width: 42px; height: 42px;">
                          <img src="<?php echo htmlspecialchars($gym_settings_data['gym_logo'] ?? '../../images/logo.png'); ?>" alt="Gym Logo" style="width: 32px; height: 32px; border-radius: 50%; object-fit: contain;" />
                        </div>
                      </div>
                     <div id="upi_amount_display" style="font-family: 'Orbitron', sans-serif; font-size: 20px; font-weight: 900; color: #10b981; margin-top: 10px;">₹0</div>
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
                         var upiId = '7620453195-2@ybl';
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
               <td height="35">&ensp;&ensp;&ensp;&ensp;&ensp;&ensp; &ensp;&ensp;&ensp;&ensp;&ensp;&ensp;&ensp;&ensp; &ensp;&ensp;&ensp;&ensp;&ensp; &ensp;&ensp;&ensp;&ensp;&ensp;&ensp; <input class="a1-btn a1-blue" type="submit" name="submit" id="submit" value="ADD PAYMENT" >
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
        </script>

<?php
} else {
    
}
?>
