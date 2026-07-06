<?php
header("Content-Type: application/json; charset=UTF-8");

// Load DB connection
require_once __DIR__ . '/../include/db_conn.php';

// Ensure correct timezone
date_default_timezone_set("Asia/Calcutta");

// Read POST payload (supports both raw JSON and standard form POST)
$raw_input = file_get_contents('php://input');
$input = json_decode($raw_input, true);

if (!$input) {
    $input = $_POST;
}

$biometric_id = isset($input['biometric_id']) ? $input['biometric_id'] : 0;
$timestamp = isset($input['timestamp']) ? $input['timestamp'] : '';

// Ensure gate logging table exists
mysqli_query($con, "CREATE TABLE IF NOT EXISTS biometric_gate_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    uid VARCHAR(50) NOT NULL,
    timestamp DATETIME NOT NULL,
    status VARCHAR(20) NOT NULL,
    type VARCHAR(30) NOT NULL,
    error_reason VARCHAR(255) NULL
)");

if ($biometric_id <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid or missing biometric_id parameter.'
    ]);
    exit();
}

// Find member mapping
$biometric_id_esc = mysqli_real_escape_string($con, $biometric_id);
$q_user = mysqli_query($con, "SELECT userid, username, mobile, biometric_enabled FROM users WHERE biometric_id = '$biometric_id_esc' OR (biometric_id IS NULL AND userid = '$biometric_id_esc') LIMIT 1");

if (!$q_user || mysqli_num_rows($q_user) === 0) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'message' => "No gym member mapped to Biometric ID: $biometric_id"
    ]);
    exit();
}

$user = mysqli_fetch_assoc($q_user);
$userid = $user['userid'];
$username = $user['username'];
$mobile = $user['mobile'];
$biometric_enabled = isset($user['biometric_enabled']) ? intval($user['biometric_enabled']) : 1;

// Parse time and date
$time_parsed = !empty($timestamp) ? strtotime($timestamp) : time();
$log_date = date('Y-m-d', $time_parsed);
$log_time = date('H:i:s', $time_parsed);

// Check if attendance record exists for this member on this date
$userid_esc = mysqli_real_escape_string($con, $userid);
$log_date_esc = mysqli_real_escape_string($con, $log_date);
$log_time_esc = mysqli_real_escape_string($con, $log_time);

// 1. Check if biometric access is disabled
if ($biometric_enabled === 0) {
    mysqli_query($con, "INSERT INTO biometric_gate_logs (uid, timestamp, status, type, error_reason) VALUES ('$userid_esc', '$log_date_esc $log_time_esc', 'failed', 'access-denied', 'Biometric account disabled')");
    echo json_encode([
        'success' => false,
        'message' => 'Access Denied: Biometric access is disabled for this account.',
        'member_id' => $userid,
        'name' => $username,
        'action' => 'access-denied'
    ]);
    exit();
}

// 2. Check if membership plan is expired
$q_exp = mysqli_query($con, "SELECT MAX(expire) as max_expire FROM enrolls_to WHERE uid = '$userid_esc'");
$max_expire = null;
if ($q_exp && mysqli_num_rows($q_exp) > 0) {
    $exp_row = mysqli_fetch_assoc($q_exp);
    $max_expire = $exp_row['max_expire'];
}

if ($max_expire && strtotime($max_expire) < strtotime($log_date)) {
    mysqli_query($con, "INSERT INTO biometric_gate_logs (uid, timestamp, status, type, error_reason) VALUES ('$userid_esc', '$log_date_esc $log_time_esc', 'failed', 'expired', 'Membership expired')");
    echo json_encode([
        'success' => false,
        'message' => "Access Denied: Membership expired on $max_expire.",
        'member_id' => $userid,
        'name' => $username,
        'action' => 'expired'
    ]);
    exit();
}

// 3. Women-Only Batch Biometric Access Restriction Check
$gym_info = get_gym_details($con);
if (isset($gym_info['women_batch_enabled']) && intval($gym_info['women_batch_enabled']) === 1) {
    $current_time_str = $log_time; // HH:ii:ss
    $batch_start = $gym_info['women_batch_start'];
    $batch_end = $gym_info['women_batch_end'];
    
    // Check if the punch occurs during the restricted women-only batch hour
    if ($current_time_str >= $batch_start && $current_time_str <= $batch_end) {
        $member_gender = strtolower(trim($user['gender']));
        
        // If the current member attempting to check-in is NOT a woman (e.g. male)
        if ($member_gender !== 'female' && $member_gender !== 'f') {
            
            // Check if there are any women currently checked in (present in the gym)
            // A woman is "inside the gym" if she checked in today and has NOT checked out yet (exit_time IS NULL)
            $women_inside_query = "SELECT COUNT(*) as count FROM attendance a 
                                   INNER JOIN users u ON a.uid = u.userid 
                                   WHERE a.date = '$log_date_esc' 
                                     AND a.exit_time IS NULL 
                                     AND (LOWER(u.gender) = 'female' OR LOWER(u.gender) = 'f')";
            
            $res_women_inside = mysqli_query($con, $women_inside_query);
            $women_count = 0;
            if ($res_women_inside) {
                $women_row = mysqli_fetch_assoc($res_women_inside);
                $women_count = intval($women_row['count']);
            }
            
            if ($women_count > 0) {
                // Deny access because women are active inside the gym during women-only batch hours
                mysqli_query($con, "INSERT INTO biometric_gate_logs (uid, timestamp, status, type, error_reason) VALUES ('$userid_esc', '$log_date_esc $log_time_esc', 'failed', 'access-denied', 'Women-Only Batch active')");
                echo json_encode([
                    'success' => false,
                    'message' => "Access Denied: Women-Only Batch is currently active inside the gym.",
                    'member_id' => $userid,
                    'name' => $username,
                    'action' => 'access-denied'
                ]);
                exit();
            }
        }
    }
}

$q_att = mysqli_query($con, "SELECT * FROM attendance WHERE uid = '$userid_esc' AND date = '$log_date_esc' LIMIT 1");

if ($q_att && mysqli_num_rows($q_att) > 0) {
    // Log exists, update exit_time
    $att_row = mysqli_fetch_assoc($q_att);
    $att_id = $att_row['id'];
    
    $update_sql = "UPDATE attendance SET exit_time = '$log_time_esc' WHERE id = '$att_id'";
    if (mysqli_query($con, $update_sql)) {
        mysqli_query($con, "INSERT INTO biometric_gate_logs (uid, timestamp, status, type) VALUES ('$userid_esc', '$log_date_esc $log_time_esc', 'success', 'check-out')");
        
        $gym = get_gym_details($con);
        $gym_name = $gym['gym_name'];
        $streak = get_member_streak($con, $userid);
        if (!empty($mobile)) {
            $wa_msg = "👋 *Goodbye from {$gym_name}!* 👋\n\n" .
                       "Hello *{$username}*,\n\n" .
                       "You checked out at * " . date('h:i A', strtotime($log_time)) . "*.\n\n" .
                       "Great session today! Keep up the consistency. See you next time! 🔌";
                       
            enqueue_whatsapp_message($con, $mobile, $wa_msg);
        }
        
        echo json_encode([
            'success' => true,
            'action' => 'check-out',
            'member_id' => $userid,
            'name' => $username,
            'date' => $log_date,
            'time' => $log_time,
            'streak' => $streak,
            'message' => "Check-out logged successfully for $username."
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database error updating check-out log: ' . mysqli_error($con)
        ]);
    }
} else {
    // Log does not exist, insert check-in
    $insert_sql = "INSERT INTO attendance (uid, date, entry_time, exit_time) 
                   VALUES ('$userid_esc', '$log_date_esc', '$log_time_esc', NULL)";
    if (mysqli_query($con, $insert_sql)) {
        mysqli_query($con, "INSERT INTO biometric_gate_logs (uid, timestamp, status, type) VALUES ('$userid_esc', '$log_date_esc $log_time_esc', 'success', 'check-in')");
        
        // Gamification: Add 50 XP for daily check-in
        $xp_data = add_member_xp($con, $userid, 50);
        
        $gym = get_gym_details($con);
        $gym_name = $gym['gym_name'];
        $streak = get_member_streak($con, $userid);
        $is_milestone = ($streak > 0 && $streak % 5 === 0);
        if (!empty($mobile)) {
            $wa_msg = "🏋️ *Welcome to {$gym_name}!* 🏋️\n\n" .
                       "Hello *{$username}*,\n\n" .
                       "You checked in at * " . date('h:i A', strtotime($log_time)) . "*.\n" .
                       "🔥 *Check-In Streak:* *{$streak} days!*\n\n";
                       
            if ($is_milestone) {
                $wa_msg .= "🌟 *MILESTONE UNLOCKED!* 🌟\n" .
                           "Congratulations on reaching a *{$streak}-day attendance streak*! Your consistency is inspiring. Keep up the amazing work! 🏆\n\n";
            }
            
            $wa_msg .= "Let's crush today's workout! 💪";
                       
            enqueue_whatsapp_message($con, $mobile, $wa_msg);
        }
        
        echo json_encode([
            'success' => true,
            'action' => 'check-in',
            'member_id' => $userid,
            'name' => $username,
            'date' => $log_date,
            'time' => $log_time,
            'streak' => $streak,
            'xp_earned' => 50,
            'new_rank' => $xp_data ? $xp_data['new_rank'] : 'Beginner',
            'message' => "Check-in logged successfully for $username."
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database error inserting check-in log: ' . mysqli_error($con)
        ]);
    }
}
