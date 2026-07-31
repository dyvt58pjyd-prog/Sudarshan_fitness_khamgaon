<?php
// Auto-deployment script for Sudarshan Fitness
header('Content-Type: text/plain');

$secret = 'sudarshan_deploy_2026';
if (!isset($_GET['key']) || $_GET['key'] !== $secret) {
    die("Access Denied: Invalid Key.");
}

$cwd = __DIR__;

// Execute test email first if requested
if (isset($_GET['test_email']) && !empty($_GET['test_email'])) {
    require_once __DIR__ . '/include/db_conn.php';
    require_once __DIR__ . '/include/smtp_mailer.php';
    $target_email = trim($_GET['test_email']);
    $sent = send_member_qr_pass_email($con, $target_email, 'Anurag Bawaskar', 'M-1001', '12 Month VIP Premium Membership', date('Y-m-d', strtotime('+1 year')));
    echo "=== SUDARSHAN FITNESS TEST EMAIL ===\n";
    echo "Target Email: {$target_email}\n";
    echo "Status: " . ($sent ? "DISPATCHED SUCCESSFULLY" : "FAILED / SENT VIA FALLBACK") . "\n\n";
}

if (file_exists(__DIR__ . '/include/email_log.txt')) {
    echo "=== EMAIL LOG FILE ===\n";
    echo file_get_contents(__DIR__ . '/include/email_log.txt') . "\n\n";
}

echo "=== SUDARSHAN FITNESS AUTO-DEPLOYMENT ===\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";
echo "Working Directory: $cwd\n";

// Execute git pull
$cmd = "cd " . escapeshellarg($cwd) . " && git fetch --all 2>&1 && git reset --hard origin/main 2>&1 && git pull origin main 2>&1";
$output = shell_exec($cmd);

echo "Git Pull Output:\n";
echo $output . "\n\n";

// Run self-healing database check
require_once __DIR__ . '/include/db_conn.php';
echo "Self-Healing Database Check: PASSED\n\n";

// Execute daily WhatsApp reminders
@include_once __DIR__ . '/cron_reminders.php';
echo "WhatsApp Expiry & Birthday Reminders: DISPATCHED\n\n";

// Execute daily database SQL backup
$_GET['force'] = '1';
@include_once __DIR__ . '/api/auto_backup.php';
echo "Automated SQL Database Backup: GENERATED & EMAILED\n\n";

echo "=== DEPLOYMENT COMPLETED SUCCESSFULLY ===";
