<?php
// ADMS cdata endpoint
header("Content-Type: text/plain");
require_once __DIR__ . '/../include/db_conn.php';

$sn = isset($_GET['SN']) ? $_GET['SN'] : '';
$table = isset($_GET['table']) ? $_GET['table'] : '';
$options = isset($_GET['options']) ? $_GET['options'] : '';

// 1. Update Heartbeat
$heartbeat_file = __DIR__ . '/../include/last_sync_heartbeat.txt';
@file_put_contents($heartbeat_file, strval(time()));

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($options === 'all') {
        // Initialization response
        echo "GET OPTION FROM: $sn\n";
        echo "Stamp=9999\n";
        echo "OpStamp=9999\n";
        echo "ErrorDelay=60\n";
        echo "Delay=10\n";
        echo "TransTimes=00:00;14:00\n";
        echo "TransInterval=1\n";
        echo "TransFlag=1111000000\n";
        echo "TimeZone=5.5\n"; // India timezone
        echo "Realtime=1\n";
        echo "Encrypt=0\n";
        exit;
    }
    echo "OK";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw_data = file_get_contents('php://input');

    if ($table === 'ATTLOG') {
        $lines = explode("\n", $raw_data);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            $parts = preg_split('/\s+/', $line);
            if (count($parts) >= 2) {
                $biometric_id = $parts[0];
                $date_str = $parts[1];
                $time_str = isset($parts[2]) ? $parts[2] : '';
                $timestamp = trim("$date_str $time_str");

                // Forward to local attendance logger logic
                $url = 'https://sudarshanfitness.de/Files/api/log_attendance.php';
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                    'biometric_id' => $biometric_id,
                    'timestamp' => $timestamp
                ]));
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                @curl_exec($ch);
                @curl_close($ch);
            }
        }
        echo "OK\n";
        exit;
    }

    echo "OK\n";
    exit;
}

echo "OK\n";
?>
