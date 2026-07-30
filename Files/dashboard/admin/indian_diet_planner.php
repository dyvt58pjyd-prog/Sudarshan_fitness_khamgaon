<?php
require '../../include/db_conn.php';
page_protect();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Indian Diet &amp; Nutrition Planner | Sudarshan Fitness v2.0</title>
    <link rel="stylesheet" href="../../css/premium.css">
    <link rel="stylesheet" href="../../css/entypo.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@600;800;900&family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { background: var(--bg-dark); color: #fff; padding: 25px; }
        .card { background: rgba(15, 7, 18, 0.94); border: 1px solid var(--glass-border); border-radius: 20px; padding: 25px; margin-bottom: 25px; box-shadow: var(--glass-shadow); }
        .grid-2 { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; }
        .meal-box { background: rgba(3,7,18,0.8); border: 1px solid rgba(255,0,60,0.3); border-radius: 16px; padding: 20px; margin-bottom: 15px; }
        .meal-title { font-family: 'Orbitron'; color: var(--accent-primary); font-size: 15px; font-weight: 800; margin-bottom: 8px; }
        .tag-diet { display: inline-block; background: rgba(16,185,129,0.2); color: #10b981; border: 1px solid #10b981; padding: 2px 8px; border-radius: 8px; font-size: 10px; font-weight: bold; font-family: 'Orbitron'; margin-right: 6px; }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>

    <div style="max-width: 1300px; margin: 0 auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <div>
                <h2 style="font-family: 'Orbitron'; color: var(--accent-primary); margin: 0;">🍛 INDIAN NUTRITION &amp; DIET PLANNER</h2>
                <div style="color: var(--text-muted); font-size: 13px; font-family: 'Orbitron';">SUDARSHAN FITNESS v2.0 • INDIA-FOCUSED MACRO DATABASE</div>
            </div>
            <a href="index.php" style="background: rgba(255,0,60,0.15); color: var(--accent-primary); border: 1px solid var(--glass-border); padding: 8px 18px; border-radius: 12px; text-decoration: none; font-family: 'Orbitron'; font-weight: 800; font-size: 12px;">← DASHBOARD</a>
        </div>

        <!-- Category Selector -->
        <div class="card">
            <h3 style="font-family: 'Orbitron'; color: #fff; margin-top: 0;">🥗 Select Diet Preference</h3>
            <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                <button style="background: var(--accent-primary); color: #030712; border: none; padding: 10px 20px; border-radius: 12px; font-weight: 800; font-family: 'Orbitron'; cursor: pointer;">🌱 VEGETARIAN (PANEER/DAL)</button>
                <button style="background: rgba(255,0,60,0.15); color: var(--accent-primary); border: 1px solid var(--glass-border); padding: 10px 20px; border-radius: 12px; font-weight: 800; font-family: 'Orbitron'; cursor: pointer;">🍗 HIGH PROTEIN NON-VEG</button>
                <button style="background: rgba(255,0,60,0.15); color: var(--accent-primary); border: 1px solid var(--glass-border); padding: 10px 20px; border-radius: 12px; font-weight: 800; font-family: 'Orbitron'; cursor: pointer;">🌿 JAIN NUTRITION</button>
                <button style="background: rgba(255,0,60,0.15); color: var(--accent-primary); border: 1px solid var(--glass-border); padding: 10px 20px; border-radius: 12px; font-weight: 800; font-family: 'Orbitron'; cursor: pointer;">🌾 VEGAN MEAL PLAN</button>
            </div>
        </div>

        <!-- Indian Meal Structure -->
        <div class="grid-2">
            <div class="card">
                <h3 style="font-family: 'Orbitron'; color: #fff; margin-top: 0;">🌅 Morning &amp; Breakfast Options</h3>
                
                <div class="meal-box">
                    <div class="meal-title">🍳 High-Protein Egg Paratha &amp; Oats</div>
                    <div><span class="tag-diet">HIGH PROTEIN</span><span class="tag-diet">NON-VEG</span></div>
                    <p style="font-size: 13px; color: #cbd5e1; margin-top: 8px;">3 Whole Eggs + 1 Wheat Paratha + 50g Oats in Almond Milk &amp; Nuts.</p>
                    <div style="margin-top: 8px; font-size: 11px; color: var(--accent-primary); font-family: 'Orbitron';">MACROS: 480 kcal • 32g Protein • 52g Carbs • 14g Fat</div>
                </div>

                <div class="meal-box">
                    <div class="meal-title">🌱 Sprouts &amp; Paneer Poha</div>
                    <div><span class="tag-diet">VEGETARIAN</span><span class="tag-diet">JAIN FRIENDLY</span></div>
                    <p style="font-size: 13px; color: #cbd5e1; margin-top: 8px;">1 Bowl Vegetable Poha + 100g Sautéed Paneer Cubes + 1 Glass Buttermilk (Chaas).</p>
                    <div style="margin-top: 8px; font-size: 11px; color: var(--accent-primary); font-family: 'Orbitron';">MACROS: 420 kcal • 24g Protein • 58g Carbs • 12g Fat</div>
                </div>
            </div>

            <div class="card">
                <h3 style="font-family: 'Orbitron'; color: #fff; margin-top: 0;">🍛 Lunch &amp; Dinner Options</h3>
                
                <div class="meal-box">
                    <div class="meal-title">🍗 Chicken Tikka &amp; Brown Rice</div>
                    <div><span class="tag-diet">HIGH PROTEIN</span><span class="tag-diet">FAT LOSS</span></div>
                    <p style="font-size: 13px; color: #cbd5e1; margin-top: 8px;">200g Tandoori Chicken Breast + 150g Steamed Rice + Cucumber Tomato Salad.</p>
                    <div style="margin-top: 8px; font-size: 11px; color: var(--accent-primary); font-family: 'Orbitron';">MACROS: 580 kcal • 52g Protein • 48g Carbs • 10g Fat</div>
                </div>

                <div class="meal-box">
                    <div class="meal-title">🍲 Dal Tadka, Paneer &amp; 2 Roti</div>
                    <div><span class="tag-diet">VEGETARIAN</span><span class="tag-diet">MUSCLE GAIN</span></div>
                    <p style="font-size: 13px; color: #cbd5e1; margin-top: 8px;">1 Large Bowl Moong/Toor Dal + 120g Matar Paneer + 2 Multigrain Roti.</p>
                    <div style="margin-top: 8px; font-size: 11px; color: var(--accent-primary); font-family: 'Orbitron';">MACROS: 610 kcal • 30g Protein • 72g Carbs • 18g Fat</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
