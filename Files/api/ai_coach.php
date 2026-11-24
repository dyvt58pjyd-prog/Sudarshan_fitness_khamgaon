<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../include/db_conn.php';
require_once __DIR__ . '/../include/gemini_config.php';
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

$raw_message = isset($input['message']) ? trim($input['message']) : '';
$message = strtolower($raw_message);
$userid = $_SESSION['user_data'];

if (empty($message)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Message is required.']);
    exit();
}

// Fetch user data for personalized responses
$uid_esc = mysqli_real_escape_string($con, $userid);
$q = mysqli_query($con, "SELECT u.username, u.fitness_goal, u.gym_rank, u.xp_points, h.weight, h.height FROM users u LEFT JOIN health_status h ON u.userid = h.uid WHERE u.userid = '$uid_esc' ORDER BY h.hid DESC LIMIT 1");
$user = ($q && mysqli_num_rows($q) > 0) ? mysqli_fetch_assoc($q) : [
    'username' => $_SESSION['full_name'] ?? 'Athlete',
    'fitness_goal' => 'General Fitness',
    'gym_rank' => 'Beginner',
    'xp_points' => 0,
    'weight' => '',
    'height' => ''
];
$name = explode(' ', $user['username'])[0]; // First name

$response_text = "";
$delay_ms = 200;

$sys = "You are Sudarshan Fitness Virtual Coach in Khamgaon, India. Member: {$user['username']}, Goal: {$user['fitness_goal']}, Rank: {$user['gym_rank']}, XP: {$user['xp_points']}, Weight: {$user['weight']}kg, Height: {$user['height']}cm. Give concise, practical, motivating gym/Indian nutrition advice in 3-6 short bullet points or sentences.";
$ai_reply = query_gemini_ai($raw_message, $sys);

if (!empty($ai_reply)) {
    $response_text = $ai_reply;
} else {
    // Fallback smart-matching engine if AI provider is unavailable
    $delay_ms = rand(400, 900);
    if (preg_match('/(hi|hello|hey|sup)/i', $message)) {
        $response_text = "Hey there $name! I'm your Sudarshan Fitness Virtual Coach 🦾. How can I help you crush your goals today?";
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
        $response_text = "I'm here to help with diets, workout routines, or tracking your XP rank, $name! Ask me anything about your training. 💪";
    }
}

echo json_encode([
    'success' => true,
    'response' => $response_text,
    'delay' => $delay_ms
]);
?>
