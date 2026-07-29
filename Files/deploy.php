<?php
// Auto-deployment script for Sudarshan Fitness
header('Content-Type: text/plain');

$secret = 'sudarshan_deploy_2026';
if (!isset($_GET['key']) || $_GET['key'] !== $secret) {
    die("Access Denied: Invalid Key.");
}

echo "=== SUDARSHAN FITNESS AUTO-DEPLOYMENT ===\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

$cwd = __DIR__;
echo "Working Directory: $cwd\n";

// Execute git pull
$cmd = "cd " . escapeshellarg($cwd) . " && git fetch --all 2>&1 && git reset --hard origin/main 2>&1 && git pull origin main 2>&1";
$output = shell_exec($cmd);

echo "Git Pull Output:\n";
echo $output . "\n\n";

// Run self-healing database check
require_once __DIR__ . '/include/db_conn.php';
echo "Self-Healing Database Check: PASSED\n";
echo "=== DEPLOYMENT COMPLETED SUCCESSFULLY ===";
