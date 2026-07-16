<?php
// Automated Daily SQL Database Backup & Email Dispatcher
// Can be run via CLI/Cron, or called as an API by admin dashboard

$is_cli = (php_sapi_name() === 'cli');

if (!$is_cli) {
    require_once __DIR__ . '/../include/db_conn.php';
    page_protect();
    
    // Only permit admins, owners, or receptionists to trigger backups
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['super_admin', 'owner', 'reception'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
        exit();
    }
} else {
    $_SERVER['SERVER_NAME'] = 'localhost';
    require_once __DIR__ . '/../include/db_conn.php';
}

header("Content-Type: application/json; charset=UTF-8");
date_default_timezone_set("Asia/Calcutta");

$today_str = date('Y-m-d');
$backup_dir = __DIR__ . '/../uploads/backups';

// Ensure backup folder exists
if (!file_exists($backup_dir)) {
    @mkdir($backup_dir, 0755, true);
}

$backup_file_name = "db_backup_" . $today_str . ".sql";
$backup_file_path = $backup_dir . '/' . $backup_file_name;

// Check if a backup has already been successfully run today
$status_file = __DIR__ . '/../include/last_backup_status.json';
$last_status = file_exists($status_file) ? json_decode(trim(@file_get_contents($status_file)), true) : null;

// Allow forcing a backup run via GET param (e.g. forced by click)
$force_run = isset($_GET['force']) && $_GET['force'] === '1';

if ($last_status && $last_status['last_backup_date'] === $today_str && !$force_run && file_exists($backup_file_path)) {
    echo json_encode([
        'success' => true,
        'message' => 'Backup already completed for today.',
        'details' => $last_status
    ]);
    exit();
}

// Start SQL export generation
$sql_content = "-- SUDARSHAN FITNESS KHAMGAON AUTOMATED DATABASE BACKUP\n";
$sql_content .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n\n";
$sql_content .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

// Get list of tables
$tables = [];
$res_tables = mysqli_query($con, "SHOW TABLES");
if ($res_tables) {
    while ($row = mysqli_fetch_row($res_tables)) {
        $tables[] = $row[0];
    }
}

foreach ($tables as $table) {
    $sql_content .= "DROP TABLE IF EXISTS `" . $table . "`;\n";
    
    // Get create table sql
    $res_create = mysqli_query($con, "SHOW CREATE TABLE `" . $table . "`");
    if ($res_create) {
        $row_create = mysqli_fetch_row($res_create);
        $sql_content .= $row_create[1] . ";\n\n";
    }
    
    // Get row inserts
    $res_rows = mysqli_query($con, "SELECT * FROM `" . $table . "`");
    if ($res_rows) {
        while ($row = mysqli_fetch_assoc($res_rows)) {
            $fields = array_keys($row);
            $values = [];
            
            foreach ($row as $val) {
                if ($val === null) {
                    $values[] = "NULL";
                } else {
                    $values[] = "'" . mysqli_real_escape_string($con, $val) . "'";
                }
            }
            
            $sql_content .= "INSERT INTO `" . $table . "` (`" . implode("`, `", $fields) . "`) VALUES (" . implode(", ", $values) . ");\n";
        }
        $sql_content .= "\n";
    }
}

$sql_content .= "SET FOREIGN_KEY_CHECKS=1;\n";

// Write to SQL backup file
$written = @file_put_contents($backup_file_path, $sql_content);

if ($written === false) {
    $error_status = [
        'last_backup_date' => $today_str,
        'status' => 'failed',
        'time' => date('H:i:s'),
        'error' => 'Failed to write SQL backup file to uploads/backups/. Check permissions.'
    ];
    @file_put_contents($status_file, json_encode($error_status, JSON_PRETTY_PRINT));
    
    http_response_code(500);
    echo json_encode($error_status);
    exit();
}

// Prune historical backups (older than 30 days)
$pruned_files = [];
$all_backups = glob($backup_dir . '/db_backup_*.sql');
if ($all_backups) {
    foreach ($all_backups as $file) {
        if (time() - filemtime($file) > 30 * 86400) {
            if (@unlink($file)) {
                $pruned_files[] = basename($file);
            }
        }
    }
}

// Retrieve gym settings to dispatch backup email
$gym = get_gym_details($con);
$gym_name = $gym['gym_name'];
$gym_email = $gym['gym_email'];

$email_sent = false;
$email_err = '';

if (!empty($gym_email)) {
    require_once __DIR__ . '/../include/smtp_mailer.php';
    
    $subject = "🔒 Automated Database Backup - $gym_name ($today_str)";
    $body = "
    <html>
    <head>
        <style>
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8fafc; color: #1e293b; padding: 25px; margin: 0; }
            .container { background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 30px; max-width: 600px; margin: 0 auto; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
            h2 { color: #ff6b00; margin-top: 0; }
            p { font-size: 14px; line-height: 1.6; color: #475569; }
            .details { background-color: #f1f5f9; padding: 15px; border-radius: 8px; font-family: monospace; font-size: 13px; margin: 20px 0; border-left: 4px solid #ff6b00; }
            .footer { margin-top: 30px; font-size: 11px; color: #94a3b8; text-align: center; border-top: 1px solid #e2e8f0; padding-top: 15px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h2>Automated Daily SQL Backup</h2>
            <p>Hello Gym Owner,</p>
            <p>Your gym application has automatically performed a scheduled database backup. Please find the attached SQL dump file for recovery and data redundancy safeguards.</p>
            
            <div class='details'>
                Backup Date: $today_str<br>
                Time of execution: " . date('H:i:s') . "<br>
                File Name: $backup_file_name<br>
                Size: " . number_format($written / 1024, 2) . " KB<br>
                Pruned files: " . (count($pruned_files) > 0 ? implode(', ', $pruned_files) : 'None') . "
            </div>
            
            <p>Keep this dump secure. In the event of local computer failures or database corruption, this file can be imported to restore all membership information, payment ledgers, and attendance punches.</p>
            
            <div class='footer'>
                This is an automated security service from $gym_name management system.
            </div>
        </div>
    </body>
    </html>";
    
    // Send email using SMTP
    $email_sent = send_smtp_email($gym_email, $gym_name, $subject, $body, $backup_file_path, $backup_file_name);
    
    if (!$email_sent) {
        // Fallback to PHP native mail
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: $gym_email\r\n";
        $email_sent = @mail($gym_email, $subject, $body, $headers);
        if (!$email_sent) {
            $email_err = 'SMTP setup or fallback mail failed to deliver.';
        }
    }
} else {
    $email_err = 'No gym email address defined in settings.';
}

$success_status = [
    'last_backup_date' => $today_str,
    'status' => 'success',
    'time' => date('H:i:s'),
    'file' => $backup_file_name,
    'size' => $written,
    'email_sent' => $email_sent,
    'pruned' => $pruned_files,
    'error' => $email_err
];

@file_put_contents($status_file, json_encode($success_status, JSON_PRETTY_PRINT));

echo json_encode([
    'success' => true,
    'message' => 'Daily automated database backup completed.',
    'details' => $success_status
]);
exit();
