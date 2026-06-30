<?php
// cron_reminders.php
// To be run via Hostinger Cron Job every morning (e.g. 0 8 * * *)
// Command: /usr/bin/php /home/username/public_html/cron_reminders.php

require_once __DIR__ . '/include/db_conn.php';
require_once __DIR__ . '/include/whatsapp_api.php';

// 1. Birthday Reminders
$today_md = date('m-d');
$bday_query = "SELECT username, mobile FROM users WHERE DATE_FORMAT(dob, '%m-%d') = '$today_md'";
$bday_res = mysqli_query($con, $bday_query);

if ($bday_res) {
    while ($row = mysqli_fetch_assoc($bday_res)) {
        if (!empty($row['mobile'])) {
            $msg = "🎉 Happy Birthday " . $row['username'] . "! 🎂\n\nWishing you a fantastic day and another year of crushing your fitness goals with Sudarshan Fitness! Have an amazing one! 💪🔥";
            sendWhatsAppMessage($row['mobile'], $msg);
        }
    }
}

// 2. Expiry Reminders (3 days warning)
$expiry_target = date('Y-m-d', strtotime('+3 days'));
$exp_query = "SELECT u.username, u.mobile, p.planName 
              FROM users u 
              JOIN enrolls_to e ON u.userid = e.uid 
              JOIN plan p ON e.pid = p.pid 
              WHERE e.expire = '$expiry_target'";
$exp_res = mysqli_query($con, $exp_query);

if ($exp_res) {
    while ($row = mysqli_fetch_assoc($exp_res)) {
        if (!empty($row['mobile'])) {
            $msg = "⚠️ Reminder: Your Gym Membership is Expiring Soon!\n\nHi " . $row['username'] . ",\nYour " . $row['planName'] . " plan will expire in exactly 3 days.\n\nPlease renew it soon via the reception or your member dashboard to avoid any interruption in your workouts. Keep grinding! 💪";
            sendWhatsAppMessage($row['mobile'], $msg);
        }
    }
}

echo "Reminders processed successfully at " . date('Y-m-d H:i:s');
?>
