<?php
require '../../include/db_conn.php';
page_protect();

if ($_SESSION['role'] !== 'super_admin' && $_SESSION['role'] !== 'owner') {
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

// Fetch user details
$user_q = mysqli_query($con, "SELECT username, email, mobile FROM users WHERE userid='$userid'");
$user_row = mysqli_fetch_assoc($user_q);
$mem_name = $user_row['username'];
$mem_email = $user_row['email'];
$mem_mobile = $user_row['mobile'];

date_default_timezone_set("Asia/Calcutta");
$cdate = date('Y-m-d');
$launch_date = '2026-07-08';
$calc_base_date = ($cdate < $launch_date) ? $launch_date : $cdate;
$payment_mode = 'UPI';
$received_by = 'Admin Verification (' . $_SESSION['full_name'] . ')';

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
    $plan_q = mysqli_query($con, "SELECT planName, validity FROM plan WHERE pid = '$pid'");
    if ($plan_q && mysqli_num_rows($plan_q) > 0) {
        $plan_data = mysqli_fetch_assoc($plan_q);
        $plan_name = $plan_data['planName'];
        $validity = intval($plan_data['validity']);
        
        $is_new_member = false;
        $chk_prev_enroll = mysqli_query($con, "SELECT et_id FROM enrolls_to WHERE uid='$userid'");
        if (mysqli_num_rows($chk_prev_enroll) == 0) {
            $is_new_member = true;
        }

        mysqli_query($con, "UPDATE enrolls_to SET renewal='no' WHERE uid='$userid'");
        
        $d = strtotime("+" . $validity . " Months", strtotime($calc_base_date));
        $expiredate = date("Y-m-d", $d);
        
        $ins_enroll = "INSERT INTO enrolls_to (pid, uid, paid_date, expire, renewal, payment_mode, received_by, discount_amount, paid_amount) 
                       VALUES ('$pid', '$userid', '$cdate', '$expiredate', 'yes', '$payment_mode', '$received_by', 0, $amount)";
        
        if (mysqli_query($con, $ins_enroll)) {
            $new_entry_code = strval(rand(100000, 999999));
            mysqli_query($con, "UPDATE users SET entry_code = '$new_entry_code' WHERE userid = '$userid'");
            mysqli_query($con, "UPDATE payment_requests SET status = 'approved' WHERE id = $req_id");
            
            require_once '../../include/smtp_mailer.php';
            if ($is_new_member) {
                // Fetch gender for welcome email
                $g_q = mysqli_query($con, "SELECT gender FROM users WHERE userid='$userid'");
                $gender = ($g_q && mysqli_num_rows($g_q)>0) ? mysqli_fetch_assoc($g_q)['gender'] : '';
                send_member_email($con, $mem_email, $mem_name, $userid, '1234', $plan_name, $amount, $expiredate, $new_entry_code, 0, $amount, $gender);
            } else {
                send_payment_email($con, $mem_email, $mem_name, $userid, $plan_name, $amount, $expiredate, $payment_mode, $received_by, $new_entry_code, 0, $amount);
            }
            
            echo "<script>alert('Membership Payment Approved and Activated!'); window.location.href='payment_requests.php';</script>";
        } else {
            echo "<script>alert('Database Error.'); window.location.href='payment_requests.php';</script>";
        }
    } else {
        echo "<script>alert('Plan no longer exists.'); window.location.href='payment_requests.php';</script>";
    }
}
?>
