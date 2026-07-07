<?php
require '../../include/db_conn.php';
page_protect();

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['super_admin', 'owner', 'reception'])) {
    die("Access Denied");
}

if (!isset($_GET['id'])) {
    header("Location: payment_requests.php");
    exit();
}

$req_id = intval($_GET['id']);

$q = mysqli_query($con, "SELECT * FROM payment_requests WHERE id = $req_id AND status = 'pending'");
if (!$q || mysqli_num_rows($q) == 0) {
    echo "<script>alert('Request not found or already processed.'); window.location.href='payment_requests.php';</script>";
    exit();
}

$req = mysqli_fetch_assoc($q);
$userid = $req['uid'];
$pid = $req['pid'];
$amount = intval($req['amount']);
$utr = $req['utr'];
$is_new_reg = intval($req['is_new_registration']);
$payload = json_decode($req['registration_payload'], true);

date_default_timezone_set("Asia/Calcutta");
$cdate = date('Y-m-d');
$launch_date = '2026-07-08';
$calc_base_date = ($cdate < $launch_date) ? $launch_date : $cdate;
$payment_mode = 'UPI';
$received_by = 'Admin Verification (' . (isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Admin') . ')';

// ------------------------------------------------------------------------------------------
// DEFERRED NEW REGISTRATION APPROVAL LOGIC
// ------------------------------------------------------------------------------------------
if ($is_new_reg == 1 && $userid === 'PENDING') {
    // Generate ID
    $next_id = 101;
    $res_max = mysqli_query($con, "SELECT userid FROM users WHERE userid REGEXP '^[0-9]+$' AND CAST(userid AS UNSIGNED) < 100000000");
    if ($res_max && mysqli_num_rows($res_max) > 0) {
        $max_val = 100;
        while ($row_max = mysqli_fetch_assoc($res_max)) {
            $val = intval($row_max['userid']);
            if ($val > $max_val) $max_val = $val;
        }
        $next_id = $max_val + 1;
    }

    $uname = mysqli_real_escape_string($con, $payload['uname']);
    $gender = mysqli_real_escape_string($con, $payload['gender']);
    $phn = mysqli_real_escape_string($con, $payload['phn']);
    $email = mysqli_real_escape_string($con, $payload['email']);
    $dob = mysqli_real_escape_string($con, $payload['dob']);
    $joining_date_val = ($cdate < $launch_date) ? "'$launch_date'" : "CURRENT_DATE()";
    $entry_code = strval(rand(100000, 999999));
    $photo_path_db = mysqli_real_escape_string($con, $payload['photo_path_db']);
    $password = mysqli_real_escape_string($con, $payload['password']);
    
    // Read assigned batch from approval parameters (default to Batch 1)
    $assigned_batch = isset($_REQUEST['assign_batch']) ? mysqli_real_escape_string($con, $_REQUEST['assign_batch']) : '1';

    // Create User
    $query_user = "INSERT INTO users (username, gender, mobile, email, dob, joining_date, userid, entry_code, biometric_id, biometric_enabled, photo, biometric_batch) 
                   VALUES ('$uname', '$gender', '$phn', '$email', '$dob', $joining_date_val, '$next_id', '$entry_code', '$next_id', 1, '$photo_path_db', '$assigned_batch')";
    
    if (mysqli_query($con, $query_user)) {
        // Create Address
        $stname = mysqli_real_escape_string($con, $payload['stname']);
        $state = mysqli_real_escape_string($con, $payload['state']);
        $city = mysqli_real_escape_string($con, $payload['city']);
        $zipcode = mysqli_real_escape_string($con, $payload['zipcode']);
        mysqli_query($con, "INSERT INTO address (id, streetName, state, city, zipcode) VALUES ('$next_id', '$stname', '$state', '$city', '$zipcode')");
        
        // Create Health
        $weight = mysqli_real_escape_string($con, $payload['weight']);
        $height = mysqli_real_escape_string($con, $payload['height']);
        mysqli_query($con, "INSERT INTO health_status (uid, weight, height) VALUES ('$next_id', '$weight', '$height')");
        if (!empty($weight) || !empty($height)) {
            mysqli_query($con, "INSERT INTO health_history (uid, weight, height, logged_date) VALUES ('$next_id', '$weight', '$height', CURRENT_DATE())");
        }
        
        // Create Login
        mysqli_query($con, "INSERT INTO admin (username, pass_key, securekey, Full_name, role) VALUES ('$next_id', '$password', 'member', '$uname', 'member')");

        // Activate Subscription
        $plan_q = mysqli_query($con, "SELECT planName, validity, amount FROM plan WHERE pid = '$pid'");
        $validity = 1;
        $planName = 'Premium Plan';
        $plan_amount = $amount;
        if ($plan_q && mysqli_num_rows($plan_q) > 0) {
            $plan_data = mysqli_fetch_assoc($plan_q);
            $validity = intval($plan_data['validity']);
            $planName = $plan_data['planName'];
            $plan_amount = intval($plan_data['amount']);
        }
        
        $discount_amt = 0;
        if ($plan_amount == 12000 && $amount == 10000) {
            $discount_amt = 2000;
        } else if ($plan_amount > $amount) {
            $discount_amt = $plan_amount - $amount;
        }
        
        $d = strtotime("+" . $validity . " Months", strtotime($calc_base_date));
        $expiredate = date("Y-m-d", $d);
        
        mysqli_query($con, "INSERT INTO enrolls_to (pid, uid, paid_date, expire, renewal, payment_mode, received_by, discount_amount, paid_amount) 
                            VALUES ('$pid', '$next_id', '$calc_base_date', '$expiredate', 'yes', '$payment_mode', '$received_by', $discount_amt, $amount)");
        
        // Update payment request
        mysqli_query($con, "UPDATE payment_requests SET status = 'approved', uid = '$next_id' WHERE id = $req_id");
        
        $cmd_payload = json_encode(['reason' => 'new_online_registration', 'pin' => $entry_code, 'name' => $uname]);
        mysqli_query($con, "INSERT INTO biometric_commands (command_type, target_uid, payload, status) VALUES ('UPDATE_USERINFO', '$next_id', '$cmd_payload', 'pending')");
        
        // Send Welcome Email
        require_once '../../include/smtp_mailer.php';
        send_member_email($con, $email, $uname, $next_id, $password, $planName, $amount, $expiredate, $entry_code, $discount_amt, $amount, $gender);
        
        // Send WhatsApp Welcome Message
        require_once '../../include/whatsapp_api.php';
        $wa_msg = "🔥 Welcome to Sudarshan Fitness, $uname! 🔥\n\nYour Pre-Booking has been verified and approved!\n\nMembership ID: $next_id\nGym Entry PIN: $entry_code\nPlan Paid: ₹$amount\n\nShow this message at the front desk. Get ready to transform your life! 💪";
        sendWhatsAppMessage($phn, $wa_msg);
        
        echo "<script>alert('Registration Approved! ID Assigned and Activated.'); window.location.href='manual_approve.php';</script>";
        exit();
    } else {
        echo "<script>alert('Failed to generate user account: " . mysqli_error($con) . "'); window.location.href='manual_approve.php';</script>";
        exit();
    }
}

// ------------------------------------------------------------------------------------------
// STANDARD RENEWAL / PT APPROVAL LOGIC (For Existing Users)
// ------------------------------------------------------------------------------------------
// Fetch user details
$user_q = mysqli_query($con, "SELECT username, email, mobile FROM users WHERE userid='$userid'");
$user_row = mysqli_fetch_assoc($user_q);
$mem_name = $user_row['username'];
$mem_email = $user_row['email'];
$mem_mobile = $user_row['mobile'];

// Is this a PT plan or standard membership?
if (strpos($pid, 'PT_') === 0) {
    // PT Plan format: PT_trainerid_duration
    $parts = explode('_', $pid);
    $trainer_id = $parts[1];
    $duration = intval($parts[2]);
    
    // Fetch trainer
    $tr_q = mysqli_query($con, "SELECT Full_name FROM admin WHERE username='$trainer_id'");
    $trainer_name = ($tr_q && mysqli_num_rows($tr_q)>0) ? mysqli_fetch_assoc($tr_q)['Full_name'] : 'Assigned Trainer';
    
    $d = strtotime("+" . $duration . " Months", strtotime($calc_base_date));
    $expiredate = date("Y-m-d", $d);
    
    $ins_pt = "INSERT INTO pt_enrollments (uid, trainer_id, enroll_date, expire_date, amount, payment_mode, received_by) 
               VALUES ('$userid', '$trainer_id', '$cdate', '$expiredate', $amount, '$payment_mode', '$received_by')";
    
    if (mysqli_query($con, $ins_pt)) {
        mysqli_query($con, "UPDATE users SET trainer_id = '$trainer_id' WHERE userid = '$userid'");
        mysqli_query($con, "UPDATE payment_requests SET status = 'approved' WHERE id = $req_id");
        
        require_once '../../include/smtp_mailer.php';
        send_pt_email($con, $mem_email, $mem_name, $userid, $trainer_name, $amount, $expiredate, $payment_mode, $received_by);
        
        require_once '../../include/whatsapp_core.php';
        send_whatsapp_trainer_pt_notification($con, $trainer_id, $mem_name, $userid);
        
        echo "<script>alert('PT Payment Approved and Activated!'); window.location.href='payment_requests.php';</script>";
    } else {
        echo "<script>alert('Database Error.'); window.location.href='payment_requests.php';</script>";
    }
} else {
    // Regular Membership
    $plan_q = mysqli_query($con, "SELECT planName, validity, amount FROM plan WHERE pid = '$pid'");
    $plan_amount = $amount;
    $plan_name = 'Premium Plan';
    $validity = 1;
    if ($plan_q && mysqli_num_rows($plan_q) > 0) {
        $plan_data = mysqli_fetch_assoc($plan_q);
        $plan_name = $plan_data['planName'];
        $validity = intval($plan_data['validity']);
        $plan_amount = intval($plan_data['amount']);
    }
    
    $discount_amt = 0;
    if ($plan_amount == 12000 && $amount == 10000) {
        $discount_amt = 2000;
    } else if ($plan_amount > $amount) {
        $discount_amt = $plan_amount - $amount;
    }
        
    $is_new_member = false;
    $chk_prev_enroll = mysqli_query($con, "SELECT et_id FROM enrolls_to WHERE uid='$userid'");
    if (mysqli_num_rows($chk_prev_enroll) == 0) {
        $is_new_member = true;
    }

    mysqli_query($con, "UPDATE enrolls_to SET renewal='no' WHERE uid='$userid'");
    
    $d = strtotime("+" . $validity . " Months", strtotime($calc_base_date));
    $expiredate = date("Y-m-d", $d);
    
    $ins_enroll = "INSERT INTO enrolls_to (pid, uid, paid_date, expire, renewal, payment_mode, received_by, discount_amount, paid_amount) 
                   VALUES ('$pid', '$userid', '$cdate', '$expiredate', 'yes', '$payment_mode', '$received_by', $discount_amt, $amount)";
    
    if (mysqli_query($con, $ins_enroll)) {
        $new_entry_code = strval(rand(100000, 999999));
        mysqli_query($con, "UPDATE users SET entry_code = '$new_entry_code' WHERE userid = '$userid'");
        mysqli_query($con, "UPDATE payment_requests SET status = 'approved' WHERE id = $req_id");
        
        $bio_q = mysqli_query($con, "SELECT biometric_id FROM users WHERE userid='$userid'");
        if ($bio_q && mysqli_num_rows($bio_q) > 0) {
            $bio_id = mysqli_fetch_assoc($bio_q)['biometric_id'];
            if (!empty($bio_id)) {
                $cmd_payload = json_encode(['reason' => 'renewed_plan', 'pin' => $new_entry_code, 'name' => $mem_name]);
                mysqli_query($con, "INSERT INTO biometric_commands (command_type, target_uid, payload, status) VALUES ('UPDATE_USERINFO', '$bio_id', '$cmd_payload', 'pending')");
            }
        }
        
        require_once '../../include/smtp_mailer.php';
        require_once '../../include/whatsapp_api.php';
        if ($is_new_member) {
            $g_q = mysqli_query($con, "SELECT gender FROM users WHERE userid='$userid'");
            $gender = ($g_q && mysqli_num_rows($g_q)>0) ? mysqli_fetch_assoc($g_q)['gender'] : '';
            send_member_email($con, $mem_email, $mem_name, $userid, '1234', $plan_name, $amount, $expiredate, $new_entry_code, $discount_amt, $amount, $gender);
            
            $wa_msg = "🔥 Welcome to Sudarshan Fitness, $mem_name! 🔥\n\nYour Pre-Booking has been verified and approved!\n\nMembership ID: $userid\nGym Entry PIN: $new_entry_code\nPlan Paid: ₹$amount\n\nShow this message at the front desk. Get ready to transform your life! 💪";
            sendWhatsAppMessage($mem_mobile, $wa_msg);
        } else {
            send_payment_email($con, $mem_email, $mem_name, $userid, $plan_name, $amount, $expiredate, $payment_mode, $received_by, $new_entry_code, $discount_amt, $amount);
            $wa_msg = "✅ Payment Successful! ✅\n\nHi $mem_name,\nYour gym membership ($plan_name) has been renewed successfully. It is now valid until $expiredate.\n\nYour new Entry PIN is: $new_entry_code\n\nKeep up the great work! 💪";
            sendWhatsAppMessage($mem_mobile, $wa_msg);
        }
        
        echo "<script>alert('Membership Payment Approved and Activated!'); window.location.href='payment_requests.php';</script>";
    } else {
        echo "<script>alert('Database Error.'); window.location.href='payment_requests.php';</script>";
    }
}
?>
