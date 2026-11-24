<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../include/db_conn.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit();
}

$raw_input = file_get_contents('php://input');
$input = json_decode($raw_input, true);
if (!is_array($input)) {
    $input = $_POST;
}

$req_uid = $input['uid'] ?? '';
$sig     = $input['sig'] ?? '';
$exp     = $input['exp'] ?? 0;

$auth = authorize_wallet_pass_request($con, $req_uid, $sig, $exp);
if (!$auth['authorized']) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized: Please sign in or use a signed Member Pass.'
    ]);
    exit();
}

$uid_esc = mysqli_real_escape_string($con, $auth['uid']);
$q_user = mysqli_query($con, "SELECT * FROM users WHERE userid = '$uid_esc' LIMIT 1");
if (!$q_user || mysqli_num_rows($q_user) === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Member profile not found.']);
    exit();
}

$user = mysqli_fetch_assoc($q_user);
$res = process_member_attendance_checkin($con, $user, [
    'cooldown_minutes' => 5,
    'verified_qr'      => true,
    'channel'          => 'pwa'
]);

$http_code = $res['http_code'] ?? 200;
unset($res['http_code']);
http_response_code($http_code);
echo json_encode($res);
