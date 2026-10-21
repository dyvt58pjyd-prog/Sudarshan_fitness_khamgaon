<?php
/**
 * Sudarshan Fitness Native App REST API - Member Authentication
 * 
 * Endpoint: POST /api/member_login.php or /Files/api/member_login.php
 * Content-Type: application/json or application/x-www-form-urlencoded
 */

// Enable CORS for mobile apps
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../include/db_conn.php';

// Accept JSON payload or standard POST
$raw_input = file_get_contents('php://input');
$json_data = json_decode($raw_input, true);

$identifier = '';
$mobile = '';
$password = '';

if (is_array($json_data)) {
    $identifier = trim($json_data['identifier'] ?? $json_data['userid'] ?? $json_data['username'] ?? '');
    $mobile     = trim($json_data['mobile'] ?? $json_data['phone'] ?? '');
    $password   = trim($json_data['password'] ?? $json_data['pass_key'] ?? '');
} else {
    $identifier = trim($_POST['identifier'] ?? $_POST['userid'] ?? $_POST['username'] ?? '');
    $mobile     = trim($_POST['mobile'] ?? $_POST['phone'] ?? '');
    $password   = trim($_POST['password'] ?? $_POST['pass_key'] ?? '');
}

if (empty($identifier) && empty($mobile)) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'code' => 'MISSING_CREDENTIALS',
        'message' => 'Please provide your Member ID or registered Mobile Number.'
    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit();
}

// Clean inputs
$id_esc = mysqli_real_escape_string($con, $identifier);
$mobile_clean = preg_replace('/\D/', '', !empty($mobile) ? $mobile : $identifier);
if (strlen($mobile_clean) === 12 && substr($mobile_clean, 0, 2) === '91') {
    $mobile_clean = substr($mobile_clean, 2);
}
$mob_esc = mysqli_real_escape_string($con, $mobile_clean);

// 1. Flexible lookup: match by (userid AND mobile) OR userid OR mobile
$user = null;
if (!empty($identifier) && !empty($mobile)) {
    $q = mysqli_query($con, "SELECT * FROM users WHERE (userid='$id_esc' OR mobile='$id_esc' OR mobile='$mob_esc') AND (mobile='$mob_esc' OR userid='$mob_esc' OR userid='$id_esc') LIMIT 1");
    if ($q && mysqli_num_rows($q) > 0) {
        $user = mysqli_fetch_assoc($q);
    }
}

if (!$user && !empty($identifier)) {
    $q = mysqli_query($con, "SELECT * FROM users WHERE userid='$id_esc' OR mobile='$id_esc' OR mobile='$mob_esc' LIMIT 1");
    if ($q && mysqli_num_rows($q) > 0) {
        $user = mysqli_fetch_assoc($q);
    }
}

if (!$user && !empty($mob_esc)) {
    $q = mysqli_query($con, "SELECT * FROM users WHERE mobile='$mob_esc' OR mobile LIKE '%$mob_esc' LIMIT 1");
    if ($q && mysqli_num_rows($q) > 0) {
        $user = mysqli_fetch_assoc($q);
    }
}

if (!$user) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'code' => 'INVALID_CREDENTIALS',
        'message' => 'Invalid Member ID or Mobile Number. Please check your credentials or contact gym reception.'
    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit();
}

// Check for couple/partner profile
$is_couple = false;
$profiles = [];
$uid = $user['userid'];

$proto = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'sudarshanfitness.de';
$base_url = rtrim($proto . $host, '/');

// Member 1 photo
$photo_url = '';
if (!empty($user['photo_base64']) && strpos($user['photo_base64'], 'data:image') === 0) {
    $photo_url = $user['photo_base64'];
} elseif (!empty($user['photo'])) {
    $clean_p = ltrim(str_replace('../', '', $user['photo']), '/');
    $photo_url = $base_url . '/' . $clean_p;
}

$profiles[] = [
    'userid' => $user['userid'],
    'username' => $user['username'],
    'mobile' => $user['mobile'],
    'gender' => $user['gender'],
    'photo_url' => $photo_url,
    'gym_rank' => $user['gym_rank'] ?? 'Beginner',
    'xp_points' => intval($user['xp_points'] ?? 0)
];

// Check Partner
$partner_uid = $user['partner_uid'] ?? '';
if (!empty($partner_uid)) {
    $qp = mysqli_query($con, "SELECT * FROM users WHERE userid='$partner_uid' LIMIT 1");
    if ($qp && $p_user = mysqli_fetch_assoc($qp)) {
        $is_couple = true;
        $p_photo_url = '';
        if (!empty($p_user['photo_base64']) && strpos($p_user['photo_base64'], 'data:image') === 0) {
            $p_photo_url = $p_user['photo_base64'];
        } elseif (!empty($p_user['photo'])) {
            $p_clean_p = ltrim(str_replace('../', '', $p_user['photo']), '/');
            $p_photo_url = $base_url . '/' . $p_clean_p;
        }
        $profiles[] = [
            'userid' => $p_user['userid'],
            'username' => $p_user['username'],
            'mobile' => $p_user['mobile'],
            'gender' => $p_user['gender'],
            'photo_url' => $p_photo_url,
            'gym_rank' => $p_user['gym_rank'] ?? 'Beginner',
            'xp_points' => intval($p_user['xp_points'] ?? 0)
        ];
    }
}

// Generate Auth Token
$token_payload = [
    'uid' => $user['userid'],
    'time' => time(),
    'hash' => hash('sha256', $user['userid'] . ':' . $user['mobile'] . ':SF_NATIVE_2026')
];
$auth_token = base64_encode(json_encode($token_payload));

http_response_code(200);
echo json_encode([
    'status' => 'success',
    'code' => 'LOGIN_SUCCESS',
    'message' => 'Login successful',
    'data' => [
        'token' => $auth_token,
        'is_couple' => $is_couple,
        'selected_member' => $profiles[0],
        'profiles' => $profiles
    ]
], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
exit();
