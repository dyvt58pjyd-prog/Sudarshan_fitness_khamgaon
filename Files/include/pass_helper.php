<?php
/**
 * Signed Digital Pass, Rotating Gate QR (SFQ1), and Shared Check-In Helpers
 */

if (!function_exists('get_pass_hmac_secret')) {
    /**
     * Resolve or bootstrap a persistent 256-bit HMAC signing secret without committing keys to Git.
     */
    function get_pass_hmac_secret($con = null) {
        static $cached_secret = null;
        if ($cached_secret !== null) {
            return $cached_secret;
        }

        $env_secret = getenv('PASS_HMAC_SECRET');
        if (!empty($env_secret)) {
            $cached_secret = $env_secret;
            return $cached_secret;
        }
        if (defined('PASS_HMAC_SECRET') && PASS_HMAC_SECRET !== '') {
            $cached_secret = PASS_HMAC_SECRET;
            return $cached_secret;
        }

        if ($con && $con instanceof mysqli) {
            $chk = @mysqli_query($con, "SHOW COLUMNS FROM gym_details LIKE 'pass_secret'");
            if ($chk && mysqli_num_rows($chk) === 0) {
                @mysqli_query($con, "ALTER TABLE gym_details ADD COLUMN pass_secret VARCHAR(128) DEFAULT NULL");
            }
            $res = @mysqli_query($con, "SELECT id, pass_secret FROM gym_details LIMIT 1");
            if ($res && $row = mysqli_fetch_assoc($res)) {
                if (!empty($row['pass_secret'])) {
                    $cached_secret = $row['pass_secret'];
                    return $cached_secret;
                }
                $new_secret = bin2hex(random_bytes(32));
                $sec_esc = mysqli_real_escape_string($con, $new_secret);
                $row_id = intval($row['id']);
                if (@mysqli_query($con, "UPDATE gym_details SET pass_secret = '$sec_esc' WHERE id = $row_id")) {
                    $cached_secret = $new_secret;
                    return $cached_secret;
                }
            }
        }

        $cached_secret = hash('sha256', __DIR__ . '|' . php_uname('n') . '|SF_PASS_FALLBACK');
        return $cached_secret;
    }
}

if (!function_exists('sign_pass_url_signature')) {
    /**
     * Create an HMAC signature for a wallet pass URL.
     */
    function sign_pass_url_signature($con, $uid, $exp = 0) {
        $uid_str = trim((string)$uid);
        $exp_int = intval($exp);
        $payload = "PASS_URL|{$uid_str}|{$exp_int}";
        return substr(hash_hmac('sha256', $payload, get_pass_hmac_secret($con)), 0, 24);
    }
}

if (!function_exists('verify_pass_url_signature')) {
    /**
     * Verify a wallet pass URL signature and optional expiration timestamp.
     */
    function verify_pass_url_signature($con, $uid, $sig, $exp = 0) {
        $uid_str = trim((string)$uid);
        $sig_str = strtolower(trim((string)$sig));
        $exp_int = intval($exp);
        if ($uid_str === '' || $sig_str === '') {
            return false;
        }
        if ($exp_int > 0 && time() > $exp_int) {
            return false;
        }
        $expected = sign_pass_url_signature($con, $uid_str, $exp_int);
        return hash_equals($expected, $sig_str);
    }
}

if (!function_exists('build_signed_wallet_pass_url')) {
    /**
     * Build a full HTTPS/HTTP signed wallet pass URL with proper /Files prefix detection.
     */
    function build_signed_wallet_pass_url($con, $uid, $exp = 0, $relative_only = false, $platform = 'universal') {
        $uid_str = trim((string)$uid);
        $exp_int = intval($exp);
        $sig = sign_pass_url_signature($con, $uid_str, $exp_int);

        $has_files_prefix = (strpos($_SERVER['SCRIPT_NAME'] ?? '', '/Files/') !== false || strpos($_SERVER['REQUEST_URI'] ?? '', '/Files/') !== false || php_sapi_name() === 'cli');
        $base_prefix = $has_files_prefix ? '/Files' : '';

        $path = $base_prefix . '/member_app/wallet_pass.php?uid=' . urlencode($uid_str) . '&sig=' . urlencode($sig);
        if ($exp_int > 0) {
            $path .= '&exp=' . $exp_int;
        }
        $platform_norm = strtolower(trim((string)$platform));
        if (in_array($platform_norm, ['apple', 'android'], true)) {
            $path .= '&platform=' . urlencode($platform_norm);
        }
        if ($relative_only) {
            return $path;
        }

        $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? 0) == 443);
        $host = $_SERVER['HTTP_HOST'] ?? 'sudarshanfitness.de';
        $is_local_host = (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false || preg_match('/^(192\.168\.|10\.|172\.)/', $host));
        $scheme = $is_https ? 'https' : ($is_local_host ? 'http' : 'https');
        return $scheme . '://' . $host . $path;
    }
}

if (!function_exists('generate_rotating_qr_payload')) {
    /**
     * Generate a 60-second time-windowed HMAC QR payload: SFQ1:<uid>:<window>:<sig16>.
     */
    function generate_rotating_qr_payload($con, $uid, $window_offset = 0) {
        $uid_str = trim((string)$uid);
        $window = intval(floor(time() / 60)) + intval($window_offset);
        $sig = substr(hash_hmac('sha256', "GATE_QR|{$uid_str}|{$window}", get_pass_hmac_secret($con)), 0, 16);
        return "SFQ1:{$uid_str}:{$window}:{$sig}";
    }
}

if (!function_exists('get_rotating_qr_remaining_seconds')) {
    function get_rotating_qr_remaining_seconds() {
        $rem = 60 - (time() % 60);
        return $rem > 0 ? $rem : 60;
    }
}

if (!function_exists('resolve_checkin_identifier')) {
    /**
     * Parse and validate rotating SFQ1 QR payloads or fallback manual/legacy identifiers.
     */
    function resolve_checkin_identifier($con, $raw_identifier, $scan_source = 'manual') {
        $val = trim((string)$raw_identifier);
        if ($val === '') {
            return ['ok' => false, 'code' => 400, 'message' => 'Please enter your Member ID or scan a valid pass.'];
        }

        if (strpos($val, 'SFQ1:') === 0) {
            if (!preg_match('/^SFQ1:([^:]+):(\d+):([a-f0-9]{16})$/i', $val, $m)) {
                return [
                    'ok' => false,
                    'code' => 403,
                    'invalid_qr' => true,
                    'message' => 'Malformed Dynamic QR Pass. Please reopen your Smart Pass.'
                ];
            }
            $qr_uid = trim($m[1]);
            $qr_window = intval($m[2]);
            $qr_sig = strtolower($m[3]);
            $cur_window = intval(floor(time() / 60));

            if (abs($qr_window - $cur_window) > 1) {
                return [
                    'ok' => false,
                    'code' => 403,
                    'expired_qr' => true,
                    'message' => 'QR Screenshot Expired: Dynamic QR passes rotate every 60 seconds. Please open your live Smart Pass.'
                ];
            }

            $expected_sig = substr(hash_hmac('sha256', "GATE_QR|{$qr_uid}|{$qr_window}", get_pass_hmac_secret($con)), 0, 16);
            if (!hash_equals($expected_sig, $qr_sig)) {
                return [
                    'ok' => false,
                    'code' => 403,
                    'invalid_qr' => true,
                    'message' => 'Invalid QR Pass signature. Screenshot or tampered code rejected.'
                ];
            }

            return ['ok' => true, 'uid' => $qr_uid, 'verified_qr' => true];
        }

        if (strpos($val, 'MEMBER_ID:') === 0) {
            return ['ok' => true, 'lookup' => trim(substr($val, 10)), 'verified_qr' => false];
        }

        return ['ok' => true, 'lookup' => $val, 'verified_qr' => false];
    }
}

if (!function_exists('authorize_wallet_pass_request')) {
    /**
     * Authorize access to a member wallet pass via active session, couple link, or HMAC signature.
     */
    function authorize_wallet_pass_request($con, $requested_uid = '', $sig = '', $exp = 0) {
        $req_uid = trim((string)$requested_uid);
        $sig_str = trim((string)$sig);
        $exp_int = intval($exp);

        $role = $_SESSION['role'] ?? '';
        $is_staff = in_array($role, ['super_admin', 'owner', 'reception', 'trainer', 'auditor'], true);

        $session_uid = '';
        if (!empty($_SESSION['member_uid'])) {
            $session_uid = trim((string)$_SESSION['member_uid']);
        } elseif ($role === 'member') {
            $raw_sess = $_SESSION['userid'] ?? ($_SESSION['user_data'] ?? '');
            $session_uid = is_array($raw_sess) ? trim((string)($raw_sess['userid'] ?? '')) : trim((string)$raw_sess);
        }

        if ($req_uid !== '' && $sig_str !== '') {
            if (verify_pass_url_signature($con, $req_uid, $sig_str, $exp_int)) {
                return ['authorized' => true, 'uid' => $req_uid, 'via' => 'signed_url', 'is_owner_session' => ($session_uid === $req_uid)];
            }
            return ['authorized' => false, 'reason' => 'invalid_signature'];
        }

        if ($is_staff) {
            if ($req_uid !== '') {
                return ['authorized' => true, 'uid' => $req_uid, 'via' => 'staff', 'is_owner_session' => false];
            }
            $staff_id = is_array($_SESSION['user_data'] ?? '') ? '' : trim((string)($_SESSION['user_data'] ?? ''));
            return $staff_id !== ''
                ? ['authorized' => true, 'uid' => $staff_id, 'via' => 'staff', 'is_owner_session' => true]
                : ['authorized' => false, 'reason' => 'missing_uid'];
        }

        if ($session_uid !== '') {
            if ($req_uid === '' || $req_uid === $session_uid) {
                return ['authorized' => true, 'uid' => $session_uid, 'via' => 'member_session', 'is_owner_session' => true];
            }
            // Allow couple partner access only if genuinely linked
            $req_esc = mysqli_real_escape_string($con, $req_uid);
            $sess_esc = mysqli_real_escape_string($con, $session_uid);
            $q_pair = mysqli_query($con, "SELECT userid FROM users WHERE (userid='$req_esc' AND partner_uid='$sess_esc') OR (userid='$sess_esc' AND partner_uid='$req_esc') LIMIT 1");
            if ($q_pair && mysqli_num_rows($q_pair) > 0) {
                return ['authorized' => true, 'uid' => $req_uid, 'via' => 'couple_session', 'is_owner_session' => true];
            }
            return ['authorized' => false, 'reason' => 'forbidden_uid'];
        }

        return ['authorized' => false, 'reason' => ($req_uid !== '' ? 'unsigned_url' : 'unauthenticated')];
    }
}

if (!function_exists('handle_wallet_pass_redirect')) {
    /**
     * Shared redirect logic for Google/Apple Wallet pass entrypoints with HMAC signing.
     */
    function handle_wallet_pass_redirect($con) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $req_uid = $_GET['uid'] ?? '';
        $sig = $_GET['sig'] ?? '';
        $exp = $_GET['exp'] ?? 0;

        $auth = authorize_wallet_pass_request($con, $req_uid, $sig, $exp);
        if (!$auth['authorized']) {
            if (($auth['reason'] ?? '') === 'invalid_signature' || ($auth['reason'] ?? '') === 'unsigned_url' || ($auth['reason'] ?? '') === 'forbidden_uid') {
                http_response_code(403);
                die('Access Denied: Signed pass token required.');
            }
            header("Location: ../member_app/index.php");
            exit;
        }

        $target_uid = $auth['uid'];
        $signed_sig = sign_pass_url_signature($con, $target_uid, 0);
        header("Location: ../member_app/wallet_pass.php?uid=" . urlencode($target_uid) . "&sig=" . urlencode($signed_sig));
        exit;
    }
}

if (!function_exists('process_member_attendance_checkin')) {
    /**
     * Validate active subscription and log member check-in / check-out + XP + streak + WhatsApp alert.
     */
    function process_member_attendance_checkin($con, $user, $options = []) {
        date_default_timezone_set("Asia/Calcutta");

        $uid = $user['userid'];
        $uid_esc = mysqli_real_escape_string($con, $uid);
        $name = $user['username'] ?? 'Member';
        $mobile = $user['mobile'] ?? '';
        $verified_qr = !empty($options['verified_qr']);
        $cooldown_minutes = isset($options['cooldown_minutes']) ? intval($options['cooldown_minutes']) : 0;
        $channel = $options['channel'] ?? 'kiosk';

        $today_str = date('Y-m-d');
        $q_sub = mysqli_query($con, "SELECT e.*, p.planName FROM enrolls_to e 
                                     INNER JOIN plan p ON e.pid = p.pid 
                                     WHERE e.uid = '$uid_esc' AND e.renewal = 'yes' 
                                     ORDER BY e.expire DESC LIMIT 1");

        if (!$q_sub || mysqli_num_rows($q_sub) === 0) {
            return [
                'http_code' => 403,
                'success' => false,
                'message' => 'Access Denied: No active membership subscription found for ' . htmlspecialchars($name)
            ];
        }

        $sub = mysqli_fetch_assoc($q_sub);
        $expire_date = $sub['expire'];
        $plan_name = $sub['planName'];

        $expire_timestamp = strtotime($expire_date);
        $today_timestamp = strtotime($today_str);
        $days_left = floor(($expire_timestamp - $today_timestamp) / 86400);

        if ($expire_date < $today_str) {
            return [
                'http_code' => 403,
                'success' => false,
                'expired' => true,
                'name' => $name,
                'uid' => $uid,
                'plan' => $plan_name,
                'expiry' => date('d-M-Y', $expire_timestamp),
                'days_expired' => abs($days_left),
                'message' => 'Access Denied: Membership for ' . htmlspecialchars($name) . ' expired on ' . date('d-M-Y', $expire_timestamp) . ' (' . abs($days_left) . ' days ago). Please renew!'
            ];
        }

        $expiry_status = ($days_left <= 7) ? 'EXPIRING_SOON' : 'ACTIVE';
        $log_time = date('H:i:s');
        $q_att = mysqli_query($con, "SELECT * FROM attendance WHERE uid = '$uid_esc' AND date = '$today_str' LIMIT 1");

        $gym = get_gym_details($con);
        $gym_name = $gym['gym_name'] ?? 'Sudarshan Fitness';

        if ($q_att && mysqli_num_rows($q_att) > 0) {
            $att_row = mysqli_fetch_assoc($q_att);

            if (empty($att_row['exit_time']) || $att_row['exit_time'] === '00:00:00') {
                if ($cooldown_minutes > 0 && !empty($att_row['entry_time'])) {
                    $entry_ts = strtotime($today_str . ' ' . $att_row['entry_time']);
                    $elapsed_secs = time() - $entry_ts;
                    if ($elapsed_secs >= 0 && $elapsed_secs < ($cooldown_minutes * 60)) {
                        $streak = function_exists('get_member_streak') ? get_member_streak($con, $uid) : 0;
                        return [
                            'http_code' => 200,
                            'success' => true,
                            'action' => 'cooldown',
                            'name' => $name,
                            'uid' => $uid,
                            'plan' => $plan_name,
                            'expiry' => date('d-M-Y', $expire_timestamp),
                            'days_left' => $days_left,
                            'expiry_status' => $expiry_status,
                            'streak' => $streak,
                            'verified_qr' => $verified_qr,
                            'time' => date('h:i A', $entry_ts),
                            'message' => 'Already checked in at ' . date('h:i A', $entry_ts) . '. Tap again later to check out.'
                        ];
                    }
                }

                $att_id = intval($att_row['id']);
                $update_sql = "UPDATE attendance SET exit_time = '$log_time' WHERE id = $att_id";
                if (mysqli_query($con, $update_sql)) {
                    $streak = function_exists('get_member_streak') ? get_member_streak($con, $uid) : 0;
                    if (!empty($mobile) && function_exists('enqueue_whatsapp_message')) {
                        $wa_msg = "👋 *Goodbye from {$gym_name}!* 👋\n\n" .
                                  "Hello *{$name}*,\n\n" .
                                  "You checked out at *" . date('h:i A', strtotime($log_time)) . "*.\n\n" .
                                  "Great session today! Keep up the consistency. See you next time! 🔌";
                        enqueue_whatsapp_message($con, $mobile, $wa_msg);
                    }

                    return [
                        'http_code' => 200,
                        'success' => true,
                        'action' => 'check-out',
                        'name' => $name,
                        'uid' => $uid,
                        'plan' => $plan_name,
                        'expiry' => date('d-M-Y', $expire_timestamp),
                        'days_left' => $days_left,
                        'expiry_status' => $expiry_status,
                        'streak' => $streak,
                        'verified_qr' => $verified_qr,
                        'channel' => $channel,
                        'time' => date('h:i A', strtotime($log_time)),
                        'message' => 'Goodbye, ' . htmlspecialchars($name) . '! Check-out logged successfully.'
                    ];
                }

                return [
                    'http_code' => 500,
                    'success' => false,
                    'message' => 'Database error updating check-out.'
                ];
            }

            $streak = function_exists('get_member_streak') ? get_member_streak($con, $uid) : 0;
            return [
                'http_code' => 200,
                'success' => true,
                'action' => 'already-logged',
                'name' => $name,
                'uid' => $uid,
                'plan' => $plan_name,
                'expiry' => date('d-M-Y', $expire_timestamp),
                'days_left' => $days_left,
                'expiry_status' => $expiry_status,
                'streak' => $streak,
                'verified_qr' => $verified_qr,
                'time' => date('h:i A', strtotime($att_row['entry_time'])),
                'message' => htmlspecialchars($name) . ' has already checked in and out today.'
            ];
        }

        $insert_sql = "INSERT INTO attendance (uid, date, entry_time, exit_time) VALUES ('$uid_esc', '$today_str', '$log_time', NULL)";
        if (mysqli_query($con, $insert_sql)) {
            $xp_data = function_exists('add_member_xp') ? add_member_xp($con, $uid, 50) : null;
            $streak = function_exists('get_member_streak') ? get_member_streak($con, $uid) : 0;
            $is_milestone = ($streak > 0 && $streak % 5 === 0);

            if (!empty($mobile) && function_exists('enqueue_whatsapp_message')) {
                $wa_msg = "🏋️ *Welcome to {$gym_name}!* 🏋️\n\n" .
                          "Hello *{$name}*,\n\n" .
                          "You checked in at *" . date('h:i A', strtotime($log_time)) . "*.\n" .
                          "🔥 *Check-In Streak:* *{$streak} days!*\n\n";
                if ($is_milestone) {
                    $wa_msg .= "🌟 *MILESTONE UNLOCKED!* 🌟\n" .
                               "Congratulations on reaching a *{$streak}-day attendance streak*! Your consistency is inspiring. Keep up the amazing work! 🏆\n\n";
                }
                $wa_msg .= "Let's crush today's workout! 💪";
                enqueue_whatsapp_message($con, $mobile, $wa_msg);
            }

            return [
                'http_code' => 200,
                'success' => true,
                'action' => 'check-in',
                'name' => $name,
                'uid' => $uid,
                'plan' => $plan_name,
                'expiry' => date('d-M-Y', $expire_timestamp),
                'days_left' => $days_left,
                'expiry_status' => $expiry_status,
                'streak' => $streak,
                'xp_earned' => 50,
                'new_rank' => $xp_data ? $xp_data['new_rank'] : 'Beginner',
                'verified_qr' => $verified_qr,
                'channel' => $channel,
                'time' => date('h:i A', strtotime($log_time)),
                'message' => 'Welcome, ' . htmlspecialchars($name) . '! Check-in logged successfully.'
            ];
        }

        return [
            'http_code' => 500,
            'success' => false,
            'message' => 'Database error inserting check-in.'
        ];
    }
}

if (!function_exists('resolve_gym_logo_url')) {
    /**
     * Resolve the gym's configured logo to a valid path/URL across nested portals.
     */
    function resolve_gym_logo_url($gym, $relative_prefix = '../', $absolute_url = false) {
        $raw = trim((string)($gym['gym_logo'] ?? ''));
        $files_root = realpath(__DIR__ . '/..');
        $rel = '';

        if ($raw !== '') {
            if (preg_match('#^https?://#i', $raw)) {
                return $raw;
            }
            $cleaned = ltrim(str_replace(['\\', '../', './'], ['/', '', ''], $raw), '/');
            $cleaned = preg_replace('#^Files/#i', '', $cleaned);
            if ($files_root && file_exists($files_root . '/' . $cleaned)) {
                $rel = str_replace(' ', '%20', $cleaned);
            }
        }

        if ($rel === '' && $files_root) {
            foreach (['images/logo.png', 'images/logo.jpg', 'logo.png'] as $cand) {
                if (file_exists($files_root . '/' . $cand)) {
                    $rel = $cand;
                    break;
                }
            }
        }
        if ($rel === '') {
            $rel = 'images/logo.png';
        }

        if ($absolute_url) {
            $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? 0) == 443);
            $host = $_SERVER['HTTP_HOST'] ?? 'sudarshanfitness.de';
            $is_local_host = (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false || preg_match('/^(192\.168\.|10\.|172\.)/', $host));
            $scheme = $is_https ? 'https' : ($is_local_host ? 'http' : 'https');
            $has_files_prefix = (strpos($_SERVER['SCRIPT_NAME'] ?? '', '/Files/') !== false || strpos($_SERVER['REQUEST_URI'] ?? '', '/Files/') !== false || php_sapi_name() === 'cli');
            $base_prefix = $has_files_prefix ? '/Files' : '';
            return $scheme . '://' . $host . $base_prefix . '/' . $rel;
        }

        return $relative_prefix . $rel;
    }
}

if (!function_exists('ensure_wallet_pass_logs_table')) {
    /**
     * Self-healing audit table for reception/admin/owner wallet pass issuances.
     */
    function ensure_wallet_pass_logs_table($con) {
        if (!$con || !($con instanceof mysqli)) return;
        @mysqli_query($con, "CREATE TABLE IF NOT EXISTS wallet_pass_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            uid VARCHAR(50) NOT NULL,
            platform VARCHAR(20) NOT NULL DEFAULT 'universal',
            channel VARCHAR(30) NOT NULL,
            issued_by VARCHAR(100) NOT NULL,
            staff_role VARCHAR(30) DEFAULT '',
            link_expires_at DATETIME DEFAULT NULL,
            note VARCHAR(255) DEFAULT '',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_uid (uid),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
}

if (!function_exists('log_wallet_pass_issuance')) {
    function log_wallet_pass_issuance($con, $uid, $platform, $channel, $issued_by, $staff_role = '', $exp_ts = 0, $note = '') {
        ensure_wallet_pass_logs_table($con);
        $u_esc = mysqli_real_escape_string($con, (string)$uid);
        $p_esc = mysqli_real_escape_string($con, (string)$platform);
        $c_esc = mysqli_real_escape_string($con, (string)$channel);
        $i_esc = mysqli_real_escape_string($con, (string)$issued_by);
        $r_esc = mysqli_real_escape_string($con, (string)$staff_role);
        $n_esc = mysqli_real_escape_string($con, substr((string)$note, 0, 250));
        $exp_sql = ($exp_ts > 0) ? ("'" . date('Y-m-d H:i:s', intval($exp_ts)) . "'") : "NULL";
        return @mysqli_query($con, "INSERT INTO wallet_pass_logs (uid, platform, channel, issued_by, staff_role, link_expires_at, note) VALUES ('$u_esc', '$p_esc', '$c_esc', '$i_esc', '$r_esc', $exp_sql, '$n_esc')");
    }
}

if (!function_exists('fetch_wallet_pass_member_context')) {
    /**
     * Load normalized member profile, active/latest plan, coach, and batch details for passes.
     */
    function fetch_wallet_pass_member_context($con, $uid_raw, $assets_prefix = '../') {
        $uid = mysqli_real_escape_string($con, trim((string)$uid_raw));
        $gym = get_gym_details($con);

        $q_user = mysqli_query($con, "SELECT u.*, a.city, a.state FROM users u LEFT JOIN address a ON u.userid = a.id WHERE u.userid='$uid' LIMIT 1");
        if (!$q_user || mysqli_num_rows($q_user) === 0) {
            return null;
        }
        $user = mysqli_fetch_assoc($q_user);

        date_default_timezone_set("Asia/Calcutta");
        $today_str = date('Y-m-d');
        $q_plan = mysqli_query($con, "SELECT p.planName, p.validity, e.expire, e.paid_date FROM enrolls_to e INNER JOIN plan p ON e.pid = p.pid WHERE e.uid='$uid' ORDER BY e.expire DESC LIMIT 1");
        $plan_name = "No Active Plan";
        $expire_date = "N/A";
        $expire_iso = "";
        $days_left = 0;
        $is_active = false;
        if ($q_plan && mysqli_num_rows($q_plan) > 0) {
            $plan_row = mysqli_fetch_assoc($q_plan);
            $plan_name = $plan_row['planName'];
            $expire_iso = $plan_row['expire'];
            $expire_date = date('d M Y', strtotime($plan_row['expire']));
            $diff = strtotime($plan_row['expire']) - strtotime($today_str);
            if ($diff >= 0) {
                $is_active = true;
                $days_left = intval(ceil($diff / 86400));
            }
        }
        if (!$is_active && !empty($user['partner_uid'])) {
            $p_esc = mysqli_real_escape_string($con, $user['partner_uid']);
            $q_p = mysqli_query($con, "SELECT p.planName, p.validity, e.expire FROM enrolls_to e INNER JOIN plan p ON e.pid = p.pid WHERE e.uid='$p_esc' ORDER BY e.expire DESC LIMIT 1");
            if ($q_p && mysqli_num_rows($q_p) > 0) {
                $p_row = mysqli_fetch_assoc($q_p);
                $p_diff = strtotime($p_row['expire']) - strtotime($today_str);
                if ($p_diff >= 0) {
                    $is_active = true;
                    $plan_name = $p_row['planName'] . ' (Couple)';
                    $expire_iso = $p_row['expire'];
                    $expire_date = date('d M Y', strtotime($p_row['expire']));
                    $days_left = intval(ceil($p_diff / 86400));
                }
            }
        }

        $trainer_name = "General Floor Coach";
        if (!empty($user['trainer_id'])) {
            $tr_id_esc = mysqli_real_escape_string($con, $user['trainer_id']);
            $q_tr = mysqli_query($con, "SELECT Full_name FROM admin WHERE username='$tr_id_esc' LIMIT 1");
            if ($q_tr && $r_tr = mysqli_fetch_assoc($q_tr)) {
                $trainer_name = $r_tr['Full_name'];
            }
        }

        $batch_name = "General Batch (06:00 AM - 10:00 PM)";
        if (isset($user['biometric_batch']) && $user['biometric_batch'] !== '') {
            $b_id = intval($user['biometric_batch']);
            $q_bt = mysqli_query($con, "SELECT batch_name, start_time, end_time FROM biometric_batches WHERE id=$b_id LIMIT 1");
            if ($q_bt && $r_bt = mysqli_fetch_assoc($q_bt)) {
                $batch_name = $r_bt['batch_name'] . " (" . date('h:i A', strtotime($r_bt['start_time'])) . " - " . date('h:i A', strtotime($r_bt['end_time'])) . ")";
            }
        }

        $photo_url = function_exists('get_member_photo_url')
            ? get_member_photo_url($user, $assets_prefix)
            : ($assets_prefix . 'images/default_avatar.png');
        $gym_logo = resolve_gym_logo_url($gym, $assets_prefix, false);
        $gym_logo_abs = resolve_gym_logo_url($gym, $assets_prefix, true);

        return [
            'gym'          => $gym,
            'gym_logo'     => $gym_logo,
            'gym_logo_abs' => $gym_logo_abs,
            'user'         => $user,
            'photo_url'    => $photo_url,
            'plan_name'    => $plan_name,
            'expire_date'  => $expire_date,
            'expire_iso'   => $expire_iso,
            'days_left'    => $days_left,
            'is_active'    => $is_active,
            'trainer_name' => $trainer_name,
            'batch_name'   => $batch_name
        ];
    }
}

if (!function_exists('build_wallet_pass_whatsapp_text')) {
    function build_wallet_pass_whatsapp_text($ctx, $signed_url, $platform = 'universal') {
        $gym = $ctx['gym'];
        $user = $ctx['user'];
        $plat_hint = '';
        if ($platform === 'apple') {
            $plat_hint = "\n🍏 *iPhone Tip:* Open in Safari -> Tap Share (⬆️) -> *Add to Home Screen* for 1-tap Wallet pass access.\n";
        } elseif ($platform === 'android') {
            $plat_hint = "\n🤖 *Android Tip:* Open in Chrome -> Tap ⋮ -> *Install App / Add to Home Screen* for 1-tap gate access.\n";
        }

        return "🏋️ *" . strtoupper($gym['gym_name']) . " — VIP DIGITAL PASS*\n\n"
             . "👤 *Athlete:* " . $user['username'] . "\n"
             . "🆔 *Member ID:* #" . $user['userid'] . "\n"
             . "📋 *Plan:* " . $ctx['plan_name'] . "\n"
             . "📅 *Valid Till:* " . $ctx['expire_date'] . ($ctx['is_active'] ? " (Active)" : " (Expired)") . "\n"
             . "⏰ *Batch:* " . $ctx['batch_name'] . "\n"
             . $plat_hint . "\n"
             . "📲 *Open My Signed Gate Pass:*\n" . $signed_url . "\n\n"
             . "📍 " . ($gym['gym_address'] ?? '');
    }
}

if (!function_exists('send_wallet_pass_branded_email')) {
    function send_wallet_pass_branded_email($con, $ctx, $signed_url, $platform = 'universal') {
        $user = $ctx['user'];
        $email = trim((string)($user['email'] ?? ''));
        if ($email === '' || strpos($email, '@sudarshanfitness.local') !== false || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['sent' => false, 'reason' => 'Member does not have a valid external email address.'];
        }

        require_once __DIR__ . '/smtp_mailer.php';
        $gym = $ctx['gym'];
        $gym_name = htmlspecialchars($gym['gym_name'] ?? 'Sudarshan Fitness');
        $gym_addr = htmlspecialchars($gym['gym_address'] ?? '');
        $gym_tel  = htmlspecialchars($gym['gym_contact'] ?? '');
        $logo_url = htmlspecialchars($ctx['gym_logo_abs']);
        $m_name   = htmlspecialchars($user['username']);
        $m_uid    = htmlspecialchars($user['userid']);
        $plan     = htmlspecialchars($ctx['plan_name']);
        $exp      = htmlspecialchars($ctx['expire_date']);
        $batch    = htmlspecialchars($ctx['batch_name']);
        $coach    = htmlspecialchars($ctx['trainer_name']);
        $pass_href = htmlspecialchars($signed_url);

        $device_badge = $platform === 'apple'
            ? '🍎 Apple iPhone Smart Wallet Pass'
            : ($platform === 'android' ? '🤖 Android Smart Wallet Pass' : '📲 Universal Mobile Digital Pass');
        $device_steps = $platform === 'apple'
            ? '<strong>iPhone (Safari):</strong> Open the pass button above in Safari, tap <strong>Share (⬆️)</strong>, and choose <strong>Add to Home Screen</strong>.'
            : ($platform === 'android'
                ? '<strong>Android (Chrome):</strong> Open the pass button above in Chrome, tap <strong>⋮ Menu</strong>, and choose <strong>Install App / Add to Home Screen</strong>.'
                : '<strong>Phone Setup:</strong> Open in Safari (iPhone) or Chrome (Android) and tap <strong>Add to Home Screen</strong> for instant 1-tap gate entry.');

        $qr_img = 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' . urlencode($signed_url);
        $subject = ($gym['gym_name'] ?? 'Sudarshan Fitness') . " — Your Official Digital Gym Wallet Pass (#{$user['userid']})";

        $html = "<!DOCTYPE html><html><body style='margin:0;padding:24px;background:#0f172a;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;color:#f8fafc;'>
        <div style='max-width:540px;margin:0 auto;background:#1e293b;border:1px solid #334155;border-radius:20px;overflow:hidden;'>
            <div style='background:linear-gradient(135deg,#ff6b00,#d97706);padding:22px;text-align:center;'>
                <img src='{$logo_url}' alt='Gym Logo' style='max-height:64px;border-radius:50%;border:2px solid #fff;background:#fff;margin-bottom:8px;'><br>
                <div style='font-size:20px;font-weight:900;color:#fff;'>{$gym_name}</div>
                <div style='font-size:12px;font-weight:700;color:#fff7ed;margin-top:4px;'>{$device_badge}</div>
            </div>
            <div style='padding:24px;'>
                <p style='margin:0 0 14px;font-size:15px;'>Hi <strong>{$m_name}</strong>,</p>
                <p style='margin:0 0 18px;font-size:13.5px;color:#cbd5e1;line-height:1.5;'>Your signed Digital Gate Pass is ready. It includes your rotating 60-second entrance QR code and 1-tap check-in.</p>
                <div style='background:#0f172a;border:1px solid #334155;border-radius:14px;padding:16px;font-size:13px;line-height:1.7;margin-bottom:18px;'>
                    <div><strong>Member ID:</strong> #{$m_uid}</div>
                    <div><strong>Plan:</strong> {$plan}</div>
                    <div><strong>Valid Until:</strong> {$exp}</div>
                    <div><strong>Batch:</strong> {$batch}</div>
                    <div><strong>Coach:</strong> {$coach}</div>
                </div>
                <div style='text-align:center;margin:20px 0;'>
                    <a href='{$pass_href}' style='display:inline-block;background:#10b981;color:#fff;text-decoration:none;font-weight:800;font-size:15px;padding:14px 26px;border-radius:12px;'>📲 Open My Signed Digital Pass</a>
                </div>
                <div style='text-align:center;background:#fff;padding:12px;border-radius:12px;width:190px;margin:0 auto 18px;'>
                    <img src='" . htmlspecialchars($qr_img) . "' width='170' height='170' alt='Pass Link QR' style='display:block;margin:0 auto;'>
                    <div style='color:#0f172a;font-size:10px;font-weight:800;margin-top:6px;'>SCAN TO OPEN PASS ON PHONE</div>
                </div>
                <div style='background:rgba(56,189,248,0.12);border:1px solid rgba(56,189,248,0.3);border-radius:12px;padding:12px;font-size:12px;color:#bae6fd;line-height:1.5;'>
                    {$device_steps}
                </div>
            </div>
            <div style='background:#0b1120;padding:14px 20px;font-size:11px;color:#94a3b8;text-align:center;'>
                📍 {$gym_addr} &bull; 📞 {$gym_tel}
            </div>
        </div></body></html>";

        $sent = false;
        if (function_exists('send_smtp_email')) {
            $sent = send_smtp_email($email, $user['username'], $subject, $html);
        }
        if (!$sent) {
            $from_header_name = !empty($gym['gym_name']) ? $gym['gym_name'] : 'Sudarshan Fitness';
            $headers  = "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "From: =?UTF-8?B?" . base64_encode($from_header_name) . "?= <info@sudarshanfitness.de>\r\n";
            $sent = @mail($email, $subject, $html, $headers);
        }

        return ['sent' => (bool)$sent, 'reason' => $sent ? '' : 'SMTP / PHP mail failed (check SMTP settings).'];
    }
}
