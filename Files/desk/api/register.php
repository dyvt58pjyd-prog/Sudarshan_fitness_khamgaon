<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../include/db_conn.php';

header('Content-Type: application/json; charset=UTF-8');

// Staff permission check
$allowed_roles = ['super_admin', 'owner', 'reception', 'trainer', 'auditor'];
if (!isset($_SESSION['user_data']) || !isset($_SESSION['role']) || !in_array(strtolower($_SESSION['role']), $allowed_roles) || $_SESSION['role'] === 'member') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized: Staff credentials required to register members.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed. Use POST.']);
    exit();
}

// Read inputs
$input = $_POST;

$uname   = isset($input['u_name']) ? trim($input['u_name']) : '';
$phn     = isset($input['mobile']) ? trim($input['mobile']) : '';
$gender  = isset($input['gender']) ? trim($input['gender']) : 'Male';
$dob     = isset($input['dob']) ? trim($input['dob']) : '';
$email   = isset($input['email']) ? trim($input['email']) : '';
$stname  = isset($input['street_name']) ? trim($input['street_name']) : 'Main Road';
$city    = isset($input['city']) ? trim($input['city']) : 'Khamgaon';
$zipcode = isset($input['zipcode']) ? trim($input['zipcode']) : '444303';
$state   = isset($input['state']) ? trim($input['state']) : 'Maharashtra';
$jdate   = isset($input['jdate']) ? trim($input['jdate']) : date('Y-m-d');
$plan    = isset($input['plan']) ? trim($input['plan']) : '';
$height  = isset($input['height']) ? trim($input['height']) : '';
$weight  = isset($input['weight']) ? trim($input['weight']) : '';
$biometric_batch = isset($input['biometric_batch']) ? trim($input['biometric_batch']) : '1';

if (empty($uname)) {
    echo json_encode(['success' => false, 'error' => 'Member Full Name is required.']);
    exit();
}

if (empty($phn) || strlen($phn) < 10) {
    echo json_encode(['success' => false, 'error' => 'Valid 10-digit mobile number is required.']);
    exit();
}

if (empty($plan)) {
    echo json_encode(['success' => false, 'error' => 'Please select a membership plan.']);
    exit();
}

// Verify Plan Exists
$plan_esc = mysqli_real_escape_string($con, $plan);
$q_plan = mysqli_query($con, "SELECT * FROM plan WHERE pid = '$plan_esc' LIMIT 1");
if (!$q_plan || mysqli_num_rows($q_plan) === 0) {
    echo json_encode(['success' => false, 'error' => "Selected Plan '$plan' does not exist."]);
    exit();
}
$plan_row = mysqli_fetch_assoc($q_plan);

// Calculate Next Safe Unique Membership ID
function get_next_free_desk_userid($con) {
    $res = mysqli_query($con, "SELECT userid FROM users WHERE userid REGEXP '^[0-9]+$' AND CAST(userid AS UNSIGNED) < 100000000");
    $max_val = 100;
    if ($res) {
        while ($r = mysqli_fetch_assoc($res)) {
            $v = intval($r['userid']);
            if ($v > $max_val) $max_val = $v;
        }
    }
    $next = $max_val + 1;
    while (true) {
        $s = strval($next);
        $chk_u = mysqli_query($con, "SELECT userid FROM users WHERE userid = '$s'");
        $chk_a = mysqli_query($con, "SELECT username, role FROM admin WHERE username = '$s'");
        
        $u_exists = ($chk_u && mysqli_num_rows($chk_u) > 0);
        $a_exists = false;
        if ($chk_a && mysqli_num_rows($chk_a) > 0) {
            $ar = mysqli_fetch_assoc($chk_a);
            if ($ar['role'] !== 'member') {
                $a_exists = true;
            } else {
                mysqli_query($con, "DELETE FROM admin WHERE username = '$s' AND role = 'member'");
            }
        }
        
        if ($u_exists || $a_exists) {
            $next++;
        } else {
            return strval($next);
        }
    }
}

$memID = get_next_free_desk_userid($con);

// Generate default email if not provided
if (empty($email)) {
    $email = "member_" . $memID . "@sudarshanfitness.local";
}

// Process Member Photo
$photo_path = NULL;
$photo_base64_val = "NULL";
$upload_dir = __DIR__ . '/../../uploads/member_photos/';
if (!file_exists($upload_dir)) {
    @mkdir($upload_dir, 0755, true);
}

if (!empty($input['member_photo_base64'])) {
    $base64_data = trim($input['member_photo_base64']);
    $photo_base64_val = "'" . mysqli_real_escape_string($con, $base64_data) . "'";
    $data_pieces = explode(',', $base64_data);
    if (count($data_pieces) > 1) {
        $img_bytes = base64_decode($data_pieces[1]);
        if ($img_bytes !== false) {
            $filename = 'profile_' . $memID . '_' . time() . '.jpg';
            $full_path = $upload_dir . $filename;
            if (@file_put_contents($full_path, $img_bytes)) {
                $photo_path = '../../uploads/member_photos/' . $filename;
            }
        }
    }
} elseif (isset($_FILES['member_photo_file']) && $_FILES['member_photo_file']['error'] === UPLOAD_ERR_OK) {
    $file_tmp = $_FILES['member_photo_file']['tmp_name'];
    $file_raw = @file_get_contents($file_tmp);
    if ($file_raw !== false) {
        $b64_str = 'data:image/jpeg;base64,' . base64_encode($file_raw);
        $photo_base64_val = "'" . mysqli_real_escape_string($con, $b64_str) . "'";
    }
    $ext = pathinfo($_FILES['member_photo_file']['name'], PATHINFO_EXTENSION);
    if (empty($ext)) $ext = 'jpg';
    $filename = 'profile_' . $memID . '_' . time() . '.' . $ext;
    $full_path = $upload_dir . $filename;
    if (@move_uploaded_file($file_tmp, $full_path)) {
        $photo_path = '../../uploads/member_photos/' . $filename;
    }
}

$photo_val = $photo_path ? "'" . mysqli_real_escape_string($con, $photo_path) . "'" : "NULL";

// Generate 6-digit gate passcode
$entry_code = strval(rand(100000, 999999));

// Escape variables
$uname_esc   = mysqli_real_escape_string($con, $uname);
$gender_esc  = mysqli_real_escape_string($con, $gender);
$phn_esc     = mysqli_real_escape_string($con, $phn);
$email_esc   = mysqli_real_escape_string($con, $email);
$dob_esc     = mysqli_real_escape_string($con, $dob);
$jdate_esc   = mysqli_real_escape_string($con, $jdate);
$stname_esc  = mysqli_real_escape_string($con, $stname);
$city_esc    = mysqli_real_escape_string($con, $city);
$zipcode_esc = mysqli_real_escape_string($con, $zipcode);
$state_esc   = mysqli_real_escape_string($con, $state);
$batch_esc   = mysqli_real_escape_string($con, $biometric_batch);

// 1. Insert into Users
$q_user = "INSERT INTO users (username, gender, mobile, email, dob, joining_date, userid) 
           VALUES ('$uname_esc', '$gender_esc', '$phn_esc', '$email_esc', '$dob_esc', '$jdate_esc', '$memID')";

if (!mysqli_query($con, $q_user)) {
    echo json_encode(['success' => false, 'error' => 'Database error inserting user: ' . mysqli_error($con)]);
    exit();
}

// Update photo and biometric details
@mysqli_query($con, "UPDATE users SET photo=$photo_val, member_photo=$photo_val, photo_base64=$photo_base64_val, entry_code='$entry_code', biometric_id='$memID', biometric_enabled=1, biometric_batch='$batch_esc' WHERE userid='$memID'");

// 2. Insert into Address
$q_addr = "INSERT INTO address (id, streetName, state, city, zipcode) 
           VALUES ('$memID', '$stname_esc', '$state_esc', '$city_esc', '$zipcode_esc')";
mysqli_query($con, $q_addr);

// 3. Insert into Health Status
$height_esc = mysqli_real_escape_string($con, $height);
$weight_esc = mysqli_real_escape_string($con, $weight);
$q_health = "INSERT INTO health_status (uid, weight, height) VALUES ('$memID', '$weight_esc', '$height_esc')";
mysqli_query($con, $q_health);

// 4. Calculate Plan Expiration & Pricing
$cdate = $jdate;
$validity = $plan_row['validity'];
$calc_exp = calculate_expiration_date($cdate, $validity);
if (!$calc_exp) {
    $calc_exp = strtotime("+1 Month", strtotime($cdate));
}
$expiredate = date("Y-m-d", $calc_exp);

$plan_price = intval($plan_row['amount']);
$discount   = isset($input['discount']) ? max(0, intval($input['discount'])) : 0;
$total_payable = max(0, $plan_price - $discount);

$payment_mode = isset($input['payment_mode']) ? mysqli_real_escape_string($con, trim($input['payment_mode'])) : 'Cash';

if ($payment_mode === 'Complimentary') {
    $paid_now = 0;
    $discount = $plan_price;
    $balance  = 0;
    $due_date_val = "NULL";
} else {
    if (isset($input['paid_amount']) && $input['paid_amount'] !== '') {
        $paid_now = max(0, floatval($input['paid_amount']));
    } else {
        $paid_now = $total_payable;
    }
    $balance = max(0, $total_payable - $paid_now);
    $balance_due_date = !empty($input['balance_due_date']) ? mysqli_real_escape_string($con, $input['balance_due_date']) : NULL;
    $due_date_val = ($balance > 0 && !empty($balance_due_date)) ? "'$balance_due_date'" : "NULL";
}

$received_by = isset($_SESSION['full_name']) ? mysqli_real_escape_string($con, $_SESSION['full_name']) : 'Front Desk';
$transaction_date = !empty($input['transaction_date']) ? mysqli_real_escape_string($con, $input['transaction_date']) : date('Y-m-d');

// 5. Insert into Enrolls_to
$chk_start_col = mysqli_query($con, "SHOW COLUMNS FROM enrolls_to LIKE 'start_date'");
if ($chk_start_col && mysqli_num_rows($chk_start_col) > 0) {
    $q_enroll = "INSERT INTO enrolls_to (pid, uid, paid_date, expire, renewal, payment_mode, received_by, discount_amount, paid_amount, balance, balance_due_date, start_date) 
                 VALUES ('$plan_esc', '$memID', '$transaction_date', '$expiredate', 'yes', '$payment_mode', '$received_by', $discount, $paid_now, $balance, $due_date_val, '$cdate')";
} else {
    $q_enroll = "INSERT INTO enrolls_to (pid, uid, paid_date, expire, renewal, payment_mode, received_by, discount_amount, paid_amount, balance, balance_due_date) 
                 VALUES ('$plan_esc', '$memID', '$transaction_date', '$expiredate', 'yes', '$payment_mode', '$received_by', $discount, $paid_now, $balance, $due_date_val)";
}

if (!mysqli_query($con, $q_enroll)) {
    // Rollback user
    mysqli_query($con, "DELETE FROM users WHERE userid='$memID'");
    echo json_encode(['success' => false, 'error' => 'Database error saving enrollment: ' . mysqli_error($con)]);
    exit();
}

$new_et_id = mysqli_insert_id($con);

// 6. Create Member Portal Login Credentials
$default_pass = '1234';
$q_auth = "INSERT INTO admin (username, pass_key, securekey, Full_name, role) 
           VALUES ('$memID', '$default_pass', 'member', '$uname_esc', 'member')";
mysqli_query($con, $q_auth);

// 7. Generate PDF and Dispatch Confirmations
$pdf_file = null;
if (file_exists(__DIR__ . '/../../include/pdf_generator.php')) {
    require_once __DIR__ . '/../../include/pdf_generator.php';
    if (function_exists('generate_receipt_pdf_file')) {
        $pdf_file = @generate_receipt_pdf_file($con, $memID, $new_et_id, $plan_esc);
    }
}

// Send Welcome Email (also enqueues WhatsApp Welcome + PDF receipt)
$welcome_dispatched = false;
if (!empty($email) && function_exists('send_member_email')) {
    try {
        @send_member_email($con, $email, $uname, $memID, $default_pass, $plan_row['planName'], $plan_row['amount'], $expiredate, $entry_code, $discount, $paid_now, $gender);
        $welcome_dispatched = true;
    } catch (\Throwable $e) {}
}

// Fallback: Send WhatsApp Welcome Receipt if email was empty or email helper skipped
if (!$welcome_dispatched && !empty($phn) && function_exists('send_whatsapp_welcome_confirmation')) {
    try {
        @send_whatsapp_welcome_confirmation($con, $phn, $uname, $memID, $default_pass, $plan_row['planName'], $paid_now, $expiredate, $payment_mode, $entry_code, $gender, $pdf_file);
    } catch (\Throwable $e) {}
}

// 8. Log Event
log_security_event($con, 'DESK_REGISTRATION_COMPLETED', "Desk registration: Registered new member $uname (#$memID) with plan '{$plan_row['planName']}'", 'info', $memID, $_SESSION['user_data'] ?? 'desk');

echo json_encode([
    'success'         => true,
    'message'         => "Member $uname registered successfully with ID #$memID!",
    'userid'          => $memID,
    'et_id'           => $new_et_id,
    'member_name'     => $uname,
    'mobile'          => $phn,
    'plan_name'       => $plan_row['planName'],
    'expire_date'     => $expiredate,
    'entry_code'      => $entry_code,
    'paid_amount'     => $paid_now,
    'balance'         => $balance,
    'payment_mode'    => $payment_mode,
    'receipt_url'     => "receipt.php?etid=$new_et_id&uid=$memID"
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
