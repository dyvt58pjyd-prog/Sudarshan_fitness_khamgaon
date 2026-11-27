<?php
// Live Today's Attendance Feed & Real-time Stats API
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../include/db_conn.php';

header('Content-Type: application/json; charset=UTF-8');

// Staff access check
$allowed_roles = ['super_admin', 'owner', 'reception', 'trainer', 'staff', 'admin', 'auditor'];
if (!isset($_SESSION['user_data']) || !isset($_SESSION['role']) || !in_array(strtolower($_SESSION['role']), $allowed_roles) || $_SESSION['role'] === 'member') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized staff access.']);
    exit();
}

date_default_timezone_set("Asia/Calcutta");
$today = date('Y-m-d');

// Undo / delete accidental log
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw_input = file_get_contents('php://input');
    $input = json_decode($raw_input, true) ?: $_POST;
    $action = $input['action'] ?? '';
    $record_id = intval($input['id'] ?? 0);

    if ($action === 'delete' && $record_id > 0) {
        $del = mysqli_query($con, "DELETE FROM attendance WHERE id = $record_id AND date = '$today' LIMIT 1");
        if ($del) {
            echo json_encode(['success' => true, 'message' => 'Attendance log deleted successfully.']);
            exit();
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete record.']);
            exit();
        }
    }
}

// 1. Overall Stats Today
$q_stats = mysqli_query($con, "SELECT 
    COUNT(DISTINCT uid) as total_present,
    COUNT(CASE WHEN exit_time IS NULL OR exit_time = '00:00:00' THEN 1 END) as inside_now,
    COUNT(CASE WHEN exit_time IS NOT NULL AND exit_time != '00:00:00' THEN 1 END) as checked_out
    FROM attendance 
    WHERE date = '$today'");

$stats_row = ($q_stats) ? mysqli_fetch_assoc($q_stats) : null;
$total_present = intval($stats_row['total_present'] ?? 0);
$inside_now    = intval($stats_row['inside_now'] ?? 0);
$checked_out   = intval($stats_row['checked_out'] ?? 0);

// Denied count today
$q_denied = @mysqli_query($con, "SELECT COUNT(*) as denied_cnt FROM biometric_gate_logs WHERE status = 'failed' AND DATE(timestamp) = '$today'");
$denied_cnt = 0;
if ($q_denied && $d_row = mysqli_fetch_assoc($q_denied)) {
    $denied_cnt = intval($d_row['denied_cnt']);
}

// 2. Fetch Recent 20 Logs Today
$sql = "SELECT a.id, a.uid, a.date, a.entry_time, a.exit_time,
               u.username, u.mobile, u.email, u.photo, u.photo_base64,
               e.expire, p.planName
        FROM attendance a
        LEFT JOIN users u ON a.uid = u.userid
        LEFT JOIN (
            SELECT e1.uid, e1.pid, e1.expire
            FROM enrolls_to e1
            INNER JOIN (
                SELECT uid, MAX(et_id) as max_et_id
                FROM enrolls_to
                GROUP BY uid
            ) e2 ON e1.et_id = e2.max_et_id
        ) e ON a.uid = e.uid
        LEFT JOIN plan p ON e.pid = p.pid
        WHERE a.date = '$today'
        ORDER BY a.id DESC
        LIMIT 25";

$res = mysqli_query($con, $sql);
$logs = [];

if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $entry_t = !empty($row['entry_time']) && $row['entry_time'] !== '00:00:00' ? date('h:i A', strtotime($row['entry_time'])) : '--';
        $exit_t  = !empty($row['exit_time']) && $row['exit_time'] !== '00:00:00' ? date('h:i A', strtotime($row['exit_time'])) : null;
        $expire_f = !empty($row['expire']) ? date('d-M-Y', strtotime($row['expire'])) : 'Active';
        $photo_url = get_member_photo_url($row, '../');

        $logs[] = [
            'id'         => intval($row['id']),
            'uid'        => $row['uid'],
            'username'   => $row['username'] ?? 'Gym Member',
            'mobile'     => $row['mobile'] ?? '',
            'email'      => $row['email'] ?? '',
            'photo_url'  => $photo_url,
            'plan_name'  => $row['planName'] ?? 'Member Plan',
            'expire'     => $expire_f,
            'entry_time' => $entry_t,
            'exit_time'  => $exit_t,
            'is_inside'  => empty($exit_t)
        ];
    }
}

echo json_encode([
    'success' => true,
    'today' => date('d M Y'),
    'stats' => [
        'total_present' => $total_present,
        'inside_now'    => $inside_now,
        'checked_out'   => $checked_out,
        'denied_today'  => $denied_cnt
    ],
    'logs' => $logs
], JSON_UNESCAPED_SLASHES);
