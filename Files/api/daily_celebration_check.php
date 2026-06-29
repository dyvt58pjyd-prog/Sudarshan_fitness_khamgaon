<?php
// Daily Automated Birthday & Anniversary Greetings Script
// Can be run via CLI/cron, or triggered by admin dashboard load

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
$lock_file = __DIR__ . '/../include/last_celebration_check.txt';
$last_check = file_exists($lock_file) ? trim(@file_get_contents($lock_file)) : '';

$force = isset($_GET['force']) && $_GET['force'] === '1';

if ($last_check === $today_str && !$force) {
    if ($is_direct) {
        echo json_encode([
            'success' => true,
            'message' => 'Celebration checks already run today.'
        ]);
        exit();
    }
    return;
}

$gym = get_gym_details($con);
$gym_name = $gym['gym_name'];
$today_md = date('m-d');
$birthday_sent = 0;
$anniversary_sent = 0;

// 1. Query birthdays today
$q_bday = mysqli_query($con, "
    SELECT username, mobile, dob 
    FROM users 
    WHERE DATE_FORMAT(dob, '%m-%d') = '$today_md' 
      AND mobile IS NOT NULL 
      AND mobile != ''
");

if ($q_bday && mysqli_num_rows($q_bday) > 0) {
    while ($row = mysqli_fetch_assoc($q_bday)) {
        $name = $row['username'];
        $mobile = $row['mobile'];
        
        $message = "🏋️ *Happy Birthday from {$gym_name}!* 🎂\n\n" .
                   "Dear *{$name}*,\n\n" .
                   "On behalf of the entire *{$gym_name}* team, we wish you a fantastic birthday! May this year bring you closer to all your fitness and life goals. Keep crushing it! 💪\n\n" .
                   "Enjoy your special day!\n" .
                   "*{$gym_name}*";
        
        enqueue_whatsapp_message($con, $mobile, $message);
        $birthday_sent++;
    }
}

// 2. Query gym anniversaries today
$q_ann = mysqli_query($con, "
    SELECT username, mobile, joining_date, (YEAR(CURRENT_DATE()) - YEAR(joining_date)) as years 
    FROM users 
    WHERE DATE_FORMAT(joining_date, '%m-%d') = '$today_md' 
      AND YEAR(joining_date) < YEAR(CURRENT_DATE())
      AND mobile IS NOT NULL 
      AND mobile != ''
");

if ($q_ann && mysqli_num_rows($q_ann) > 0) {
    while ($row = mysqli_fetch_assoc($q_ann)) {
        $name = $row['username'];
        $mobile = $row['mobile'];
        $years = intval($row['years']);
        
        $year_word = ($years === 1) ? "year" : "years";
        $message = "🎉 *Happy Gym Anniversary!* 🏋️\n\n" .
                   "Dear *{$name}*,\n\n" .
                   "Congratulations on completing *{$years} {$year_word}* since you joined *{$gym_name}*!\n\n" .
                   "Thank you for choosing us as your fitness home and for your amazing consistency and dedication. Keep grinding and inspiring everyone around you! 🏆\n\n" .
                   "Best regards,\n" .
                   "*{$gym_name}*";
        
        enqueue_whatsapp_message($con, $mobile, $message);
        $anniversary_sent++;
    }
}

// Write lock file
@file_put_contents($lock_file, $today_str);

if ($is_direct) {
    echo json_encode([
        'success' => true,
        'message' => "Celebration check complete.",
        'birthdays_queued' => $birthday_sent,
        'anniversaries_queued' => $anniversary_sent
    ]);
    exit();
}
?>
