<?php
header('Content-Type: application/json');

$API_SECRET = 'TITAN_GYM_SECRET_KEY_123';

$key = isset($_GET['key']) ? $_GET['key'] : '';
if ($key !== $API_SECRET) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$raw_post = file_get_contents('php://input');
$data = json_decode($raw_post, true);

if ($data && isset($data['status'])) {
    $statusFile = __DIR__ . '/whatsapp_status.json';
    
    // Save the status and user/QR data to a local file
    file_put_contents($statusFile, json_encode([
        'status' => $data['status'],
        'user' => isset($data['user']) ? $data['user'] : null,
        'qrImage' => isset($data['qrImage']) ? $data['qrImage'] : null,
        'last_updated' => time()
    ]));
    
    echo json_encode(['success' => true]);
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
}
?>
