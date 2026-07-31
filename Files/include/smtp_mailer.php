<?php
if (!function_exists('send_smtp_email')) {
    function send_smtp_email($to_email, $to_name, $subject, $html_body, $attachment_path = null, $attachment_filename = null, $sender_role = 'admin') {
        global $con;
        
        // Fetch SMTP Settings
        $res = mysqli_query($con, "SELECT * FROM smtp_settings WHERE id = 1");
        if (!$res || mysqli_num_rows($res) === 0) {
            return false;
        }
        $smtp = mysqli_fetch_assoc($res);
        
        // If SMTP username is not configured, fall back to native mail
        if (empty($smtp['smtp_username'])) {
            return false;
        }
        
        $host = $smtp['smtp_host'];
        $port = intval($smtp['smtp_port']);
        $secure = strtolower($smtp['smtp_secure']); // 'ssl' or 'tls' or 'none'

        // Dynamic Role-Based Sender Configuration
        if ($sender_role === 'payments') {
            $username = !empty($smtp['smtp_user_payments']) ? $smtp['smtp_user_payments'] : $smtp['smtp_username'];
            $password = !empty($smtp['smtp_pass_payments']) ? $smtp['smtp_pass_payments'] : $smtp['smtp_password'];
            $from_name = !empty($smtp['smtp_name_payments']) ? $smtp['smtp_name_payments'] : 'Sudarshan Fitness Billing';
        } else if ($sender_role === 'recovery') {
            $username = !empty($smtp['smtp_user_recovery']) ? $smtp['smtp_user_recovery'] : $smtp['smtp_username'];
            $password = !empty($smtp['smtp_pass_recovery']) ? $smtp['smtp_pass_recovery'] : $smtp['smtp_password'];
            $from_name = !empty($smtp['smtp_name_recovery']) ? $smtp['smtp_name_recovery'] : 'Sudarshan Fitness Security';
        } else if ($sender_role === 'cyber.officer') {
            $username = !empty($smtp['smtp_user_cyber']) ? $smtp['smtp_user_cyber'] : $smtp['smtp_username'];
            $password = !empty($smtp['smtp_pass_cyber']) ? $smtp['smtp_pass_cyber'] : $smtp['smtp_password'];
            $from_name = !empty($smtp['smtp_name_cyber']) ? $smtp['smtp_name_cyber'] : 'Sudarshan Fitness Cyber Defense';
        } else {
            $username = $smtp['smtp_username'];
            $password = $smtp['smtp_password'];
            $from_name = !empty($smtp['smtp_from_name']) ? $smtp['smtp_from_name'] : 'Sudarshan Fitness System';
        }
        
        $from_email = $username;
        
        // Setup stream context to verify SSL/TLS against the original hostname (SNI)
        $context = stream_context_create([
            'ssl' => [
                'peer_name' => $host,
                'verify_peer' => true,
                'verify_peer_name' => true
            ]
        ]);
        
        // Connect to server: try direct hostname first, fallback to resolved IPv4 if fails
        $socket_host = ($secure === 'ssl') ? "ssl://$host" : "tcp://$host";
        $fp = @stream_socket_client($socket_host . ':' . $port, $errno, $errstr, 5, STREAM_CLIENT_CONNECT, $context);
        if (!$fp) {
            $ip = gethostbyname($host);
            $socket_host_fallback = ($secure === 'ssl') ? "ssl://$ip" : "tcp://$ip";
            $fp = @stream_socket_client($socket_host_fallback . ':' . $port, $errno, $errstr, 5, STREAM_CLIENT_CONNECT, $context);
        }
        
        if (!$fp) {
            $log_entry = "[" . date('Y-m-d H:i:s') . "] [SMTP CONNECTION ERROR] Failed to connect to $secure://$host:$port. Error: $errstr ($errno)\n";
            @file_put_contents(__DIR__ . "/email_log.txt", $log_entry, FILE_APPEND);
            return false;
        }
        
        // Helper to read server response
        $read_response = function($fp) {
            $response = "";
            while ($str = fgets($fp, 515)) {
                $response .= $str;
                if (substr($str, 3, 1) === " ") {
                    break;
                }
            }
            return $response;
        };
        
        // Read greeting
        $resp = $read_response($fp);
        if (substr($resp, 0, 3) !== '220') {
            @file_put_contents(__DIR__ . "/email_log.txt", "[" . date('Y-m-d H:i:s') . "] [SMTP GREETING FAIL] Host: $host, Resp: $resp\n", FILE_APPEND);
            fclose($fp);
            return false;
        }
        
        // EHLO
        fputs($fp, "EHLO localhost\r\n");
        $resp = $read_response($fp);
        if (substr($resp, 0, 3) !== '250') {
            @file_put_contents(__DIR__ . "/email_log.txt", "[" . date('Y-m-d H:i:s') . "] [SMTP EHLO FAIL] Resp: $resp\n", FILE_APPEND);
            fclose($fp);
            return false;
        }
        
        // If secure is 'tls', send STARTTLS
        if ($secure === 'tls') {
            fputs($fp, "STARTTLS\r\n");
            $resp = $read_response($fp);
            if (substr($resp, 0, 3) !== '220') {
                @file_put_contents(__DIR__ . "/email_log.txt", "[" . date('Y-m-d H:i:s') . "] [SMTP STARTTLS FAIL] Resp: $resp\n", FILE_APPEND);
                fclose($fp);
                return false;
            }
            // Enable encryption on socket
            if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                @file_put_contents(__DIR__ . "/email_log.txt", "[" . date('Y-m-d H:i:s') . "] [SMTP CRYPTO FAIL] TLS negotiation failed\n", FILE_APPEND);
                fclose($fp);
                return false;
            }
            // Send EHLO again after STARTTLS
            fputs($fp, "EHLO localhost\r\n");
            $resp = $read_response($fp);
            if (substr($resp, 0, 3) !== '250') {
                fclose($fp);
                return false;
            }
        }
        
        // AUTH LOGIN
        fputs($fp, "AUTH LOGIN\r\n");
        $resp = $read_response($fp);
        if (substr($resp, 0, 3) !== '334') {
            @file_put_contents(__DIR__ . "/email_log.txt", "[" . date('Y-m-d H:i:s') . "] [SMTP AUTH INIT FAIL] Resp: $resp\n", FILE_APPEND);
            fclose($fp);
            return false;
        }
        
        // Send base64 username
        fputs($fp, base64_encode($username) . "\r\n");
        $resp = $read_response($fp);
        if (substr($resp, 0, 3) !== '334') {
            @file_put_contents(__DIR__ . "/email_log.txt", "[" . date('Y-m-d H:i:s') . "] [SMTP USERNAME FAIL] User: $username, Resp: $resp\n", FILE_APPEND);
            fclose($fp);
            return false;
        }
        
        // Send base64 password
        fputs($fp, base64_encode($password) . "\r\n");
        $resp = $read_response($fp);
        if (substr($resp, 0, 3) !== '235') {
            @file_put_contents(__DIR__ . "/email_log.txt", "[" . date('Y-m-d H:i:s') . "] [SMTP PASS FAIL] Resp: $resp\n", FILE_APPEND);
            fclose($fp);
            return false;
        }
        
        // MAIL FROM
        fputs($fp, "MAIL FROM:<" . $from_email . ">\r\n");
        $resp = $read_response($fp);
        if (substr($resp, 0, 3) !== '250') {
            fclose($fp);
            return false;
        }
        
        // RCPT TO
        fputs($fp, "RCPT TO:<" . $to_email . ">\r\n");
        $resp = $read_response($fp);
        if (substr($resp, 0, 3) !== '250' && substr($resp, 0, 3) !== '251') {
            fclose($fp);
            return false;
        }
        
        // DATA
        fputs($fp, "DATA\r\n");
        $resp = $read_response($fp);
        if (substr($resp, 0, 3) !== '354') {
            fclose($fp);
            return false;
        }
        
        // Construct standard mail headers and body
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "From: =?UTF-8?B?" . base64_encode($from_name) . "?= <" . $from_email . ">\r\n";
        $headers .= "To: =?UTF-8?B?" . base64_encode($to_name) . "?= <" . $to_email . ">\r\n";
        $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
        $headers .= "Date: " . date('r') . "\r\n";
        $headers .= "Message-ID: <" . md5(uniqid(time())) . "@" . $host . ">\r\n";
        
        if ($attachment_path && file_exists($attachment_path)) {
            $boundary = md5(uniqid(time()));
            $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";
            
            $body = "--$boundary\r\n";
            $body .= "Content-Type: text/html; charset=UTF-8\r\n";
            $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
            $body .= $html_body . "\r\n\r\n";
            
            $file_content = file_get_contents($attachment_path);
            $file_encoded = chunk_split(base64_encode($file_content), 76, "\r\n");
            $filename = !empty($attachment_filename) ? $attachment_filename : basename($attachment_path);
            
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $mime = 'application/octet-stream';
            if ($ext === 'pdf') {
                $mime = 'application/pdf';
            } elseif ($ext === 'sql') {
                $mime = 'application/sql';
            } elseif ($ext === 'csv') {
                $mime = 'text/csv';
            } elseif ($ext === 'json') {
                $mime = 'application/json';
            }
            
            $body .= "--$boundary\r\n";
            $body .= "Content-Type: $mime; name=\"$filename\"\r\n";
            $body .= "Content-Transfer-Encoding: base64\r\n";
            $body .= "Content-Disposition: attachment; filename=\"$filename\"\r\n\r\n";
            $body .= $file_encoded . "\r\n";
            $body .= "--$boundary--\r\n";
        } else {
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $body = $html_body;
        }
        
        $email_content = $headers . "\r\n" . $body . "\r\n.\r\n";
        
        // Write the data in chunks of 8192 bytes to avoid SSL buffer issues / Broken pipe errors
        $length = strlen($email_content);
        $written = 0;
        $write_success = true;
        while ($written < $length) {
            $chunk = substr($email_content, $written, 8192);
            $res_write = @fputs($fp, $chunk);
            if ($res_write === false || $res_write === 0) {
                $write_success = false;
                break;
            }
            $written += $res_write;
        }
        
        $resp = $read_response($fp);
        
        // QUIT
        fputs($fp, "QUIT\r\n");
        fclose($fp);
        
        $success = $write_success && (substr($resp, 0, 3) === '250');
        if (!$success) {
            $log_entry = "[" . date('Y-m-d H:i:s') . "] [SMTP DISPATCH ERROR] Server rejected message or write failed. Response: $resp\n";
            @file_put_contents(__DIR__ . "/email_log.txt", $log_entry, FILE_APPEND);
        }
        
        return $success;
    }
}

if (!function_exists('send_member_qr_pass_email')) {
    function send_member_qr_pass_email($con, $to_email, $to_name, $userid, $plan_name = 'Active Subscription', $expire_date = 'N/A') {
        if (empty($to_email) || strpos($to_email, '@sudarshanfitness.local') !== false) {
            return false;
        }

        $gym = get_gym_details($con);
        $gym_name = !empty($gym['gym_name']) ? $gym['gym_name'] : 'Sudarshan Fitness Khamgaon';
        $logo_url = !empty($gym['gym_logo']) ? $gym['gym_logo'] : 'https://sudarshanfitness.de/logo192.png';
        
        $qr_image_url = "https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=" . urlencode($userid);
        $expire_fmt = ($expire_date !== 'N/A' && !empty($expire_date)) ? date('d-M-Y', strtotime($expire_date)) : 'Active';

        $subject = "📷 Your Official Gym Entrance QR Pass - {$gym_name} (ID: {$userid})";

        $html_body = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <style>
                body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #0b0f19; margin: 0; padding: 20px; color: #ffffff; }
                .card { max-width: 550px; margin: 0 auto; background: #1e293b; border-radius: 20px; border: 2px solid #ff6b00; overflow: hidden; box-shadow: 0 15px 35px rgba(0,0,0,0.5); }
                .header { background: linear-gradient(135deg, #0f172a, #1e293b); padding: 25px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
                .logo { max-height: 50px; margin-bottom: 10px; }
                .title { color: #ff6b00; font-size: 20px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; margin: 0; }
                .subtitle { color: #94a3b8; font-size: 12px; margin-top: 4px; }
                .body-content { padding: 30px; text-align: center; }
                .greeting { font-size: 18px; font-weight: 700; color: #ffffff; margin-bottom: 15px; }
                .instructions { font-size: 13px; color: #cbd5e1; line-height: 1.6; margin-bottom: 25px; }
                .qr-container { background: #ffffff; padding: 20px; border-radius: 16px; display: inline-block; box-shadow: 0 10px 25px rgba(0,0,0,0.4); margin-bottom: 20px; }
                .qr-image { width: 220px; height: 220px; display: block; margin: 0 auto; }
                .member-details { background: rgba(0,0,0,0.3); border-radius: 14px; padding: 15px; margin-top: 15px; border: 1px solid rgba(255,255,255,0.08); text-align: left; }
                .detail-row { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 13px; }
                .detail-row:last-child { border-bottom: none; }
                .detail-label { color: #94a3b8; font-weight: 600; }
                .detail-val { color: #ffffff; font-weight: 700; }
                .footer { background: #0f172a; padding: 20px; text-align: center; font-size: 11px; color: #64748b; border-top: 1px solid rgba(255,255,255,0.05); }
                .btn { display: inline-block; background: linear-gradient(135deg, #ff6b00, #ff8800); color: #ffffff; text-decoration: none; padding: 12px 25px; border-radius: 12px; font-weight: 800; font-size: 13px; margin-top: 15px; box-shadow: 0 4px 15px rgba(255,107,0,0.4); }
            </style>
        </head>
        <body>
            <div class='card'>
                <div class='header'>
                    <img src='{$logo_url}' class='logo' alt='{$gym_name}'><br>
                    <div class='title'>📷 DIGITAL ENTRANCE QR PASS</div>
                    <div class='subtitle'>Official Member Gate Access Pass</div>
                </div>
                <div class='body-content'>
                    <div class='greeting'>Hello, " . htmlspecialchars($to_name) . "! 👋</div>
                    <div class='instructions'>
                        Below is your official <strong>Sudarshan Fitness Entrance QR Pass</strong>. You can save this email, download the QR code image, or take a screenshot to scan at the gym entrance terminal for daily sub-second check-in and check-out.
                    </div>
                    
                    <div class='qr-container'>
                        <img src='{$qr_image_url}' class='qr-image' alt='Member QR Code'>
                        <div style='color: #0f172a; font-weight: 800; font-size: 12px; margin-top: 8px; text-transform: uppercase;'>{$gym_name}</div>
                    </div>

                    <div class='member-details'>
                        <div class='detail-row'>
                            <span class='detail-label'>Member Name</span>
                            <span class='detail-val'>" . htmlspecialchars($to_name) . "</span>
                        </div>
                        <div class='detail-row'>
                            <span class='detail-label'>Membership ID</span>
                            <span class='detail-val' style='color: #38bdf8;'>" . htmlspecialchars($userid) . "</span>
                        </div>
                        <div class='detail-row'>
                            <span class='detail-label'>Active Plan</span>
                            <span class='detail-val'>" . htmlspecialchars($plan_name) . "</span>
                        </div>
                        <div class='detail-row'>
                            <span class='detail-label'>Plan Expiration Date</span>
                            <span class='detail-val' style='color: #10b981;'>{$expire_fmt}</span>
                        </div>
                    </div>

                    <div style='margin-top: 25px; text-align: center;'>
                        <a href='https://sudarshanfitness.de/Files/download_app.php' style='display: block; width: 100%; box-sizing: border-box; background: linear-gradient(135deg, #ff003c, #7000ff); color: #ffffff; text-decoration: none; padding: 16px 20px; border-radius: 14px; font-weight: 800; font-size: 15px; box-shadow: 0 6px 25px rgba(255,0,60,0.5); text-transform: uppercase; letter-spacing: 0.5px;'>📲 INSTALL APPLICATION ON PHONE (DIRECT 1-CLICK APK)</a>
                        <div style='font-size: 11px; color: #94a3b8; margin-top: 8px;'>Tap to install Sudarshan Fitness native app (5.3 MB) directly on your phone</div>
                    </div>

                    <div style='margin-top: 15px; text-align: center;'>
                        <a href='https://sudarshanfitness.de/qr_checkin.php' class='btn' style='display: inline-block; background: rgba(255,255,255,0.08); color: #cbd5e1; text-decoration: none; padding: 10px 20px; border-radius: 10px; font-weight: 600; font-size: 12px; border: 1px solid rgba(255,255,255,0.15);'>🚀 Open Live QR Scanner Terminal</a>
                    </div>
                </div>
                <div class='footer'>
                    This email contains your personal entrance QR pass for {$gym_name}. Please do not share this QR pass with non-members.<br>
                    Engineered by <strong>Anurag Bawaskar</strong> | Sudarshan Fitness System
                </div>
            </div>
        </body>
        </html>
        ";

        $sent = send_smtp_email($to_email, $to_name, $subject, $html_body);
        if (!$sent) {
            // Fallback to native PHP mail server
            $from_header_name = !empty($gym_name) ? $gym_name : 'Sudarshan Fitness';
            $headers  = "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "From: =?UTF-8?B?" . base64_encode($from_header_name) . "?= <info@sudarshanfitness.de>\r\n";
            $headers .= "Reply-To: info@sudarshanfitness.de\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion();
            $sent = @mail($to_email, $subject, $html_body, $headers);
        }
        return $sent;
    }
}
?>
