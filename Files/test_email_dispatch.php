<?php
header('Content-Type: text/plain');

require_once __DIR__ . '/include/db_conn.php';
require_once __DIR__ . '/include/smtp_mailer.php';

$target_email = 'anuragbawaskar680@gmail.com';
$target_name  = 'Anurag Bawaskar';
$userid       = 'M-TEST-1001';
$plan_name    = '12 Month VIP Premium Membership';
$expire_date  = date('Y-m-d', strtotime('+1 year'));

echo "=== SENDING TEST EMAIL TO: {$target_email} ===\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

$result = send_member_qr_pass_email($con, $target_email, $target_name, $userid, $plan_name, $expire_date);

if ($result) {
    echo "SUCCESS: Test registration & entrance QR pass email was dispatched successfully to {$target_email}!\n";
} else {
    echo "FAILED: Email dispatch failed. Attempting direct SMTP test...\n";
    $subject = "📷 Test Registration Email - Sudarshan Fitness";
    $body = "<h2>Test Email Confirmation</h2><p>Hello Anurag, this is a test email confirmation from Sudarshan Fitness Application System.</p><p><a href='https://sudarshanfitness.de/Files/download_app.php' style='padding: 12px 20px; background: #ff003c; color: white; border-radius: 8px; text-decoration: none;'>Install Application</a></p>";
    $direct = send_smtp_email($target_email, $target_name, $subject, $body);
    if ($direct) {
        echo "SUCCESS: Direct SMTP email sent successfully to {$target_email}!\n";
    } else {
        echo "ERROR: Could not dispatch email via SMTP or native mail server.\n";
    }
}

// Output email log file if exists
if (file_exists(__DIR__ . '/include/email_log.txt')) {
    echo "\n--- Email Dispatch Log ---\n";
    echo file_get_contents(__DIR__ . '/include/email_log.txt');
}
