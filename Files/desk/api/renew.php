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
    echo json_encode(['success' => false, 'error' => 'Unauthorized: Staff credentials required to renew memberships.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed. Use POST.']);
    exit();
}

// Read either POST form-urlencoded or JSON payload
$input = $_POST;
if (empty($input)) {
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    if (is_array($json)) {
        $input = $json;
    }
}

$memID = isset($input['m_id']) ? trim($input['m_id']) : (isset($input['uid']) ? trim($input['uid']) : '');
$plan  = isset($input['plan']) ? trim($input['plan']) : (isset($input['pid']) ? trim($input['pid']) : '');

if (empty($memID) || empty($plan)) {
    echo json_encode(['success' => false, 'error' => 'Member ID and Plan must be provided.']);
    exit();
}

$memID_esc = mysqli_real_escape_string($con, $memID);
$plan_esc  = mysqli_real_escape_string($con, $plan);

// 1. Verify Member Exists
$q_user = mysqli_query($con, "SELECT userid, username, mobile, email, biometric_id FROM users WHERE userid = '$memID_esc' LIMIT 1");
if (!$q_user || mysqli_num_rows($q_user) === 0) {
    echo json_encode(['success' => false, 'error' => "Member #$memID not found in system."]);
    exit();
}
$user_row = mysqli_fetch_assoc($q_user);

// 2. Verify Plan Exists
$q_plan = mysqli_query($con, "SELECT * FROM plan WHERE pid = '$plan_esc' LIMIT 1");
if (!$q_plan || mysqli_num_rows($q_plan) === 0) {
    echo json_encode(['success' => false, 'error' => "Selected Plan ID '$plan' does not exist."]);
    exit();
}
$plan_row = mysqli_fetch_assoc($q_plan);

date_default_timezone_set("Asia/Calcutta");
$today = date('Y-m-d');

// Determine Effective Start Date
$start_date_val = $today;
if (!empty($input['start_date'])) {
    $parsed_sd = date('Y-m-d', strtotime($input['start_date']));
    if ($parsed_sd && $parsed_sd !== '1970-01-01') {
        $start_date_val = $parsed_sd;
    }
}

// Calculate Expiry Date
$validity = $plan_row['validity'];
$calc_exp = calculate_expiration_date($start_date_val, $validity);
if (!$calc_exp) {
    // Fallback: 1 month if calculate_expiration_date fails
    $calc_exp = strtotime("+1 Month", strtotime($start_date_val));
}
$expiredate = date("Y-m-d", $calc_exp);

// Pricing & Discount Calculations
$plan_price = intval($plan_row['amount']);
$discount   = isset($input['discount']) ? max(0, intval($input['discount'])) : 0;
$discount_lock = intval($plan_row['discount_lock'] ?? 0);

// Validate discount limit for non-superadmin
$current_role = strtolower($_SESSION['role'] ?? '');
if ($discount_lock > 0 && $discount > $discount_lock && $current_role !== 'super_admin' && $current_role !== 'owner') {
    echo json_encode(['success' => false, 'error' => "Maximum discount permitted for '{$plan_row['planName']}' is ₹$discount_lock."]);
    exit();
}

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
$transaction_date = !empty($input['transaction_date']) ? mysqli_real_escape_string($con, $input['transaction_date']) : $today;

// 3. Mark previous active plans renewal='no'
mysqli_query($con, "UPDATE enrolls_to SET renewal='no' WHERE uid='$memID_esc'");

// 4. Insert new enrollment record
$chk_start_col = mysqli_query($con, "SHOW COLUMNS FROM enrolls_to LIKE 'start_date'");
if ($chk_start_col && mysqli_num_rows($chk_start_col) > 0) {
    $sql_insert = "INSERT INTO enrolls_to (pid, uid, paid_date, expire, renewal, payment_mode, received_by, discount_amount, paid_amount, balance, balance_due_date, start_date) 
                   VALUES ('$plan_esc', '$memID_esc', '$transaction_date', '$expiredate', 'yes', '$payment_mode', '$received_by', $discount, $paid_now, $balance, $due_date_val, '$start_date_val')";
} else {
    $sql_insert = "INSERT INTO enrolls_to (pid, uid, paid_date, expire, renewal, payment_mode, received_by, discount_amount, paid_amount, balance, balance_due_date) 
                   VALUES ('$plan_esc', '$memID_esc', '$transaction_date', '$expiredate', 'yes', '$payment_mode', '$received_by', $discount, $paid_now, $balance, $due_date_val)";
}

if (!mysqli_query($con, $sql_insert)) {
    echo json_encode(['success' => false, 'error' => 'Database error while saving enrollment: ' . mysqli_error($con)]);
    exit();
}

$new_et_id = mysqli_insert_id($con);

// 5. Generate New 6-Digit Gate Entry PIN
$new_entry_code = strval(rand(100000, 999999));
mysqli_query($con, "UPDATE users SET entry_code = '$new_entry_code' WHERE userid = '$memID_esc'");

// 6. Queue Biometric Sync Update if bio device exists
$bio_id = $user_row['biometric_id'] ?? $memID;
if (!empty($bio_id)) {
    $chk_bio_table = mysqli_query($con, "SHOW TABLES LIKE 'biometric_commands'");
    if ($chk_bio_table && mysqli_num_rows($chk_bio_table) > 0) {
        $payload_json = mysqli_real_escape_string($con, json_encode([
            'reason' => 'desk_renewal',
            'pin'    => $new_entry_code,
            'name'   => $user_row['username']
        ]));
        @mysqli_query($con, "INSERT INTO biometric_commands (command_type, target_uid, payload, status) VALUES ('UPDATE_USERINFO', '$bio_id', '$payload_json', 'pending')");
    }
}

// 7. Generate PDF and Dispatch Confirmations
$pdf_file = null;
if (file_exists(__DIR__ . '/../../include/pdf_generator.php')) {
    require_once __DIR__ . '/../../include/pdf_generator.php';
    if (function_exists('generate_receipt_pdf_file')) {
        $pdf_file = @generate_receipt_pdf_file($con, $memID, $new_et_id, $plan_esc);
    }
}

// Send Email Receipt (also enqueues WhatsApp Receipt + PDF)
$receipt_dispatched = false;
if (!empty($user_row['email']) && function_exists('send_payment_email')) {
    try {
        @send_payment_email($con, $user_row['email'], $user_row['username'], $memID, $plan_row['planName'], $plan_row['amount'], $expiredate, $payment_mode, $received_by, $new_entry_code, $discount, $paid_now);
        $receipt_dispatched = true;
    } catch (\Throwable $e) {}
}

// Fallback: Send WhatsApp Receipt with PDF if member has no email
if (!$receipt_dispatched && !empty($user_row['mobile']) && function_exists('send_whatsapp_payment_confirmation')) {
    try {
        @send_whatsapp_payment_confirmation($con, $user_row['mobile'], $user_row['username'], $plan_row['planName'], $paid_now, $expiredate, $payment_mode, $pdf_file, $memID);
    } catch (\Throwable $e) {}
}

// 8. Log Event
log_security_event($con, 'DESK_RENEWAL_COMPLETED', "Desk renewal: {$user_row['username']} (#$memID) renewed plan '{$plan_row['planName']}' until $expiredate. Paid: ₹$paid_now ($payment_mode)", 'info', $memID, $_SESSION['user_data'] ?? 'desk');

echo json_encode([
    'success'         => true,
    'message'         => "Membership successfully renewed for {$user_row['username']}!",
    'et_id'           => $new_et_id,
    'uid'             => $memID,
    'member_name'     => $user_row['username'],
    'plan_name'       => $plan_row['planName'],
    'start_date'      => $start_date_val,
    'expire_date'     => $expiredate,
    'entry_code'      => $new_entry_code,
    'paid_amount'     => $paid_now,
    'balance'         => $balance,
    'payment_mode'    => $payment_mode,
    'receipt_url'     => "receipt.php?etid=$new_et_id&uid=$memID"
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
