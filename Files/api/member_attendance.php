<?php
/**
 * Sudarshan Fitness Native App REST API - Member Attendance History
 * 
 * Endpoint: GET /api/member_attendance.php or /Files/api/member_attendance.php
 * Accepts:
 *   - ?uid=1001 (required)
 *   - ?month=YYYY-MM (optional, default current month)
 */

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../include/db_conn.php';

$uid = trim($_GET['uid'] ?? '');
if (empty($uid)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Member UID is required.'], JSON_PRETTY_PRINT);
    exit();
}

$month = trim($_GET['month'] ?? date('Y-m'));
if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
    $month = date('Y-m');
}

$uid_esc = mysqli_real_escape_string($con, $uid);
$month_esc = mysqli_real_escape_string($con, $month);

$q = mysqli_query($con, "
    SELECT date, time 
    FROM attendance 
    WHERE uid = '$uid_esc' AND date LIKE '$month_esc%' 
    ORDER BY date DESC, time DESC
");

$records = [];
$unique_dates = [];
if ($q) {
    while ($r = mysqli_fetch_assoc($q)) {
        $d = $r['date'];
        $unique_dates[$d] = true;
        $records[] = [
            'date' => $d,
            'time' => $r['time'],
            'day_name' => date('l', strtotime($d)),
            'formatted_date' => date('d M Y', strtotime($d))
        ];
    }
}

// Attendance Streak
$streak = function_exists('get_member_streak') ? get_member_streak($con, $uid) : 0;

http_response_code(200);
echo json_encode([
    'status' => 'success',
    'code' => 'ATTENDANCE_DATA',
    'data' => [
        'uid' => $uid,
        'month' => $month,
        'streak_days' => $streak,
        'days_attended_in_month' => count($unique_dates),
        'checkins_count' => count($records),
        'records' => $records
    ]
], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
exit();
