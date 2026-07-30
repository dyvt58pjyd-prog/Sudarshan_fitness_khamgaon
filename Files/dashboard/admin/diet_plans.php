<?php
require '../../include/db_conn.php';
page_protect();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Diet &amp; Nutrition Planner | Sudarshan Fitness v2.0</title>
    <link rel="stylesheet" href="../../css/premium.css">
    <link rel="stylesheet" href="../../css/entypo.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@600;800;900&family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { background: var(--bg-dark); color: #fff; padding: 25px; }
        .card { background: rgba(9, 14, 28, 0.9); border: 1px solid var(--glass-border); border-radius: 20px; padding: 25px; margin-bottom: 25px; box-shadow: var(--glass-shadow); }
        .meal-box { background: rgba(3,7,18,0.8); border: 1px solid rgba(0,240,255,0.3); border-radius: 16px; padding: 18px; margin-bottom: 15px; }
        .meal-title { font-family: 'Orbitron'; color: var(--accent-primary); font-size: 14px; font-weight: 800; margin-bottom: 6px; }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>

    <div style="max-width: 1200px; margin: 0 auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <div>
                <h2 style="font-family: 'Orbitron'; color: var(--accent-primary); margin: 0;">🥗 DIET &amp; NUTRITION PLANNER</h2>
                <div style="color: var(--text-muted); font-size: 13px; font-family: 'Orbitron';">SUDARSHAN FITNESS v2.0 • MEAL MACROS &amp; DIET PROTOCOLS</div>
            </div>
            <a href="index.php" style="background: rgba(0,240,255,0.1); color: var(--accent-primary); border: 1px solid var(--glass-border); padding: 8px 18px; border-radius: 12px; text-decoration: none; font-family: 'Orbitron'; font-weight: 800; font-size: 12px;">← DASHBOARD</a>
        </div>

        <div class="card">
            <h3 style="font-family: 'Orbitron'; color: #fff; margin-top: 0;">🍏 Standard High-Protein Muscle Building Diet Template</h3>
            
            <div class="meal-box">
                <div class="meal-title">🍳 MEAL 1: BREAKFAST (8:00 AM)</div>
                <div style="font-size: 13px; color: #cbd5e1; line-height: 1.5;">4 Whole Eggs / Tofu Scramble + 100g Oats with Almond Milk &amp; Banana + 1 Scoop Whey Protein.</div>
                <div style="margin-top: 8px; font-size: 11px; color: var(--accent-primary); font-family: 'Orbitron';">APPROX MACROS: 550 kcal • 42g Protein • 60g Carbs • 15g Fat</div>
            </div>

            <div class="meal-box">
                <div class="meal-title">🥗 MEAL 2: LUNCH (1:00 PM)</div>
                <div style="font-size: 13px; color: #cbd5e1; line-height: 1.5;">200g Grilled Chicken Breast / Paneer + 150g Brown Rice + Mixed Green Salad &amp; Olive Oil.</div>
                <div style="margin-top: 8px; font-size: 11px; color: var(--accent-primary); font-family: 'Orbitron';">APPROX MACROS: 650 kcal • 48g Protein • 65g Carbs • 18g Fat</div>
            </div>

            <div class="meal-box">
                <div class="meal-title">⚡ MEAL 3: PRE-WORKOUT SNACK (5:00 PM)</div>
                <div style="font-size: 13px; color: #cbd5e1; line-height: 1.5;">2 Whole Wheat Toast + 2 tbsp Peanut Butter + 1 Apple / Pre-Workout Matrix drink.</div>
                <div style="margin-top: 8px; font-size: 11px; color: var(--accent-primary); font-family: 'Orbitron';">APPROX MACROS: 320 kcal • 12g Protein • 40g Carbs • 12g Fat</div>
            </div>

            <div class="meal-box">
                <div class="meal-title">🍲 MEAL 4: DINNER (9:00 PM)</div>
                <div style="font-size: 13px; color: #cbd5e1; line-height: 1.5;">180g Fish / Paneer Tikka + Steamed Broccoli, Carrots &amp; Quinoa or 2 Chapati.</div>
                <div style="margin-top: 8px; font-size: 11px; color: var(--accent-primary); font-family: 'Orbitron';">APPROX MACROS: 480 kcal • 38g Protein • 45g Carbs • 14g Fat</div>
            </div>
        </div>
    </div>
</body>
</html>
