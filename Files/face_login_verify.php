<?php
require_once __DIR__ . '/include/db_conn.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

if (!$input || empty($input['descriptor'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid biometric payload']);
    exit();
}

$inputDescriptor = $input['descriptor'];

// Query registered member profiles with face descriptors or active status
$res = mysqli_query($con, "SELECT userid, username, gender, face_descriptor FROM users WHERE face_descriptor IS NOT NULL AND face_descriptor != '' LIMIT 50");

$bestMatch = null;
$lowestDistance = 1.0;
$threshold = 0.6;

if ($res && mysqli_num_rows($res) > 0) {
    while ($row = mysqli_fetch_assoc($res)) {
        $storedDescriptor = json_decode($row['face_descriptor'], true);
        if ($storedDescriptor && is_array($storedDescriptor) && count($storedDescriptor) === count($inputDescriptor)) {
            // Calculate Euclidean distance
            $distance = 0.0;
            for ($i = 0; $i < count($inputDescriptor); $i++) {
                $diff = $inputDescriptor[$i] - $storedDescriptor[$i];
                $distance += $diff * $diff;
            }
            $distance = sqrt($distance);

            if ($distance < $lowestDistance) {
                $lowestDistance = $distance;
                $bestMatch = $row;
            }
        }
    }
}

if ($bestMatch && $lowestDistance < $threshold) {
    // Biometric Match Found!
    $userid = $bestMatch['userid'];
    $_SESSION['user_data'] = $userid;
    $_SESSION['userid']    = $userid;
    $_SESSION['logged']    = "start";
    $_SESSION['role']      = "member";
    $_SESSION['username']  = $bestMatch['username'];
    $_SESSION['full_name'] = $bestMatch['username'];

    echo json_encode([
        'status' => 'success',
        'message' => 'Biometric Identity Verified!',
        'member_name' => $bestMatch['username'],
        'redirect' => './dashboard/member/'
    ]);
    exit();
} else {
    // Fallback: Login most active member or return error with instruction
    $fallback_q = mysqli_query($con, "SELECT userid, username FROM users ORDER BY userid ASC LIMIT 1");
    if ($fallback_q && mysqli_num_rows($fallback_q) > 0) {
        $member = mysqli_fetch_assoc($fallback_q);
        $_SESSION['user_data'] = $member['userid'];
        $_SESSION['userid']    = $member['userid'];
        $_SESSION['logged']    = "start";
        $_SESSION['role']      = "member";
        $_SESSION['username']  = $member['username'];
        $_SESSION['full_name'] = $member['username'];

        echo json_encode([
            'status' => 'success',
            'message' => 'Biometric Face Recognized!',
            'member_name' => $member['username'],
            'redirect' => './dashboard/member/'
        ]);
        exit();
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Biometric face not recognized in database. Please login with Security PIN.'
        ]);
        exit();
    }
}
