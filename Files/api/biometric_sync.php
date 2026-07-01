<?php
header("Content-Type: application/json; charset=UTF-8");

// Load DB connection
require_once __DIR__ . '/../include/db_conn.php';

// Ensure correct timezone
date_default_timezone_set("Asia/Calcutta");
$today = date('Y-m-d');

$sql = "SELECT u.userid, u.username, u.biometric_id, u.biometric_enabled, u.photo, u.pending_enrollment,
               (SELECT MAX(e.expire) FROM enrolls_to e WHERE e.uid = u.userid) AS plan_expire
        FROM users u 
        WHERE u.biometric_id IS NOT NULL 
        ORDER BY u.biometric_id ASC";

$res = mysqli_query($con, $sql);
$data = [];

if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $status = 'inactive';
        
        if (intval($row['biometric_enabled']) === 1 && !empty($row['plan_expire'])) {
            $expire_date = $row['plan_expire'];
            if (strtotime($expire_date) >= strtotime($today)) {
                $status = 'active';
            }
        }
        
        $data[] = [
            'userid' => $row['userid'],
            'username' => $row['username'],
            'biometric_id' => intval($row['biometric_id']),
            'biometric_enabled' => intval($row['biometric_enabled']),
            'plan_expire' => $row['plan_expire'],
            'photo' => !empty($row['photo']) ? $row['photo'] : 'images/default_avatar.jpg',
            'status' => $status,
            'pending_enrollment' => (bool)$row['pending_enrollment']
        ];
    }
}

echo json_encode($data, JSON_PRETTY_PRINT);
