<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../include/db_conn.php';
date_default_timezone_set("Asia/Calcutta");

// Check if biometric_gate_logs exists
mysqli_query($con, "CREATE TABLE IF NOT EXISTS biometric_gate_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    uid VARCHAR(50) NOT NULL,
    timestamp DATETIME NOT NULL,
    status VARCHAR(20) NOT NULL,
    type VARCHAR(30) NOT NULL,
    error_reason VARCHAR(255) NULL
)");

$auth_string = "268724:anurag.bawaskar:Anurag@268724:true";
$today = date("d/m/Y"); // eTimeOffice format

$api_url = "http://api.etimeoffice.com/api/DownloadInOutPunchData?Empcode=ALL&FromDate=" . $today . "&ToDate=" . $today;

$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Basic " . base64_encode($auth_string)]);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
$synced_count = 0;

if ($data && isset($data['InOutPunchData']) && is_array($data['InOutPunchData'])) {
    foreach ($data['InOutPunchData'] as $punch) {
        $empcode = trim($punch['Empcode']);
        
        // eTimeOffice punch formats are usually MM/DD/YYYY or DD/MM/YYYY
        // InOutPunchData from pyetimeoffice typically returns Date and Time separately
        // Note: Assuming PunchDate = "01/07/2026", PunchTime = "09:30:00"
        // Since we don't have a solid punch yet, we use a robust parse strategy:
        $raw_date_time = $punch['PunchDate'] . " " . $punch['PunchTime'];
        // Format to Y-m-d H:i:s
        $time_parsed = strtotime(str_replace('/', '-', $raw_date_time));
        
        // If strtotime fails due to weird formats, try without replacement
        if (!$time_parsed) {
            $time_parsed = strtotime($raw_date_time);
        }
        
        if (!$time_parsed) continue; // Skip invalid dates
        
        $sql_timestamp = date('Y-m-d H:i:s', $time_parsed);
        
        // Match Empcode to users table
        $emp_esc = mysqli_real_escape_string($con, $empcode);
        $q_user = mysqli_query($con, "SELECT userid FROM users WHERE biometric_id = '$emp_esc' OR (biometric_id IS NULL AND userid = '$emp_esc') LIMIT 1");
        
        if ($q_user && mysqli_num_rows($q_user) > 0) {
            $user = mysqli_fetch_assoc($q_user);
            $userid = $user['userid'];
            $userid_esc = mysqli_real_escape_string($con, $userid);
            
            // Check if this EXACT punch is already logged
            $q_check = mysqli_query($con, "SELECT id FROM biometric_gate_logs WHERE uid = '$userid_esc' AND timestamp = '$sql_timestamp'");
            
            if ($q_check && mysqli_num_rows($q_check) === 0) {
                // Not logged yet! Send to log_attendance.php internally
                $post_payload = json_encode([
                    'biometric_id' => $empcode,
                    'timestamp' => $sql_timestamp
                ]);
                
                $local_ch = curl_init("http://localhost/api/log_attendance.php");
                if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/api/log_attendance.php')) {
                    $local_ch = curl_init("http://" . $_SERVER['HTTP_HOST'] . "/api/log_attendance.php");
                }
                
                curl_setopt($local_ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($local_ch, CURLOPT_POST, true);
                curl_setopt($local_ch, CURLOPT_POSTFIELDS, $post_payload);
                curl_setopt($local_ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
                curl_exec($local_ch);
                curl_close($local_ch);
                
                $synced_count++;
            }
        }
    }
}

echo json_encode([
    'success' => true,
    'synced_count' => $synced_count,
    'message' => "Synced $synced_count new punches from eTimeOffice."
]);
