<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../include/db_conn.php';

date_default_timezone_set("Asia/Calcutta");

$phone = isset($_GET['phone']) ? trim($_GET['phone']) : '';
$command = isset($_GET['command']) ? trim(strtolower($_GET['command'])) : '';

if (empty($phone) || empty($command)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Phone and command are required parameters.'
    ]);
    exit();
}

// Clean incoming phone to last 10 digits
$phone_digits = preg_replace('/\D/', '', $phone);
if (strlen($phone_digits) < 10) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid phone number format.'
    ]);
    exit();
}

$phone_digits_esc = mysqli_real_escape_string($con, $phone_digits);

// Check if this phone belongs to an admin (matching last 10 digits to be country-code agnostic)
$q_admin = mysqli_query($con, "SELECT * FROM admin WHERE RIGHT(REPLACE(REPLACE(REPLACE(mobile, '+', ''), ' ', ''), '-', ''), 10) = RIGHT('$phone_digits_esc', 10) LIMIT 1");

if (!$q_admin || mysqli_num_rows($q_admin) === 0) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized: This phone number is not linked to any administrator profile.'
    ]);
    exit();
}

$admin_user = mysqli_fetch_assoc($q_admin);
$admin_name = $admin_user['Full_name'];

$today = date('Y-m-d');
$response_text = "";

if ($command === 'report' || $command === '/report') {
    // 1. Membership collections today
    $q_mem = mysqli_query($con, "SELECT e.paid_amount, e.discount_amount, p.amount FROM enrolls_to e 
                                 INNER JOIN plan p ON e.pid = p.pid 
                                 WHERE e.paid_date = '$today'");
    $membership_today = 0;
    if ($q_mem) {
        while ($row = mysqli_fetch_assoc($q_mem)) {
            if ($row['paid_amount'] !== null) {
                $membership_today += intval($row['paid_amount']);
            } else {
                $membership_today += (intval($row['amount']) - intval($row['discount_amount']));
            }
        }
    }

    // 2. PT collections today
    $q_pt = mysqli_query($con, "SELECT SUM(amount) as total FROM pt_enrollments WHERE enroll_date = '$today'");
    $pt_today = 0;
    if ($q_pt && $row = mysqli_fetch_assoc($q_pt)) {
        $pt_today = isset($row['total']) ? intval($row['total']) : 0;
    }

    // 3. Expenses today
    $q_exp = mysqli_query($con, "SELECT SUM(amount) as total FROM expenses WHERE expense_date = '$today'");
    $expenses_today = 0;
    if ($q_exp && $row = mysqli_fetch_assoc($q_exp)) {
        $expenses_today = isset($row['total']) ? intval($row['total']) : 0;
    }

    $net_balance = ($membership_today + $pt_today) - $expenses_today;

    // 4. Checked in count today
    $q_in = mysqli_query($con, "SELECT COUNT(*) as total FROM attendance WHERE date = '$today' AND (exit_time IS NULL OR exit_time = '00:00:00')");
    $checked_in = 0;
    if ($q_in && $row = mysqli_fetch_assoc($q_in)) {
        $checked_in = intval($row['total']);
    }

    // 5. New registrations today
    $q_reg = mysqli_query($con, "SELECT COUNT(*) as total FROM users WHERE joining_date = '$today'");
    $registrations = 0;
    if ($q_reg && $row = mysqli_fetch_assoc($q_reg)) {
        $registrations = intval($row['total']);
    }

    $response_text = "🏋️ *Sudarshan Fitness - Today's Summary* 🏋️\n\n" .
                     "📅 Date: *" . date('d-M-Y') . "*\n" .
                     "👤 Admin requested: *{$admin_name}*\n\n" .
                     "💰 *Financial Summary:*\n" .
                     "• Membership Collections: *₹" . number_format($membership_today) . "*\n" .
                     "• PT Collections: *₹" . number_format($pt_today) . "*\n" .
                     "• Gym Expenses: *₹" . number_format($expenses_today) . "*\n" .
                     "• *Net Cashflow:* *" . ($net_balance >= 0 ? '₹' : '-₹') . number_format(abs($net_balance)) . "*\n\n" .
                     "👥 *Attendance & Registration:*\n" .
                     "• Active Checked-in Members: *{$checked_in}*\n" .
                     "• New Registrations Today: *{$registrations}*\n\n" .
                     "To get lists of members currently inside, reply with *who is in*. 💪";

} elseif ($command === 'who is in' || $command === 'whoisin' || $command === '/whoisin') {
    $q_members = mysqli_query($con, "SELECT u.username, a.entry_time FROM attendance a 
                                     INNER JOIN users u ON a.uid = u.userid 
                                     WHERE a.date = '$today' AND (a.exit_time IS NULL OR a.exit_time = '00:00:00') 
                                     ORDER BY a.entry_time DESC");
    
    if ($q_members && mysqli_num_rows($q_members) > 0) {
        $response_text = "👥 *Members Currently inside Facility:* \n\n";
        $idx = 1;
        while ($row = mysqli_fetch_assoc($q_members)) {
            $response_text .= "{$idx}. *{$row['username']}* (In: " . date('h:i A', strtotime($row['entry_time'])) . ")\n";
            $idx++;
        }
        $total_in = mysqli_num_rows($q_members);
        $response_text .= "\n*Total:* *{$total_in} active members* inside.";
    } else {
        $response_text = "👥 *Entrance Activity Notice:* \n\nNo gym members are currently checked inside the facility.";
    }

} elseif ($command === 'status' || $command === '/status') {
    $response_text = "⚙️ *Sudarshan Fitness - System Health Check:* \n\n" .
                     "• Local MySQL Database: *🟢 CONNECTED (Online)*\n" .
                     "• Server Date: *" . date('d-M-Y') . "*\n" .
                     "• Server Time: *" . date('h:i A') . "*\n" .
                     "• Timezone: *Asia/Calcutta*";
} else {
    $response_text = "❌ *Command Not Recognized.*\n\n" .
                     "Hi *{$admin_name}*, you can use the following remote control commands:\n\n" .
                     "👉 *report* - Daily cash collections and attendance totals.\n" .
                     "👉 *who is in* - Names and entry times of people currently working out.\n" .
                     "👉 *status* - System connectivity check.";
}

echo json_encode([
    'success' => true,
    'message' => $response_text
]);
exit();
