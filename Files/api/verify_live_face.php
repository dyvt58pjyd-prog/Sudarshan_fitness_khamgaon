<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");

require '../include/db_conn.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || empty($input['descriptor']) || !is_array($input['descriptor'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid face descriptor.']);
    exit();
}

$liveDescriptor = $input['descriptor'];

if (count($liveDescriptor) !== 128) {
    echo json_encode(['success' => false, 'error' => 'Invalid face descriptor length.']);
    exit();
}

// Function to calculate Euclidean distance between two 128-d arrays
function euclideanDistance($arr1, $arr2) {
    if (count($arr1) !== count($arr2)) return 999;
    $sum = 0;
    for ($i = 0; $i < count($arr1); $i++) {
        $sum += pow($arr1[$i] - $arr2[$i], 2);
    }
    return sqrt($sum);
}

$requestedRole = isset($input['requested_role']) ? $input['requested_role'] : null;

// Fetch all registered faces for roles that support Face ID
$query = "SELECT username, Full_name, role, webauthn_credential FROM admin WHERE role IN ('owner', 'super_admin', 'reception', 'trainer') AND webauthn_credential IS NOT NULL AND webauthn_credential != ''";
$result = mysqli_query($con, $query);

$bestMatch = null;
$bestDistance = 0.55; // Relaxed threshold for facial recognition (0.55 is recommended for ssdMobilenetv1 in varying lighting)

while ($row = mysqli_fetch_assoc($result)) {
    $storedDescriptor = json_decode($row['webauthn_credential'], true);
    
    if (is_array($storedDescriptor) && count($storedDescriptor) === 128) {
        $distance = euclideanDistance($liveDescriptor, $storedDescriptor);
        
        if ($distance < $bestDistance) {
            $bestDistance = $distance;
            $bestMatch = $row;
        }
    }
}

if ($bestMatch) {
    $assignedRole = $bestMatch['role'];
    
    // Cross-role logic: If they requested auditor, verify they are allowed
    if ($requestedRole === 'auditor') {
        if (in_array($bestMatch['role'], ['owner', 'reception', 'super_admin'])) {
            $assignedRole = 'auditor';
        } else {
            echo json_encode(['success' => false, 'error' => 'Your role is not authorized to access the Auditor dashboard.']);
            exit();
        }
    } else if ($requestedRole && $requestedRole !== $assignedRole) {
        // Enforce that they must be logging into their own role, unless handled above
        echo json_encode(['success' => false, 'error' => 'Face verified, but you are not authorized to login as ' . htmlspecialchars($requestedRole) . '.']);
        exit();
    }

    // Authenticate the user
    $_SESSION['user_data']  = $bestMatch['username'];
    $_SESSION['logged']     = "start";
    $_SESSION['role']       = $assignedRole;
    $_SESSION['full_name']  = $bestMatch['Full_name'];
    $_SESSION['username']   = $bestMatch['username'];

    echo json_encode([
        'success' => true,
        'username' => $bestMatch['username'],
        'distance' => $bestDistance
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Face not recognized in the system.']);
}
?>
