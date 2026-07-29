<?php
// dispatch_all_qr_emails.php
// Dispatch dedicated Member Entrance QR Pass Emails to all registered members

header("Content-Type: text/plain; charset=UTF-8");
require_once __DIR__ . '/include/db_conn.php';
require_once __DIR__ . '/include/smtp_mailer.php';

$secret = 'sudarshan_deploy_2026';
$is_cli = (php_sapi_name() === 'cli');

if (!$is_cli && (!isset($_GET['key']) || $_GET['key'] !== $secret)) {
    page_protect();
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['super_admin', 'owner', 'reception'])) {
        die("Access Denied: Invalid authentication.");
    }
}

echo "=== SUDARSHAN FITNESS ENTRANCE QR PASS BATCH DISPATCHER ===\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

// Fetch all registered members with valid emails
$q_members = mysqli_query($con, "SELECT userid, username, email, gender FROM users WHERE email IS NOT NULL AND email != '' AND email NOT LIKE '%@sudarshanfitness.local' ORDER BY CAST(userid AS UNSIGNED) ASC");

if (!$q_members || mysqli_num_rows($q_members) === 0) {
    die("No members found with valid email addresses.");
}

$total_members = mysqli_num_rows($q_members);
echo "Found $total_members registered members with email addresses.\n";
echo "Starting email dispatches...\n\n";

$sent_count = 0;
$failed_count = 0;

while ($user = mysqli_fetch_assoc($q_members)) {
    $uid = $user['userid'];
    $name = $user['username'];
    $email = $user['email'];
    
    // Fetch active plan details
    $q_plan = mysqli_query($con, "SELECT p.planName, e.expire 
                                  FROM enrolls_to e 
                                  JOIN plan p ON e.pid = p.pid 
                                  WHERE e.uid = '$uid' 
                                  ORDER BY e.expire DESC LIMIT 1");
    $plan_name = "Active Subscription";
    $expire_date = "N/A";
    
    if ($q_plan && mysqli_num_rows($q_plan) > 0) {
        $p_row = mysqli_fetch_assoc($q_plan);
        $plan_name = $p_row['planName'];
        $expire_date = $p_row['expire'];
    }

    echo "[$uid] Sending Entrance QR Pass to $name ($email)... ";
    
    $sent = send_member_qr_pass_email($con, $email, $name, $uid, $plan_name, $expire_date);
    
    if ($sent) {
        echo "SUCCESS ✓\n";
        $sent_count++;
    } else {
        echo "FAILED ✕ (Check SMTP settings / log)\n";
        $failed_count++;
    }
}

$summary = "=== BATCH QR EMAIL DISPATCH COMPLETED ===\n" .
         "Date: " . date('Y-m-d H:i:s') . "\n" .
         "Total Sent Successfully: $sent_count\n" .
         "Total Failed / Skipped: $failed_count\n" .
         "============================================\n";

echo "\n" . $summary;
@file_put_contents(__DIR__ . '/include/qr_email_dispatch_log.txt', $summary, FILE_APPEND);
