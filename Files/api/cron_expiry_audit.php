<?php
/**
 * Daily Expiry Audit Cron
 * Finds members whose plans expired yesterday and blocks them from the biometric machine.
 */
require_once __DIR__ . '/../include/db_conn.php';

$yesterday = date('Y-m-d', strtotime('-1 day'));

// Find enrollments that expired yesterday
$sql = "SELECT e.uid, u.username, u.biometric_id FROM enrolls_to e 
        INNER JOIN users u ON u.userid = e.uid
        WHERE e.expire = '$yesterday'";
$res = mysqli_query($con, $sql);

if ($res && mysqli_num_rows($res) > 0) {
    while ($row = mysqli_fetch_assoc($res)) {
        $uid = $row['uid'];
        $username = $row['username'];
        $bio_id = $row['biometric_id'];
        
        // Skip if bio_id is missing or if they renewed already (another active enrolls_to)
        if (empty($bio_id)) continue;
        
        $check_active = mysqli_query($con, "SELECT id FROM enrolls_to WHERE uid = '$uid' AND expire >= CURDATE()");
        if (mysqli_num_rows($check_active) > 0) {
            continue; // They have another active plan
        }
        
        // Block them
        $payload = json_encode(['reason' => 'expired_plan']);
        mysqli_query($con, "INSERT INTO biometric_commands (command_type, target_uid, payload, status) VALUES ('BLOCK_MEMBER', '$bio_id', '$payload', 'pending')");
        
        echo "Blocked member: $username ($uid) | Bio ID: $bio_id\n";
    }
}

echo "Audit Complete.\n";
?>
