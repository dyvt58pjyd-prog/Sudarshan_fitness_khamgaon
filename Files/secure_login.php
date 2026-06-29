<?php
include './include/db_conn.php';

$user_id_auth = isset($_POST['user_id_auth']) ? ltrim(rtrim($_POST['user_id_auth'])) : '';
$pass_key = isset($_POST['pass_key']) ? ltrim(rtrim($_POST['pass_key'])) : '';
$login_role = isset($_POST['login_role']) ? $_POST['login_role'] : 'member';

$valid_roles = ['member', 'reception', 'trainer', 'owner', 'super_admin'];
if (!in_array($login_role, $valid_roles)) {
    $login_role = 'member';
}

$user_id_auth = stripslashes($user_id_auth);
$pass_key     = stripslashes($pass_key);

if ($pass_key == "" || $user_id_auth == "") {
    header("Location: index.php?error=wrong_password&role=" . urlencode($login_role));
    exit();
} else {
    $user_id_auth = mysqli_real_escape_string($con, $user_id_auth);
    $pass_key     = mysqli_real_escape_string($con, $pass_key);
    
    // Verify credentials and strict role matching
    $sql          = "SELECT * FROM admin WHERE username='$user_id_auth' and pass_key='$pass_key' and role='$login_role'";
    $result       = mysqli_query($con, $sql);
    $count        = mysqli_num_rows($result);
    
    if ($count == 1) {
        $row = mysqli_fetch_assoc($result);
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        // Store session data
        $_SESSION['user_data']  = $user_id_auth;
        $_SESSION['logged']     = "start";
        $_SESSION['role']       = $row['role'];
        $_SESSION['full_name']  = $row['Full_name'];
        $_SESSION['username']   = $user_id_auth;

        if ($_SESSION['role'] === 'member') {
            header("Location: ./dashboard/member/");
        } else {
            header("Location: ./dashboard/admin/");
        }
        exit();
    } else {
        if ($login_role !== 'member') {
            // Security Lock / Intruder Alert logging for administrative roles
            $ip = $_SERVER['REMOTE_ADDR'];
            if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
            }
            
            $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Unknown';
            $timestamp = date('Y-m-d H:i:s');
            
            // Extract OS/mobile device details from User-Agent safely
            $os = "Unknown OS";
            $device = "Unknown Device";
            if (preg_match('/android/i', $user_agent)) {
                $os = "Android";
                if (preg_match('/android\s+([a-zA-Z0-9\.\-_ ]+);/i', $user_agent, $matches)) {
                    $device = trim($matches[1]);
                }
                if (preg_match('/;\s+([a-zA-Z0-9\.\-_ ]+)\s+Build/i', $user_agent, $matches)) {
                    $device = trim($matches[1]);
                }
            } elseif (preg_match('/iphone/i', $user_agent)) {
                $os = "iOS";
                $device = "iPhone";
            } elseif (preg_match('/ipad/i', $user_agent)) {
                $os = "iOS";
                $device = "iPad";
            } elseif (preg_match('/macintosh|mac os x/i', $user_agent)) {
                $os = "macOS";
                $device = "Macintosh Computer";
            } elseif (preg_match('/windows/i', $user_agent)) {
                $os = "Windows";
                $device = "Windows PC";
            } elseif (preg_match('/linux/i', $user_agent)) {
                $os = "Linux";
                $device = "Linux Workstation";
            }
            
            $device_info = "$os ($device)";
            
            // Fetch owner and branding email addresses
            $gym = get_gym_details($con);
            $emails = [];
            if (!empty($gym['gym_email'])) {
                $emails[] = $gym['gym_email'];
            }
            $owner_q = mysqli_query($con, "SELECT email FROM users WHERE userid IN (SELECT username FROM admin WHERE role='owner' OR role='super_admin')");
            if ($owner_q) {
                while ($o_row = mysqli_fetch_assoc($owner_q)) {
                    if (!empty($o_row['email']) && !in_array($o_row['email'], $emails)) {
                        $emails[] = $o_row['email'];
                    }
                }
            }
            
            // Format security email alert body
            $subject = "⚠️ SECURITY LOCK ALERT: Failed Login Attempt Detected";
            $mail_body = "
            <html>
            <head>
                <style>
                    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; padding: 20px; }
                    .container { max-width: 600px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; border-top: 5px solid #d9534f; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
                    h2 { color: #d9534f; margin-top: 0; }
                    .meta-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                    .meta-table th, .meta-table td { padding: 10px; border-bottom: 1px solid #ddd; text-align: left; }
                    .meta-table th { background-color: #f9f9f9; width: 35%; font-weight: bold; }
                    .warning-box { background-color: #fdf7f7; border-left: 4px solid #d9534f; padding: 15px; margin: 20px 0; font-weight: bold; color: #a94442; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <h2>⚠️ Intruder Alert Notice</h2>
                    <div class='warning-box'>
                        An unauthorized failed login attempt was detected on a protected account portal. The user has been locked out with a countdown threat display.
                    </div>
                    <table class='meta-table'>
                        <tr>
                            <th>Attempted Username</th>
                            <td>" . htmlspecialchars($user_id_auth) . "</td>
                        </tr>
                        <tr>
                            <th>Portal Role</th>
                            <td>" . htmlspecialchars($login_role) . "</td>
                        </tr>
                        <tr>
                            <th>IP Address</th>
                            <td>" . htmlspecialchars($ip) . "</td>
                        </tr>
                        <tr>
                            <th>Date & Time</th>
                            <td>" . htmlspecialchars($timestamp) . "</td>
                        </tr>
                        <tr>
                            <th>Device OS / Model</th>
                            <td>" . htmlspecialchars($device_info) . "</td>
                        </tr>
                        <tr>
                            <th>Full User Agent</th>
                            <td><small>" . htmlspecialchars($user_agent) . "</small></td>
                        </tr>
                    </table>
                </div>
            </body>
            </html>
            ";
            
            // Dispatch SMTP / PHP mail alert
            require_once __DIR__ . '/include/smtp_mailer.php';
            foreach ($emails as $email) {
                $sent = send_smtp_email($email, "Titan Gym Owner/Admin", $subject, $mail_body, null, null, 'cyber.officer');
                if (!$sent) {
                    $headers  = "MIME-Version: 1.0\r\n";
                    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
                    $headers .= "From: " . $gym['gym_email'] . "\r\n";
                    $headers .= "Reply-To: " . $gym['gym_email'] . "\r\n";
                    @mail($email, $subject, $mail_body, $headers);
                }
            }
            
            header("Location: index.php?error=intruder_alert&role=" . urlencode($login_role) . "&ip=" . urlencode($ip) . "&time=" . urlencode($timestamp));
            exit();
        } else {
            header("Location: index.php?error=wrong_password&role=" . urlencode($login_role));
            exit();
        }
    }
}
?>
