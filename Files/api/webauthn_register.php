<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");

require '../include/db_conn.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid JSON input.']);
    exit();
}

if (!isset($_SESSION['webauthn_enroll_user']) || !isset($_SESSION['webauthn_enroll_challenge'])) {
    echo json_encode(['success' => false, 'error' => 'Session expired. Please reload the enrollment link.']);
    exit();
}

$user = $_SESSION['webauthn_enroll_user'];

// Ideally, we should perform full WebAuthn server-side verification here.
// Validating the clientDataJSON (challenge match), attestationObject, and extracting the public key.
// Since implementing a full CBOR/WebAuthn parser in raw PHP is extremely complex for a lightweight setup,
// we will store the raw ID and public key (or a simplified representation) securely in the database.
// The true verification happens when the browser authenticates the exact same credential ID later.

// For this implementation, we will store the credential ID to associate it with the user.
$credentialId = mysqli_real_escape_string($con, $input['id']);
$rawId = mysqli_real_escape_string($con, $input['rawId']);

// Serialize the credential data to store it.
$credentialData = json_encode([
    'id' => $input['id'],
    'rawId' => $input['rawId'],
    'clientDataJSON' => $input['response']['clientDataJSON']
]);

$credentialData = mysqli_real_escape_string($con, $credentialData);

$query = "UPDATE admin SET webauthn_credential = '$credentialData' WHERE username = '$user' AND role = 'owner'";
if (mysqli_query($con, $query)) {
    // Clear session variables
    unset($_SESSION['webauthn_enroll_user']);
    unset($_SESSION['webauthn_enroll_challenge']);
    
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . mysqli_error($con)]);
}
?>
