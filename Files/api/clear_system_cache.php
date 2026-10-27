<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/../include/db_conn.php';

// Security check: Only authenticated admins/owners or requests with valid token
$is_authenticated = false;
if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['super_admin', 'owner', 'reception'])) {
    $is_authenticated = true;
}

// Token fallback for cron or maintenance requests
$secret_token = 'sudarshan_speed_turbo_2026';
if (isset($_GET['token']) && $_GET['token'] === $secret_token) {
    $is_authenticated = true;
}

if (!$is_authenticated) {
    echo json_encode(['success' => false, 'error' => 'Access Denied: Administrator authentication required.']);
    exit();
}

$report = [
    'timestamp' => date('Y-m-d H:i:s'),
    'opcache_cleared' => false,
    'schema_cache_refreshed' => false,
    'junk_files_deleted' => 0,
    'tables_optimized' => [],
    'message' => 'System cache successfully purged and application performance optimized.'
];

// 1. Reset PHP OPcache (forces PHP to recompile scripts from disk immediately)
if (function_exists('opcache_reset')) {
    $report['opcache_cleared'] = @opcache_reset();
}

// 2. Remove Schema Flag to force a fresh schema synchronization if requested
$schema_flag = __DIR__ . '/../include/.db_schema_v2.flag';
if (isset($_GET['recheck_schema']) && file_exists($schema_flag)) {
    @unlink($schema_flag);
    $report['schema_cache_refreshed'] = true;
}

// 3. Clean unwanted temporary junk files (.DS_Store, error_log)
$root_dir = realpath(__DIR__ . '/..');
$junk_patterns = ['.DS_Store', 'error_log', 'Thumbs.db'];
$deleted_count = 0;

try {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root_dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($iterator as $item) {
        if ($item->isFile() && in_array($item->getFilename(), $junk_patterns)) {
            @unlink($item->getPathname());
            $deleted_count++;
        }
    }
} catch (Exception $e) {
    // Ignore permission restrictions
}
$report['junk_files_deleted'] = $deleted_count;

// 4. Optimize Core Database Tables (reclaims fragmented space and rebuilds indexes)
$core_tables = ['users', 'attendance', 'enrolls_to', 'plan', 'admin', 'login_attempts', 'security_audit_logs'];
foreach ($core_tables as $table) {
    $res = @mysqli_query($con, "OPTIMIZE TABLE $table");
    if ($res) {
        $report['tables_optimized'][] = $table;
    }
}

// 5. Clean expired sessions from login_attempts
@mysqli_query($con, "DELETE FROM login_attempts WHERE attempt_time < NOW() - INTERVAL 7 DAY");
@mysqli_query($con, "DELETE FROM security_audit_logs WHERE created_at < NOW() - INTERVAL 30 DAY");

$report['success'] = true;
echo json_encode($report, JSON_PRETTY_PRINT);
exit();
