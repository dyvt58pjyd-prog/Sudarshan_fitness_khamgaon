<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/include/db_conn.php';

$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

if (!$input || empty($input['descriptor']) || !is_array($input['descriptor'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid biometric payload.']);
    exit();
}

$inputDescriptor = $input['descriptor'];

if (count($inputDescriptor) !== 128) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid face descriptor dimensions.']);
    exit();
}

$requestedRole = isset($input['requested_role']) ? trim($input['requested_role']) : '';

// Helper to compute Euclidean distance between two 128-d vectors
function computeEuclideanDistance($a, $b) {
    if (count($a) !== count($b)) return 999.0;
    $sum = 0.0;
    for ($i = 0; $i < count($a); $i++) {
        $diff = $a[$i] - $b[$i];
        $sum += $diff * $diff;
    }
    return sqrt($sum);
}

$bestMatch = null;
$bestDistance = 1.0;
$threshold = 0.48; // Safe and highly accurate recognition threshold
$matchType = ''; // 'member' or 'admin'

// 1. Search Admin Profiles
$q_admin = mysqli_query($con, "SELECT username, Full_name, role, face_descriptor, webauthn_credential FROM admin WHERE (face_descriptor IS NOT NULL AND face_descriptor != '') OR (webauthn_credential IS NOT NULL AND webauthn_credential != '')");
if ($q_admin && mysqli_num_rows($q_admin) > 0) {
    while ($row = mysqli_fetch_assoc($q_admin)) {
        $raw_desc = !empty($row['face_descriptor']) ? $row['face_descriptor'] : $row['webauthn_credential'];
        $stored = json_decode($raw_desc, true);
        if (is_array($stored) && count($stored) === 128) {
            $dist = computeEuclideanDistance($inputDescriptor, $stored);
            if ($dist < $bestDistance) {
                $bestDistance = $dist;
                $bestMatch = $row;
                $matchType = 'admin';
            }
        }
    }
}

// 2. Search Member Profiles
$q_members = mysqli_query($con, "SELECT userid, username, gender, face_descriptor FROM users WHERE face_descriptor IS NOT NULL AND face_descriptor != ''");
if ($q_members && mysqli_num_rows($q_members) > 0) {
    while ($row = mysqli_fetch_assoc($q_members)) {
        $stored = json_decode($row['face_descriptor'], true);
        if (is_array($stored) && count($stored) === 128) {
            $dist = computeEuclideanDistance($inputDescriptor, $stored);
            if ($dist < $bestDistance) {
                $bestDistance = $dist;
                $bestMatch = $row;
                $matchType = 'member';
            }
        }
    }
}

// Check if a true match was found within threshold
if ($bestMatch && $bestDistance < $threshold) {
    if ($matchType === 'member') {
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
            'member_id' => $bestMatch['userid'],
            'role' => 'member',
            'confidence' => round((1 - $bestDistance) * 100, 1),
            'redirect' => './dashboard/member/'
        ]);
        exit();
    } elseif ($matchType === 'admin') {
        $assignedRole = $bestMatch['role'];
        if ($requestedRole === 'auditor' && in_array($assignedRole, ['owner', 'reception', 'super_admin'])) {
            $assignedRole = 'auditor';
        }

        $_SESSION['user_data']  = $bestMatch['username'];
        $_SESSION['userid']     = $bestMatch['username'];
        $_SESSION['logged']     = "start";
        $_SESSION['role']       = $assignedRole;
        $_SESSION['full_name']  = $bestMatch['Full_name'];
        $_SESSION['username']   = $bestMatch['username'];

        $redirect = ($assignedRole === 'auditor') ? './dashboard/auditor/' : './dashboard/admin/';

        echo json_encode([
            'status' => 'success',
            'message' => 'Administrator Identity Verified!',
            'member_name' => $bestMatch['Full_name'],
            'member_id' => $bestMatch['username'],
            'role' => $assignedRole,
            'confidence' => round((1 - $bestDistance) * 100, 1),
            'redirect' => $redirect
        ]);
        exit();
    }
}

// Strict No-Match Policy: Never log in random strangers!
echo json_encode([
    'status' => 'error',
    'message' => 'Face not recognized in database. Please align your face clearly or log in with your Security PIN.',
    'closest_distance' => ($bestDistance < 1.0) ? round($bestDistance, 3) : null
]);
exit();
