<?php
// CRON JOB SCRIPT: Runs daily to send Expiry Reminders
// Usage: curl -s https://yourdomain.com/Files/api/cron_reminders.php

require_once '../include/db_conn.php';
require_once '../include/whatsapp_core.php';
require_once '../include/smtp_mailer.php';

$gym = get_gym_details($con);

date_default_timezone_set("Asia/Calcutta");
$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$three_days = date('Y-m-d', strtotime('+3 days'));

echo "Running Cron Reminders for " . $today . "<br>";

$query = "SELECT e.uid, e.expire, u.username, u.mobile, u.email 
          FROM enrolls_to e 
          JOIN users u ON e.uid = u.userid 
          WHERE e.renewal = 'yes' 
          AND (e.expire = '$today' OR e.expire = '$tomorrow' OR e.expire = '$three_days')";

$res = mysqli_query($con, $query);

if ($res && mysqli_num_rows($res) > 0) {
    while ($row = mysqli_fetch_assoc($res)) {
        $uid = $row['uid'];
        $expire_date = $row['expire'];
        $name = $row['username'];
        $mobile = $row['mobile'];
        $email = $row['email'];
        
        $days_left = 0;
        $reminder_type = "0_days";
        
        if ($expire_date === $tomorrow) {
            $days_left = 1;
            $reminder_type = "1_days";
        } elseif ($expire_date === $three_days) {
            $days_left = 3;
            $reminder_type = "3_days";
        }
        
        // Check if already sent today to prevent spam
        $chk = mysqli_query($con, "SELECT id FROM reminders_log WHERE uid='$uid' AND reminder_type='$reminder_type' AND sent_date='$today'");
        if ($chk && mysqli_num_rows($chk) > 0) {
            continue; // Already sent
        }
        
        // Prepare Message
        $msg = "Hi $name! 🏋️\n\n";
        if ($days_left == 0) {
            $msg .= "Your membership at " . $gym['gym_name'] . " *EXPIRES TODAY* ($expire_date).\n\n";
        } else {
            $msg .= "Your membership at " . $gym['gym_name'] . " is expiring in *$days_left days* (on $expire_date).\n\n";
        }
        $msg .= "Please login to your portal to renew instantly via UPI to avoid any interruptions to your fitness journey!\n\n";
        $msg .= "Thank you,\n" . $gym['gym_name'];
        
        // Send WhatsApp
        send_meta_whatsapp_message($con, $mobile, $msg);
        
        // Send Email (We can use a simple mail function or SMTP)
        // Note: For simplicity in cron, we will use basic mail() if SMTP isn't fully configured for bulk, or just log it.
        $subject = "Membership Expiry Reminder - " . $gym['gym_name'];
        send_smtp_email($email, $name, $subject, nl2br($msg), 'admin');
        
        // Log it
        mysqli_query($con, "INSERT INTO reminders_log (uid, reminder_type, sent_date) VALUES ('$uid', '$reminder_type', '$today')");
        
        echo "Sent reminder ($reminder_type) to $name ($uid).<br>";
    }
} else {
    echo "No expiring memberships found for today.";
}
?>
