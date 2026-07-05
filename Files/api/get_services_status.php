<?php
// PHP Services Monitoring and Heartbeat Validator API
$is_cli = (php_sapi_name() === 'cli');

if (!$is_cli) {
    require_once __DIR__ . '/../include/db_conn.php';
    page_protect();
    
    // Only permit authenticated admins
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['super_admin', 'owner', 'reception'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
        exit();
    }
} else {
    $_SERVER['SERVER_NAME'] = 'localhost';
    require_once __DIR__ . '/../include/db_conn.php';
}

header("Content-Type: application/json; charset=UTF-8");
date_default_timezone_set("Asia/Calcutta");

$status = [];

// 1. WhatsApp Daemon Check
$statusFile = __DIR__ . '/whatsapp_status.json';
if (file_exists($statusFile)) {
    $wa_data = json_decode(file_get_contents($statusFile), true);
    
    // Check if the status is stale (older than 30 seconds)
    if (isset($wa_data['last_updated']) && (time() - $wa_data['last_updated'] > 30)) {
        $status['whatsapp'] = [
            'status' => 'OFFLINE',
            'user' => null,
            'label' => 'Offline (Stale)'
        ];
    } else {
        $status['whatsapp'] = [
            'status' => $wa_data['status'] ?? 'CONNECTED',
            'user' => $wa_data['user'] ?? null,
            'label' => ($wa_data['status'] === 'CONNECTED') ? 'Online' : (($wa_data['status'] === 'QR_READY') ? 'QR Authentication Required' : 'Disconnected')
        ];
    }
} else {
    $status['whatsapp'] = [
        'status' => 'OFFLINE',
        'user' => null,
        'label' => 'Offline'
    ];
}

// 2. Biometric Sync Gateway Check
$heartbeat_file = __DIR__ . '/../include/last_sync_heartbeat.txt';
$agent_active = false;
$last_heartbeat_time = 0;

if (file_exists($heartbeat_file)) {
    $last_heartbeat_time = intval(trim(@file_get_contents($heartbeat_file)));
    $time_diff = time() - $last_heartbeat_time;
    if ($time_diff <= 35) { // Active within last 35 seconds (sync interval is 10s)
        $agent_active = true;
    }
}

// Double check using system process checks if heartbeat is slightly delayed
if (!$agent_active) {
    $pids = [];
    @exec("pgrep -f biometric_sync_agent.py", $pids);
    if (!empty($pids) && count($pids) > 0) {
        $agent_active = true;
    }
}

$status['biometric_sync'] = [
    'status' => $agent_active ? 'ONLINE' : 'OFFLINE',
    'last_seen' => $last_heartbeat_time > 0 ? date('Y-m-d H:i:s', $last_heartbeat_time) : 'Never',
    'label' => $agent_active ? 'Synchronized & Running' : 'Offline'
];

// 3. Gate Controller / Biometric Device Check
$config_path = __DIR__ . '/../../biometric_config.json';
$device_ip = '192.168.1.201';
$device_port = 4370;
$simulation_mode = true;

if (file_exists($config_path)) {
    $config_data = json_decode(trim(@file_get_contents($config_path)), true);
    if ($config_data) {
        $device_ip = $config_data['device_ip'] ?? $device_ip;
        $device_port = intval($config_data['device_port'] ?? $device_port);
        $simulation_mode = isset($config_data['simulation_mode']) ? (bool)$config_data['simulation_mode'] : $simulation_mode;
    }
}

if ($simulation_mode) {
    $status['gate_controller'] = [
        'status' => 'SIMULATED',
        'ip' => $device_ip,
        'label' => 'Simulated Gate (Online)'
    ];
} else {
    // Attempt standard TCP connection check (timeout 1s)
    $connection = @fsockopen($device_ip, $device_port, $errno, $errstr, 1.2);
    if (is_resource($connection)) {
        fclose($connection);
        $status['gate_controller'] = [
            'status' => 'ONLINE',
            'ip' => $device_ip,
            'label' => 'Online (' . $device_ip . ')'
        ];
    } else {
        $status['gate_controller'] = [
            'status' => 'OFFLINE',
            'ip' => $device_ip,
            'label' => 'Connection Failed (' . $device_ip . ')'
        ];
    }
}

// 4. Auto Expiry Auditing Check
$lock_file = __DIR__ . '/../include/last_expiry_check.txt';
$today_str = date('Y-m-d');
$last_check = file_exists($lock_file) ? trim(@file_get_contents($lock_file)) : 'Never';

$status['expiry_audit'] = [
    'status' => ($last_check === $today_str) ? 'COMPLETED' : 'PENDING',
    'last_run' => $last_check,
    'label' => ($last_check === $today_str) ? 'Active (Checked Today)' : 'Pending Launch'
];

// 5. Database Backup Check
$backup_status_file = __DIR__ . '/../include/last_backup_status.json';
$last_backup = file_exists($backup_status_file) ? json_decode(trim(@file_get_contents($backup_status_file)), true) : null;

$status['database_backup'] = [
    'status' => ($last_backup && $last_backup['last_backup_date'] === $today_str && $last_backup['status'] === 'success') ? 'COMPLETED' : 'PENDING',
    'last_backup' => $last_backup ? ($last_backup['last_backup_date'] . ' ' . ($last_backup['time'] ?? '')) : 'Never',
    'email_sent' => $last_backup['email_sent'] ?? false,
    'label' => ($last_backup && $last_backup['last_backup_date'] === $today_str && $last_backup['status'] === 'success') ? 'Auto Backup Safe' : 'Backup Pending'
];

echo json_encode([
    'success' => true,
    'services' => $status
]);
exit();
