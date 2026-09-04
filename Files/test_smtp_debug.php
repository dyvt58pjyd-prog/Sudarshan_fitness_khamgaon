<?php
header('Content-Type: text/plain');

require_once __DIR__ . '/include/db_conn.php';

$res = mysqli_query($con, "SELECT * FROM smtp_settings WHERE id = 1");
if (!$res || mysqli_num_rows($res) === 0) {
    die("ERROR: smtp_settings table record not found in database.");
}

$smtp = mysqli_fetch_assoc($res);
echo "=== CURRENT SMTP CONFIGURATION ===\n";
echo "Host: " . $smtp['smtp_host'] . "\n";
echo "Port: " . $smtp['smtp_port'] . "\n";
echo "Secure: " . $smtp['smtp_secure'] . "\n";
echo "Username: " . $smtp['smtp_username'] . "\n";
echo "From Name: " . $smtp['smtp_from_name'] . "\n\n";

$target_email = isset($_GET['email']) ? trim($_GET['email']) : 'anuragbawaskar680@gmail.com';

echo "=== TESTING DIRECT SOCKET SMTP DISPATCH TO: {$target_email} ===\n";

require_once __DIR__ . '/include/smtp_mailer.php';

$result = send_smtp_email($target_email, 'Anurag Test', '📷 Test Email Pass - Sudarshan Fitness', '<h2 style="color:#ff7b00;">Sudarshan Fitness App Test Email</h2><p>Testing direct SMTP connection to anuragbawaskar680@gmail.com.</p><p><a href="https://sudarshanfitness.de/Files/download_app.php">Install App</a></p>');

echo "SMTP Dispatch Result: " . ($result ? "SUCCESS (Accepted by SMTP server)" : "FAILED (Rejected or Socket Error)") . "\n\n";

if (file_exists(__DIR__ . '/include/email_log.txt')) {
    echo "--- Email Log Output ---\n";
    echo file_get_contents(__DIR__ . '/include/email_log.txt');
}
