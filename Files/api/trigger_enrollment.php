<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../include/db_conn.php';

$data = json_decode(file_get_contents("php://input"), true);
$biometric_id = isset($data['biometric_id']) ? intval($data['biometric_id']) : 0;

if ($biometric_id > 0) {
    $stmt = $con->prepare("UPDATE users SET pending_enrollment = 1 WHERE biometric_id = ?");
    $stmt->bind_param("i", $biometric_id);
    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Machine enrollment triggered"]);
    } else {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Database error"]);
    }
    $stmt->close();
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid biometric_id"]);
}
?>
