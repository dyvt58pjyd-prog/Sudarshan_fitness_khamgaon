<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../include/db_conn.php';
require_once __DIR__ . '/../include/gemini_config.php';
page_protect();

header('Content-Type: application/json; charset=UTF-8');

$allowed_roles = ['super_admin', 'owner', 'reception'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

$festival = trim($input['campaign_name'] ?? '');
if (empty($festival)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Campaign / festival name is required.']);
    exit();
}

$gym = get_gym_details($con);
$gym_name = $gym['gym_name'] ?? 'Sudarshan Fitness Khamgaon';

$system = "You write concise, energetic WhatsApp/Email promotional messages for an Indian gym named '{$gym_name}'.";
$prompt = "Write a short, engaging 2-3 sentence promotional WhatsApp/Email message for '{$gym_name}' for the occasion of '{$festival}'. Include relevant emojis, start with a warm greeting, keep it motivational, and do not use hashtags. Return only the message text.";

$text = query_gemini_ai($prompt, $system);
if (empty($text)) {
    $text = "🎉 Warm wishes from everyone at {$gym_name} on {$festival}! 💪 Celebrate with energy, consistency, and strength—see you on the gym floor today!";
}

echo json_encode([
    'success' => true,
    'message' => $text
]);
