<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../include/db_conn.php';

date_default_timezone_set("Asia/Calcutta");

$raw_input = file_get_contents('php://input');
$input = json_decode($raw_input, true);
if (!is_array($input)) {
    $input = $_POST;
}

$identifier  = isset($input['identifier']) ? trim($input['identifier']) : '';
$scan_source = isset($input['source']) ? trim((string)$input['source']) : 'manual';

$resolved = resolve_checkin_identifier($con, $identifier, $scan_source);
if (empty($resolved['ok'])) {
    http_response_code($resolved['code'] ?? 403);
    unset($resolved['ok'], $resolved['code']);
    $resolved['success'] = false;
    echo json_encode($resolved);
    exit();
}

$verified_qr = !empty($resolved['verified_qr']);
if (!empty($resolved['uid'])) {
    $uid_esc = mysqli_real_escape_string($con, $resolved['uid']);
    $q_user = mysqli_query($con, "SELECT * FROM users WHERE userid = '$uid_esc' LIMIT 1");
} else {
    $lookup_esc = mysqli_real_escape_string($con, $resolved['lookup']);
    $q_user = mysqli_query($con, "SELECT * FROM users WHERE userid = '$lookup_esc' OR mobile = '$lookup_esc' LIMIT 1");
}

if (!$q_user || mysqli_num_rows($q_user) === 0) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'message' => 'Gym member not found with ID or Mobile: ' . htmlspecialchars($identifier)
    ]);
    exit();
}

$user = mysqli_fetch_assoc($q_user);
$res = process_member_attendance_checkin($con, $user, [
    'verified_qr' => $verified_qr,
    'channel'     => ($scan_source === 'qr' ? 'qr_kiosk' : 'kiosk')
]);

$http_code = $res['http_code'] ?? 200;
unset($res['http_code']);
http_response_code($http_code);
echo json_encode($res);
