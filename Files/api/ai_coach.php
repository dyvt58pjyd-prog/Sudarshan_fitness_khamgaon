<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../include/db_conn.php';
page_protect();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit();
}

$raw_input = file_get_contents('php://input');
$input = json_decode($raw_input, true);
if (!$input) {
    $input = $_POST;
}

$message = isset($input['message']) ? strtolower(trim($input['message'])) : '';
$userid = $_SESSION['user_data'];

if (empty($message)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Message is required.']);
    exit();
}

// Fetch user data for personalized responses
$uid_esc = mysqli_real_escape_string($con, $userid);
$q = mysqli_query($con, "SELECT username, fitness_goal, gym_rank, xp_points FROM users WHERE userid = '$uid_esc'");
$user = mysqli_fetch_assoc($q);
$name = explode(' ', $user['username'])[0]; // First name

// SMART-MATCHING ENGINE (Rule-based AI)
// This simulates a highly intelligent gym bot without needing an OpenAI key immediately.
$response_text = "";
$delay_ms = rand(600, 1500); // Simulate typing delay

// Detect Intent
if (preg_match('/(hi|hello|hey|sup)/i', $message)) {
    $response_text = "Hey there $name! I'm your Titan Virtual Coach 🦾. How can I help you crush your goals today?";
}
elseif (preg_match('/(diet|eat|food|nutrition|protein)/i', $message)) {
    if (strpos(strtolower($user['fitness_goal']), 'weight loss') !== false) {
        $response_text = "Since your goal is Weight Loss, focus on a high-protein, calorie-deficit diet. Lean meats, eggs, lots of veggies, and keep carbs low in the evening. Hydrate well! 💧";
    } else {
        $response_text = "To fuel those gains, you need about 1.6g to 2.2g of protein per kg of bodyweight! Chicken, eggs, paneer, and whey protein are your best friends. Eat in a slight caloric surplus. 🍗";
    }
}
elseif (preg_match('/(workout|routine|exercise|train|split)/i', $message)) {
    $response_text = "A great split for most members is Push/Pull/Legs. \n- **Push**: Chest, Shoulders, Triceps\n- **Pull**: Back, Biceps\n- **Legs**: Quads, Hamstrings, Calves\nRemember to log your workouts in the Muscle Heatmap above! 🔥";
}
elseif (preg_match('/(sore|pain|hurt|rest|recover)/i', $message)) {
    $response_text = "Soreness is just weakness leaving the body! But seriously, if a muscle is glowing Red on your heatmap, let it rest for 48 hours. Do some active recovery like walking or stretching. 🧘‍♂️";
}
elseif (preg_match('/(rank|xp|level|points)/i', $message)) {
    $response_text = "You currently have **" . $user['xp_points'] . " XP** and you are rank **" . $user['gym_rank'] . "**! Keep checking in and logging workouts to hit the legendary Titan rank! 🏆";
}
elseif (preg_match('/(time|open|close|hours)/i', $message)) {
    $response_text = "We are open 6:00 AM to 10:00 PM, Monday through Saturday. Sunday is rest day! ⏰";
}
elseif (preg_match('/(cost|fee|price|plan)/i', $message)) {
    $response_text = "Our memberships start at ₹1,000/month. We also have a special Pre-Booking offer right now: ₹2,000 off the annual plan! Speak to the admin to upgrade. 💰";
}
else {
    $response_text = "I'm still learning! But whether you want to talk about diets, workout routines, or your XP rank, I'm here for you, $name. 💪";
}

// TODO: In the future, you can hook up an OpenAI API key here:
/*
$openai_key = 'YOUR_KEY_HERE';
if ($openai_key) {
    // Call OpenAI endpoint
    // $response_text = $openai_response;
}
*/

echo json_encode([
    'success' => true,
    'response' => $response_text,
    'delay' => $delay_ms
]);
?>
