<?php
header("Content-Type: text/plain");
require_once __DIR__ . '/../include/db_conn.php';

// Update Heartbeat
$heartbeat_file = __DIR__ . '/../include/last_sync_heartbeat.txt';
@file_put_contents($heartbeat_file, strval(time()));

$sn = isset($_GET['SN']) ? mysqli_real_escape_string($con, $_GET['SN']) : '';

// Fetch pending commands
$sql = "SELECT id, command_type, target_uid, payload FROM biometric_commands WHERE status = 'pending' ORDER BY created_at ASC LIMIT 10";
$res = mysqli_query($con, $sql);

$output = "";

if ($res && mysqli_num_rows($res) > 0) {
    while ($row = mysqli_fetch_assoc($res)) {
        $cmd_id = $row['id'];
        $type = $row['command_type'];
        $uid = $row['target_uid'];
        $payload = json_decode($row['payload'], true);
        
        $cmd_str = "";
        
        if ($type === 'UPDATE_USERINFO') {
            $name = isset($payload['name']) ? $payload['name'] : 'User';
            $pin = isset($payload['pin']) ? $payload['pin'] : $uid; // entry_code or uid
            // Format: DATA UPDATE USERINFO PIN=xyz Name=xyz
            $cmd_str = "DATA UPDATE USERINFO PIN={$uid}\tName={$name}";
            // Note: We use target_uid as PIN in ZKTeco to match biometric_id.
        } else if ($type === 'CLEAR_FINGERPRINT') {
            $cmd_str = "DATA DELETE FINGERTMP PIN={$uid}";
        } else if ($type === 'BLOCK_MEMBER') {
            $cmd_str = "DATA DELETE USERINFO PIN={$uid}";
        }
        
        if (!empty($cmd_str)) {
            $output .= "C:{$cmd_id}:{$cmd_str}\n";
            mysqli_query($con, "UPDATE biometric_commands SET status = 'sent' WHERE id = {$cmd_id}");
        }
    }
}

if (empty($output)) {
    echo "OK\n";
} else {
    echo $output;
}
?>
