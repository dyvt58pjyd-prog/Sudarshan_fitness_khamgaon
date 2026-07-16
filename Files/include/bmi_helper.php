<?php
if (!function_exists('get_bmi_suggestions')) {
    function get_bmi_suggestions($height, $weight) {
        $height = floatval($height);
        $weight = floatval($weight);
        
        if ($height <= 0 || $weight <= 0) {
            return [
                'bmi' => '--',
                'category' => 'No Data',
                'color' => '#a3a3a3',
                'goal' => 'Please record height and weight details first.',
                'workouts' => 'N/A',
                'veg_diet' => 'N/A',
                'nonveg_diet' => 'N/A'
            ];
        }
        
        $height_m = $height / 100;
        $bmi = round($weight / ($height_m * $height_m), 1);
        
        if ($bmi < 18.5) {
            return [
                'bmi' => $bmi,
                'category' => 'Underweight',
                'color' => '#38bdf8', // Sky Blue
                'goal' => 'Healthy weight gain and lean muscle hypertrophy.',
                'workouts' => "• Focus on strength training / hypertrophy workouts (3-4 sessions per week).\n• Restrict excessive cardio workouts to 1-2 low-intensity sessions.\n• Prioritize compound exercises (squats, bench press, deadlifts) with progressive overload.",
                'veg_diet' => "• Protein: Paneer (low-fat), Tofu, Soya chunks, Chickpeas, Lentils, and Greek Yogurt.\n• Carbs & Fats: Almonds, walnuts, peanut butter, bananas, oats, potatoes, and sweet potatoes.\n• Calorie Goal: Maintain a caloric surplus of 300-500 kcal per day.",
                'nonveg_diet' => "• Protein: Chicken breast, whole eggs, lean beef, fish (Salmon/Tuna), and Whey protein.\n• Carbs & Fats: White/Brown rice, sweet potatoes, avocados, nuts, seeds, and extra virgin olive oil.\n• Calorie Goal: Maintain a caloric surplus of 300-500 kcal per day."
            ];
        } elseif ($bmi < 25.0) {
            return [
                'bmi' => $bmi,
                'category' => 'Normal Weight',
                'color' => '#10b981', // Emerald Green
                'goal' => 'Fitness maintenance, cardiovascular conditioning, and body toning.',
                'workouts' => "• Balanced program: 3 days of resistance/strength training, 2 days of aerobic cardio (running, cycling).\n• Incorporate core stability exercises and flexibility training (stretching or yoga).",
                'veg_diet' => "• Protein: Sprouts, pulses, lentils, low-fat paneer, chia seeds, and quinoa.\n• Complex Carbs & Fiber: Oats, brown rice, whole wheat bread, green vegetables, and fresh fruits.\n• Calorie Goal: Maintain status-quo (caloric maintenance level).",
                'nonveg_diet' => "• Protein: Grilled fish, egg whites, turkey breast, chicken stir-fry, and Whey protein.\n• Complex Carbs & Fiber: Quinoa, oats, broccoli, asparagus, spinach, and sweet potatoes.\n• Calorie Goal: Maintain status-quo (caloric maintenance level)."
            ];
        } elseif ($bmi < 30.0) {
            return [
                'bmi' => $bmi,
                'category' => 'Overweight',
                'color' => '#ffb000', // Amber/Orange
                'goal' => 'Fat loss, metabolic conditioning, and endurance enhancement.',
                'workouts' => "• Focus on HIIT (High Intensity Interval Training) and Circuit training (3-4 times a week).\n• Maintain strength training to preserve muscle mass while burning fat.\n• Aim for 10,000 steps daily.",
                'veg_diet' => "• Diet Strategy: Caloric deficit (reduce intake by 300-500 kcal).\n• Foods: Salads, clear vegetable soups, sprouts, boiled chana, roasted makhana, and low-fat curd.\n• Avoid: Refined sugars, soft drinks, processed snacks, and white flour (maida).",
                'nonveg_diet' => "• Diet Strategy: Caloric deficit (reduce intake by 300-500 kcal).\n• Foods: Baked or boiled chicken breast, steamed fish, egg white omelet, green salads, and broccoli.\n• Avoid: Deep-fried meats, sugary sauces, fast food, and white bread."
            ];
        } else {
            return [
                'bmi' => $bmi,
                'category' => 'Obese',
                'color' => '#ef4444', // Hot Red
                'goal' => 'Structured weight reduction, joint health protection, and cardiovascular stamina.',
                'workouts' => "• Focus on low-impact cardio: walking, water aerobics, swimming, and stationary cycling.\n• Avoid high-impact joint loading exercises (like running on hard surfaces or heavy jumping).\n• Perform light functional mobility and basic resistance exercises.",
                'veg_diet' => "• Diet Strategy: Strict caloric deficit under supervision. Avoid all processed fats.\n• Foods: High-fiber leafy greens (spinach, lettuce, cucumber), boiled lentils, vegetable stews, and green tea.\n• Focus: Portion control and drinking 3-4 liters of water daily.",
                'nonveg_diet' => "• Diet Strategy: Strict caloric deficit under supervision. Avoid all processed fats.\n• Foods: Poached egg whites, grilled skinless chicken, baked fish, cucumber salads, and lemon water.\n• Focus: High-protein, zero-sugar, low-carb structure. Drink 3-4 liters of water daily."
            ];
        }
    }
}
?>
