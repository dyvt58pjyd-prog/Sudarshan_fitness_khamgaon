<?php
require '../../include/db_conn.php';
page_protect();

 $memID = isset($_POST['m_id']) ? mysqli_real_escape_string($con, trim($_POST['m_id'])) : '';
 $uname = mysqli_real_escape_string($con, trim($_POST['u_name']));
 $stname = mysqli_real_escape_string($con, trim($_POST['street_name']));
 $city = mysqli_real_escape_string($con, trim($_POST['city']));
 $zipcode = mysqli_real_escape_string($con, trim($_POST['zipcode']));
 $state = mysqli_real_escape_string($con, trim($_POST['state']));
 $gender = mysqli_real_escape_string($con, trim($_POST['gender']));
 $dob = mysqli_real_escape_string($con, trim($_POST['dob']));
 $phn = mysqli_real_escape_string($con, trim($_POST['mobile']));
 $email = mysqli_real_escape_string($con, trim($_POST['email']));
 $jdate = mysqli_real_escape_string($con, trim($_POST['jdate']));
 $plan = mysqli_real_escape_string($con, trim($_POST['plan']));

 // Self-Healing Auto-Assignment: If $memID is empty or already registered concurrently by another staff user, calculate next available ID automatically
 function get_next_free_userid($con, $requested_id) {
     $id = intval($requested_id);
     if ($id < 101) {
         $id = 101;
     }

     while (true) {
         $id_str = strval($id);
         $chk_u = mysqli_query($con, "SELECT userid FROM users WHERE userid = '$id_str'");
         $chk_a = mysqli_query($con, "SELECT username, role FROM admin WHERE username = '$id_str'");
         
         $u_exists = ($chk_u && mysqli_num_rows($chk_u) > 0);
         $a_exists = false;
         
         if ($chk_a && mysqli_num_rows($chk_a) > 0) {
             $a_row = mysqli_fetch_assoc($chk_a);
             if ($a_row['role'] !== 'member') {
                 $a_exists = true; // Staff reserved ID
             } else {
                 // Orphaned member auth record - clean it up
                 mysqli_query($con, "DELETE FROM admin WHERE username = '$id_str' AND role = 'member'");
             }
         }
         
         if ($u_exists || $a_exists) {
             $id++;
         } else {
             return strval($id);
         }
     }
 }

 $memID = get_next_free_userid($con, $memID);

 $chk_email = mysqli_query($con, "SELECT email FROM users WHERE email = '$email'");
 if ($chk_email && mysqli_num_rows($chk_email) > 0) {
     echo "<head><script>alert('Duplicate Entry: Email address $email is already registered!');</script></head></html>";
     echo "<meta http-equiv='refresh' content='0; url=new_entry.php'>";
     exit();
 }
 // Require photo capture or upload
 if (empty($_POST['member_photo_base64']) && (!isset($_FILES['member_photo_file']) || $_FILES['member_photo_file']['error'] !== UPLOAD_ERR_OK)) {
     echo "<head><script>alert('Error: Please capture a live photo or upload a profile picture. This is mandatory for gym identification.');</script></head></html>";
     echo "<meta http-equiv='refresh' content='0; url=new_entry.php'>";
     exit();
 }

// Process Member Photo (Webcam or File Upload)
$photo_path = NULL;
$photo_base64_val = "NULL";
$upload_dir = __DIR__ . '/../../uploads/member_photos/';
if (!file_exists($upload_dir)) {
    @mkdir($upload_dir, 0755, true);
}

if (!empty($_POST['member_photo_base64'])) {
    // Captured via webcam
    $base64_data = trim($_POST['member_photo_base64']);
    $photo_base64_val = "'" . mysqli_real_escape_string($con, $base64_data) . "'";
    $data_pieces = explode(',', $base64_data);
    if (count($data_pieces) > 1) {
        $image_data = base64_decode($data_pieces[1]);
        $filename = 'profile_' . $memID . '_' . time() . '.jpg';
        $full_path = $upload_dir . $filename;
        if (@file_put_contents($full_path, $image_data)) {
            $photo_path = '../../uploads/member_photos/' . $filename;
        }
    }
} elseif (isset($_FILES['member_photo_file']) && $_FILES['member_photo_file']['error'] === UPLOAD_ERR_OK) {
    // Uploaded via file input
    $file_tmp = $_FILES['member_photo_file']['tmp_name'];
    $file_raw = @file_get_contents($file_tmp);
    if ($file_raw !== false) {
        $b64_str = 'data:image/jpeg;base64,' . base64_encode($file_raw);
        $photo_base64_val = "'" . mysqli_real_escape_string($con, $b64_str) . "'";
    }
    $ext = pathinfo($_FILES['member_photo_file']['name'], PATHINFO_EXTENSION);
    $filename = 'profile_' . $memID . '_' . time() . '.' . $ext;
    $full_path = $upload_dir . $filename;
    if (@move_uploaded_file($file_tmp, $full_path)) {
        $photo_path = '../../uploads/member_photos/' . $filename;
    }
}

// Generate random 6-digit passcode for gate entry
$entry_code = strval(rand(100000, 999999));

//inserting into users table
$routine = !empty($_POST['routine']) ? (int)$_POST['routine'] : "NULL";
$trainer_id = isset($_POST['trainer_id']) && !empty($_POST['trainer_id']) ? mysqli_real_escape_string($con, $_POST['trainer_id']) : '';
$trainer_val = !empty($trainer_id) ? "'" . $trainer_id . "'" : "NULL";
$pool_group_id = isset($_POST['pool_group_id']) && !empty($_POST['pool_group_id']) ? mysqli_real_escape_string($con, $_POST['pool_group_id']) : '';
$pool_val = !empty($pool_group_id) ? "'" . $pool_group_id . "'" : "NULL";
$photo_val = $photo_path ? "'" . mysqli_real_escape_string($con, $photo_path) . "'" : "NULL";
$fitness_goal = isset($_POST['fitness_goal']) ? mysqli_real_escape_string($con, $_POST['fitness_goal']) : 'general';
$biometric_batch = isset($_POST['biometric_batch']) ? mysqli_real_escape_string($con, $_POST['biometric_batch']) : '1';
$query="INSERT INTO users(username,gender,mobile,email,dob,joining_date,userid,tid) VALUES('$uname','$gender','$phn','$email','$dob','$jdate','$memID', $routine)";
    if(mysqli_query($con,$query)){
        @mysqli_query($con, "UPDATE users SET photo=$photo_val, member_photo=$photo_val, photo_base64=$photo_base64_val, entry_code='$entry_code', trainer_id=$trainer_val, biometric_id='$memID', biometric_enabled=1, fitness_goal='$fitness_goal', biometric_batch='$biometric_batch', pool_group_id=$pool_val WHERE userid='$memID'");
        
      $partner_uid = null;
      $is_couple_selected = false;
      
      if (isset($_POST['is_couple']) && $_POST['is_couple'] == '1') {
          $is_couple_selected = true;
      } else if (!empty($_POST['partner_name'])) {
          $is_couple_selected = true;
      } else if (!empty($plan)) {
          $q_chk_plan = mysqli_query($con, "SELECT planName FROM plan WHERE pid='$plan'");
          if ($q_chk_plan && mysqli_num_rows($q_chk_plan) > 0) {
              $p_row_chk = mysqli_fetch_assoc($q_chk_plan);
              if (stripos($p_row_chk['planName'], 'couple') !== false) {
                  $is_couple_selected = true;
              }
          }
      }

      if ($is_couple_selected) {
          $p_name = isset($_POST['partner_name']) && !empty($_POST['partner_name']) ? mysqli_real_escape_string($con, trim($_POST['partner_name'])) : "Partner of " . $uname;
          $p_gender = isset($_POST['partner_gender']) && !empty($_POST['partner_gender']) ? mysqli_real_escape_string($con, trim($_POST['partner_gender'])) : ($gender == 'Male' ? 'Female' : 'Male');
          $p_dob = isset($_POST['partner_dob']) && !empty($_POST['partner_dob']) ? mysqli_real_escape_string($con, $_POST['partner_dob']) : $dob;
          $p_mobile = isset($_POST['partner_mobile']) && !empty($_POST['partner_mobile']) ? mysqli_real_escape_string($con, trim($_POST['partner_mobile'])) : $phn;
          
          // Generate unique partner ID
          $partner_uid = get_next_free_userid($con, intval($memID) + 1);
          
          $p_email = "partner_" . $partner_uid . "_" . time() . "@sudarshanfitness.local";
          if (!empty($email)) {
              $chk_p_em = mysqli_query($con, "SELECT email FROM users WHERE email = '$email'");
              if ($chk_p_em && mysqli_num_rows($chk_p_em) == 0) {
                  $p_email = $email;
              }
          }
          
          $q_partner = "INSERT INTO users (username, gender, mobile, email, dob, joining_date, userid, partner_uid, biometric_id, biometric_enabled, fitness_goal, biometric_batch) 
                        VALUES ('$p_name', '$p_gender', '$p_mobile', '$p_email', '$p_dob', '$jdate', '$partner_uid', '$memID', '$partner_uid', 1, '$fitness_goal', '$biometric_batch')";
          mysqli_query($con, $q_partner);
          
          // Bi-directionally link Primary User to Partner User
          mysqli_query($con, "UPDATE users SET partner_uid = '$partner_uid' WHERE userid = '$memID'");
      }

      //Retrieve information of plan selected by user
      $query1="select * from plan where pid='$plan'";
      $result=mysqli_query($con,$query1);

        if($result){
          $value=mysqli_fetch_row($result);
          date_default_timezone_set("Asia/Calcutta"); 
          $cdate=mysqli_real_escape_string($con, $_POST['jdate']);
          
          $launch_date = '2026-07-08';
          $cdate = ($cdate < $launch_date) ? $launch_date : $cdate;
          
          $d = calculate_expiration_date($cdate, $value[3]);
          $expiredate=date("Y-m-d",$d); //adding validity retrieve from plan to current date
          $discount = isset($_POST['discount']) ? intval($_POST['discount']) : 0;
          $plan_price = intval($value[4]);
          
          // Automatic welcome bonus discount for 12000, 6000 and 3900/4000/4500 (3 Months) plans, limited to 100 members
          if (($plan_price == 12000 || $plan_price == 6000 || $plan_price == 3900 || $plan_price == 4000 || $plan_price == 4500) && $discount == 0) {
              $cnt_q = mysqli_query($con, "SELECT COUNT(*) as cnt FROM enrolls_to e JOIN plan p ON e.pid = p.pid WHERE e.discount_amount > 0 AND (p.amount=12000 OR p.amount=6000 OR p.amount=3900 OR p.amount=4000 OR p.amount=4500)");
              $cnt_row = mysqli_fetch_assoc($cnt_q);
              if (intval($cnt_row['cnt']) < 100) {
                  if ($plan_price == 12000) {
                      $discount = 2000;
                  } elseif ($plan_price == 6000) {
                      $discount = 1000;
                  } elseif ($plan_price == 4500) {
                      $discount = 600; // Welcome Bonus final price is 3900 (4500 - 600)
                  } elseif ($plan_price == 4000) {
                      $discount = 100; // Welcome Bonus final price is 3900 (4000 - 100)
                  } elseif ($plan_price == 3900) {
                      $discount = 0; // If plan catalog price is already 3900, final is 3900
                  }
              }
          }
          
          $total_payable = $plan_price - $discount;
          if ($total_payable < 0) {
              $total_payable = 0;
          }
          
          $paid_now = isset($_POST['paid_amount']) && $_POST['paid_amount'] !== '' ? floatval($_POST['paid_amount']) : $total_payable;
          $balance = $total_payable - $paid_now;
          if ($balance < 0) $balance = 0;
          
          $balance_due_date = isset($_POST['balance_due_date']) ? mysqli_real_escape_string($con, $_POST['balance_due_date']) : NULL;
          $due_date_val = $balance > 0 && !empty($balance_due_date) ? "'$balance_due_date'" : "NULL";

          $payment_mode = isset($_POST['payment_mode']) ? mysqli_real_escape_string($con, $_POST['payment_mode']) : 'Cash';
          if ($payment_mode === 'Complimentary') {
              $paid_now = 0;
              $discount = $plan_price;
              $balance = 0;
              $due_date_val = "NULL";
          }

          $received_by = isset($_SESSION['full_name']) ? mysqli_real_escape_string($con, $_SESSION['full_name']) : 'System';
          
          // Use submitted transaction date for auditor, default to today if not provided
          $transaction_date = isset($_POST['transaction_date']) && !empty($_POST['transaction_date']) ? mysqli_real_escape_string($con, $_POST['transaction_date']) : date('Y-m-d');
          
          $query2="insert into enrolls_to(pid,uid,paid_date,expire,renewal,payment_mode,received_by,discount_amount,paid_amount,balance,balance_due_date) values('$plan','$memID','$transaction_date','$expiredate','yes','$payment_mode','$received_by',$discount,$paid_now,$balance,$due_date_val)";
          if(mysqli_query($con,$query2)==1){
              
            // Enroll Partner (Zero payment)
            if (!empty($partner_uid)) {
                $q_partner_enroll = "INSERT INTO enrolls_to (pid, uid, paid_date, expire, renewal, payment_mode, received_by, discount_amount, paid_amount, balance, balance_due_date) 
                                     VALUES ('$plan', '$partner_uid', '$transaction_date', '$expiredate', 'yes', 'Couple Plan', 'System', 0, 0, 0, NULL)";
                mysqli_query($con, $q_partner_enroll);
                
                // Auth for partner
                $p_pass = '1234';
                mysqli_query($con, "INSERT INTO admin (username, pass_key, securekey, Full_name, role) VALUES ('$partner_uid', '$p_pass', 'member', '$p_name', 'member')");
            }
            $weight = isset($_POST['weight']) ? mysqli_real_escape_string($con, $_POST['weight']) : '';
            $height = isset($_POST['height']) ? mysqli_real_escape_string($con, $_POST['height']) : '';
            $query4="insert into health_status(uid, weight, height) values('$memID', '$weight', '$height')";
            if (!empty($partner_uid)) {
                mysqli_query($con, "INSERT INTO health_status(uid, weight, height) VALUES ('$partner_uid', '$weight', '$height')");
            }
            if(mysqli_query($con,$query4)==1){
              if (!empty($weight) || !empty($height)) {
                  mysqli_query($con, "INSERT INTO health_history (uid, weight, height, logged_date) VALUES ('$memID', '$weight', '$height', '$cdate')");
              }

              $query5="insert into address(id,streetName,state,city,zipcode) values('$memID','$stname','$state','$city','$zipcode')";
              if (!empty($partner_uid)) {
                  mysqli_query($con, "INSERT INTO address(id,streetName,state,city,zipcode) VALUES ('$partner_uid','$stname','$state','$city','$zipcode')");
              }
              if(mysqli_query($con,$query5)==1){
                // Create user login auth in admin table
                $password = '1234';
                $query_auth = "INSERT INTO admin (username, pass_key, securekey, Full_name, role) VALUES ('$memID', '$password', 'member', '$uname', 'member')";
                mysqli_query($con, $query_auth);

                // Insert PT enrollment if trainer is assigned
                if (!empty($trainer_id)) {
                    $pt_duration = isset($_POST['pt_duration']) ? intval($_POST['pt_duration']) : 3;
                    $pt_fees = isset($_POST['pt_fees']) ? intval($_POST['pt_fees']) : 0;
                    
                    $d_pt = calculate_expiration_date($cdate, $pt_duration);
                    $pt_expire_date = date("Y-m-d", $d_pt);
                    
                    $pt_payment_mode = isset($_POST['payment_mode']) ? mysqli_real_escape_string($con, $_POST['payment_mode']) : 'Cash';
                    $pt_received_by = isset($_SESSION['full_name']) ? mysqli_real_escape_string($con, $_SESSION['full_name']) : 'System';
                    
                    mysqli_query($con, "INSERT INTO pt_enrollments (uid, trainer_id, enroll_date, expire_date, amount, payment_mode, received_by) 
                                        VALUES ('$memID', '$trainer_id', '$cdate', '$pt_expire_date', $pt_fees, '$pt_payment_mode', '$pt_received_by')");
                    
                    // Lookup trainer's full name
                    $trainer_fullname = "Trainer";
                    $q_tname = mysqli_query($con, "SELECT Full_name FROM admin WHERE username = '$trainer_id'");
                    if ($q_tname && $tname_row = mysqli_fetch_assoc($q_tname)) {
                        $trainer_fullname = $tname_row['Full_name'];
                    }
                    
                    // Send separate PT email receipt
                    send_pt_email($con, $email, $uname, $memID, $trainer_fullname, $pt_fees, $pt_expire_date, $pt_payment_mode, $pt_received_by);
                    
                    // Notify trainer of the new PT client assignment
                    send_whatsapp_trainer_pt_notification($con, $trainer_id, $uname, $memID);
                }

                // Send Confirmation Email with entry code and discount details
                send_member_email($con, $email, $uname, $memID, $password, $value[1], $value[4], $expiredate, $entry_code, $discount, $paid_amount, $gender);

                echo "<head><script>alert('Member Added Successfully!');</script></head></html>";
                echo "<meta http-equiv='refresh' content='0; url=new_entry.php?success=1'>";
              }
              else{
                  echo "<head><script>alert('Member Added Failed');</script></head></html>";
                 echo "error: ".mysqli_error($con);
                 //Deleting record of users if inserting to enrolls_to table failed to execute
                 $query3 = "DELETE FROM users WHERE userid='$memID'";
                 mysqli_query($con,$query3);
              }
            }
             
            else{
               echo "<head><script>alert('Member Added Failed');</script></head></html>";
              echo "error: ".mysqli_error($con);
               //Deleting record of users if inserting to enrolls_to table failed to execute
                $query3 = "DELETE FROM users WHERE userid='$memID'";
                mysqli_query($con,$query3);
            }
            
          }
          else{
            echo "<head><script>alert('Member Added Failed');</script></head></html>";
            echo "error: ".mysqli_error($con);
            //Deleting record of users if inserting to enrolls_to table failed to execute
             $query3 = "DELETE FROM users WHERE userid='$memID'";
             mysqli_query($con,$query3);
          }

         
        }
        else
        {
          echo "<head><script>alert('Member Added Failed');</script></head></html>";
          echo "error: ".mysqli_error($con);
           //Deleting record of users if retrieving inf of plan failed
          $query3 = "DELETE FROM users WHERE userid='$memID'";
          mysqli_query($con,$query3);
        }

    }
    else{
        echo "<head><script>alert('Member Added Failed');</script></head></html>";
        echo "error: ".mysqli_error($con);
      }
?>
