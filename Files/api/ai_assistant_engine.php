<?php
require_once __DIR__ . '/../include/db_conn.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['user_data']) && !isset($_SESSION['userid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit();
}

$userid = isset($_SESSION['userid']) ? $_SESSION['userid'] : ($_SESSION['user_data']['userid'] ?? '');
$input = json_decode(file_get_contents('php://input'), true);
$query = strtolower(trim($input['query'] ?? ''));

if (empty($query)) {
    echo json_encode(['status' => 'error', 'message' => 'Please ask a question.']);
    exit();
}

// Fetch member context
$user_q = mysqli_query($con, "SELECT username, gender, fitness_goal, weight, height, xp_points, gym_rank FROM users WHERE userid = '$userid'");
$user_info = mysqli_fetch_assoc($user_q) ?: [
    'username' => 'Hunter',
    'gender' => 'Male',
    'fitness_goal' => 'General Fitness',
    'weight' => '70',
    'height' => '175',
    'xp_points' => '100',
    'gym_rank' => 'Beginner'
];

$name = htmlspecialchars($user_info['username']);
$goal = htmlspecialchars($user_info['fitness_goal'] ?: 'Muscle Gain & Fitness');
$rank = htmlspecialchars($user_info['gym_rank']);
$xp   = intval($user_info['xp_points']);

require_once __DIR__ . '/../include/gemini_config.php';

// Intent Analysis & Gemini AI Response Generation
$system_instruction = "You are Sudarshan AI Fitness Coach for gym member '$name' (Gender: {$user_info['gender']}, Goal: $goal, Rank: $rank, EXP: $xp). Provide friendly, highly practical gym workout, Indian nutrition, and fitness advice.";
$ai_response = query_gemini_ai("Member query: " . $input['query'], $system_instruction);

if (!empty($ai_response)) {
    $reply = "🤖 **Sudarshan AI Coach (Powered by Gemini):**\n\n" . $ai_response;
} else {
    // Intelligent Fallback
    if (strpos($query, 'workout today') !== false || strpos($query, 'today workout') !== false || strpos($query, 'routine') !== false) {
        $reply = "👑 **AI Coach Response for $name:**\n\nBased on your goal (**$goal**), your scheduled workout for today is:\n\n" .
                 "• **Bench Press / Dumbbell Press**: 4 Sets x 10 Reps\n" .
                 "• **Incline Dumbbell Flyes**: 3 Sets x 12 Reps\n" .
                 "• **Tricep Rope Pushdowns**: 4 Sets x 15 Reps\n" .
                 "• **Push-up Burnout**: 3 Sets to Failure\n\n" .
                 "🔥 *Tip: Log your completed reps in the 3D Workout Studio to earn +50 EXP!*";
    } elseif (strpos($query, 'chest') !== false) {
        $reply = "🏋️ **Personalized Chest Workout (Goal: $goal):**\n\n" .
                 "1. **Barbell Bench Press**: 4 Sets x 8-10 Reps (Focus on progressive overload)\n" .
                 "2. **Incline Dumbbell Press**: 4 Sets x 10-12 Reps\n" .
                 "3. **Cable Crossover / Pec Fly**: 3 Sets x 15 Reps\n" .
                 "4. **Dips or Push-ups**: 3 Sets to Failure\n\n" .
                 "Rest 60-90 seconds between sets!";
    } elseif (strpos($query, 'eat') !== false || strpos($query, 'dinner') !== false || strpos($query, 'diet') !== false) {
        $reply = "🥗 **Indian Nutrition Plan for $name (Goal: $goal):**\n\n" .
                 "• **Dinner Suggestion**: 180g Paneer / Chicken Breast + Steamed Vegetables + 1 Bowl Dal + 2 Chapati or Quinoa.\n" .
                 "• **Approx Macros**: ~480 Calories | 38g Protein | 42g Carbs | 12g Fat.\n\n" .
                 "💧 *Don't forget to drink 500ml water 30 minutes after dinner!*";
    } elseif (strpos($query, 'missed') !== false || strpos($query, 'skip') !== false) {
        $reply = "⚡ **No Worries, $name!**\n\n" .
                 "If you missed Monday's session, combine **Chest & Back** in a compound push-pull routine today, or push your workout schedule back by 1 day.\n\n" .
                 "Consistency beats perfection — let's crush today's session!";
    } elseif (strpos($query, 'progress') !== false || strpos($query, 'score') !== false || strpos($query, 'rank') !== false) {
        $reply = "📊 **Hunter Progress Overview:**\n\n" .
                 "• **Current Rank**: $rank Rank\n" .
                 "• **Total EXP**: $xp EXP\n" .
                 "• **Fitness Score**: 84 / 100 (Excellent 🌟)\n" .
                 "• **Goal**: $goal\n\n" .
                 "Keep logging workouts and daily visits to unlock the next Rank Tier!";
    } else {
        $reply = "🤖 **Sudarshan AI Coach:**\n\nGreetings $name! I'm your personal AI Fitness Assistant. I can help you with personalized workout routines, Indian diet meal plans, calorie targets, and progress tracking.\n\nTry asking:\n• *What is my workout today?*\n• *Give me a chest workout.*\n• *What should I eat for dinner?*\n• *How am I progressing?*";
    }
}

echo json_encode([
    'status' => 'success',
    'response' => $reply,
    'member_name' => $name,
    'fitness_goal' => $goal
]);
