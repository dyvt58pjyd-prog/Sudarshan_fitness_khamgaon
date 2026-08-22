<?php
// Sudarshan Fitness - Automated 24/7 WhatsApp AI Self-Service Bot Engine
header("Content-Type: application/json");
require '../include/db_conn.php';

$gym = get_gym_details($con);

// Read incoming JSON or GET/POST parameters
$input_json = file_get_contents('php://input');
$data = json_decode($input_json, true) ?? $_POST ?? $_GET;

$from_mobile = preg_replace('/[^0-9]/', '', $data['from'] ?? $data['mobile'] ?? $data['phone'] ?? '');
$message_body = trim($data['message'] ?? $data['body'] ?? $data['text'] ?? '');

if (empty($from_mobile) || empty($message_body)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing mobile number or message body.'
    ]);
    exit;
}

// Normalize last 10 digits for Indian numbers
if (strlen($from_mobile) > 10) {
    $from_mobile = substr($from_mobile, -10);
}

// Lookup Member by mobile
$q_user = mysqli_query($con, "SELECT * FROM users WHERE mobile LIKE '%$from_mobile' LIMIT 1");
$user = ($q_user && mysqli_num_rows($q_user) > 0) ? mysqli_fetch_assoc($q_user) : null;

$msg_upper = strtoupper(trim($message_body));
$reply = "";

// Check active plan if member found
$plan_name = "No Active Plan";
$expire_date = "N/A";
$is_expired = true;
if ($user) {
    $uid = $user['userid'];
    $q_plan = mysqli_query($con, "SELECT p.planName, e.expire FROM enrolls_to e INNER JOIN plan p ON e.pid = p.pid WHERE e.uid='$uid' AND e.renewal='yes' ORDER BY e.expire DESC LIMIT 1");
    if ($q_plan && mysqli_num_rows($q_plan) > 0) {
        $p_row = mysqli_fetch_assoc($q_plan);
        $plan_name = $p_row['planName'];
        $expire_date = date('d M Y', strtotime($p_row['expire']));
        $is_expired = (strtotime($p_row['expire']) < strtotime(date('Y-m-d')));
    }
}

// ── NLP Keyword Dispatcher ───────────────────────────────────────────────────

if (strpos($msg_upper, 'EXPIRY') !== false || strpos($msg_upper, 'VALIDITY') !== false || strpos($msg_upper, 'STATUS') !== false) {
    if ($user) {
        $status_emoji = $is_expired ? "🔴 EXPIRED" : "🟢 ACTIVE";
        $reply = "🏋️ *Sudarshan Fitness — Member Status*\n\n"
               . "👤 Athlete: *" . $user['username'] . "* (ID: #" . $user['userid'] . ")\n"
               . "📋 Plan: *" . $plan_name . "*\n"
               . "📅 Valid Till: *" . $expire_date . "* (" . $status_emoji . ")\n\n";
        if ($is_expired) {
            $reply .= "⚠️ Your membership is expired. Click here to renew online:\nhttps://sudarshanfitness.de/Files/dashboard/member/payment.php";
        } else {
            $reply .= "💪 Keep pushing your limits! See you on the gym floor today.";
        }
    } else {
        $reply = "👋 Welcome to *Sudarshan Fitness Khamgaon*!\nYour mobile number is not yet registered. Visit our gym or pre-book online:\nhttps://sudarshanfitness.de/Files/prebook.php";
    }
} elseif (strpos($msg_upper, 'TIMING') !== false || strpos($msg_upper, 'HOURS') !== false || strpos($msg_upper, 'BATCH') !== false) {
    $reply = "⏰ *Sudarshan Fitness Gym Timings*\n\n"
           . "🌅 *Morning Batch:* 06:00 AM – 11:00 AM\n"
           . "👩 *Women's Batch:* 11:00 AM – 01:00 PM\n"
           . "🌇 *Evening Batch:* 04:00 PM – 10:00 PM\n"
           . "📅 *Sunday:* 07:00 AM – 12:00 PM (Morning Only)\n\n"
           . "📍 *Location:* " . $gym['gym_address'] . "\n"
           . "📞 *Contact:* " . $gym['gym_contact'];
} elseif (strpos($msg_upper, 'RENEW') !== false || strpos($msg_upper, 'PAY') !== false || strpos($msg_upper, 'UPI') !== false || strpos($msg_upper, 'FEES') !== false) {
    $active_upi = !empty($gym['upi_id']) ? $gym['upi_id'] : 'anuragbawaskar4326@sbi';
    $reply = "💳 *Sudarshan Fitness — Instant Renewal & Payment*\n\n"
           . "Pay your gym membership or PT fees directly via UPI:\n\n"
           . "🔹 *Official UPI ID:* `" . $active_upi . "`\n"
           . "🔹 *Payee Name:* " . $gym['gym_name'] . "\n\n"
           . "🔗 *1-Click Instant Renewal Portal:*\nhttps://sudarshanfitness.de/Files/dashboard/member/payment.php\n\n"
           . "After paying, upload your receipt screenshot on the portal for instant activation! ✅";
} elseif (strpos($msg_upper, 'DIET') !== false || strpos($msg_upper, 'ROUTINE') !== false || strpos($msg_upper, 'WORKOUT') !== false) {
    if ($user) {
        $uid = $user['userid'];
        $q_rt = mysqli_query($con, "SELECT * FROM member_routines WHERE uid='$uid'");
        if ($q_rt && mysqli_num_rows($q_rt) > 0) {
            $rt = mysqli_fetch_assoc($q_rt);
            $reply = "🥗 *Your Custom Diet & Workout Routine*\n\n"
                   . "🏋️ *Workout Split:*\n" . ($rt['workout_plan'] ?: 'General Full Body Routine') . "\n\n"
                   . "🍎 *Diet Plan:*\n" . ($rt['diet_plan'] ?: 'Standard High Protein Diet') . "\n\n"
                   . "📲 View on your Member App: https://sudarshanfitness.de/Files/member_app/";
        } else {
            $reply = "🥗 *Sudarshan Fitness Diet & Workout*\nYour personalized routine is being prepared by your trainer. Please check your Member App:\nhttps://sudarshanfitness.de/Files/member_app/";
        }
    } else {
        $reply = "🥗 Custom diet & workout plans are available for all registered members. Join us today!\nhttps://sudarshanfitness.de/Files/prebook.php";
    }
} elseif (strpos($msg_upper, 'STORE') !== false || strpos($msg_upper, 'SUPPLEMENT') !== false || strpos($msg_upper, 'PROTEIN') !== false) {
    $reply = "💊 *Sudarshan Nutrition & Supplement Store*\n\n"
           . "100% Authentic Whey Protein, Creatine, Pre-Workouts & Gym Apparel at discounted member prices!\n\n"
           . "🛍️ *Browse Store Catalog:*\nhttps://sudarshanfitness.de/Files/member_app/store.php";
} else {
    // Default Help Menu
    $name_salutation = $user ? ("Athlete " . explode(' ', $user['username'])[0]) : "Friend";
    $reply = "👋 *Hello " . $name_salutation . "! Welcome to Sudarshan Fitness 24/7 AI Bot.* 🤖\n\n"
           . "Reply with any keyword to get instant answers:\n\n"
           . "👉 *EXPIRY* — Check plan validity & expiry date\n"
           . "👉 *TIMINGS* — View morning, evening & women's batch hours\n"
           . "👉 *PAY* — Get official UPI ID & renewal link\n"
           . "👉 *DIET* — View assigned diet & workout routine\n"
           . "👉 *STORE* — Browse protein & nutrition store\n"
           . "👉 *PASS* — Access your digital mobile gym pass\n\n"
           . "📍 Station Road, Khamgaon | 📞 " . $gym['gym_contact'];
}

echo json_encode([
    'status' => 'success',
    'from' => $from_mobile,
    'reply' => $reply
]);
