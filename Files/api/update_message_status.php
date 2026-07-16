<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../include/db_conn.php';

// API to update message status from the WhatsApp Node.js service

$secret = "TITAN_GYM_SECRET_KEY_123";
$auth = isset($_GET['key']) ? $_GET['key'] : '';

if ($auth !== $secret) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Read JSON payload from Node.js
$raw_post = file_get_contents('php://input');
$data = json_decode($raw_post, true);

$id = isset($data['id']) ? intval($data['id']) : 0;
$status = isset($data['status']) ? mysqli_real_escape_string($con, $data['status']) : '';

if ($id > 0 && !empty($status)) {
    $now = date('Y-m-d H:i:s');
    mysqli_query($con, "UPDATE whatsapp_outbox SET status = '$status', attempts = attempts + 1, last_attempt = '$now' WHERE id = $id");
    echo json_encode(['success' => true]);
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
}
?>
