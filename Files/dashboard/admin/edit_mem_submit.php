<?php
require '../../include/db_conn.php';
page_protect();
    
    
   $uid=$_POST['uid'];
   $uname=$_POST['uname'];
   $gender=$_POST['gender'];
   $mobile=$_POST['phone'];
   $email=$_POST['email'];
   $dob=$_POST['dob'];
   $jdate=$_POST['jdate'];
   $stname=$_POST['stname'];
   $state=$_POST['state'];
   $city=$_POST['city'];
   $zipcode=$_POST['zipcode'];
   $routine = !empty($_POST['routine']) ? (int)$_POST['routine'] : "NULL";
   $query1="update users set username='".$uname."',gender='".$gender."',mobile='".$mobile."',email='".$email."',dob='".$dob."',joining_date='".$jdate."',tid=".$routine." where userid='".$uid."'";

    if(mysqli_query($con,$query1)){
      // If a plan is selected, check and assign/update their active enrollment
      if (isset($_POST['plan']) && !empty($_POST['plan'])) {
          $plan = mysqli_real_escape_string($con, $_POST['plan']);
          // Check current active plan
          $q_curr = "SELECT pid FROM enrolls_to WHERE uid='$uid' AND renewal='yes' ORDER BY expire DESC LIMIT 1";
          $res_curr = mysqli_query($con, $q_curr);
          $current_pid = "";
          if ($res_curr && mysqli_num_rows($res_curr) > 0) {
              $row_curr = mysqli_fetch_assoc($res_curr);
              $current_pid = $row_curr['pid'];
          }
          
          if ($plan !== $current_pid) {
              // Update previous renewals for this user to 'no'
              mysqli_query($con, "UPDATE enrolls_to SET renewal='no' WHERE uid='$uid'");
              
              // Get selected plan details
              $q_p = mysqli_query($con, "SELECT * FROM plan WHERE pid='$plan'");
              if ($q_p && mysqli_num_rows($q_p) > 0) {
                  $plan_data = mysqli_fetch_assoc($q_p);
                  $validity = intval($plan_data['validity']); // In months
                  
                  date_default_timezone_set("Asia/Calcutta"); 
                  $cdate = isset($_SESSION['working_year']) ? ($_SESSION['working_year'] . '-' . date('m-d')) : date('Y-m-d');
                  $launch_date = '2026-07-08';
                  $cdate = ($cdate < $launch_date) ? $launch_date : $cdate;
                  $d = strtotime("+" . $validity . " Months", strtotime($cdate));
                  $expiredate = date("Y-m-d", $d);
                  
                  $payment_mode = 'Cash';
                  $received_by = isset($_SESSION['full_name']) ? mysqli_real_escape_string($con, $_SESSION['full_name']) : 'System';
                  $discount = isset($_POST['discount']) ? intval($_POST['discount']) : 0;
                  $plan_price = intval($plan_data['amount']);
                  $paid_amount = $plan_price - $discount;
                  if ($paid_amount < 0) {
                      $paid_amount = 0;
                  }

                  // Generate new passcode on plan assignment
                  $new_entry_code = strval(rand(100000, 999999));
                  mysqli_query($con, "UPDATE users SET entry_code = '$new_entry_code' WHERE userid = '$uid'");
                  
                  mysqli_query($con, "INSERT INTO enrolls_to(pid, uid, paid_date, expire, renewal, payment_mode, received_by, discount_amount, paid_amount) 
                                      VALUES ('$plan', '$uid', '$cdate', '$expiredate', 'yes', '$payment_mode', '$received_by', $discount, $paid_amount)");

                  // Send Payment/New Membership Confirmation Email
                  send_payment_email($con, $email, $uname, $uid, $plan_data['planName'], $plan_data['amount'], $expiredate, $payment_mode, $received_by, $new_entry_code, $discount, $paid_amount);
              }
          }
      }

      $query2="update address set streetName='".$stname."',state='".$state."',city='".$city."',zipcode='".$zipcode."' where id='".$uid."'";
     if(mysqli_query($con,$query2)){
         // Update Health Status
         $height = isset($_POST['height']) ? mysqli_real_escape_string($con, $_POST['height']) : '';
         $weight = isset($_POST['weight']) ? mysqli_real_escape_string($con, $_POST['weight']) : '';
         $calorie = isset($_POST['calorie']) ? mysqli_real_escape_string($con, $_POST['calorie']) : '';
         $fat = isset($_POST['fat']) ? mysqli_real_escape_string($con, $_POST['fat']) : '';
         $remarks = isset($_POST['remarks']) ? mysqli_real_escape_string($con, $_POST['remarks']) : '';

         $chk_health = mysqli_query($con, "SELECT hid FROM health_status WHERE uid='$uid'");
         if (mysqli_num_rows($chk_health) > 0) {
             mysqli_query($con, "UPDATE health_status SET height='$height', weight='$weight', calorie='$calorie', fat='$fat', remarks='$remarks' WHERE uid='$uid'");
         } else {
             mysqli_query($con, "INSERT INTO health_status (uid, height, weight, calorie, fat, remarks) VALUES ('$uid', '$height', '$weight', '$calorie', '$fat', '$remarks')");
         }

         // Update user login auth in admin table
         $password = mysqli_real_escape_string($con, $_POST['password']);
         $chk_auth = mysqli_query($con, "SELECT username FROM admin WHERE username='$uid'");
         if (mysqli_num_rows($chk_auth) > 0) {
             mysqli_query($con, "UPDATE admin SET pass_key='$password', Full_name='$uname' WHERE username='$uid'");
         } else {
             mysqli_query($con, "INSERT INTO admin (username, pass_key, securekey, Full_name, role) VALUES ('$uid', '$password', 'member', '$uname', 'member')");
         }

         echo "<html><head><script>alert('Member Update Successfully');</script></head></html>";
         echo "<meta http-equiv='refresh' content='0; url=view_mem.php'>";
     }else{
         echo "<html><head><script>alert('ERROR! Update Opertaion Unsucessfull');</script></head></html>";
         echo "error".mysqli_error($con);
     }
   }else{
        echo "<html><head><script>alert('ERROR! Update Opertaion Unsucessfull');</script></head></html>";
         echo "error".mysqli_error($con);
     }
    

?>
