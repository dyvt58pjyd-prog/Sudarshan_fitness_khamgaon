<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");

require '../include/db_conn.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit();
}

if (empty($_GET['u'])) {
    echo json_encode(['success' => false, 'error' => 'Username required.']);
    exit();
}

$user = mysqli_real_escape_string($con, $_GET['u']);

$query = "SELECT webauthn_credential FROM admin WHERE username='$user' AND role IN ('owner', 'super_admin') LIMIT 1";
$result = mysqli_query($con, $query);

if (mysqli_num_rows($result) === 1) {
    $row = mysqli_fetch_assoc($result);
    if (!empty($row['webauthn_credential'])) {
        $cred = json_decode($row['webauthn_credential'], true);
        if ($cred && !empty($cred['id'])) {
            echo json_encode(['success' => true, 'credentialId' => $cred['id']]);
            exit();
        }
    }
}

echo json_encode(['success' => false, 'error' => 'No Face ID setup for this user.']);
?>
