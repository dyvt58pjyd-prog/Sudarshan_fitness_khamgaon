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

if (!function_exists('get_animated_stickers_catalog')) {
    function get_animated_stickers_catalog() {
        return [
            'fitness_fire' => [
                'name' => '🔥 Flame Workout Streak',
                'emoji' => '🔥',
                'badge' => 'ENERGY BOOST',
                'icon_html' => '<span style="font-size:38px; display:inline-block; animation:pulse 1.5s infinite;">🔥</span>'
            ],
            'gym_dumbbell' => [
                'name' => '🏋️ Power Dumbbell Pump',
                'emoji' => '🏋️',
                'badge' => 'IRON POWER',
                'icon_html' => '<span style="font-size:38px; display:inline-block;">🏋️</span>'
            ],
            'timer_alert' => [
                'name' => '⏱️ Urgency Countdown Clock',
                'emoji' => '⏱️',
                'badge' => 'LIMITED TIME',
                'icon_html' => '<span style="font-size:38px; display:inline-block;">⏱️</span>'
            ],
            'trophy_win' => [
                'name' => '🏆 Golden Champion Trophy',
                'emoji' => '🏆',
                'badge' => 'VERIFIED PAYMENT',
                'icon_html' => '<span style="font-size:38px; display:inline-block;">🏆</span>'
            ],
            'party_sparkles' => [
                'name' => '🎉 Birthday Celebration Sparkles',
                'emoji' => '🎉',
                'badge' => 'VIP CELEBRATION',
                'icon_html' => '<span style="font-size:38px; display:inline-block;">🎉</span>'
            ],
            'shield_pass' => [
                'name' => '🛡️ Biometric Security Pass',
                'emoji' => '🛡️',
                'badge' => 'OFFICIAL ACCESS',
                'icon_html' => '<span style="font-size:38px; display:inline-block;">🛡️</span>'
            ]
        ];
    }
}

if (!function_exists('get_email_template')) {
    function get_email_template($con, $template_key) {
        $key_esc = mysqli_real_escape_string($con, $template_key);
        $q = mysqli_query($con, "SELECT * FROM email_templates WHERE template_key = '$key_esc' LIMIT 1");
        if ($q && mysqli_num_rows($q) > 0) {
            return mysqli_fetch_assoc($q);
        }
        
        // Fallback defaults
        return [
            'template_key' => $template_key,
            'template_name' => ucwords(str_replace('_', ' ', $template_key)),
            'subject' => "Notification from {gym_name}",
            'badge_title' => "OFFICIAL GYM NOTIFICATION",
            'badge_icon' => "🏋️",
            'accent_color' => "#ff6b00",
            'greeting' => "Hello, {member_name}! 👋",
            'intro_text' => "Important update regarding your membership account at {gym_name}.",
            'body_content' => "Please review the updated information and contact gym reception if you have any questions.",
            'promo_banner' => "⭐ Check out our latest fitness routines and track your streak in the member app!",
            'cta_text' => "OPEN MEMBER PORTAL",
            'cta_url' => "https://sudarshanfitness.de",
            'footer_text' => "Engineered by Sudarshan Fitness System. Do not share personal access credentials.",
            'animated_sticker' => "fitness_fire"
        ];
    }
}

if (!function_exists('render_email_template_html')) {
    function render_email_template_html($template, $placeholders = [], $gym = null) {
        if (!$gym) {
            global $con;
            $gym = function_exists('get_gym_details') ? get_gym_details($con) : [];
        }

        $gym_name    = !empty($gym['gym_name']) ? $gym['gym_name'] : 'Sudarshan Fitness Khamgaon';
        $gym_address = !empty($gym['gym_address']) ? $gym['gym_address'] : 'Station Road, Khamgaon';
        $gym_phone   = !empty($gym['gym_contact']) ? $gym['gym_contact'] : '9325205075';
        $gym_email   = !empty($gym['gym_email']) ? $gym['gym_email'] : 'sudarshan.fitness.khm@gmail.com';
        $logo_url    = !empty($gym['gym_logo']) ? $gym['gym_logo'] : 'https://sudarshanfitness.de/logo192.png';

        // Standard merge values
        $defaults = [
            '{gym_name}'    => $gym_name,
            '{gym_address}' => $gym_address,
            '{gym_phone}'   => $gym_phone,
            '{gym_email}'   => $gym_email,
            '{logo_url}'    => $logo_url,
            '{member_name}' => 'Valued Member',
            '{userid}'      => 'MEM-001',
            '{plan_name}'   => 'Annual Fitness Tier',
            '{expire_date}' => date('d-M-Y', strtotime('+30 days')),
            '{days_left}'   => '5 Days',
            '{amount}'      => '8,000',
            '{receipt_no}'  => 'REC-' . date('Ymd') . '-01',
            '{qr_image_url}'=> 'https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=DEMO_PASS',
            '{current_year}'=> date('Y')
        ];

        $merged = array_merge($defaults, $placeholders);

        $replace_vars = function($str) use ($merged) {
            return str_replace(array_keys($merged), array_values($merged), (string)$str);
        };

        $subject       = $replace_vars($template['subject'] ?? 'Notification');
        $badge_title   = $replace_vars($template['badge_title'] ?? 'GYM NOTICE');
        $badge_icon    = $template['badge_icon'] ?? '🏋️';
        $accent_color  = !empty($template['accent_color']) ? $template['accent_color'] : '#ff6b00';
        $greeting      = $replace_vars($template['greeting'] ?? 'Hello!');
        $intro_text    = $replace_vars($template['intro_text'] ?? '');
        $body_content  = $replace_vars($template['body_content'] ?? '');
        $promo_banner  = $replace_vars($template['promo_banner'] ?? '');
        $cta_text      = $replace_vars($template['cta_text'] ?? 'VIEW PORTAL');
        $cta_url       = $replace_vars($template['cta_url'] ?? 'https://sudarshanfitness.de');
        $footer_text   = $replace_vars($template['footer_text'] ?? '');
        $sticker_key   = $template['animated_sticker'] ?? 'fitness_fire';

        $stickers = get_animated_stickers_catalog();
        $sticker_data = $stickers[$sticker_key] ?? $stickers['fitness_fire'];
        $sticker_icon_html = $sticker_data['icon_html'];

        // Determine if QR card should show
        $qr_card_html = '';
        if (isset($merged['{qr_image_url}']) && !empty($merged['{qr_image_url}']) && ($template['template_key'] ?? '') === 'member_qr_pass') {
            $qr_card_html = "
            <div style='background: #ffffff; padding: 22px; border-radius: 18px; display: inline-block; box-shadow: 0 12px 30px rgba(0,0,0,0.5); margin: 20px 0;'>
                <img src='{$merged['{qr_image_url}']}' alt='Entrance QR Pass' style='width: 220px; height: 220px; display: block; margin: 0 auto;'>
                <div style='color: #0f172a; font-weight: 800; font-size: 13px; margin-top: 10px; text-transform: uppercase; letter-spacing: 0.5px;'>{$gym_name}</div>
            </div>";
        }

        // Details breakdown table
        $details_box_html = "
        <div style='background: rgba(0,0,0,0.35); border-radius: 14px; padding: 18px; margin: 20px 0; border: 1px solid rgba(255,255,255,0.08); text-align: left;'>
            <div style='display: flex; justify-content: space-between; padding: 7px 0; border-bottom: 1px solid rgba(255,255,255,0.06); font-size: 13px;'>
                <span style='color: #94a3b8; font-weight: 600;'>Member Name</span>
                <span style='color: #ffffff; font-weight: 700;'>{$merged['{member_name}']}</span>
            </div>
            <div style='display: flex; justify-content: space-between; padding: 7px 0; border-bottom: 1px solid rgba(255,255,255,0.06); font-size: 13px;'>
                <span style='color: #94a3b8; font-weight: 600;'>Membership ID</span>
                <span style='color: #38bdf8; font-weight: 800;'>{$merged['{userid}']}</span>
            </div>
            <div style='display: flex; justify-content: space-between; padding: 7px 0; border-bottom: 1px solid rgba(255,255,255,0.06); font-size: 13px;'>
                <span style='color: #94a3b8; font-weight: 600;'>Active Subscription</span>
                <span style='color: #ffffff; font-weight: 700;'>{$merged['{plan_name}']}</span>
            </div>
            <div style='display: flex; justify-content: space-between; padding: 7px 0; font-size: 13px;'>
                <span style='color: #94a3b8; font-weight: 600;'>Expiration Date</span>
                <span style='color: #10b981; font-weight: 800;'>{$merged['{expire_date}']}</span>
            </div>
        </div>";

        // Promo Banner HTML
        $promo_banner_html = "";
        if (!empty($promo_banner)) {
            $promo_banner_html = "
            <div style='background: linear-gradient(135deg, rgba(255,255,255,0.05), rgba(255,255,255,0.02)); border: 1.5px dashed {$accent_color}; border-radius: 14px; padding: 15px 20px; margin: 22px 0; font-size: 13px; color: #f8fafc; line-height: 1.6; text-align: left;'>
                {$promo_banner}
            </div>";
        }

        // Complete Compiled Email HTML
        $html = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='utf-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>{$subject}</title>
            <style>
                @keyframes pulse { 0% { transform: scale(1); } 50% { transform: scale(1.15); } 100% { transform: scale(1); } }
                body { margin: 0; padding: 25px 15px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #0b0f19; color: #ffffff; }
            </style>
        </head>
        <body style='margin:0; padding:25px 15px; font-family:-apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif; background-color:#0b0f19; color:#ffffff;'>
            <table width='100%' cellpadding='0' cellspacing='0' style='background:#0b0f19;'>
                <tr>
                    <td align='center'>
                        <table width='100%' cellpadding='0' cellspacing='0' style='max-width:580px; background:#1e293b; border-radius:24px; border:2px solid {$accent_color}; overflow:hidden; box-shadow:0 20px 45px rgba(0,0,0,0.6);'>
                            <!-- Top Header Banner -->
                            <tr>
                                <td style='background:linear-gradient(135deg, #0f172a 0%, #1e293b 100%); padding:30px 25px; text-align:center; border-bottom:1px solid rgba(255,255,255,0.08); position:relative;'>
                                    <!-- Animated Sticker Badge -->
                                    <div style='display:inline-block; margin-bottom:12px;'>
                                        {$sticker_icon_html}
                                    </div>
                                    <div style='color:{$accent_color}; font-size:11px; font-weight:900; letter-spacing:2px; text-transform:uppercase; background:rgba(255,255,255,0.06); border:1px solid {$accent_color}; display:inline-block; padding:5px 14px; border-radius:30px; margin-bottom:12px;'>
                                        {$badge_icon} {$badge_title}
                                    </div>
                                    <h1 style='color:#ffffff; font-size:22px; font-weight:800; margin:0 0 5px 0; letter-spacing:-0.5px;'>{$gym_name}</h1>
                                    <div style='color:#94a3b8; font-size:12px;'>{$gym_address} | Phone: {$gym_phone}</div>
                                </td>
                            </tr>
                            
                            <!-- Email Body Content -->
                            <tr>
                                <td style='padding:32px 28px; text-align:center;'>
                                    <h2 style='font-size:19px; font-weight:700; color:#ffffff; margin:0 0 14px 0;'>{$greeting}</h2>
                                    
                                    <p style='font-size:14px; color:#cbd5e1; line-height:1.7; margin:0 0 16px 0; text-align:left;'>
                                        {$intro_text}
                                    </p>

                                    {$qr_card_html}

                                    {$details_box_html}

                                    <p style='font-size:13.5px; color:#cbd5e1; line-height:1.7; margin:0 0 20px 0; text-align:left;'>
                                        {$body_content}
                                    </p>

                                    {$promo_banner_html}

                                    <!-- CTA Button -->
                                    <div style='margin-top:26px;'>
                                        <a href='{$cta_url}' style='display:block; box-sizing:border-box; background:linear-gradient(135deg, {$accent_color}, #ff8800); color:#ffffff; text-decoration:none; padding:15px 24px; border-radius:14px; font-weight:800; font-size:14px; text-align:center; box-shadow:0 8px 24px rgba(255,107,0,0.35); text-transform:uppercase; letter-spacing:0.5px;'>
                                            {$cta_text}
                                        </a>
                                    </div>
                                </td>
                            </tr>

                            <!-- Email Footer -->
                            <tr>
                                <td style='background:#0f172a; padding:22px 25px; text-align:center; border-top:1px solid rgba(255,255,255,0.06); font-size:11.5px; color:#64748b; line-height:1.6;'>
                                    <div style='color:#94a3b8; margin-bottom:6px;'>{$footer_text}</div>
                                    <div>&copy; " . date('Y') . " {$gym_name}. All rights reserved. | <a href='https://sudarshanfitness.de/privacy' style='color:#94a3b8; text-decoration:underline;'>Privacy Policy</a></div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>";

        return [
            'subject' => $subject,
            'html'    => $html
        ];
    }
}

if (!function_exists('send_member_qr_pass_email')) {
    function send_member_qr_pass_email($con, $to_email, $to_name, $userid, $plan_name = 'Active Subscription', $expire_date = 'N/A') {
        if (empty($to_email) || strpos($to_email, '@sudarshanfitness.local') !== false) {
            return false;
        }

        $template = get_email_template($con, 'member_qr_pass');
        $gym = get_gym_details($con);

        $qr_image_url = "https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=" . urlencode($userid);
        $expire_fmt = ($expire_date !== 'N/A' && !empty($expire_date)) ? date('d-M-Y', strtotime($expire_date)) : 'Active';

        $rendered = render_email_template_html($template, [
            '{member_name}' => $to_name,
            '{userid}'      => $userid,
            '{plan_name}'   => $plan_name,
            '{expire_date}' => $expire_fmt,
            '{qr_image_url}'=> $qr_image_url
        ], $gym);

        $sent = send_smtp_email($to_email, $to_name, $rendered['subject'], $rendered['html']);
        if (!$sent) {
            // Fallback to native PHP mail server
            $from_header_name = !empty($gym['gym_name']) ? $gym['gym_name'] : 'Sudarshan Fitness';
            $headers  = "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "From: =?UTF-8?B?" . base64_encode($from_header_name) . "?= <info@sudarshanfitness.de>\r\n";
            $headers .= "Reply-To: info@sudarshanfitness.de\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion();
            $sent = @mail($to_email, $rendered['subject'], $rendered['html'], $headers);
        }
        return $sent;
    }
}

if (!function_exists('send_dynamic_template_email')) {
    function send_dynamic_template_email($con, $to_email, $to_name, $template_key, $custom_placeholders = []) {
        if (empty($to_email) || strpos($to_email, '@sudarshanfitness.local') !== false) {
            return false;
        }

        $template = get_email_template($con, $template_key);
        $gym = get_gym_details($con);

        $rendered = render_email_template_html($template, $custom_placeholders, $gym);

        $sent = send_smtp_email($to_email, $to_name, $rendered['subject'], $rendered['html']);
        if (!$sent) {
            $from_header_name = !empty($gym['gym_name']) ? $gym['gym_name'] : 'Sudarshan Fitness';
            $headers  = "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "From: =?UTF-8?B?" . base64_encode($from_header_name) . "?= <info@sudarshanfitness.de>\r\n";
            $headers .= "Reply-To: info@sudarshanfitness.de\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion();
            $sent = @mail($to_email, $rendered['subject'], $rendered['html'], $headers);
        }
        return $sent;
    }
}
?>
