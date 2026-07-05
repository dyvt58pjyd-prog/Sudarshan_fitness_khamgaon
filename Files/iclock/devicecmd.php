<?php
header("Content-Type: text/plain");
require_once __DIR__ . '/../include/db_conn.php';

// Update Heartbeat
$heartbeat_file = __DIR__ . '/../include/last_sync_heartbeat.txt';
@file_put_contents($heartbeat_file, strval(time()));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw_data = file_get_contents('php://input');
    // Format is like:
    // ID=1&Return=0&CMD=DATA UPDATE USERINFO...
    
    // Sometimes it's just raw POST body, let's try to parse
    // e.g. ID=1&Return=0
    parse_str($raw_data, $parsed);
    
    // if not in raw body, maybe in $_POST or $_GET
    $cmd_id = isset($_POST['ID']) ? $_POST['ID'] : (isset($_GET['ID']) ? $_GET['ID'] : (isset($parsed['ID']) ? $parsed['ID'] : null));
    $return_code = isset($_POST['Return']) ? $_POST['Return'] : (isset($_GET['Return']) ? $_GET['Return'] : (isset($parsed['Return']) ? $parsed['Return'] : null));
    
    if ($cmd_id !== null) {
        $cmd_id_safe = intval($cmd_id);
        $status = ($return_code !== null && intval($return_code) >= 0) ? 'completed' : 'failed';
        mysqli_query($con, "UPDATE biometric_commands SET status = '$status' WHERE id = $cmd_id_safe");
    }
}

echo "OK\n";
?>
