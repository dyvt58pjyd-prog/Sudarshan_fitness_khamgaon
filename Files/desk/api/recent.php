<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../include/db_conn.php';

header('Content-Type: application/json; charset=UTF-8');

// Staff permission check
$allowed_roles = ['super_admin', 'owner', 'reception', 'trainer', 'auditor'];
if (!isset($_SESSION['user_data']) || !isset($_SESSION['role']) || !in_array(strtolower($_SESSION['role']), $allowed_roles) || $_SESSION['role'] === 'member') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 25;
if ($limit <= 0 || $limit > 100) $limit = 25;

$sql = "SELECT e.et_id, e.uid, e.pid, e.paid_date, e.expire, e.renewal, e.paid_amount, e.discount_amount, e.balance, e.payment_mode, e.received_by,
               u.username, u.mobile, u.gender, u.joining_date,
               p.planName, p.amount as catalog_price
        FROM enrolls_to e
        INNER JOIN users u ON e.uid = u.userid
        LEFT JOIN plan p ON e.pid = p.pid
        ORDER BY e.et_id DESC
        LIMIT $limit";

$res = mysqli_query($con, $sql);
$items = [];

if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        // Distinguish between New Admission vs Renewal
        // If joining_date is within 1 day of paid_date, or first ever enrollment, it's New Admission
        $is_new = ($row['joining_date'] === $row['paid_date']);

        $items[] = [
            'et_id'        => $row['et_id'],
            'uid'          => $row['uid'],
            'username'     => $row['username'],
            'mobile'       => $row['mobile'],
            'gender'       => $row['gender'],
            'plan_name'    => $row['planName'] ?? 'Custom Plan',
            'paid_amount'  => floatval($row['paid_amount'] ?? 0),
            'balance'      => floatval($row['balance'] ?? 0),
            'paid_date'    => $row['paid_date'],
            'expire'       => $row['expire'],
            'payment_mode' => $row['payment_mode'] ?? 'Cash',
            'received_by'  => $row['received_by'] ?? 'Desk',
            'type'         => $is_new ? 'New Registration' : 'Renewal',
            'receipt_url'  => "receipt.php?etid={$row['et_id']}&uid={$row['uid']}"
        ];
    }
}

echo json_encode([
    'success' => true,
    'count'   => count($items),
    'items'   => $items
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
