<?php
// PHP API to retrieve the latest 5 attendance check-in/out records
require_once __DIR__ . '/../include/db_conn.php';
page_protect();

header("Content-Type: application/json; charset=UTF-8");
date_default_timezone_set("Asia/Calcutta");

if (!isset($_SESSION['role'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit();
}

$query = "SELECT g.id as log_id, g.timestamp, g.status, g.type, g.error_reason, u.username, u.biometric_id, g.uid 
          FROM biometric_gate_logs g 
          INNER JOIN users u ON g.uid = u.userid 
          ORDER BY g.id DESC 
          LIMIT 5";

$result = mysqli_query($con, $query);
$logs = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $timestamp = strtotime($row['timestamp']);
        $logs[] = [
            'log_id' => intval($row['log_id']),
            'name' => htmlspecialchars($row['username']),
            'biometric_id' => $row['biometric_id'] ? $row['biometric_id'] : 'Auto (' . $row['uid'] . ')',
            'date' => date('Y-m-d', $timestamp),
            'time' => date('H:i:s', $timestamp),
            'type' => $row['type'],
            'status' => $row['status'],
            'error_reason' => $row['error_reason'] ? htmlspecialchars($row['error_reason']) : ''
        ];
    }
}

echo json_encode(['success' => true, 'logs' => $logs]);
exit();
