<?php
/**
 * Sudarshan Fitness Native App REST API - Health Status & History
 * 
 * Endpoint: GET or POST /api/member_health.php or /Files/api/member_health.php
 * GET:
 *   - ?uid=1001 (returns current vitals + past 10 logged records)
 * POST:
 *   - {"uid": "1001", "weight": 72.5, "height": 175}
 */

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../include/db_conn.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $raw_input = file_get_contents('php://input');
    $json = json_decode($raw_input, true);

    $uid = trim($json['uid'] ?? $_POST['uid'] ?? '');
    $weight = floatval($json['weight'] ?? $_POST['weight'] ?? 0);
    $height = floatval($json['height'] ?? $_POST['height'] ?? 0);

    if (empty($uid) || $weight <= 0) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Valid Member UID and Weight are required.'], JSON_PRETTY_PRINT);
        exit();
    }

    $uid_esc = mysqli_real_escape_string($con, $uid);
    $wt_esc = mysqli_real_escape_string($con, strval($weight));
    $ht_esc = mysqli_real_escape_string($con, strval($height));
    $today = date('Y-m-d');

    // Update or insert current health status
    $chk = mysqli_query($con, "SELECT id FROM health_status WHERE uid = '$uid_esc' LIMIT 1");
    if ($chk && mysqli_num_rows($chk) > 0) {
        if ($height > 0) {
            mysqli_query($con, "UPDATE health_status SET weight = '$wt_esc', height = '$ht_esc' WHERE uid = '$uid_esc'");
        } else {
            mysqli_query($con, "UPDATE health_status SET weight = '$wt_esc' WHERE uid = '$uid_esc'");
        }
    } else {
        mysqli_query($con, "INSERT INTO health_status (uid, weight, height) VALUES ('$uid_esc', '$wt_esc', '$ht_esc')");
    }

    // Insert history record
    mysqli_query($con, "INSERT INTO health_history (uid, weight, height, logged_date) VALUES ('$uid_esc', '$wt_esc', '$ht_esc', '$today')");

    // Calculate BMI
    $bmi = null;
    $bmi_cat = 'Unknown';
    if ($weight > 0 && $height > 0) {
        $m = $height / 100;
        $bmi = round($weight / ($m * $m), 1);
        if ($bmi < 18.5) $bmi_cat = 'Underweight';
        elseif ($bmi < 24.9) $bmi_cat = 'Normal weight';
        elseif ($bmi < 29.9) $bmi_cat = 'Overweight';
        else $bmi_cat = 'Obese';
    }

    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'message' => 'Health vitals updated successfully.',
        'data' => [
            'uid' => $uid,
            'weight' => $weight,
            'height' => $height,
            'bmi' => $bmi,
            'bmi_category' => $bmi_cat,
            'logged_date' => $today
        ]
    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit();

} else {
    // GET: fetch health records
    $uid = trim($_GET['uid'] ?? '');
    if (empty($uid)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Member UID is required.'], JSON_PRETTY_PRINT);
        exit();
    }

    $uid_esc = mysqli_real_escape_string($con, $uid);
    $q_cur = mysqli_query($con, "SELECT * FROM health_status WHERE uid = '$uid_esc' LIMIT 1");
    $current = [
        'weight' => null,
        'height' => null,
        'calorie' => null,
        'fat' => null,
        'remarks' => null
    ];
    if ($q_cur && $rc = mysqli_fetch_assoc($q_cur)) {
        $current['weight'] = floatval($rc['weight'] ?? 0);
        $current['height'] = floatval($rc['height'] ?? 0);
        $current['calorie'] = floatval($rc['calorie'] ?? 0);
        $current['fat'] = floatval($rc['fat'] ?? 0);
        $current['remarks'] = $rc['remarks'] ?? null;
    }

    $q_hist = mysqli_query($con, "SELECT weight, height, logged_date FROM health_history WHERE uid = '$uid_esc' ORDER BY id DESC LIMIT 15");
    $history = [];
    if ($q_hist) {
        while ($rh = mysqli_fetch_assoc($q_hist)) {
            $history[] = [
                'weight' => floatval($rh['weight']),
                'height' => floatval($rh['height']),
                'date' => $rh['logged_date']
            ];
        }
    }

    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'code' => 'HEALTH_DATA',
        'data' => [
            'uid' => $uid,
            'current' => $current,
            'history' => $history
        ]
    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit();
}
