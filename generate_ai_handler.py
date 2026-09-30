import os

file_content = """<?php
header('Content-Type: application/json');

$api_settings_file = '../include/ai_settings.json';

// Handle Saving API Key
if (isset($_POST['action']) && $_POST['action'] === 'save_key') {
    $key = trim($_POST['api_key']);
    if (empty($key)) {
        echo json_encode(['success' => false, 'error' => 'Key is empty']);
        exit;
    }
    
    $settings = ['gemini_api_key' => $key, 'updated_at' => date('Y-m-d H:i:s')];
    file_put_contents($api_settings_file, json_encode($settings));
    echo json_encode(['success' => true]);
    exit;
}

// Handle AI Generation
if (isset($_POST['action']) && $_POST['action'] === 'generate') {
    if (!file_exists($api_settings_file)) {
        echo json_encode(['success' => false, 'error' => 'API Key not configured.']);
        exit;
    }
    
    $settings = json_decode(file_get_contents($api_settings_file), true);
    $api_key = $settings['gemini_api_key'] ?? '';
    
    if (empty($api_key)) {
        echo json_encode(['success' => false, 'error' => 'API Key missing.']);
        exit;
    }

    $name = $_POST['member_name'] ?? 'Member';
    $weight = $_POST['weight'] ?? '70';
    $height = $_POST['height'] ?? '170';
    $goal = $_POST['goal'] ?? 'General Fitness';
    $diet_type = $_POST['diet_type'] ?? 'Vegetarian';
    $language = $_POST['language'] ?? 'English';
    $medical = $_POST['medical'] ?? 'None';

    $prompt = "You are an elite, highly professional gym trainer and nutritionist at 'Sudarshan Fitness'. 
Create a highly structured 4-week Diet and Workout plan for a member named $name.
Their stats: Weight: $weight kg, Height: $height cm.
Goal: $goal.
Diet Preference: $diet_type.
Medical Conditions: $medical.

Write the entire plan in $language. If the language is Marathi or Hindi, translate everything appropriately but keep gym terms (like Bench Press, Dumbbell) in English script or commonly understood terms if needed.
Format the output in clean Markdown. Use headings, bullet points, and bold text. 
Start directly with the plan, no introductory filler. 
Make sure the diet includes specific local Indian/Maharashtrian foods if $language is Marathi.
Divide it into:
1. Member Overview
2. Macro-Nutrient Guidelines
3. 7-Day Workout Split (Detailed sets/reps)
4. Daily Diet Plan (Breakfast, Mid-Morning, Lunch, Pre-Workout, Post-Workout, Dinner)
5. Crucial Rules for Success";

    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . $api_key;
    
    $data = [
        "contents" => [
            [
                "parts" => [
                    ["text" => $prompt]
                ]
            ]
        ],
        "generationConfig" => [
            "temperature" => 0.7,
            "maxOutputTokens" => 2000
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200) {
        $err = json_decode($response, true);
        $err_msg = $err['error']['message'] ?? 'API Request Failed';
        echo json_encode(['success' => false, 'error' => $err_msg]);
        exit;
    }

    $res_json = json_decode($response, true);
    $generated_text = $res_json['candidates'][0]['content']['parts'][0]['text'] ?? '';
    
    if (empty($generated_text)) {
        echo json_encode(['success' => false, 'error' => 'No content returned from AI.']);
        exit;
    }

    echo json_encode(['success' => true, 'text' => $generated_text]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid Request']);
"""

filepath = "./Files/api/ai_handler.php"
with open(filepath, "w", encoding="utf-8") as f:
    f.write(file_content)
print(f"Created {filepath}")
