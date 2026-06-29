<?php
// AI Fitness Counselor & Diet Customizer Endpoint
require_once __DIR__ . '/../include/db_conn.php';
page_protect();

header("Content-Type: application/json; charset=UTF-8");
date_default_timezone_set("Asia/Calcutta");

$uid = $_SESSION['user_data'];
$uid_clean = mysqli_real_escape_string($con, $uid);

// Fetch member physical stats
$sql_stats = "SELECT height, weight, fat, remarks FROM health_status WHERE uid = '$uid_clean' ORDER BY hid DESC LIMIT 1";
$res_stats = mysqli_query($con, $sql_stats);
$stats = ($res_stats && mysqli_num_rows($res_stats) > 0) ? mysqli_fetch_assoc($res_stats) : null;

$height = ($stats && !empty($stats['height'])) ? floatval($stats['height']) : 170.0;
$weight = ($stats && !empty($stats['weight'])) ? floatval($stats['weight']) : 70.0;
$fat = ($stats && !empty($stats['fat'])) ? floatval($stats['fat']) : 15.0;

// Read POST data
$input = json_decode(file_get_contents('php://input'), true);
$goal = isset($input['goal']) ? trim($input['goal']) : 'Fat Loss';
$diet = isset($input['diet']) ? trim($input['diet']) : 'Vegetarian';
$medical = isset($input['medical']) ? trim($input['medical']) : 'None';
$send_wa = isset($input['send_whatsapp']) && $input['send_whatsapp'] === true;

// Compute BMI
$height_m = $height / 100;
$bmi = ($height_m > 0) ? round($weight / ($height_m * $height_m), 1) : 22.0;

$bmi_category = 'Normal';
if ($bmi < 18.5) $bmi_category = 'Underweight';
elseif ($bmi < 24.9) $bmi_category = 'Normal';
elseif ($bmi < 29.9) $bmi_category = 'Overweight';
else $bmi_category = 'Obese';

// Compute daily maintenance calories (simple Harris-Benedict approximation)
$bmr = 10 * $weight + 6.25 * $height - 5 * 25 + 5; // standard approximation for active male
$tdee = round($bmr * 1.375); // Light activity level

// Formulate target calories based on goal
$target_cal = $tdee;
if ($goal === 'Fat Loss') {
    $target_cal = $tdee - 500;
} elseif ($goal === 'Muscle Gain') {
    $target_cal = $tdee + 400;
} elseif ($goal === 'Lean Builder') {
    $target_cal = $tdee - 100;
}

// Generate Custom Workout Chart based on goal and medical remarks
$workout = [];
if ($goal === 'Fat Loss') {
    $workout = [
        "Day 1" => "Push Day (Chest/Shoulders/Triceps) + 15m Cardio",
        "Day 2" => "Pull Day (Back/Biceps) + 15m Cardio",
        "Day 3" => "Legs & Abs Conditioning",
        "Day 4" => "HIIT Cardio Circuit (Rowing, Cycle, Treadmill Incline)",
        "Day 5" => "Full Body Core & Calisthenics",
        "Notes" => "Focus on progressive overload. Keep rest times to 45-60 seconds. Medical constraint alert: " . htmlspecialchars($medical)
    ];
} elseif ($goal === 'Muscle Gain') {
    $workout = [
        "Day 1" => "Heavy Chest & Back Strength Builder",
        "Day 2" => "Hypertrophy Arms & Shoulders",
        "Day 3" => "Heavy Legs & Squats",
        "Day 4" => "Upper Body Push/Pull Splits",
        "Day 5" => "Leg Extensions & Core Builders",
        "Notes" => "Prioritize form and heavy lifts. Rest 90-120 seconds between heavy sets. Medical constraint alert: " . htmlspecialchars($medical)
    ];
} else { // Lean Builder / Strength
    $workout = [
        "Day 1" => "Full Body Conditioning Split",
        "Day 2" => "Upper Body Hypertrophy",
        "Day 3" => "Lower Body Functional Strength",
        "Day 4" => "Core Stability & Active Mobility",
        "Day 5" => "Power & Speed Drills",
        "Notes" => "Balance cardio and weights. Stretch for 10 minutes post-workout. Medical constraint alert: " . htmlspecialchars($medical)
    ];
}

// Modify workouts dynamically if joint pain / injuries are noted
if (stripos($medical, 'knee') !== false || stripos($medical, 'leg') !== false) {
    foreach ($workout as $day => $desc) {
        if (stripos($desc, 'legs') !== false || stripos($desc, 'squat') !== false) {
            $workout[$day] = str_ireplace(['heavy legs', 'squats'], 'Low-Impact Legs (No Squats, Leg Press / Leg Curl only)', $desc);
        }
    }
}
if (stripos($medical, 'shoulder') !== false || stripos($medical, 'shoulder') !== false) {
    foreach ($workout as $day => $desc) {
        if (stripos($desc, 'shoulders') !== false || stripos($desc, 'overhead') !== false) {
            $workout[$day] = $desc . " (Avoid overhead heavy presses - side raises only)";
        }
    }
}

// Generate custom diet plan based on target calories and diet preference
$diet_plan = [];
if ($diet === 'Vegetarian') {
    $diet_plan = [
        "Meal 1 (Breakfast)" => "Oatmeal with whey protein, almonds, and banana slice (approx. 450 kcal)",
        "Meal 2 (Lunch)" => "Paneer Bhurji (150g) with 2 whole wheat chapatis and green salad (approx. 550 kcal)",
        "Meal 3 (Snack)" => "Roasted chana (50g) or double-toned milk paneer (approx. 200 kcal)",
        "Meal 4 (Dinner)" => "Soya chunks curry with brown rice (1 cup) and boiled lentils (approx. 500 kcal)",
        "Macro Targets" => "Protein: ~110g, Carbs: ~220g, Fats: ~65g"
    ];
} elseif ($diet === 'Vegan') {
    $diet_plan = [
        "Meal 1 (Breakfast)" => "Tofu scramble (150g) with spinach, toast, and vegan protein shake (approx. 400 kcal)",
        "Meal 2 (Lunch)" => "Chickpea brown rice bowl with mixed broccoli, carrots, and peanut sauce (approx. 550 kcal)",
        "Meal 3 (Snack)" => "Almond milk shake with chia seeds and pumpkin seeds (approx. 200 kcal)",
        "Meal 4 (Dinner)" => "Lentil soup with baked sweet potatoes and stir-fry tempeh (approx. 500 kcal)",
        "Macro Targets" => "Protein: ~95g, Carbs: ~240g, Fats: ~55g"
    ];
} else { // Non-Vegetarian
    $diet_plan = [
        "Meal 1 (Breakfast)" => "4 egg whites + 1 whole egg omelette, 2 brown bread slices, coffee (approx. 450 kcal)",
        "Meal 2 (Lunch)" => "Grilled chicken breast (200g) with brown rice (1 cup) and steamed vegetables (approx. 600 kcal)",
        "Meal 3 (Snack)" => "Whey protein isolate shake with handful of mixed walnuts (approx. 250 kcal)",
        "Meal 4 (Dinner)" => "Baked fish (150g) or minced chicken with boiled pulses and cucumber salad (approx. 450 kcal)",
        "Macro Targets" => "Protein: ~140g, Carbs: ~180g, Fats: ~60g"
    ];
}

// Format plan string for WhatsApp sending
$wa_message = "🏋️ *Sudarshan Fitness AI Workout & Diet Planner* 🏋️\n\n" .
              "Hello *{$_SESSION['full_name']}*,\nHere is your custom training & nutrition chart generated based on your health metrics:\n\n" .
              "📊 *Physical Analysis:*\n" .
              "• BMI: *{$bmi} ({$bmi_category})*\n" .
              "• Daily Target: *{$target_cal} kcal* (Goal: *{$goal}*)\n" .
              "• Medical Restrictions: *{$medical}*\n\n" .
              "📅 *Weekly Gym Workout split:*\n";
              
foreach ($workout as $day => $routine) {
    $wa_message .= "• *{$day}*: {$routine}\n";
}

$wa_message .= "\n🥗 *Daily Meal Split ({$diet}):*\n";
foreach ($diet_plan as $meal => $food) {
    $wa_message .= "• *{$meal}*: {$food}\n";
}

$wa_message .= "\nLet's crush these goals! 💪\n*Sudarshan Fitness Team*";

$wa_sent = false;
if ($send_wa) {
    // Fetch member mobile number
    $q_mob = mysqli_query($con, "SELECT mobile FROM users WHERE userid = '$uid_clean'");
    if ($q_mob && $mob_row = mysqli_fetch_assoc($q_mob)) {
        $mobile = $mob_row['mobile'];
        if (!empty($mobile)) {
            $wa_mobile = preg_replace('/[^0-9]/', '', $mobile);
            if (strlen($wa_mobile) === 10) $wa_mobile = '91' . $wa_mobile;
            
            if (enqueue_whatsapp_message($con, $wa_mobile, $wa_message)) {
                $wa_sent = true;
            }
        }
    }
}

// Return JSON response
echo json_encode([
    'success' => true,
    'bmi' => $bmi,
    'bmi_category' => $bmi_category,
    'target_calories' => $target_cal,
    'workout' => $workout,
    'diet' => $diet_plan,
    'whatsapp_sent' => $wa_sent
]);
exit();
?>
