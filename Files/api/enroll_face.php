<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../include/db_conn.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit();
}

$raw = file_get_contents('php://input');
$input = json_decode($raw, true);

if (!$input || empty($input['descriptor']) || !is_array($input['descriptor'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid face descriptor payload.']);
    exit();
}

$descriptor = $input['descriptor'];
if (count($descriptor) !== 128) {
    echo json_encode(['success' => false, 'error' => 'Invalid biometric vector length (must be 128-d).']);
    exit();
}

// Clean descriptor array: ensure all 128 elements are floats
$clean_descriptor = [];
foreach ($descriptor as $val) {
    $clean_descriptor[] = round(floatval($val), 6);
}
$descriptor_json = mysqli_real_escape_string($con, json_encode($clean_descriptor));

$target_table = null;
$target_id = null;
$id_col = null;

// Scenario 1: Active authenticated session
if (isset($_SESSION['user_data']) && !empty($_SESSION['user_data'])) {
    $target_id = mysqli_real_escape_string($con, $_SESSION['user_data']);
    $role = $_SESSION['role'] ?? 'member';

    if ($role === 'member') {
        $target_table = 'users';
        $id_col = 'userid';
    } else {
        $target_table = 'admin';
        $id_col = 'username';
    }
}
// Scenario 2: WebAuthn enrollment session challenge (from enroll_face_id.php)
elseif (isset($_SESSION['webauthn_enroll_user']) && !empty($_SESSION['webauthn_enroll_user'])) {
    $target_id = mysqli_real_escape_string($con, $_SESSION['webauthn_enroll_user']);
    $target_table = 'admin';
    $id_col = 'username';
}
// Scenario 3: Explicit token passed in request
elseif (!empty($input['u']) && !empty($input['token'])) {
    $user = mysqli_real_escape_string($con, trim($input['u']));
    $token = trim($input['token']);

    // Check admin
    $q_adm = mysqli_query($con, "SELECT username, securekey FROM admin WHERE username='$user'");
    if ($q_adm && mysqli_num_rows($q_adm) === 1) {
        $row = mysqli_fetch_assoc($q_adm);
        if (hash_equals(hash('sha256', $user . $row['securekey']), $token)) {
            $target_table = 'admin';
            $id_col = 'username';
            $target_id = $user;
        }
    }
    
    // Check users
    if (!$target_table) {
        $q_usr = mysqli_query($con, "SELECT userid, password FROM users WHERE userid='$user'");
        if ($q_usr && mysqli_num_rows($q_usr) === 1) {
            $row = mysqli_fetch_assoc($q_usr);
            if (hash_equals(hash('sha256', $user . $row['password']), $token)) {
                $target_table = 'users';
                $id_col = 'userid';
                $target_id = $user;
            }
        }
    }
}

if (!$target_table || !$target_id) {
    echo json_encode(['success' => false, 'error' => 'Authentication required to enroll Face ID. Please log in first.']);
    exit();
}

$now = date('Y-m-d H:i:s');
$update_sql = "UPDATE $target_table SET face_descriptor = '$descriptor_json', face_enrolled_at = '$now' WHERE $id_col = '$target_id'";

if ($target_table === 'admin') {
    // Keep webauthn_credential in sync for legacy compatibility
    $update_sql = "UPDATE admin SET face_descriptor = '$descriptor_json', webauthn_credential = '$descriptor_json', face_enrolled_at = '$now' WHERE username = '$target_id'";
}

if (mysqli_query($con, $update_sql)) {
    unset($_SESSION['webauthn_enroll_user']);
    unset($_SESSION['webauthn_enroll_challenge']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Face ID biometric profile enrolled successfully!',
        'enrolled_user' => $target_id
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . mysqli_error($con)]);
}
