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
            fclose($fp);
            return false;
        }
        
        // EHLO
        fputs($fp, "EHLO localhost\r\n");
        $resp = $read_response($fp);
        if (substr($resp, 0, 3) !== '250') {
            fclose($fp);
            return false;
        }
        
        // If secure is 'tls', send STARTTLS
        if ($secure === 'tls') {
            fputs($fp, "STARTTLS\r\n");
            $resp = $read_response($fp);
            if (substr($resp, 0, 3) !== '220') {
                fclose($fp);
                return false;
            }
            // Enable encryption on socket
            if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
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
            fclose($fp);
            return false;
        }
        
        // Send base64 username
        fputs($fp, base64_encode($username) . "\r\n");
        $resp = $read_response($fp);
        if (substr($resp, 0, 3) !== '334') {
            fclose($fp);
            return false;
        }
        
        // Send base64 password
        fputs($fp, base64_encode($password) . "\r\n");
        $resp = $read_response($fp);
        if (substr($resp, 0, 3) !== '235') {
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
?>
