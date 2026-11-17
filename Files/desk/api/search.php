<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../include/db_conn.php';

header('Content-Type: application/json; charset=UTF-8');

// Staff access check
$allowed_roles = ['super_admin', 'owner', 'reception', 'trainer', 'auditor'];
if (!isset($_SESSION['user_data']) || !isset($_SESSION['role']) || !in_array(strtolower($_SESSION['role']), $allowed_roles) || $_SESSION['role'] === 'member') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized staff access.']);
    exit();
}

date_default_timezone_set("Asia/Calcutta");
$today = date('Y-m-d');
$today_ts = strtotime($today);

$id = isset($_GET['id']) ? trim($_GET['id']) : '';
$q  = isset($_GET['q']) ? trim($_GET['q']) : '';

// 1. Fetch Full Member Profile by Exact ID or Mobile
if ($id !== '') {
    $id_esc = mysqli_real_escape_string($con, $id);
    
    $sql = "SELECT u.*, a.streetName, a.city, a.zipcode, a.state,
                   e.et_id, e.pid, e.paid_date, e.expire, e.paid_amount, e.discount_amount, e.balance, e.payment_mode,
                   p.planName, p.amount as plan_amount, p.validity as plan_validity, p.discount_lock
            FROM users u
            LEFT JOIN address a ON u.userid = a.id
            LEFT JOIN (
                SELECT e1.*
                FROM enrolls_to e1
                INNER JOIN (
                    SELECT uid, MAX(et_id) as max_et_id
                    FROM enrolls_to
                    GROUP BY uid
                ) e2 ON e1.et_id = e2.max_et_id
            ) e ON u.userid = e.uid
            LEFT JOIN plan p ON e.pid = p.pid
            WHERE u.userid = '$id_esc' OR u.mobile = '$id_esc'
            LIMIT 1";

    $res = mysqli_query($con, $sql);
    if ($res && mysqli_num_rows($res) > 0) {
        $row = mysqli_fetch_assoc($res);

        // Partner name lookup if couple linked
        $partner_name = '';
        if (!empty($row['partner_uid'])) {
            $p_uid = mysqli_real_escape_string($con, $row['partner_uid']);
            $qp = mysqli_query($con, "SELECT username FROM users WHERE userid = '$p_uid' LIMIT 1");
            if ($qp && $prow = mysqli_fetch_assoc($qp)) {
                $partner_name = $prow['username'];
            }
        }

        // Status calculation
        $expire_date = $row['expire'] ?? null;
        $is_active = false;
        $status_label = 'No Active Plan';
        $status_class = 'status-none';
        $days_diff = null;
        $suggested_start_date = $today;

        if (!empty($expire_date)) {
            $expire_ts = strtotime($expire_date);
            $days_diff = round(($expire_ts - $today_ts) / 86400);

            if ($days_diff < 0) {
                $is_active = false;
                $days_ago = abs($days_diff);
                $status_label = "Expired ($days_ago days ago)";
                $status_class = 'status-expired';
                $suggested_start_date = $today;
            } elseif ($days_diff == 0) {
                $is_active = true;
                $status_label = "Expires Today";
                $status_class = 'status-expiring';
                $suggested_start_date = $today;
            } elseif ($days_diff <= 5) {
                $is_active = true;
                $status_label = "Expiring in $days_diff days";
                $status_class = 'status-expiring';
                $suggested_start_date = $expire_date;
            } else {
                $is_active = true;
                $status_label = "Active ($days_diff days left)";
                $status_class = 'status-active';
                $suggested_start_date = $expire_date;
            }
        }

        // Photo URL resolution
        $photo_url = get_member_photo_url($row, '../');

        echo json_encode([
            'success' => true,
            'member' => [
                'userid'               => $row['userid'],
                'username'             => $row['username'],
                'mobile'               => $row['mobile'],
                'email'                => $row['email'],
                'gender'               => $row['gender'],
                'dob'                  => $row['dob'],
                'joining_date'         => $row['joining_date'],
                'entry_code'           => $row['entry_code'] ?? '',
                'photo_url'            => $photo_url,
                'streetName'           => $row['streetName'] ?? '',
                'city'                 => $row['city'] ?? 'Khamgaon',
                'zipcode'              => $row['zipcode'] ?? '444303',
                'state'                => $row['state'] ?? 'Maharashtra',
                'partner_uid'          => $row['partner_uid'],
                'partner_name'         => $partner_name,
                // Latest membership
                'et_id'                => $row['et_id'],
                'pid'                  => $row['pid'],
                'plan_name'            => $row['planName'] ?? 'No Plan',
                'plan_amount'          => intval($row['plan_amount'] ?? 0),
                'plan_validity'        => $row['plan_validity'] ?? '',
                'discount_lock'        => intval($row['discount_lock'] ?? 0),
                'paid_date'            => $row['paid_date'],
                'expire_date'          => $expire_date,
                'paid_amount'          => floatval($row['paid_amount'] ?? 0),
                'balance'              => floatval($row['balance'] ?? 0),
                'payment_mode'         => $row['payment_mode'] ?? 'Cash',
                // Calculated status
                'is_active'            => $is_active,
                'status_label'         => $status_label,
                'status_class'         => $status_class,
                'days_diff'            => $days_diff,
                'suggested_start_date' => $suggested_start_date
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit();
    } else {
        echo json_encode(['success' => false, 'error' => "Member not found with ID or Mobile: '$id'"]);
        exit();
    }
}

// 2. Autocomplete Search Query (q) - matches Name, Phone, or ID
if ($q !== '') {
    $q_esc = mysqli_real_escape_string($con, $q);

    $sql = "SELECT u.userid, u.username, u.mobile, u.gender, u.photo, u.photo_base64,
                   p.planName, e.expire
            FROM users u
            LEFT JOIN (
                SELECT e1.uid, e1.pid, e1.expire
                FROM enrolls_to e1
                INNER JOIN (
                    SELECT uid, MAX(et_id) as max_et_id
                    FROM enrolls_to
                    GROUP BY uid
                ) e2 ON e1.et_id = e2.max_et_id
            ) e ON u.userid = e.uid
            LEFT JOIN plan p ON e.pid = p.pid
            WHERE (u.username LIKE '%$q_esc%' OR u.mobile LIKE '%$q_esc%' OR u.userid LIKE '%$q_esc%')
              AND u.userid != 'PENDING'
              AND LOWER(u.userid) != 'membership id'
            ORDER BY 
                CASE 
                    WHEN u.username LIKE '$q_esc%' THEN 1 
                    WHEN u.userid = '$q_esc' THEN 2
                    WHEN u.mobile LIKE '$q_esc%' THEN 3
                    ELSE 4 
                END,
                CAST(u.userid AS UNSIGNED) ASC
            LIMIT 12";

    $res = mysqli_query($con, $sql);
    $matches = [];

    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $expire_date = $row['expire'] ?? null;
            $status_label = 'No Plan';
            $status_class = 'status-none';
            $days_diff = null;

            if (!empty($expire_date)) {
                $expire_ts = strtotime($expire_date);
                $days_diff = round(($expire_ts - $today_ts) / 86400);

                if ($days_diff < 0) {
                    $status_label = "Expired (" . abs($days_diff) . "d ago)";
                    $status_class = 'status-expired';
                } elseif ($days_diff == 0) {
                    $status_label = "Expires Today";
                    $status_class = 'status-expiring';
                } elseif ($days_diff <= 5) {
                    $status_label = "$days_diff days left";
                    $status_class = 'status-expiring';
                } else {
                    $status_label = "Active ($days_diff d left)";
                    $status_class = 'status-active';
                }
            }

            $matches[] = [
                'userid'       => $row['userid'],
                'username'     => $row['username'],
                'mobile'       => $row['mobile'],
                'gender'       => $row['gender'],
                'plan_name'    => $row['planName'] ?? 'No Plan',
                'expire_date'  => $expire_date,
                'status_label' => $status_label,
                'status_class' => $status_class,
                'days_diff'    => $days_diff,
                'photo_thumb'  => get_member_photo_url($row, '../')
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'query'   => $q,
        'count'   => count($matches),
        'matches' => $matches
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit();
}

echo json_encode(['success' => false, 'error' => 'Please provide a search parameter (id or q).']);
