<?php
require '../../include/db_conn.php';
page_protect();

 $memID=$_POST['m_id'];
 $uname=$_POST['u_name'];
 $stname=$_POST['street_name'];
 $city=$_POST['city'];
 $zipcode=$_POST['zipcode'];
 $state=$_POST['state'];
 $gender=$_POST['gender'];
 $dob=$_POST['dob'];
 $phn=$_POST['mobile'];
 $email=$_POST['email'];
 $jdate=$_POST['jdate'];
 $plan=$_POST['plan'];
 // Check for duplicates
 $memID = mysqli_real_escape_string($con, $memID);
 $email = mysqli_real_escape_string($con, $email);

 $chk_uid = mysqli_query($con, "SELECT userid FROM users WHERE userid = '$memID'");
 if ($chk_uid && mysqli_num_rows($chk_uid) > 0) {
     echo "<head><script>alert('Duplicate Entry: Membership ID $memID is already registered!');</script></head></html>";
     echo "<meta http-equiv='refresh' content='0; url=new_entry.php'>";
     exit();
 }

 // Check if ID exists in admin table (potential staff conflict or orphaned member record)
 $chk_admin = mysqli_query($con, "SELECT username, role FROM admin WHERE username = '$memID'");
 if ($chk_admin && mysqli_num_rows($chk_admin) > 0) {
     $admin_row = mysqli_fetch_assoc($chk_admin);
     if ($admin_row['role'] !== 'member') {
         echo "<head><script>alert('Error: Membership ID $memID is reserved for staff/administration!');</script></head></html>";
         echo "<meta http-equiv='refresh' content='0; url=new_entry.php'>";
         exit();
     } else {
         // Orphaned member credentials from a previous incomplete deletion. Self-heal and clean it up.
         mysqli_query($con, "DELETE FROM admin WHERE username = '$memID' AND role = 'member'");
     }
 }

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
$upload_dir = __DIR__ . '/../../uploads/';
if (!file_exists($upload_dir)) {
    @mkdir($upload_dir, 0777, true);
}

if (!empty($_POST['member_photo_base64'])) {
    // Captured via webcam
    $base64_data = $_POST['member_photo_base64'];
    $data_pieces = explode(',', $base64_data);
    if (count($data_pieces) > 1) {
        $image_data = base64_decode($data_pieces[1]);
        $filename = 'profile_' . $memID . '_' . time() . '.jpg';
        $full_path = $upload_dir . $filename;
        if (@file_put_contents($full_path, $image_data)) {
            $photo_path = '../../uploads/' . $filename;
        }
    }
} elseif (isset($_FILES['member_photo_file']) && $_FILES['member_photo_file']['error'] === UPLOAD_ERR_OK) {
    // Uploaded via file input
    $file_tmp = $_FILES['member_photo_file']['tmp_name'];
    $ext = pathinfo($_FILES['member_photo_file']['name'], PATHINFO_EXTENSION);
    $filename = 'profile_' . $memID . '_' . time() . '.' . $ext;
    $full_path = $upload_dir . $filename;
    if (@move_uploaded_file($file_tmp, $full_path)) {
        $photo_path = '../../Sudarshan Data Folder/' . $filename;
    }
}

// Generate random 6-digit passcode for gate entry
$entry_code = strval(rand(100000, 999999));

//inserting into users table
$routine = !empty($_POST['routine']) ? (int)$_POST['routine'] : "NULL";
$trainer_id = isset($_POST['trainer_id']) && !empty($_POST['trainer_id']) ? mysqli_real_escape_string($con, $_POST['trainer_id']) : '';
$trainer_val = !empty($trainer_id) ? "'" . $trainer_id . "'" : "NULL";
$photo_val = $photo_path ? "'" . mysqli_real_escape_string($con, $photo_path) . "'" : "NULL";
$fitness_goal = isset($_POST['fitness_goal']) ? mysqli_real_escape_string($con, $_POST['fitness_goal']) : 'general';
$query="insert into users(username,gender,mobile,email,dob,joining_date,userid,tid,photo,entry_code,trainer_id,biometric_id,biometric_enabled,fitness_goal) values('$uname','$gender','$phn','$email','$dob','$jdate','$memID', $routine, $photo_val, '$entry_code', $trainer_val, '$memID', 1, '$fitness_goal')";
    if(mysqli_query($con,$query)==1){
      // Queue the new PIN to the Biometric Machine
      $cmd_payload = json_encode(['reason' => 'update_pin', 'pin' => $entry_code, 'name' => $uname]);
      mysqli_query($con, "INSERT INTO biometric_commands (command_type, target_uid, payload, status) VALUES ('UPDATE_USERINFO', '$memID', '$cmd_payload', 'pending')");
      
      //Retrieve information of plan selected by user
      $query1="select * from plan where pid='$plan'";
      $result=mysqli_query($con,$query1);

        if($result){
          $value=mysqli_fetch_row($result);
          date_default_timezone_set("Asia/Calcutta"); 
          $cdate=mysqli_real_escape_string($con, $_POST['jdate']);
          
          $launch_date = '2026-07-08';
          $cdate = ($cdate < $launch_date) ? $launch_date : $cdate;
          
          $d=strtotime("+".$value[3]." Months", strtotime($cdate));
          $expiredate=date("Y-m-d",$d); //adding validity retrieve from plan to current date
          $discount = isset($_POST['discount']) ? intval($_POST['discount']) : 0;
          $plan_price = intval($value[4]);
          $paid_amount = $plan_price - $discount;
          if ($paid_amount < 0) {
              $paid_amount = 0;
          }
          $payment_mode = isset($_POST['payment_mode']) ? mysqli_real_escape_string($con, $_POST['payment_mode']) : 'Cash';
          $received_by = isset($_SESSION['full_name']) ? mysqli_real_escape_string($con, $_SESSION['full_name']) : 'System';
          $query2="insert into enrolls_to(pid,uid,paid_date,expire,renewal,payment_mode,received_by,discount_amount,paid_amount) values('$plan','$memID','$cdate','$expiredate','yes','$payment_mode','$received_by',$discount,$paid_amount)";
          if(mysqli_query($con,$query2)==1){
            $weight = isset($_POST['weight']) ? mysqli_real_escape_string($con, $_POST['weight']) : '';
            $height = isset($_POST['height']) ? mysqli_real_escape_string($con, $_POST['height']) : '';
            $query4="insert into health_status(uid, weight, height) values('$memID', '$weight', '$height')";
            if(mysqli_query($con,$query4)==1){
              if (!empty($weight) || !empty($height)) {
                  mysqli_query($con, "INSERT INTO health_history (uid, weight, height, logged_date) VALUES ('$memID', '$weight', '$height', '$cdate')");
              }

              $query5="insert into address(id,streetName,state,city,zipcode) values('$memID','$stname','$state','$city','$zipcode')";
              if(mysqli_query($con,$query5)==1){
                // Create user login auth in admin table
                $password = '1234';
                $query_auth = "INSERT INTO admin (username, pass_key, securekey, Full_name, role) VALUES ('$memID', '$password', 'member', '$uname', 'member')";
                mysqli_query($con, $query_auth);

                // Insert PT enrollment if trainer is assigned
                if (!empty($trainer_id)) {
                    $pt_duration = isset($_POST['pt_duration']) ? intval($_POST['pt_duration']) : 3;
                    $pt_fees = isset($_POST['pt_fees']) ? intval($_POST['pt_fees']) : 0;
                    
                    $d_pt = strtotime("+" . $pt_duration . " Months", strtotime($cdate));
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

                // Send Confirmation Email with entry code and discount details (which automatically triggers WhatsApp welcome with PDF receipt)
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
