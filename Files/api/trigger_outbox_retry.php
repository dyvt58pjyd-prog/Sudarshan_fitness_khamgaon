<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../include/db_conn.php';

// Protect page access to admins/owners or allowed local processes (like WhatsApp ready hook)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$is_allowed = false;
// Allow if admin session exists
if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['super_admin', 'owner', 'reception'])) {
    $is_allowed = true;
}
// Allow if local request (e.g. from local server loop)
$remote_ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
if ($remote_ip === '127.0.0.1' || $remote_ip === '::1' || empty($remote_ip)) {
    $is_allowed = true;
}

if (!$is_allowed) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access.'
    ]);
    exit();
}

date_default_timezone_set("Asia/Calcutta");

// Fetch pending or failed messages where attempts < 3
$q = mysqli_query($con, "SELECT * FROM whatsapp_outbox WHERE status IN ('pending', 'failed') AND attempts < 3 ORDER BY created_at ASC LIMIT 10");
$processed = 0;
$success_count = 0;
$failed_count = 0;

$send_url = 'http://localhost:5001/send';

if ($q && mysqli_num_rows($q) > 0) {
    while ($row = mysqli_fetch_assoc($q)) {
        $msg_id = intval($row['id']);
        $number = $row['number'];
        $message = $row['message'];
        $file_path = $row['file_path'];
        $attempts = intval($row['attempts']) + 1;
        
        $payload = [
            'number' => $number,
            'message' => $message
        ];
        if (!empty($file_path) && file_exists($file_path)) {
            $payload['filePath'] = $file_path;
        }
        
        $ch = @curl_init($send_url);
        $sent_ok = false;
        if ($ch) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 8);
            
            $res = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($http_code === 200) {
                $sent_ok = true;
            }
        }
        
        $now = date('Y-m-d H:i:s');
        if ($sent_ok) {
            mysqli_query($con, "UPDATE whatsapp_outbox SET status = 'sent', attempts = $attempts, last_attempt = '$now' WHERE id = $msg_id");
            $success_count++;
        } else {
            $status = ($attempts >= 3) ? 'failed' : 'pending';
            mysqli_query($con, "UPDATE whatsapp_outbox SET status = '$status', attempts = $attempts, last_attempt = '$now' WHERE id = $msg_id");
            $failed_count++;
        }
        $processed++;
    }
}

echo json_encode([
    'success' => true,
    'processed' => $processed,
    'sent' => $success_count,
    'failed' => $failed_count
]);
exit();
?>
