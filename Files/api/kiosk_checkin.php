<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../include/db_conn.php';

date_default_timezone_set("Asia/Calcutta");

// Read POST payload
$raw_input = file_get_contents('php://input');
$input = json_decode($raw_input, true);
if (!$input) {
    $input = $_POST;
}

$identifier = isset($input['identifier']) ? trim($input['identifier']) : '';

if (empty($identifier)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Please enter your Member ID or registered Mobile Number.'
    ]);
    exit();
}

$identifier_esc = mysqli_real_escape_string($con, $identifier);

// Find user by ID or Mobile
$q_user = mysqli_query($con, "SELECT * FROM users WHERE userid = '$identifier_esc' OR mobile = '$identifier_esc' LIMIT 1");

if (!$q_user || mysqli_num_rows($q_user) === 0) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'message' => 'Gym member not found with ID or Mobile: ' . htmlspecialchars($identifier)
    ]);
    exit();
}

$user = mysqli_fetch_assoc($q_user);
$uid = $user['userid'];
$name = $user['username'];
$mobile = $user['mobile'];

// Verify membership active subscription
$today_str = date('Y-m-d');
$q_sub = mysqli_query($con, "SELECT e.*, p.planName FROM enrolls_to e 
                             INNER JOIN plan p ON e.pid = p.pid 
                             WHERE e.uid = '$uid' AND e.renewal = 'yes' 
                             ORDER BY e.expire DESC LIMIT 1");

if (!$q_sub || mysqli_num_rows($q_sub) === 0) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Access Denied: No active membership subscription found for ' . $name
    ]);
    exit();
}

$sub = mysqli_fetch_assoc($q_sub);
$expire_date = $sub['expire'];
$plan_name = $sub['planName'];

if ($expire_date < $today_str) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Access Denied: Membership expired on ' . date('d-M-Y', strtotime($expire_date)) . '. Please renew!'
    ]);
    exit();
}

// Log attendance check-in/out
$log_time = date('H:i:s');
$q_att = mysqli_query($con, "SELECT * FROM attendance WHERE uid = '$uid' AND date = '$today_str' LIMIT 1");

$gym = get_gym_details($con);
$gym_name = $gym['gym_name'];
$whatsapp_url = 'http://localhost:5001/send';

if ($q_att && mysqli_num_rows($q_att) > 0) {
    // Record exists
    $att_row = mysqli_fetch_assoc($q_att);
    
    if (empty($att_row['exit_time']) || $att_row['exit_time'] === '00:00:00') {
        // Exit time is not logged, update it (Check-out)
        $update_sql = "UPDATE attendance SET exit_time = '$log_time' WHERE id = " . $att_row['id'];
        if (mysqli_query($con, $update_sql)) {
            // Get current streak
            $streak = get_member_streak($con, $uid);
            
            // Send WhatsApp Check-out Alert
            if (!empty($mobile)) {
                $wa_msg = "👋 *Goodbye from {$gym_name}!* 👋\n\n" .
                           "Hello *{$name}*,\n\n" .
                           "You checked out at * " . date('h:i A', strtotime($log_time)) . "*.\n\n" .
                           "Great session today! Keep up the consistency. See you next time! 🔌";
                           
                enqueue_whatsapp_message($con, $mobile, $wa_msg);
            }

            echo json_encode([
                'success' => true,
                'action' => 'check-out',
                'name' => $name,
                'uid' => $uid,
                'plan' => $plan_name,
                'expiry' => date('d-M-Y', strtotime($expire_date)),
                'streak' => $streak,
                'time' => date('h:i A', strtotime($log_time)),
                'message' => 'Goodbye, ' . htmlspecialchars($name) . '! Check-out logged successfully.'
            ]);
            exit();
        } else {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Database error updating check-out.'
            ]);
            exit();
        }
    } else {
        // Already checked out today
        $streak = get_member_streak($con, $uid);
        echo json_encode([
            'success' => true,
            'action' => 'already-logged',
            'name' => $name,
            'uid' => $uid,
            'plan' => $plan_name,
            'expiry' => date('d-M-Y', strtotime($expire_date)),
            'streak' => $streak,
            'time' => date('h:i A', strtotime($att_row['entry_time'])),
            'message' => htmlspecialchars($name) . ' has already checked in and out today.'
        ]);
        exit();
    }
} else {
    // Insert Check-in
    $insert_sql = "INSERT INTO attendance (uid, date, entry_time, exit_time) VALUES ('$uid', '$today_str', '$log_time', NULL)";
    if (mysqli_query($con, $insert_sql)) {
        // Get newly computed streak
        $streak = get_member_streak($con, $uid);
        
        // Check for milestones
        $is_milestone = ($streak > 0 && $streak % 5 === 0);
        
        // Send WhatsApp Check-in Alert
        if (!empty($mobile)) {
            $wa_msg = "🏋️ *Welcome to {$gym_name}!* 🏋️\n\n" .
                       "Hello *{$name}*,\n\n" .
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
            'name' => $name,
            'uid' => $uid,
            'plan' => $plan_name,
            'expiry' => date('d-M-Y', strtotime($expire_date)),
            'streak' => $streak,
            'time' => date('h:i A', strtotime($log_time)),
            'message' => 'Welcome, ' . htmlspecialchars($name) . '! Check-in logged successfully.'
        ]);
        exit();
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database error inserting check-in.'
        ]);
        exit();
    }
}
