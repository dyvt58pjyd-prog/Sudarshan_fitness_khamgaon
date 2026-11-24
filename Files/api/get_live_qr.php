<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

require_once __DIR__ . '/../include/db_conn.php';

$req_uid = $_GET['uid'] ?? '';
$sig     = $_GET['sig'] ?? '';
$exp     = $_GET['exp'] ?? 0;

$auth = authorize_wallet_pass_request($con, $req_uid, $sig, $exp);
if (!$auth['authorized']) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error'   => 'Unauthorized pass token request.'
    ]);
    exit();
}

$uid_raw = $auth['uid'];
$uid_esc = mysqli_real_escape_string($con, $uid_raw);

date_default_timezone_set("Asia/Calcutta");
$today_str = date('Y-m-d');
$att_state = 'none';
$entry_time = null;
$exit_time = null;

$q_att = mysqli_query($con, "SELECT entry_time, exit_time FROM attendance WHERE uid = '$uid_esc' AND date = '$today_str' ORDER BY id DESC LIMIT 1");
if ($q_att && mysqli_num_rows($q_att) > 0) {
    $row = mysqli_fetch_assoc($q_att);
    $entry_time = $row['entry_time'] ? date('h:i A', strtotime($row['entry_time'])) : null;
    if (empty($row['exit_time']) || $row['exit_time'] === '00:00:00') {
        $att_state = 'checked_in';
    } else {
        $att_state = 'completed';
        $exit_time = date('h:i A', strtotime($row['exit_time']));
    }
}

echo json_encode([
    'success'     => true,
    'uid'         => $uid_raw,
    'token'       => generate_rotating_qr_payload($con, $uid_raw),
    'expires_in'  => get_rotating_qr_remaining_seconds(),
    'att_state'   => $att_state,
    'entry_time'  => $entry_time,
    'exit_time'   => $exit_time
]);
