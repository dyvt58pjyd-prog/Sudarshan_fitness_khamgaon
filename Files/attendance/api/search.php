<?php
// High-Speed Real-Time Member Search API
// Optimized for sub-10ms response time with zero lag
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$start_time = microtime(true);
require_once __DIR__ . '/../../include/db_conn.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

// Staff access check
$allowed_roles = ['super_admin', 'owner', 'reception', 'trainer', 'staff', 'admin', 'auditor'];
if (!isset($_SESSION['user_data']) || !isset($_SESSION['role']) || !in_array(strtolower($_SESSION['role']), $allowed_roles) || $_SESSION['role'] === 'member') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized staff access.']);
    exit();
}

date_default_timezone_set("Asia/Calcutta");
$today = date('Y-m-d');
$today_ts = strtotime($today);

$q = isset($_GET['q']) ? trim($_GET['q']) : '';

if ($q === '') {
    echo json_encode([
        'success' => true,
        'query' => '',
        'count' => 0,
        'time_ms' => round((microtime(true) - $start_time) * 1000, 2),
        'results' => []
    ]);
    exit();
}

$q_esc = mysqli_real_escape_string($con, $q);
// Strip spaces and special chars for phone matching
$q_clean = preg_replace('/[^0-9a-zA-Z@._\-]/', '', $q);
$q_clean_esc = mysqli_real_escape_string($con, $q_clean);

// High performance query with compound search on Name, Mobile, Email, and UserID
$sql = "SELECT u.userid, u.username, u.mobile, u.email, u.gender, u.photo, u.photo_base64, u.partner_uid,
               e.expire, e.paid_date, p.planName, p.validity
        FROM users u
        LEFT JOIN (
            SELECT e1.uid, e1.pid, e1.expire, e1.paid_date
            FROM enrolls_to e1
            INNER JOIN (
                SELECT uid, MAX(et_id) as max_et_id
                FROM enrolls_to
                GROUP BY uid
            ) e2 ON e1.et_id = e2.max_et_id
        ) e ON u.userid = e.uid
        LEFT JOIN plan p ON e.pid = p.pid
        WHERE (
            u.username LIKE '%$q_esc%' 
            OR u.mobile LIKE '%$q_clean_esc%' 
            OR u.email LIKE '%$q_esc%' 
            OR u.userid = '$q_esc'
            OR u.userid LIKE '$q_esc%'
        )
        AND u.userid != 'PENDING'
        AND LOWER(u.userid) != 'membership id'
        ORDER BY 
            CASE 
                WHEN u.mobile = '$q_clean_esc' THEN 1
                WHEN u.email = '$q_esc' THEN 2
                WHEN u.userid = '$q_esc' THEN 3
                WHEN LOWER(u.username) = LOWER('$q_esc') THEN 4
                WHEN u.username LIKE '$q_esc%' THEN 5
                WHEN u.mobile LIKE '$q_clean_esc%' THEN 6
                WHEN u.email LIKE '$q_esc%' THEN 7
                ELSE 8
            END,
            CAST(u.userid AS UNSIGNED) ASC
        LIMIT 10";

$res = mysqli_query($con, $sql);
$results = [];

if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $uid = $row['userid'];
        $expire_date = $row['expire'] ?? null;
        $plan_name = $row['planName'] ?? 'No Active Plan';

        // Couple/partner plan fallback check if own plan is missing or expired
        if ((empty($expire_date) || $expire_date < $today) && !empty($row['partner_uid'])) {
            $p_uid = mysqli_real_escape_string($con, $row['partner_uid']);
            $qp = mysqli_query($con, "SELECT e.expire, p.planName FROM enrolls_to e LEFT JOIN plan p ON e.pid=p.pid WHERE e.uid='$p_uid' ORDER BY e.expire DESC LIMIT 1");
            if ($qp && $p_sub = mysqli_fetch_assoc($qp)) {
                if (!empty($p_sub['expire']) && $p_sub['expire'] >= $today) {
                    $expire_date = $p_sub['expire'];
                    $plan_name = ($p_sub['planName'] ?? 'Couple Membership') . " (via Partner)";
                }
            }
        }

        // Calculate Membership Active / Expired status
        $is_active = false;
        $status_text = 'NO PLAN';
        $badge_class = 'badge-none';
        $days_left = null;
        $expire_formatted = 'No Plan';

        if (!empty($expire_date)) {
            $expire_ts = strtotime($expire_date);
            $expire_formatted = date('d-M-Y', $expire_ts);
            $days_left = (int)floor(($expire_ts - $today_ts) / 86400);

            if ($days_left < 0) {
                $is_active = false;
                $days_expired = abs($days_left);
                $status_text = "EXPIRED ({$days_expired}d ago)";
                $badge_class = 'badge-expired';
            } elseif ($days_left == 0) {
                $is_active = true;
                $status_text = "EXPIRES TODAY";
                $badge_class = 'badge-warning';
            } elseif ($days_left <= 7) {
                $is_active = true;
                $status_text = "EXPIRING ({$days_left}d left)";
                $badge_class = 'badge-warning';
            } else {
                $is_active = true;
                $status_text = "ACTIVE ({$days_left}d left)";
                $badge_class = 'badge-active';
            }
        }

        // Today's attendance status check
        $uid_esc = mysqli_real_escape_string($con, $uid);
        $q_att = mysqli_query($con, "SELECT entry_time, exit_time FROM attendance WHERE uid = '$uid_esc' AND date = '$today' ORDER BY id DESC LIMIT 1");
        $today_att = null;
        if ($q_att && mysqli_num_rows($q_att) > 0) {
            $att_row = mysqli_fetch_assoc($q_att);
            $entry_t = !empty($att_row['entry_time']) && $att_row['entry_time'] !== '00:00:00' ? date('h:i A', strtotime($att_row['entry_time'])) : null;
            $exit_t = !empty($att_row['exit_time']) && $att_row['exit_time'] !== '00:00:00' ? date('h:i A', strtotime($att_row['exit_time'])) : null;

            $today_att = [
                'has_entry' => !empty($entry_t),
                'entry_time' => $entry_t,
                'exit_time' => $exit_t,
                'is_inside' => (!empty($entry_t) && empty($exit_t))
            ];
        }

        // Resolve photo URL cleanly
        $photo_url = get_member_photo_url($row, '../');

        $results[] = [
            'userid'           => $row['userid'],
            'username'         => $row['username'],
            'mobile'           => $row['mobile'] ?? '',
            'email'            => $row['email'] ?? '',
            'gender'           => $row['gender'] ?? '',
            'photo_url'        => $photo_url,
            'plan_name'        => $plan_name,
            'expire_date'      => $expire_date,
            'expire_formatted' => $expire_formatted,
            'days_left'        => $days_left,
            'is_active'        => $is_active,
            'status_text'      => $status_text,
            'badge_class'      => $badge_class,
            'today_att'        => $today_att
        ];
    }
}

$end_time = microtime(true);
$time_ms = round(($end_time - $start_time) * 1000, 2);

echo json_encode([
    'success' => true,
    'query' => $q,
    'count' => count($results),
    'time_ms' => $time_ms,
    'results' => $results
], JSON_UNESCAPED_SLASHES);
