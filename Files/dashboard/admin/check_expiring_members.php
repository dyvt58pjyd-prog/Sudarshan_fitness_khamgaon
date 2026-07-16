<?php
// Expiry Check Script
// Can be run via CLI/Cron, or included by admin/index.php

$is_cli = (php_sapi_name() === 'cli');

if (!$is_cli) {
    // If run via web, require db connection and check session protection
    require_once __DIR__ . '/../../include/db_conn.php';
    page_protect();
} else {
    // If run via CLI, make sure we connect to local DB
    $_SERVER['SERVER_NAME'] = 'localhost';
    require_once __DIR__ . '/../../include/db_conn.php';
}

if (!function_exists('send_automated_expiry_email')) {
    function send_automated_expiry_email($con, $email, $name, $planName, $expire, $daysLeft, $gym_name, $gym_email) {
        $subject = "Membership Expiry Notice - $gym_name";
        
        // Compute message text
        $day_word = ($daysLeft === 1) ? "day" : "days";
        
        // Cyberpunk/Futuristic HTML email template
        $mail_body = "
        <html>
        <head>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f8fafc; color: #1e293b; padding: 30px; margin: 0; }
                .container { background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 40px; max-width: 600px; margin: 0 auto; box-shadow: 0 4px 20px rgba(0,0,0,0.05); position: relative; overflow: hidden; }
                .top-line { position: absolute; top: 0; left: 0; right: 0; height: 5px; background: linear-gradient(90deg, #ff6b00, #ff8c00); }
                h2 { color: #ff6b00; font-size: 22px; font-weight: 700; margin-top: 10px; margin-bottom: 20px; }
                p { font-size: 14px; line-height: 1.6; color: #475569; }
                .warning-box { background-color: rgba(255, 107, 0, 0.05); border: 1px dashed rgba(255, 107, 0, 0.3); padding: 25px; margin: 25px 0; border-radius: 12px; font-size: 14px; line-height: 1.6; }
                .warning-box strong { color: #ff6b00; }
                .warning-box code { background-color: rgba(255, 107, 0, 0.1); color: #ff6b00; padding: 2px 6px; border-radius: 4px; font-size: 13px; font-weight: bold; }
                .renew-msg { font-size: 16px; font-weight: bold; color: #ff6b00; text-align: center; margin: 20px 0; border: 1px solid rgba(255,107,0,0.2); padding: 15px; border-radius: 8px; background: rgba(255,107,0,0.02); }
                .footer { margin-top: 35px; font-size: 12px; color: #94a3b8; text-align: center; border-top: 1px solid #e2e8f0; padding-top: 20px; line-height: 1.5; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='top-line'></div>
                <h2>Membership Expiry Alert</h2>
                <p>Dear <strong>$name</strong>,</p>
                <p>This is an automated notification from <strong>$gym_name</strong> to inform you that your gym membership is expiring soon.</p>
                
                <div class='warning-box'>
                    <strong>Active Subscription Details:</strong><br>
                    Current Plan: <code>$planName</code><br>
                    Time Remaining: <strong>$daysLeft $day_word</strong><br>
                    Expiration Date: <code>$expire</code>
                </div>
                
                <div class='renew-msg'>
                    renew on date = $expire
                </div>
                
                <p>To prevent any service interruption at the front gate scanner, please renewal your membership package on or before the expiration date.</p>
                
                <div class='footer'>
                    This is an automated notification from $gym_name.<br>
                    Need assistance? Contact us at: <a href='mailto:$gym_email' style='color: #ff6b00; text-decoration: none;'>$gym_email</a>
                </div>
            </div>
        </body>
        </html>";

        // 1. Send SMTP if configured, else fall back to native PHP email
        require_once __DIR__ . '/../../include/smtp_mailer.php';
        $sent = send_smtp_email($email, $name, $subject, $mail_body);

        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: $gym_email\r\n";
        $headers .= "Reply-To: $gym_email\r\n";

        if (!$sent) {
            $sent = @mail($email, $subject, $mail_body, $headers);
        }

        // 2. Write locally to log file for verification
        $log_path = __DIR__ . "/../../include/email_log.txt";
        $log_entry = "========================================================\n";
        $log_entry .= "DATE: " . date('Y-m-d H:i:s') . "\n";
        $log_entry .= "TO: $email\n";
        $log_entry .= "SUBJECT: $subject\n";
        $log_entry .= "BODY:\n" . strip_tags($mail_body) . "\n";
        $log_entry .= "========================================================\n\n";
        @file_put_contents($log_path, $log_entry, FILE_APPEND);

        return $sent;
    }
}

if (!function_exists('send_whatsapp_expiry_notice')) {
    function send_whatsapp_expiry_notice($mobile, $name, $plan, $expire, $days, $gym_name) {
        if (empty($mobile)) {
            return false;
        }

        // WhatsApp message body text format
        $day_word = ($days === 1) ? "day" : "days";
        $message = "🏋️ *{$gym_name}* Expiry Alert 🏋️\n\n" .
                   "Dear *{$name}*,\n\n" .
                   "This is a reminder that your active subscription *({$plan})* is expiring in *{$days} {$day_word}* (on *{$expire}*).\n\n" .
                   "Please renew your membership package on or before the expiration date to prevent scan lockages.\n\n" .
                   "Access your member console anytime to check your receipts and details at:\n" .
                   "👉 https://sudarshanfitness.de\n\n" .
                   "Thank you,\n" .
                   "*{$gym_name}*";

        global $con;
        return enqueue_whatsapp_message($con, $mobile, $message);
    }
}

// Retrieve gym settings
$gym = get_gym_details($con);
$gym_name = $gym['gym_name'];
$gym_email = $gym['gym_email'];

$alerts_sent = 0;

// Scan for memberships expiring in 1, 2, 3, 4, or 5 days
for ($days = 1; $days <= 5; $days++) {
    $target_date = date('Y-m-d', strtotime("+$days days"));
    
    // Select members whose LATEST plan expires on the target date (including mobile number)
    $q = "SELECT u.userid, u.username, u.email, u.mobile, e.expire, e.pid, p.planName 
          FROM users u
          INNER JOIN enrolls_to e ON u.userid = e.uid
          INNER JOIN plan p ON e.pid = p.pid
          WHERE e.expire = '$target_date'
            AND e.renewal = 'yes'
            AND e.expire = (
                SELECT MAX(e2.expire) 
                FROM enrolls_to e2 
                WHERE e2.uid = e.uid
            )";
            
    $res = mysqli_query($con, $q);
    if ($res && mysqli_num_rows($res) > 0) {
        while ($row = mysqli_fetch_assoc($res)) {
            $email = $row['email'];
            $name = $row['username'];
            $plan = $row['planName'];
            $expire = $row['expire'];
            $mobile = $row['mobile'];
            
            send_automated_expiry_email($con, $email, $name, $plan, $expire, $days, $gym_name, $gym_email);
            send_whatsapp_expiry_notice($mobile, $name, $plan, $expire, $days, $gym_name);
            $alerts_sent++;
        }
    }
}

if ($is_cli) {
    echo "Check completed. Sent $alerts_sent automated expiry alert(s).\n";
}
?>
