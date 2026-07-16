<?php
// cron_whatsapp_alerts.php
// This script should be run daily via a server CRON job.
// It finds all memberships expiring in exactly 1, 3, or 5 days and sends them an automated WhatsApp reminder via Meta Cloud API.

require __DIR__ . '/../include/db_conn.php';
require __DIR__ . '/../include/whatsapp_core.php';

// Only allow execution from CLI or with a secret key if accessed via web
if (php_sapi_name() !== 'cli' && (!isset($_GET['key']) || $_GET['key'] !== 'TITAN_GYM_SECRET_KEY_123')) {
    header("HTTP/1.1 403 Forbidden");
    exit('Unauthorized access.');
}

$gym = get_gym_details($con);
$gym_name = $gym['gym_name'];

$days_to_check = [1, 3, 5];
$messages_sent = 0;
$errors = 0;

echo "Starting WhatsApp Auto-Alerts for " . date('Y-m-d H:i:s') . "\n";
echo "=========================================================\n";

foreach ($days_to_check as $days) {
    $target_date = date('Y-m-d', strtotime("+$days days"));
    
    $q = "SELECT u.username, u.mobile, e.expire, p.planName 
          FROM users u
          INNER JOIN enrolls_to e ON u.userid = e.uid
          INNER JOIN plan p ON e.pid = p.pid
          WHERE e.expire = '$target_date'
            AND e.renewal = 'yes'
            AND e.expire = (
                SELECT MAX(e2.expire) 
                FROM enrolls_to e2 
                WHERE e2.uid = u.userid
            )";
            
    $res = mysqli_query($con, $q);
    
    if ($res && mysqli_num_rows($res) > 0) {
        $day_word = ($days === 1) ? "day" : "days";
        
        while ($row = mysqli_fetch_assoc($res)) {
            $name = $row['username'];
            $mobile = $row['mobile'];
            $plan = $row['planName'];
            $expire = $row['expire'];
            
            $msg = "Hello $name,\n\nYour membership for '$plan' at $gym_name is expiring in $days $day_word on $expire.\n\nPlease renew it soon to continue your fitness journey!\n\nRegards,\n$gym_name";
            
            $result = send_meta_whatsapp_message($con, $mobile, $msg);
            
            if ($result['success']) {
                echo "[SUCCESS] Sent $days-day alert to $name ($mobile)\n";
                $messages_sent++;
            } else {
                echo "[FAILED] Error sending to $name ($mobile): " . $result['message'] . "\n";
                $errors++;
            }
            
            // Sleep for 100ms to avoid hitting Meta rate limits
            usleep(100000);
        }
    }
}

echo "=========================================================\n";
echo "Finished. Total Sent: $messages_sent. Errors: $errors.\n";
?>
