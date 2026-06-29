<?php
require_once '../include/db_conn.php';

header('Content-Type: application/json');

// Security check: restrict access to localhost
$remote_ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
if ($remote_ip !== '127.0.0.1' && $remote_ip !== '::1' && !empty($remote_ip)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access Denied']);
    exit();
}

$phone = isset($_GET['phone']) ? trim($_GET['phone']) : '';
if (empty($phone)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Phone parameter is required']);
    exit();
}

// Clean phone number: strip non-digits, country code if 91 is prepended
$clean_phone = preg_replace('/\D/', '', $phone);
if (strlen($clean_phone) === 12 && substr($clean_phone, 0, 2) === '91') {
    $clean_phone = substr($clean_phone, 2);
}

$clean_phone = mysqli_real_escape_string($con, $clean_phone);

$sql = "SELECT u.userid, u.username, u.gender, u.mobile, u.email,
               h.calorie, h.height, h.weight, h.fat, h.remarks,
               e.paid_date, e.expire, p.planName
        FROM users u
        LEFT JOIN health_status h ON u.userid = h.uid
        LEFT JOIN enrolls_to e ON u.userid = e.uid AND e.renewal = 'yes'
        LEFT JOIN plan p ON e.pid = p.pid
        WHERE u.mobile = '$clean_phone' OR u.mobile LIKE '%$clean_phone%'
        ORDER BY e.expire DESC LIMIT 1";

$res = mysqli_query($con, $sql);

if ($res && mysqli_num_rows($res) > 0) {
    $data = mysqli_fetch_assoc($res);
    $userid_esc = mysqli_real_escape_string($con, $data['userid']);
    
    // 1. Fetch attendance streak
    $data['streak'] = get_member_streak($con, $data['userid']);
    
    // 2. Fetch assigned personal trainer (either from current active PT enrollment or users table)
    $q_trainer = mysqli_query($con, "
        (SELECT a.Full_name FROM pt_enrollments pe 
         INNER JOIN admin a ON pe.trainer_id = a.username 
         WHERE pe.uid = '$userid_esc' AND pe.expire_date >= CURRENT_DATE() 
         ORDER BY pe.expire_date DESC LIMIT 1)
        UNION
        (SELECT a.Full_name FROM users u 
         INNER JOIN admin a ON u.trainer_id = a.username 
         WHERE u.userid = '$userid_esc' LIMIT 1)
        LIMIT 1
    ");
    $trainer_name = 'None Assigned';
    if ($q_trainer && mysqli_num_rows($q_trainer) > 0) {
        $tr_row = mysqli_fetch_assoc($q_trainer);
        $trainer_name = !empty($tr_row['Full_name']) ? $tr_row['Full_name'] : 'None Assigned';
    }
    $data['trainer_name'] = $trainer_name;
    
    // 3. Compute subscription payment status
    if (empty($data['expire'])) {
        $data['payment_status'] = 'No Active Subscription';
    } else {
        $today = date('Y-m-d');
        if (strtotime($data['expire']) >= strtotime($today)) {
            $data['payment_status'] = 'Paid (Active)';
        } else {
            $data['payment_status'] = 'Expired / Renewal Pending';
        }
    }
    
    // 4. Fetch random health/gym tip
    $q_tip = mysqli_query($con, "SELECT tip_text, category FROM gym_tips ORDER BY RAND() LIMIT 1");
    if ($q_tip && mysqli_num_rows($q_tip) > 0) {
        $tip_row = mysqli_fetch_assoc($q_tip);
        $data['random_tip'] = [
            'text' => $tip_row['tip_text'],
            'category' => $tip_row['category']
        ];
    } else {
        $data['random_tip'] = [
            'text' => "Consistency is key! Every workout counts. 🏋️",
            'category' => "General"
        ];
    }

    $data['success'] = true;
    echo json_encode($data);
} else {
    echo json_encode(['success' => false, 'message' => 'Member not found']);
}
?>
