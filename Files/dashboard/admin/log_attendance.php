<?php
require '../../include/db_conn.php';
page_protect();

header('Content-Type: application/json');

if (!isset($_POST['uid']) && !isset($_POST['code'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing User ID or Entry Code']);
    exit();
}

if (isset($_POST['code'])) {
    $code = mysqli_real_escape_string($con, $_POST['code']);
    $q_code = "SELECT userid, username, photo FROM users WHERE entry_code = '$code' AND entry_code IS NOT NULL AND entry_code != ''";
    $res_code = mysqli_query($con, $q_code);
    if (!$res_code || mysqli_num_rows($res_code) == 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid Entry Code']);
        exit();
    }
    $user = mysqli_fetch_assoc($res_code);
    $uid = $user['userid'];
} else {
    $uid = mysqli_real_escape_string($con, $_POST['uid']);
    $q_user = "SELECT username, photo FROM users WHERE userid = '$uid'";
    $res_user = mysqli_query($con, $q_user);
    if (!$res_user || mysqli_num_rows($res_user) == 0) {
        echo json_encode(['status' => 'error', 'message' => 'Member not found']);
        exit();
    }
    $user = mysqli_fetch_assoc($res_user);
}

date_default_timezone_set("Asia/Calcutta");
$today_date = date('Y-m-d');
$current_time = date('H:i:s');
$display_time = date('h:i A');
$display_date = date('d-M-Y');
$display_day = date('l');

// Check active plan expiration status
$q_sub = "SELECT e.*, p.planName FROM enrolls_to e INNER JOIN plan p ON e.pid = p.pid WHERE e.uid = '$uid' AND e.renewal = 'yes' ORDER BY e.expire DESC LIMIT 1";
$res_sub = mysqli_query($con, $q_sub);

$is_expired = false;
$expire_date = '';
if ($res_sub && mysqli_num_rows($res_sub) > 0) {
    $sub = mysqli_fetch_assoc($res_sub);
    $expire_date = $sub['expire'];
    if ($expire_date < $today_date) {
        $is_expired = true;
    }
} else {
    $is_expired = true; // No active plan record
}

if ($is_expired) {
    echo json_encode([
        'status' => 'expired',
        'message' => 'Membership Expired! Please renew for entry.',
        'username' => $user['username'],
        'photo' => $user['photo'] ? $user['photo'] : '../../images/logo.png',
        'expire_date' => $expire_date ? date('d-M-Y', strtotime($expire_date)) : 'No Active Plan'
    ]);
    exit();
}

// Check latest attendance log TODAY to determine entry or exit
$q_att = "SELECT * FROM attendance WHERE uid = '$uid' AND date = '$today_date' ORDER BY id DESC LIMIT 1";
$res_att = mysqli_query($con, $q_att);

$type = '';
$record_id = 0;

if (!$res_att || mysqli_num_rows($res_att) == 0) {
    // No logs today -> First entry (Check-in)
    $q_ins = "INSERT INTO attendance (uid, date, entry_time) VALUES ('$uid', '$today_date', '$current_time')";
    if (mysqli_query($con, $q_ins)) {
        $type = 'entry';
        $record_id = mysqli_insert_id($con);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to record entry']);
        exit();
    }
} else {
    $latest = mysqli_fetch_assoc($res_att);
    if (empty($latest['exit_time']) || $latest['exit_time'] === '00:00:00') {
        // Last log today has no exit time -> Check-out (exit)
        $record_id = $latest['id'];
        $q_upd = "UPDATE attendance SET exit_time = '$current_time' WHERE id = $record_id";
        if (mysqli_query($con, $q_upd)) {
            $type = 'exit';
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to record exit']);
            exit();
        }
    } else {
        // Last log today already has an exit time -> New entry (Check-in)
        $q_ins = "INSERT INTO attendance (uid, date, entry_time) VALUES ('$uid', '$today_date', '$current_time')";
        if (mysqli_query($con, $q_ins)) {
            $type = 'entry';
            $record_id = mysqli_insert_id($con);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to record entry']);
            exit();
        }
    }
}

// Fetch final log details
$q_final = "SELECT * FROM attendance WHERE id = $record_id";
$res_final = mysqli_query($con, $q_final);
$final_row = mysqli_fetch_assoc($res_final);

echo json_encode([
    'status' => 'success',
    'type' => $type,
    'username' => $user['username'],
    'photo' => $user['photo'] ? $user['photo'] : '../../images/logo.png',
    'entry_time' => $final_row['entry_time'] ? date('h:i A', strtotime($final_row['entry_time'])) : '--',
    'exit_time' => $final_row['exit_time'] ? date('h:i A', strtotime($final_row['exit_time'])) : '--',
    'date' => $display_date,
    'day' => $display_day
]);
exit();
?>
