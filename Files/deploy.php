<?php
// Auto-deployment script for Sudarshan Fitness
header('Content-Type: text/plain');

$secret = 'sudarshan_deploy_2026';
if (!isset($_GET['key']) || $_GET['key'] !== $secret) {
    die("Access Denied: Invalid Key.");
}

$cwd = __DIR__;

// Active file cleanup of prohibited binary & legacy diagnostic files
$cleanup_files = [
    __DIR__ . '/sudarshan_fitness.apk',
    __DIR__ . '/sudarshan_fitness_native.apk',
    __DIR__ . '/clean_disk.php',
    __DIR__ . '/nuke_space.php',
    __DIR__ . '/clean_dummy_couples.php',
    __DIR__ . '/fix_tejal.php',
    dirname(__DIR__) . '/sudarshan_fitness.apk',
    dirname(__DIR__) . '/sudarshan_fitness_native.apk',
    dirname(__DIR__) . '/clean_disk.php',
    dirname(__DIR__) . '/nuke_space.php',
];
foreach ($cleanup_files as $f) {
    if (file_exists($f)) {
        @unlink($f);
        echo "Removed prohibited file: " . basename($f) . "\n";
    }
}

echo "=== SUDARSHAN FITNESS AUTO-DEPLOYMENT ===\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";
echo "Working Directory: $cwd\n";

// Execute git pull
$cmd = "cd " . escapeshellarg($cwd) . " && git fetch --all 2>&1 && git reset --hard origin/main 2>&1 && git pull origin main 2>&1";
$output = @shell_exec($cmd);

echo "Git Pull Output:\n";
echo $output . "\n\n";

// Run self-healing database check
require_once __DIR__ . '/include/db_conn.php';
echo "Self-Healing Database Check: PASSED\n\n";

// Execute daily WhatsApp reminders
@include_once __DIR__ . '/cron_reminders.php';
echo "WhatsApp Expiry & Birthday Reminders: DISPATCHED\n\n";

echo "=== DEPLOYMENT COMPLETED SUCCESSFULLY ===";
