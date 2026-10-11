<?php
require '../../include/db_conn.php';
page_protect();

header('Content-Type: application/json; charset=UTF-8');

if ($_SESSION['role'] === 'member') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$id = isset($_GET['id']) ? trim(mysqli_real_escape_string($con, $_GET['id'])) : '';
$q = isset($_GET['q']) ? trim(mysqli_real_escape_string($con, $_GET['q'])) : '';

date_default_timezone_set("Asia/Calcutta");
$today = date('Y-m-d');

// 1. Exact ID Lookup
if ($id !== '') {
    $sql = "SELECT u.userid, u.username, u.mobile, u.gender, u.dob, u.email, u.joining_date, u.partner_uid,
                   a.streetName, a.city, a.zipcode, a.state,
                   e.pid, e.expire, e.paid_date, e.paid_amount, e.payment_mode,
                   p.planName, p.amount
            FROM users u
            LEFT JOIN address a ON u.userid = a.id
            LEFT JOIN (
                SELECT e1.uid, e1.pid, e1.expire, e1.paid_date, e1.paid_amount, e1.payment_mode
                FROM enrolls_to e1
                INNER JOIN (
                    SELECT uid, MAX(et_id) as max_et_id
                    FROM enrolls_to
                    GROUP BY uid
                ) e2 ON e1.et_id = e2.max_et_id
            ) e ON u.userid = e.uid
            LEFT JOIN plan p ON e.pid = p.pid
            WHERE u.userid = '$id' OR u.mobile = '$id'
            LIMIT 1";

    $res = mysqli_query($con, $sql);
    if ($res && mysqli_num_rows($res) > 0) {
        $row = mysqli_fetch_assoc($res);
        $partner_name = '';
        if (!empty($row['partner_uid'])) {
            $qp = mysqli_query($con, "SELECT username FROM users WHERE userid = '" . mysqli_real_escape_string($con, $row['partner_uid']) . "'");
            if ($qp && $prow = mysqli_fetch_assoc($qp)) {
                $partner_name = $prow['username'];
            }
        }

        $is_active = (!empty($row['expire']) && strtotime($row['expire']) >= strtotime($today));

        echo json_encode([
            'success' => true,
            'data' => [
                'userid'       => $row['userid'],
                'username'     => $row['username'],
                'mobile'       => $row['mobile'],
                'gender'       => $row['gender'],
                'dob'          => $row['dob'],
                'email'        => $row['email'],
                'joining_date' => $row['joining_date'],
                'streetName'   => $row['streetName'] ?? '',
                'city'         => $row['city'] ?? '',
                'zipcode'      => $row['zipcode'] ?? '',
                'state'        => $row['state'] ?? 'Maharashtra',
                'pid'          => $row['pid'] ?? '',
                'plan_name'    => $row['planName'] ?? 'No Active Plan',
                'plan_price'   => $row['amount'] ?? 0,
                'paid_amount'  => $row['paid_amount'] ?? 0,
                'payment_mode' => $row['payment_mode'] ?? 'Cash',
                'expire'       => $row['expire'] ?? null,
                'is_active'    => $is_active,
                'partner_uid'  => $row['partner_uid'] ?? null,
                'partner_name' => $partner_name
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit();
    } else {
        echo json_encode(['success' => false, 'error' => 'Member not found with ID or Mobile: ' . $id]);
        exit();
    }
}

// 2. Search Autocomplete (q)
if ($q !== '') {
    $sql = "SELECT u.userid, u.username, u.mobile, u.partner_uid,
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
            WHERE (u.userid LIKE '%$q%' OR u.username LIKE '%$q%' OR u.mobile LIKE '%$q%')
              AND u.userid != 'PENDING'
              AND LOWER(u.userid) != 'membership id'
            ORDER BY CAST(u.userid AS UNSIGNED) ASC
            LIMIT 15";

    $res = mysqli_query($con, $sql);
    $matches = [];
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $is_active = (!empty($row['expire']) && strtotime($row['expire']) >= strtotime($today));
            $matches[] = [
                'userid'      => $row['userid'],
                'username'    => $row['username'],
                'mobile'      => $row['mobile'],
                'plan_name'   => $row['planName'] ?? 'No Plan',
                'expire'      => $row['expire'] ?? '',
                'is_active'   => $is_active,
                'partner_uid' => $row['partner_uid']
            ];
        }
    }

    echo json_encode(['success' => true, 'matches' => $matches]);
    exit();
}

echo json_encode(['success' => false, 'error' => 'Missing parameter id or q']);
