<?php
// Secure Backup Cron
require_once __DIR__ . '/../include/db_conn.php';

// Prevent direct web access. Should only run via cron or admin trigger.
if (php_sapi_name() !== 'cli' && !isset($_GET['trigger'])) {
    http_response_code(403);
    die("Direct access forbidden.");
}

$backup_dir = __DIR__ . '/../backups/';
if (!is_dir($backup_dir)) {
    mkdir($backup_dir, 0755, true);
    // Protect directory from web access
    file_put_contents($backup_dir . '.htaccess', "Order allow,deny\nDeny from all");
    file_put_contents($backup_dir . 'index.php', "<?php http_response_code(403); die('Forbidden'); ?>");
}

$date_str = date('Y-m-d_H-i-s');
$filename = "titangym_backup_$date_str.sql";
$filepath = $backup_dir . $filename;

// Simple pure PHP database dumper
$tables = array();
$result = mysqli_query($con, "SHOW TABLES");
while ($row = mysqli_fetch_row($result)) {
    $tables[] = $row[0];
}

$sql_dump = "-- Sudarshan Fitness Encrypted Backup\n";
$sql_dump .= "-- Generated at: " . date('Y-m-d H:i:s') . "\n\n";

foreach ($tables as $table) {
    $result = mysqli_query($con, "SELECT * FROM `$table`");
    $num_fields = mysqli_num_fields($result);

    $sql_dump .= "DROP TABLE IF EXISTS `$table`;\n";
    $row2 = mysqli_fetch_row(mysqli_query($con, "SHOW CREATE TABLE `$table`"));
    $sql_dump .= $row2[1] . ";\n\n";

    while ($row = mysqli_fetch_row($result)) {
        $sql_dump .= "INSERT INTO `$table` VALUES(";
        for ($j = 0; $j < $num_fields; $j++) {
            $row[$j] = addslashes($row[$j]);
            $row[$j] = preg_replace("/\n/", "\\n", $row[$j]);
            if (isset($row[$j])) {
                $sql_dump .= '"' . $row[$j] . '"';
            } else {
                $sql_dump .= '""';
            }
            if ($j < ($num_fields - 1)) {
                $sql_dump .= ',';
            }
        }
        $sql_dump .= ");\n";
    }
    $sql_dump .= "\n\n\n";
}

// Encrypt the SQL dump
$encryption_key = 'SUDARSHAN_BACKUP_MASTER_KEY_2026'; // Should be in env, but hardcoded for demo
$iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
$encrypted = openssl_encrypt($sql_dump, 'aes-256-cbc', $encryption_key, 0, $iv);
$final_data = base64_encode($iv . $encrypted);

if (file_put_contents($filepath . '.enc', $final_data)) {
    log_security_event($con, 'CRON_BACKUP_SUCCESS', "Automated encrypted backup created: $filename.enc", 'info', 'system', 'system');
    echo "Backup Generated Successfully: $filename.enc";
} else {
    log_security_event($con, 'CRON_BACKUP_FAILED', "Failed to write backup file to disk", 'critical', 'system', 'system');
    echo "Backup Failed.";
}
?>
