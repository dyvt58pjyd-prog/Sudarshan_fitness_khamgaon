<?php
// PHP Self-Healing Process Restarter API
require_once __DIR__ . '/../include/db_conn.php';
page_protect();

header("Content-Type: application/json; charset=UTF-8");
date_default_timezone_set("Asia/Calcutta");

// Verify administrator permissions
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['super_admin', 'owner', 'reception'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized action.']);
    exit();
}

$service = isset($_GET['service']) ? trim($_GET['service']) : '';

if ($service !== 'whatsapp' && $service !== 'biometric') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid service name. Must be whatsapp or biometric.']);
    exit();
}

$root_dir = realpath(__DIR__ . '/../..');

if ($service === 'whatsapp') {
    // 1. Kill any process listening on port 5001
    $pid_5001 = [];
    @exec("lsof -t -i :5001", $pid_5001);
    if (!empty($pid_5001)) {
        foreach ($pid_5001 as $pid) {
            $pid = intval(trim($pid));
            if ($pid > 0) {
                @exec("kill -9 $pid");
            }
        }
        sleep(1);
    }

    // 2. Launch the node server in background
    $cmd = "export NVM_DIR=\"\$HOME/.nvm\"
            if [ -s \"\$NVM_DIR/nvm.sh\" ]; then
                . \"\$NVM_DIR/nvm.sh\"
            fi
            export PATH=\"/usr/local/bin:/opt/homebrew/bin:/usr/bin:/bin:/usr/sbin:/sbin:\$PATH\"
            cd " . escapeshellarg($root_dir) . "
            nohup node whatsapp_service/server.js > whatsapp_service/server.log 2>&1 &";
            
    @exec($cmd);
    
    // Give it 1 second to start and verify
    sleep(1.5);
    $check_pid = [];
    @exec("lsof -t -i :5001", $check_pid);
    
    if (!empty($check_pid)) {
        echo json_encode([
            'success' => true,
            'message' => 'WhatsApp AI Daemon restarted successfully.',
            'pid' => $check_pid[0]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to launch WhatsApp service. Check whatsapp_service/server.log for details.'
        ]);
    }
    exit();
}

if ($service === 'biometric') {
    // 1. Kill any running biometric agents
    $pids = [];
    @exec("pgrep -f biometric_sync_agent.py", $pids);
    if (!empty($pids)) {
        foreach ($pids as $pid) {
            $pid = intval(trim($pid));
            if ($pid > 0) {
                @exec("kill -9 $pid");
            }
        }
        sleep(1);
    }

    // 2. Launch the agent in background
    $cmd = "export PATH=\"/usr/local/bin:/opt/homebrew/bin:/usr/bin:/bin:/usr/sbin:/sbin:\$PATH\"
            cd " . escapeshellarg($root_dir) . "
            nohup python3 Files/biometric_sync_agent.py > Files/biometric_sync.log 2>&1 &";
            
    @exec($cmd);
    
    // Give it 1 second to start and verify
    sleep(1.5);
    $check_pids = [];
    @exec("pgrep -f biometric_sync_agent.py", $check_pids);
    
    if (!empty($check_pids)) {
        echo json_encode([
            'success' => true,
            'message' => 'Biometric Sync Gateway restarted successfully.',
            'pids' => $check_pids
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to launch Biometric agent. Check Files/biometric_sync.log for details.'
        ]);
    }
    exit();
}
