<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../include/db_conn.php';

// Polling API for the local WhatsApp Node.js service
// Instead of PHP pushing to Node.js, Node.js polls this file.

$secret = "TITAN_GYM_SECRET_KEY_123";
$auth = isset($_GET['key']) ? $_GET['key'] : '';

if ($auth !== $secret) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Fetch pending or failed messages (up to 10 at a time)
$q = mysqli_query($con, "SELECT * FROM whatsapp_outbox WHERE status IN ('pending', 'failed') AND attempts < 3 ORDER BY created_at ASC LIMIT 10");
$messages = [];

if ($q && mysqli_num_rows($q) > 0) {
    while ($row = mysqli_fetch_assoc($q)) {
        $messages[] = [
            'id' => $row['id'],
            'number' => $row['number'],
            'message' => $row['message'],
            'filePath' => $row['file_path']
        ];
        
        // Mark as processing so the next poll cycle doesn't grab it before it's sent
        $id = $row['id'];
        mysqli_query($con, "UPDATE whatsapp_outbox SET status = 'processing' WHERE id = $id");
    }
}

echo json_encode(['success' => true, 'messages' => $messages]);
?>
