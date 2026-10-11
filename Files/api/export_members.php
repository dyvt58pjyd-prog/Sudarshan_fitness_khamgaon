<?php
/**
 * Sudarshan Fitness - Member Database Export API
 * 
 * Secure endpoint to synchronize member database and subscription expiry dates
 * with on-premise Windows software / Kiosk attendance system.
 * 
 * Endpoint URL: https://sudarshanfitness.de/api/export_members.php?key=sudarshan_fitness_secret_key
 */

// Allow cross-origin requests from local Windows sync agent
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-API-KEY");

// Handle CORS Preflight
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// -------------------------------------------------------------
// Database Configuration & Security Key (Lines 27–32)
// -------------------------------------------------------------
$dbHost = 'localhost';                         // Keep as 'localhost'
$dbUser = 'u252324937_titan';                  // Your cPanel/Hostinger database username
$dbPass = 'Nikita@268724';                    // Your cPanel/Hostinger database password
$dbName = 'u252324937_titan';                  // Your gym database name (or 'titangym' on local)
$expectedKey = 'sudarshan_fitness_secret_key'; // Security secret key
// -------------------------------------------------------------

// Security Verification: Verify Secret Key
$providedKey = '';
if (isset($_GET['key'])) {
    $providedKey = trim($_GET['key']);
} elseif (isset($_POST['key'])) {
    $providedKey = trim($_POST['key']);
} elseif (isset($_SERVER['HTTP_X_API_KEY'])) {
    $providedKey = trim($_SERVER['HTTP_X_API_KEY']);
} elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $auth = trim($_SERVER['HTTP_AUTHORIZATION']);
    if (stripos($auth, 'Bearer ') === 0) {
        $providedKey = trim(substr($auth, 7));
    }
}

if ($providedKey !== $expectedKey) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized: Invalid or missing API security key.',
        'hint' => 'Provide the key as ?key=sudarshan_fitness_secret_key in the URL parameter.'
    ], JSON_PRETTY_PRINT);
    exit;
}

// Establish Database Connection (Auto-detects db_conn.php if available)
$con = null;

$db_conn_candidates = [
    __DIR__ . '/../include/db_conn.php',
    __DIR__ . '/include/db_conn.php',
    __DIR__ . '/../../Files/include/db_conn.php',
    __DIR__ . '/../Files/include/db_conn.php'
];

foreach ($db_conn_candidates as $candidate) {
    if (file_exists($candidate)) {
        ob_start();
        require_once $candidate;
        ob_end_clean();
        if (isset($con) && $con instanceof mysqli && !mysqli_connect_errno()) {
            break;
        }
    }
}

// Fallback to direct credentials if db_conn.php didn't initialize connection
if (!$con || !($con instanceof mysqli) || mysqli_connect_errno()) {
    mysqli_report(MYSQLI_REPORT_OFF);
    $try_dbs = [$dbName, 'titangym', 'u252324937_titan'];
    $last_err = '';
    foreach ($try_dbs as $test_db) {
        try {
            $con = @mysqli_connect($dbHost, $dbUser, $dbPass, $test_db);
            if ($con && !mysqli_connect_errno()) {
                break;
            }
        } catch (Throwable $e) {
            $last_err = $e->getMessage();
            $con = false;
        }
    }
}

if (!$con || mysqli_connect_errno()) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database connection failed: ' . (!empty($last_err) ? $last_err : mysqli_connect_error())
    ], JSON_PRETTY_PRINT);
    exit;
}

// Set Indian Standard Time
date_default_timezone_set("Asia/Calcutta");
$today = date('Y-m-d');

// Fetch Members and their Latest Plan/Expiry Date
$query = "SELECT 
            u.userid,
            u.username,
            u.mobile,
            u.email,
            u.entry_code,
            u.biometric_id,
            e.expire,
            e.renewal,
            p.planName
          FROM users u
          LEFT JOIN (
              SELECT e1.uid, e1.pid, e1.expire, e1.renewal
              FROM enrolls_to e1
              INNER JOIN (
                  SELECT uid, MAX(et_id) as max_et_id
                  FROM enrolls_to
                  GROUP BY uid
              ) e2 ON e1.et_id = e2.max_et_id
          ) e ON u.userid = e.uid
          LEFT JOIN plan p ON e.pid = p.pid
          WHERE u.userid != 'PENDING'
          ORDER BY CAST(u.userid AS UNSIGNED) ASC, u.username ASC";

$result = mysqli_query($con, $query);

if (!$result) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Query execution failed: ' . mysqli_error($con)
    ], JSON_PRETTY_PRINT);
    exit;
}

$members = [];

while ($row = mysqli_fetch_assoc($result)) {
    // Determine member PIN: prefer biometric_id or entry_code, fallback to userid
    $member_pin = 0;
    if (!empty($row['biometric_id']) && is_numeric($row['biometric_id'])) {
        $member_pin = intval($row['biometric_id']);
    } elseif (!empty($row['entry_code']) && is_numeric($row['entry_code'])) {
        $member_pin = intval($row['entry_code']);
    } elseif (!empty($row['userid']) && is_numeric($row['userid'])) {
        $member_pin = intval($row['userid']);
    } else {
        $member_pin = $row['userid'];
    }

    $expiry_date = !empty($row['expire']) ? $row['expire'] : null;
    $plan_name = !empty($row['planName']) ? $row['planName'] : 'No Active Plan';
    $is_active = false;

    if ($expiry_date) {
        $is_active = (strtotime($expiry_date) >= strtotime($today));
    }

    $members[] = [
        'member_pin'        => $member_pin,
        'full_name'         => $row['username'],
        'expiry_date'       => $expiry_date,
        'membership_plan'   => $plan_name,
        'phone'             => !empty($row['mobile']) ? $row['mobile'] : '',
        'membership_active' => $is_active,
        // Extra identifiers for complete cross-compatibility
        'userid'            => $row['userid'],
        'entry_code'        => $row['entry_code'] ?? '',
        'biometric_id'      => $row['biometric_id'] ?? null
    ];
}

// Return formatted JSON matching the Windows sync software expectations
$response = [
    'success'       => true,
    'total_members' => count($members),
    'exported_at'   => date('Y-m-d H:i:s'),
    'data'          => $members
];

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
