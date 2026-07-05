<?php
header("Content-Type: text/plain");

// Update Heartbeat
$heartbeat_file = __DIR__ . '/../include/last_sync_heartbeat.txt';
@file_put_contents($heartbeat_file, strval(time()));

echo "OK\n";
?>
