import os

file_content = """<?php
require '../../include/db_conn.php';
header('Content-Type: application/json');

if (isset($_POST['action']) && $_POST['action'] === 'generate_and_save') {
    
    $uid = $_POST['member_uid'] ?? '';
    $name = $_POST['member_name'] ?? 'Member';
    $weight = $_POST['weight'] ?? '70';
    $height = $_POST['height'] ?? '170';
    $goal = $_POST['goal'] ?? 'General Fitness';
    $diet_type = $_POST['diet_type'] ?? 'Vegetarian';
    $language = $_POST['language'] ?? 'English';
    $medical = $_POST['medical'] ?? 'None';

    if(empty($uid)) {
        echo json_encode(['success' => false, 'error' => 'Member UID missing']);
        exit;
    }

    // Offline Database of Diet Plans
    $plans = [
        'English' => [
            'Fat Loss & Toning' => [
                'Vegetarian' => "
### 🥦 Vegetarian Fat Loss Diet
* **Morning (Empty Stomach):** 1 glass warm water with lemon & chia seeds.
* **Breakfast:** 1 bowl oats porridge with almonds OR 2 Moong Dal Chillas.
* **Mid-Morning:** 1 Apple or Papaya bowl.
* **Lunch:** 1 bowl salad, 1 Roti (Multigrain), 1 bowl Dal/Sprouts, 1 portion green veggies.
* **Evening Snack:** 1 cup Green Tea + roasted Makhana (fox nuts).
* **Dinner:** 1 bowl vegetable soup, Soya chunk salad OR Paneer bhurji (50g) with veggies.
                ",
                'Non-Vegetarian' => "
### 🍗 Non-Veg Fat Loss Diet
* **Morning (Empty Stomach):** 1 glass warm water with lemon.
* **Breakfast:** 3 Boiled Egg Whites + 1 whole egg, 1 slice brown bread.
* **Mid-Morning:** 1 Orange or mixed fruits.
* **Lunch:** 150g Grilled Chicken breast, 1 bowl salad, 1 Roti.
* **Evening Snack:** Black coffee + handful of almonds.
* **Dinner:** 100g Fish or Chicken tikka, roasted broccoli and carrots.
                "
            ],
            'Muscle Building (Hypertrophy)' => [
                'Vegetarian' => "
### 🧀 Vegetarian Muscle Building Diet
* **Morning (Empty Stomach):** Ashwagandha with warm water.
* **Breakfast:** 100g Paneer sandwich (brown bread), 1 Banana, Peanut Butter.
* **Mid-Morning:** 1 scoop Whey Protein (or Soya Chunks) + 1 Apple.
* **Lunch:** 2 Rotis, 1 large bowl Rajma/Chole, Rice (100g), Curd (1 bowl).
* **Evening Snack:** Sprouts salad with sweet potato.
* **Dinner:** 150g Paneer or Tofu stir-fry, mixed veggies, 1 Roti.
                ",
                'Non-Vegetarian' => "
### 🥩 Non-Veg Muscle Building Diet
* **Morning (Empty Stomach):** Black Coffee or Pre-workout.
* **Breakfast:** 4 Whole Eggs scrambled, 2 slices Brown bread, Peanut Butter.
* **Mid-Morning:** 1 scoop Whey Protein + 1 Banana.
* **Lunch:** 200g Chicken Breast, 150g White Rice, 1 bowl Dal.
* **Evening Snack:** 3 Boiled Egg Whites + Sweet potato.
* **Dinner:** 200g Fish or Chicken, large salad bowl, olive oil dressing.
                "
            ]
        ],
        'Marathi' => [
            'Fat Loss & Toning' => [
                'Vegetarian' => "
### 🥦 शाकाहारी फॅट लॉस (वजन कमी करण्याचा) आहार
* **सकाळी (उपाशी पोटी):** १ ग्लास कोमट पाणी लिंबू आणि चिया सीड्स (Chia Seeds) सोबत.
* **नाश्ता:** १ वाटी ओट्स किंवा २ मुगाच्या डाळीचे धिरडे.
* **सकाळचा स्नॅक:** १ सफरचंद किंवा पपई.
* **दुपारचे जेवण:** १ वाटी कोशिंबीर, १ ज्वारीची/बाजरीची भाकरी, १ वाटी मोड आलेले कडधान्य (उसळ).
* **संध्याकाळचा स्नॅक:** ग्रीन टी आणि भाजलेले मखाणे.
* **रात्रीचे जेवण:** १ वाटी भाज्यांचे सूप आणि ५० ग्रॅम पनीर भुर्जी (कमी तेलात).
                ",
                'Non-Vegetarian' => "
### 🍗 मांसाहारी फॅट लॉस (वजन कमी करण्याचा) आहार
* **सकाळी (उपाशी पोटी):** १ ग्लास कोमट पाणी लिंबू सोबत.
* **नाश्ता:** ३ उकडलेल्या अंड्यांचा पांढरा भाग, १ स्लाइस ब्राऊन ब्रेड.
* **सकाळचा स्नॅक:** १ सफरचंद किंवा संत्री.
* **दुपारचे जेवण:** १५० ग्रॅम ग्रिल्ड चिकन, १ वाटी कोशिंबीर, १ भाकरी.
* **संध्याकाळचा स्नॅक:** ब्लॅक कॉफी आणि ५-६ बदाम.
* **रात्रीचे जेवण:** १०० ग्रॅम फिश (मासे) किंवा चिकन टिक्का (भाजलेले), हिरव्या भाज्या.
                "
            ],
            'Muscle Building (Hypertrophy)' => [
                'Vegetarian' => "
### 🧀 शाकाहारी मसल बिल्डिंग (वजन वाढवणे) आहार
* **सकाळी (उपाशी पोटी):** कोमट पाण्यासोबत अश्वगंधा.
* **नाश्ता:** १०० ग्रॅम पनीर सँडविच, १ केळी, पीनट बटर.
* **सकाळचा स्नॅक:** १ स्कूप व्हे प्रोटीन (किंवा सोया चंक्स) + सफरचंद.
* **दुपारचे जेवण:** २ चपात्या, १ मोठी वाटी राजमा किंवा छोले, १०० ग्रॅम भात, १ वाटी दही.
* **संध्याकाळचा स्नॅक:** मोड आलेल्या मटकीची/मुगाची उसळ आणि रताळे.
* **रात्रीचे जेवण:** १५० ग्रॅम पनीर किंवा टोफू, पालेभाज्या, १ चपाती.
                ",
                'Non-Vegetarian' => "
### 🥩 मांसाहारी मसल बिल्डिंग आहार
* **सकाळी (उपाशी पोटी):** ब्लॅक कॉफी किंवा प्री-वर्कआउट.
* **नाश्ता:** ४ उकडलेली अंडी (पिवळ्या भागासह), २ ब्राऊन ब्रेड, पीनट बटर.
* **सकाळचा स्नॅक:** १ स्कूप व्हे प्रोटीन + १ केळी.
* **दुपारचे जेवण:** ۲۰۰ ग्रॅम चिकन ब्रेस्ट, १५० ग्रॅम भात, १ वाटी डाळ.
* **संध्याकाळचा स्नॅक:** ३ उकडलेल्या अंड्यांचा पांढरा भाग + रताळे.
* **रात्रीचे जेवण:** ۲۰۰ ग्रॅम मासे किंवा चिकन, मोठी कोशिंबीर (सॅलड).
                "
            ]
        ],
        'Hindi' => [
            'Fat Loss & Toning' => [
                'Vegetarian' => "
### 🥦 शाकाहारी फैट लॉस डाइट
* **सुबह (खाली पेट):** 1 गिलास गुनगुना पानी नींबू और चिया सीड्स के साथ।
* **नाश्ता:** 1 कटोरी ओट्स या 2 मूंग दाल के चीले।
* **मिड-मॉर्निंग:** 1 सेब या पपीता।
* **दोपहर का खाना:** 1 कटोरी सलाद, 1 मल्टीग्रेन रोटी, 1 कटोरी दाल/अंकुरित अनाज।
* **शाम का स्नैक:** ग्रीन टी और भुने हुए मखाने।
* **रात का खाना:** 1 कटोरी वेजिटेबल सूप, सोया चंक्स या 50 ग्राम पनीर भुर्जी।
                ",
                'Non-Vegetarian' => "
### 🍗 मांसाहारी फैट लॉस डाइट
* **सुबह (खाली पेट):** 1 गिलास गुनगुना पानी नींबू के साथ।
* **नाश्ता:** 3 उबले अंडे की सफेदी, 1 ब्राउन ब्रेड।
* **मिड-मॉर्निंग:** 1 सेब या संतरा।
* **दोपहर का खाना:** 150 ग्राम ग्रिल्ड चिकन, 1 कटोरी सलाद, 1 रोटी।
* **शाम का स्नैक:** ब्लैक कॉफी और बादाम।
* **रात का खाना:** 100 ग्राम फिश या रोस्टेड चिकन, सब्जियां।
                "
            ],
            'Muscle Building (Hypertrophy)' => [
                'Vegetarian' => "
### 🧀 शाकाहारी मसल बिल्डिंग डाइट
* **सुबह (खाली पेट):** अश्वगंधा पानी के साथ।
* **नाश्ता:** 100 ग्राम पनीर सैंडविच, 1 केला, पीनट बटर।
* **मिड-मॉर्निंग:** 1 स्कूप व्हे प्रोटीन (या सोया) + 1 सेब।
* **दोपहर का खाना:** 2 रोटी, 1 बड़ी कटोरी राजमा/छोले, चावल, 1 कटोरी दही।
* **शाम का स्नैक:** अंकुरित अनाज और शकरकंद।
* **रात का खाना:** 150 ग्राम पनीर या टोफू, हरी सब्जियां, 1 रोटी।
                ",
                'Non-Vegetarian' => "
### 🥩 मांसाहारी मसल बिल्डिंग डाइट
* **सुबह (खाली पेट):** ब्लैक कॉफी।
* **नाश्ता:** 4 उबले अंडे (पीले भाग के साथ), 2 ब्राउन ब्रेड, पीनट बटर।
* **मिड-मॉर्निंग:** 1 स्कूप व्हे प्रोटीन + 1 केला।
* **दोपहर का खाना:** 200 ग्राम चिकन ब्रेस्ट, 150 ग्राम चावल, 1 कटोरी दाल।
* **शाम का स्नैक:** 3 अंडे की सफेदी + शकरकंद।
* **रात का खाना:** 200 ग्राम मछली या चिकन, बड़ा सलाद बाउल।
                "
            ]
        ]
    ];

    // Fallback logic
    $lang_data = $plans[$language] ?? $plans['English'];
    
    // Convert goal keywords
    $goal_key = 'Fat Loss & Toning';
    if (strpos($goal, 'Muscle') !== false || strpos($goal, 'Recomposition') !== false) {
        $goal_key = 'Muscle Building (Hypertrophy)';
    }

    $diet_key = ($diet_type === 'Vegetarian' || $diet_type === 'Vegan') ? 'Vegetarian' : 'Non-Vegetarian';
    $selected_diet = $lang_data[$goal_key][$diet_key];

    // Workout Protocol (Generic)
    $workout = "";
    if ($language === 'Marathi') {
        $workout = "
### 🏋️ व्यायाम योजना (Workout Protocol)
* **सोमवार:** छाती आणि ट्रायसेप्स (Chest & Triceps) - Bench Press, Dumbbell Flyes, Pushdowns.
* **मंगळवार:** पाठ आणि बायसेप्स (Back & Biceps) - Lat Pulldown, Seated Row, Barbell Curls.
* **बुधवार:** विश्रांती किंवा कार्डिओ (Rest / 30 Min Cardio).
* **गुरुवार:** खांदे आणि पोट (Shoulders & Abs) - Overhead Press, Lateral Raises, Planks.
* **शुक्रवार:** पाय (Legs) - Squats, Leg Press, Lunges, Calf Raises.
* **शनिवार:** फुल बॉडी किंवा फंक्शनल ट्रेनिंग (Full Body / Functional).
* **रविवार:** पूर्ण विश्रांती (Complete Rest).
        ";
    } elseif ($language === 'Hindi') {
        $workout = "
### 🏋️ व्यायाम योजना (Workout Protocol)
* **सोमवार:** चेस्ट और ट्राइसेप्स (Chest & Triceps).
* **मंगलवार:** बैक और बाइसेप्स (Back & Biceps).
* **बुधवार:** आराम या कार्डियो (Rest / Cardio).
* **गुरुवार:** कंधे और एब्स (Shoulders & Abs).
* **शुक्रवार:** पैर (Legs - Squats, Leg Press).
* **शनिवार:** फुल बॉडी ट्रेनिंग (Full Body).
* **रविवार:** पूर्ण आराम (Rest).
        ";
    } else {
        $workout = "
### 🏋️ 7-Day Workout Protocol
* **Monday:** Chest & Triceps (Focus on Bench Press, Incline DB Press).
* **Tuesday:** Back & Biceps (Lat Pulldowns, Deadlifts, Curls).
* **Wednesday:** Active Recovery / 30 Min Cardio.
* **Thursday:** Shoulders & Abs (Overhead Press, Lateral Raises, Planks).
* **Friday:** Legs (Squats, Leg Press, Extensions).
* **Saturday:** Functional / CrossFit.
* **Sunday:** Complete Rest.
        ";
    }

    $greeting = "## SUDARSHAN FITNESS - OFFICIAL PROTOCOL";
    $stats = "**Member:** {$name} | **Weight:** {$weight}kg | **Height:** {$height}cm | **Goal:** {$goal}";
    $med = $medical !== 'None' ? "\n**Medical Note:** {$medical} - Please consult doctor before high-intensity training." : "";

    if ($language === 'Marathi') {
        $greeting = "## सुदर्शन फिटनेस - अधिकृत आहार आणि व्यायाम योजना";
        $stats = "**सदस्य:** {$name} | **वजन:** {$weight}kg | **उंची:** {$height}cm | **उद्दिष्ट:** {$goal}";
        $med = $medical !== 'None' ? "\n**वैद्यकीय नोंद:** {$medical} - कृपया व्यायाम सुरू करण्यापूर्वी डॉक्टरांचा सल्ला घ्या." : "";
    } elseif ($language === 'Hindi') {
        $greeting = "## सुदर्शन फिटनेस - आधिकारिक डाइट और वर्कआउट प्लान";
        $stats = "**सदस्य:** {$name} | **वजन:** {$weight}kg | **ऊंचाई:** {$height}cm | **लक्ष्य:** {$goal}";
        $med = $medical !== 'None' ? "\n**मेडिकल नोट:** {$medical} - कृपया व्यायाम से पहले डॉक्टर की सलाह लें।" : "";
    }

    $final_markdown = "{$greeting}\n{$stats}{$med}\n\n" . $selected_diet . "\n\n" . $workout;

    // SAVE TO DATABASE
    // Check if member already has a routine
    $chk = mysqli_query($con, "SELECT id FROM member_routines WHERE uid = '".mysqli_real_escape_string($con, $uid)."'");
    $safe_diet = mysqli_real_escape_string($con, $selected_diet);
    $safe_workout = mysqli_real_escape_string($con, $workout);

    session_start();
    $admin_user = isset($_SESSION['user_data']) ? $_SESSION['user_data'] : 'system';

    if(mysqli_num_rows($chk) > 0) {
        $q = "UPDATE member_routines SET diet_plan = '$safe_diet', workout_plan = '$safe_workout', trainer_id = '$admin_user', updated_at = CURRENT_TIMESTAMP WHERE uid = '".mysqli_real_escape_string($con, $uid)."'";
        mysqli_query($con, $q);
    } else {
        $q = "INSERT INTO member_routines (uid, diet_plan, workout_plan, trainer_id) VALUES ('".mysqli_real_escape_string($con, $uid)."', '$safe_diet', '$safe_workout', '$admin_user')";
        mysqli_query($con, $q);
    }

    // Simulate thinking time for effect
    sleep(1);

    echo json_encode(['success' => true, 'text' => $final_markdown]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid Request']);
"""

filepath = "./Files/api/ai_handler.php"
with open(filepath, "w", encoding="utf-8") as f:
    f.write(file_content)
print(f"Created {filepath}")
