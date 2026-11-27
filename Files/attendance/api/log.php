<?php
// Attendance Processing Engine
// Validates Active vs Expired / Inactive membership
// Logs attendance & returns instant tick + expiry date, or denies entry
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$start_time = microtime(true);
require_once __DIR__ . '/../../include/db_conn.php';

header('Content-Type: application/json; charset=UTF-8');

// Staff access verification
$allowed_roles = ['super_admin', 'owner', 'reception', 'trainer', 'staff', 'admin', 'auditor'];
if (!isset($_SESSION['user_data']) || !isset($_SESSION['role']) || !in_array(strtolower($_SESSION['role']), $allowed_roles) || $_SESSION['role'] === 'member') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized staff access.']);
    exit();
}

date_default_timezone_set("Asia/Calcutta");
$today_str = date('Y-m-d');
$current_time_str = date('H:i:s');
$display_time = date('h:i A');

// Ensure biometric_gate_logs table exists for security audit
mysqli_query($con, "CREATE TABLE IF NOT EXISTS biometric_gate_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    uid VARCHAR(50) NOT NULL,
    timestamp DATETIME NOT NULL,
    status VARCHAR(20) NOT NULL,
    type VARCHAR(30) NOT NULL,
    error_reason VARCHAR(255) NULL
)");

// Parse input (supports JSON payload or standard POST)
$raw_input = file_get_contents('php://input');
$input = json_decode($raw_input, true);
if (!is_array($input)) {
    $input = $_POST;
}

$uid        = isset($input['uid']) ? trim($input['uid']) : '';
$identifier = isset($input['identifier']) ? trim($input['identifier']) : '';

if (empty($uid) && empty($identifier)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Please provide a Member ID, Name, Mobile Number, or Email.'
    ]);
    exit();
}

// 1. Locate gym member
$user = null;
if (!empty($uid)) {
    $uid_esc = mysqli_real_escape_string($con, $uid);
    $q_user = mysqli_query($con, "SELECT * FROM users WHERE userid = '$uid_esc' LIMIT 1");
    if ($q_user && mysqli_num_rows($q_user) > 0) {
        $user = mysqli_fetch_assoc($q_user);
    }
}

if (!$user && !empty($identifier)) {
    $ident_esc = mysqli_real_escape_string($con, $identifier);
    $ident_clean = preg_replace('/[^0-9a-zA-Z@._\-]/', '', $identifier);
    $clean_esc = mysqli_real_escape_string($con, $ident_clean);

    // Try exact matches first
    $q_user = mysqli_query($con, "SELECT * FROM users 
        WHERE userid = '$ident_esc' 
           OR mobile = '$clean_esc' 
           OR email = '$ident_esc' 
           OR LOWER(username) = LOWER('$ident_esc')
        LIMIT 1");

    if ($q_user && mysqli_num_rows($q_user) > 0) {
        $user = mysqli_fetch_assoc($q_user);
    } else {
        // Fallback: prefix match
        $q_user = mysqli_query($con, "SELECT * FROM users 
            WHERE mobile LIKE '$clean_esc%' 
               OR email LIKE '$ident_esc%' 
               OR username LIKE '$ident_esc%'
            LIMIT 1");
        if ($q_user && mysqli_num_rows($q_user) > 0) {
            $user = mysqli_fetch_assoc($q_user);
        }
    }
}

if (!$user) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'action' => 'not_found',
        'message' => 'No gym member found matching: "' . htmlspecialchars(!empty($uid) ? $uid : $identifier) . '"'
    ]);
    exit();
}

$member_uid  = $user['userid'];
$member_name = $user['username'];
$member_mob  = $user['mobile'] ?? '';
$member_em   = $user['email'] ?? '';
$uid_esc     = mysqli_real_escape_string($con, $member_uid);
$photo_url   = get_member_photo_url($user, '../');

// 2. Retrieve latest membership plan & expiration date
$q_sub = mysqli_query($con, "SELECT e.*, p.planName, p.validity 
                             FROM enrolls_to e 
                             INNER JOIN plan p ON e.pid = p.pid 
                             WHERE e.uid = '$uid_esc' AND e.renewal = 'yes' 
                             ORDER BY e.expire DESC LIMIT 1");

$sub = ($q_sub && mysqli_num_rows($q_sub) > 0) ? mysqli_fetch_assoc($q_sub) : null;

// Fallback to any latest enrolls_to record if renewal='yes' filter is empty
if (!$sub) {
    $q_sub_fallback = mysqli_query($con, "SELECT e.*, p.planName, p.validity 
                                          FROM enrolls_to e 
                                          LEFT JOIN plan p ON e.pid = p.pid 
                                          WHERE e.uid = '$uid_esc' 
                                          ORDER BY e.expire DESC LIMIT 1");
    if ($q_sub_fallback && mysqli_num_rows($q_sub_fallback) > 0) {
        $sub = mysqli_fetch_assoc($q_sub_fallback);
    }
}

// Couple / Partner membership check
if ((!$sub || empty($sub['expire']) || $sub['expire'] < $today_str) && !empty($user['partner_uid'])) {
    $p_uid = mysqli_real_escape_string($con, $user['partner_uid']);
    $qp = mysqli_query($con, "SELECT e.*, p.planName, p.validity FROM enrolls_to e LEFT JOIN plan p ON e.pid=p.pid WHERE e.uid='$p_uid' ORDER BY e.expire DESC LIMIT 1");
    if ($qp && $p_sub = mysqli_fetch_assoc($qp)) {
        if (!empty($p_sub['expire']) && $p_sub['expire'] >= $today_str) {
            $sub = $p_sub;
            $sub['planName'] = ($p_sub['planName'] ?? 'Couple Membership') . " (via Partner)";
        }
    }
}

// 3. VALIDATE ACTIVE STATUS VS EXPIRED / INACTIVE
if (!$sub || empty($sub['expire'])) {
    // DENIED: No active plan found
    mysqli_query($con, "INSERT INTO biometric_gate_logs (uid, timestamp, status, type, error_reason) 
                        VALUES ('$uid_esc', '$today_str $current_time_str', 'failed', 'access-denied', 'No active subscription')");

    http_response_code(200); // 200 so UI receives structured denial payload
    echo json_encode([
        'success'      => false,
        'denied'       => true,
        'action'       => 'denied_no_plan',
        'uid'          => $member_uid,
        'name'         => $member_name,
        'mobile'       => $member_mob,
        'email'        => $member_em,
        'photo'        => $photo_url,
        'plan'         => 'No Active Plan',
        'expire'       => 'INACTIVE',
        'days_expired' => null,
        'message'      => "ENTRY DENIED: No active membership plan found for {$member_name}. Attendance blocked."
    ]);
    exit();
}

$expire_date = $sub['expire'];
$plan_name   = $sub['planName'] ?? 'Gym Membership';
$expire_ts   = strtotime($expire_date);
$today_ts    = strtotime($today_str);
$days_diff   = (int)floor(($expire_ts - $today_ts) / 86400);

if ($expire_date < $today_str) {
    // DENIED: Membership Expired!
    $days_expired = abs($days_diff);
    $exp_formatted = date('d-M-Y', $expire_ts);

    mysqli_query($con, "INSERT INTO biometric_gate_logs (uid, timestamp, status, type, error_reason) 
                        VALUES ('$uid_esc', '$today_str $current_time_str', 'failed', 'expired', 'Membership expired on $expire_date')");

    http_response_code(200);
    echo json_encode([
        'success'      => false,
        'denied'       => true,
        'action'       => 'denied_expired',
        'uid'          => $member_uid,
        'name'         => $member_name,
        'mobile'       => $member_mob,
        'email'        => $member_em,
        'photo'        => $photo_url,
        'plan'         => $plan_name,
        'expire'       => $exp_formatted,
        'days_expired' => $days_expired,
        'message'      => "ENTRY DENIED: Membership expired on {$exp_formatted} ({$days_expired} days ago). Attendance blocked."
    ]);
    exit();
}

// 4. MEMBERSHIP IS ACTIVE -> LOG ATTENDANCE
$expiry_status = ($days_diff <= 7) ? 'EXPIRING_SOON' : 'ACTIVE';
$exp_formatted = date('d-M-Y', $expire_ts);

// Check if attendance record exists today
$q_att = mysqli_query($con, "SELECT * FROM attendance WHERE uid = '$uid_esc' AND date = '$today_str' ORDER BY id DESC LIMIT 1");
$att_action = 'check-in';
$entry_recorded = $display_time;
$exit_recorded  = null;
$record_id = 0;

if ($q_att && mysqli_num_rows($q_att) > 0) {
    $existing = mysqli_fetch_assoc($q_att);

    if (empty($existing['exit_time']) || $existing['exit_time'] === '00:00:00') {
        // Member was inside -> Check them out
        $record_id = (int)$existing['id'];
        $upd = mysqli_query($con, "UPDATE attendance SET exit_time = '$current_time_str' WHERE id = $record_id");
        $att_action = 'check-out';
        $entry_recorded = !empty($existing['entry_time']) ? date('h:i A', strtotime($existing['entry_time'])) : '--';
        $exit_recorded  = $display_time;
    } else {
        // Member has already checked out -> New check-in session today
        $ins = mysqli_query($con, "INSERT INTO attendance (uid, date, entry_time) VALUES ('$uid_esc', '$today_str', '$current_time_str')");
        $record_id = mysqli_insert_id($con);
        $att_action = 'check-in';
        $entry_recorded = $display_time;
    }
} else {
    // First check-in today
    $ins = mysqli_query($con, "INSERT INTO attendance (uid, date, entry_time) VALUES ('$uid_esc', '$today_str', '$current_time_str')");
    $record_id = mysqli_insert_id($con);
    $att_action = 'check-in';
    $entry_recorded = $display_time;
}

// Calculate streak
$streak = function_exists('get_member_streak') ? get_member_streak($con, $member_uid) : 1;
if ($streak <= 0) $streak = 1;

// Award points if check-in
if ($att_action === 'check-in' && function_exists('add_member_xp')) {
    add_member_xp($con, $member_uid, 50);
}

// Log gate success
mysqli_query($con, "INSERT INTO biometric_gate_logs (uid, timestamp, status, type, error_reason) 
                    VALUES ('$uid_esc', '$today_str $current_time_str', 'success', '$att_action', 'Portal verification passed')");

$gym = get_gym_details($con);
$gym_name = $gym['gym_name'] ?? 'Sudarshan Fitness';

// Optional WhatsApp Notification for Check-in / Check-out
if (!empty($member_mob) && function_exists('enqueue_whatsapp_message')) {
    if ($att_action === 'check-in') {
        $msg = "🏋️ *Welcome to {$gym_name}!* 🏋️\n\n" .
               "Hello *{$member_name}*,\n" .
               "Attendance logged at *{$display_time}*.\n" .
               "🔥 *Streak:* *{$streak} days*\n" .
               "📅 *Valid Until:* {$exp_formatted} ({$days_diff} days left)\n\n" .
               "Have a powerful workout! 💪";
        enqueue_whatsapp_message($con, $member_mob, $msg);
    } else {
        $msg = "👋 *Goodbye from {$gym_name}!* 👋\n\n" .
               "Hello *{$member_name}*,\n" .
               "Check-out logged at *{$display_time}*.\n" .
               "Great workout session today! See you tomorrow. 🔌";
        enqueue_whatsapp_message($con, $member_mob, $msg);
    }
}

$end_time = microtime(true);
$time_ms = round(($end_time - $start_time) * 1000, 2);

echo json_encode([
    'success'       => true,
    'denied'        => false,
    'action'        => $att_action,
    'uid'           => $member_uid,
    'name'          => $member_name,
    'mobile'        => $member_mob,
    'email'         => $member_em,
    'photo'         => $photo_url,
    'plan'          => $plan_name,
    'expire'        => $exp_formatted,
    'days_left'     => $days_diff,
    'expiry_status' => $expiry_status,
    'entry_time'    => $entry_recorded,
    'exit_time'     => $exit_recorded,
    'streak'        => $streak,
    'record_id'     => $record_id,
    'time_ms'       => $time_ms,
    'message'       => ($att_action === 'check-out') 
        ? "Check-out logged for {$member_name} at {$display_time}." 
        : "Attendance Logged Successfully! Valid until {$exp_formatted}."
], JSON_UNESCAPED_SLASHES);
