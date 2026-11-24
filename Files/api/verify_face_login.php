<?php
header("Content-Type: application/json; charset=UTF-8");
http_response_code(410);
echo json_encode([
    'success' => false,
    'error' => 'Legacy assertionless WebAuthn endpoint disabled for security. Use face_login_verify.php instead.'
]);
exit();
