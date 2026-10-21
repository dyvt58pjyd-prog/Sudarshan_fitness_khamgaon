<?php
/**
 * Sudarshan Fitness Native App REST API - Member Dashboard Data
 * 
 * Endpoint: GET or POST /api/member_dashboard.php or /Files/api/member_dashboard.php
 * Accepts:
 *   - ?uid=1001 (query parameter)
 *   - JSON body: {"uid": "1001"}
 *   - Header: Authorization: Bearer <token>
 */

// Enable CORS for mobile apps
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../include/db_conn.php';

// Extract UID from token, query param, or JSON body
$uid = '';

// 1. From Authorization Header
$headers = getallheaders();
$auth_header = $headers['Authorization'] ?? $headers['authorization'] ?? '';
if (!empty($auth_header) && preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
    $decoded_token = json_decode(base64_decode($matches[1]), true);
    if (is_array($decoded_token) && !empty($decoded_token['uid'])) {
        $uid = trim($decoded_token['uid']);
    }
}

// 2. From Query or POST body
if (empty($uid)) {
    $raw_input = file_get_contents('php://input');
    $json_data = json_decode($raw_input, true);
    if (is_array($json_data) && !empty($json_data['uid'])) {
        $uid = trim($json_data['uid']);
    } elseif (isset($_REQUEST['uid'])) {
        $uid = trim($_REQUEST['uid']);
    }
}

if (empty($uid)) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'code' => 'MISSING_UID',
        'message' => 'Member UID parameter or valid Authorization token is required.'
    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit();
}

$uid_esc = mysqli_real_escape_string($con, $uid);

// 1. Fetch User Record
$q_user = mysqli_query($con, "SELECT * FROM users WHERE userid = '$uid_esc' LIMIT 1");
if (!$q_user || mysqli_num_rows($q_user) === 0) {
    http_response_code(404);
    echo json_encode([
        'status' => 'error',
        'code' => 'MEMBER_NOT_FOUND',
        'message' => "Member with ID '$uid' was not found."
    ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit();
}

$user = mysqli_fetch_assoc($q_user);

$proto = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? 'sudarshanfitness.de';
$base_url = rtrim($proto . $host, '/');

// Photo resolution
$photo_url = '';
if (!empty($user['photo_base64']) && strpos($user['photo_base64'], 'data:image') === 0) {
    $photo_url = $user['photo_base64'];
} elseif (!empty($user['photo'])) {
    $clean_p = ltrim(str_replace('../', '', $user['photo']), '/');
    $photo_url = $base_url . '/' . $clean_p;
}

// 2. Fetch Active Membership Plan
$q_plan = mysqli_query($con, "
    SELECT e.*, p.planName, p.validity, p.amount as plan_catalog_price 
    FROM enrolls_to e 
    LEFT JOIN plan p ON e.pid = p.pid 
    WHERE e.uid = '$uid_esc' 
    ORDER BY e.expire DESC, e.et_id DESC 
    LIMIT 5
");

$membership = [
    'has_plan' => false,
    'plan_name' => 'No Active Plan',
    'validity' => 'N/A',
    'start_date' => null,
    'expire_date' => null,
    'days_left' => 0,
    'is_expired' => true,
    'status' => 'expired', // active | expiring_soon | expired
    'paid_amount' => 0,
    'paid_date' => null
];

$recent_payments = [];
$today_str = date('Y-m-d');
$today_ts = strtotime($today_str);

if ($q_plan && mysqli_num_rows($q_plan) > 0) {
    $first = true;
    while ($row = mysqli_fetch_assoc($q_plan)) {
        if ($first) {
            $membership['has_plan'] = true;
            $membership['plan_name'] = !empty($row['planName']) ? $row['planName'] : 'Standard Plan';
            $membership['validity'] = !empty($row['validity']) ? $row['validity'] : 'N/A';
            $membership['start_date'] = $row['start_date'];
            $membership['expire_date'] = $row['expire'];
            $membership['paid_amount'] = floatval($row['paid_amount']);
            $membership['paid_date'] = $row['paid_date'];

            if (!empty($row['expire'])) {
                $exp_ts = strtotime($row['expire']);
                $diff_days = (int)ceil(($exp_ts - $today_ts) / 86400);
                $membership['days_left'] = $diff_days;

                if ($diff_days < 0) {
                    $membership['is_expired'] = true;
                    $membership['status'] = 'expired';
                } elseif ($diff_days <= 5) {
                    $membership['is_expired'] = false;
                    $membership['status'] = 'expiring_soon';
                } else {
                    $membership['is_expired'] = false;
                    $membership['status'] = 'active';
                }
            }
            $first = false;
        }

        $recent_payments[] = [
            'et_id' => $row['et_id'],
            'plan_name' => $row['planName'] ?? 'Subscription',
            'paid_amount' => floatval($row['paid_amount']),
            'paid_date' => $row['paid_date'],
            'start_date' => $row['start_date'],
            'expire_date' => $row['expire'],
            'payment_mode' => $row['payment_mode'] ?? 'Cash'
        ];
    }
}

// 3. Attendance Analytics & Streak
$streak = function_exists('get_member_streak') ? get_member_streak($con, $uid) : 0;

$q_att_total = mysqli_query($con, "SELECT COUNT(DISTINCT date) as total_days FROM attendance WHERE uid = '$uid_esc'");
$total_attended_days = ($q_att_total && $ra = mysqli_fetch_assoc($q_att_total)) ? intval($ra['total_days']) : 0;

$curr_month = date('Y-m');
$q_att_month = mysqli_query($con, "SELECT COUNT(DISTINCT date) as month_days FROM attendance WHERE uid = '$uid_esc' AND date LIKE '$curr_month%'");
$month_attended_days = ($q_att_month && $rm = mysqli_fetch_assoc($q_att_month)) ? intval($rm['month_days']) : 0;

$q_today = mysqli_query($con, "SELECT * FROM attendance WHERE uid = '$uid_esc' AND date = '$today_str' LIMIT 1");
$today_checked_in = ($q_today && mysqli_num_rows($q_today) > 0);

$q_last = mysqli_query($con, "SELECT date, time FROM attendance WHERE uid = '$uid_esc' ORDER BY date DESC, time DESC LIMIT 1");
$last_checkin = ($q_last && $rl = mysqli_fetch_assoc($q_last)) ? ($rl['date'] . ' ' . $rl['time']) : null;

$attendance = [
    'streak_days' => $streak,
    'total_days_attended' => $total_attended_days,
    'current_month_attended' => $month_attended_days,
    'today_checked_in' => $today_checked_in,
    'last_checkin' => $last_checkin
];

// 4. Routines & Diet
$q_routine = mysqli_query($con, "SELECT * FROM member_routines WHERE uid = '$uid_esc' LIMIT 1");
$workout_plan = "Custom routine pending coach review.";
$diet_plan = "Custom nutrition plan pending coach review.";
$trainer_name = "Assigned General Floor Coach";
$trainer_mobile = "";

if ($q_routine && mysqli_num_rows($q_routine) > 0) {
    $rout = mysqli_fetch_assoc($q_routine);
    if (!empty($rout['workout_plan'])) $workout_plan = $rout['workout_plan'];
    if (!empty($rout['diet_plan'])) $diet_plan = $rout['diet_plan'];
    if (!empty($rout['trainer_id'])) {
        $tr_id = mysqli_real_escape_string($con, $rout['trainer_id']);
        $qt = mysqli_query($con, "SELECT Full_name, mobile FROM admin WHERE username='$tr_id' LIMIT 1");
        if ($qt && $rt = mysqli_fetch_assoc($qt)) {
            $trainer_name = $rt['Full_name'];
            $trainer_mobile = $rt['mobile'];
        }
    }
}

// 5. Health Vitals
$q_health = mysqli_query($con, "SELECT * FROM health_status WHERE uid = '$uid_esc' LIMIT 1");
$vitals = [
    'height' => null,
    'weight' => null,
    'bmi' => null,
    'bmi_category' => 'Unknown',
    'fat' => null,
    'calorie' => null,
    'remarks' => null
];

if ($q_health && mysqli_num_rows($q_health) > 0) {
    $h = mysqli_fetch_assoc($q_health);
    $wt = floatval($h['weight'] ?? 0);
    $ht = floatval($h['height'] ?? 0);
    $vitals['weight'] = $wt > 0 ? $wt : null;
    $vitals['height'] = $ht > 0 ? $ht : null;
    $vitals['fat'] = !empty($h['fat']) ? floatval($h['fat']) : null;
    $vitals['calorie'] = !empty($h['calorie']) ? floatval($h['calorie']) : null;
    $vitals['remarks'] = $h['remarks'] ?? null;

    if ($wt > 0 && $ht > 0) {
        $ht_meters = $ht / 100;
        $bmi = round($wt / ($ht_meters * $ht_meters), 1);
        $vitals['bmi'] = $bmi;
        if ($bmi < 18.5) {
            $vitals['bmi_category'] = 'Underweight';
        } elseif ($bmi < 24.9) {
            $vitals['bmi_category'] = 'Normal weight';
        } elseif ($bmi < 29.9) {
            $vitals['bmi_category'] = 'Overweight';
        } else {
            $vitals['bmi_category'] = 'Obese';
        }
    }
}

// 6. Gym Info
$gym = function_exists('get_gym_details') ? get_gym_details($con) : [];
$gym_info = [
    'gym_name' => !empty($gym['gym_name']) ? $gym['gym_name'] : 'SUDARSHAN FITNESS',
    'gym_address' => !empty($gym['gym_address']) ? $gym['gym_address'] : 'Station Road, Khamgaon',
    'gym_contact' => !empty($gym['gym_contact']) ? $gym['gym_contact'] : '9325205075',
    'gym_email' => !empty($gym['gym_email']) ? $gym['gym_email'] : 'sudarshan.fitness.khm@gmail.com',
    'gym_logo_url' => $base_url . '/images/logo.png'
];

// Return complete native dashboard state
http_response_code(200);
echo json_encode([
    'status' => 'success',
    'code' => 'DASHBOARD_DATA',
    'data' => [
        'member' => [
            'userid' => $user['userid'],
            'username' => $user['username'],
            'mobile' => $user['mobile'],
            'email' => $user['email'] ?? '',
            'gender' => $user['gender'] ?? '',
            'dob' => $user['dob'] ?? '',
            'joining_date' => $user['joining_date'] ?? '',
            'gym_rank' => $user['gym_rank'] ?? 'Beginner',
            'xp_points' => intval($user['xp_points'] ?? 0),
            'photo_url' => $photo_url,
            'partner_uid' => $user['partner_uid'] ?? null
        ],
        'membership' => $membership,
        'attendance' => $attendance,
        'vitals' => $vitals,
        'routines' => [
            'workout_plan' => $workout_plan,
            'diet_plan' => $diet_plan,
            'trainer_name' => $trainer_name,
            'trainer_mobile' => $trainer_mobile
        ],
        'recent_payments' => $recent_payments,
        'gym_info' => $gym_info
    ]
], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
exit();
