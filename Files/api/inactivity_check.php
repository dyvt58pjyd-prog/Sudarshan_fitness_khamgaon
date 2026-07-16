<?php
// Daily Automated Inactivity Re-engagement Script
// Can be run via CLI/cron, or triggered by admin login

$is_cli = (php_sapi_name() === 'cli');
$is_direct = (realpath($_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__));

if ($is_direct) {
    if (!$is_cli) {
        require_once __DIR__ . '/../include/db_conn.php';
        page_protect();
        
        // Only administrators, owners, or receptionists can run this manually
        if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['super_admin', 'owner', 'reception'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
            exit();
        }
    } else {
        $_SERVER['SERVER_NAME'] = 'localhost';
        require_once __DIR__ . '/../include/db_conn.php';
    }
    header("Content-Type: application/json; charset=UTF-8");
}

date_default_timezone_set("Asia/Calcutta");

$today_str = date('Y-m-d');
$lock_file = __DIR__ . '/../include/last_inactivity_check.txt';
$last_check = file_exists($lock_file) ? trim(@file_get_contents($lock_file)) : '';

$force = isset($_GET['force']) && $_GET['force'] === '1';

if ($last_check === $today_str && !$force) {
    if ($is_direct) {
        echo json_encode([
            'success' => true,
            'message' => 'Inactivity check already run today.'
        ]);
        exit();
    }
    return;
}

$gym = get_gym_details($con);
$gym_name = $gym['gym_name'];

// Find members whose last check-in date was exactly 5 days ago and who have not checked in since
$q = "SELECT u.userid, u.username, u.mobile, MAX(a.date) as last_checkin
      FROM users u
      INNER JOIN attendance a ON u.userid = a.uid
      GROUP BY u.userid, u.username, u.mobile
      HAVING last_checkin = DATE_SUB(CURRENT_DATE(), INTERVAL 5 DAY)";

$res = mysqli_query($con, $q);
$sent_count = 0;

if ($res && mysqli_num_rows($res) > 0) {
    while ($row = mysqli_fetch_assoc($res)) {
        $mobile = $row['mobile'];
        $name = $row['username'];
        
        if (!empty($mobile)) {
            $wa_mobile = preg_replace('/[^0-9]/', '', $mobile);
            if (strlen($wa_mobile) === 10) {
                $wa_mobile = '91' . $wa_mobile;
            }
            
            $message = "👋 Hey *{$name}*,\n\nWe missed you at the gym! Consistency is key to reaching your fitness goals. See you tomorrow? 🏋️\n\nThank you,\n*{$gym_name}*";
            
            enqueue_whatsapp_message($con, $wa_mobile, $message);
            $sent_count++;
        }
    }
}

// Write today's date to lock file
@file_put_contents($lock_file, $today_str);

if ($is_direct) {
    echo json_encode([
        'success' => true,
        'message' => "Inactivity check complete. Sent {$sent_count} re-engagement alerts.",
        'sent_alerts' => $sent_count
    ]);
    exit();
}
?>
