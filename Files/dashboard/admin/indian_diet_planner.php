<?php
require '../../include/db_conn.php';
page_protect();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Indian Diet &amp; Nutrition Planner | Sudarshan Fitness v2.0</title>
    <link rel="stylesheet" href="../../css/premium.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../../css/entypo.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { background: var(--bg-dark); color: var(--text-main); padding: 25px; }
        .card { background: var(--card-bg); color: var(--text-main); border: 1px solid var(--glass-border); border-radius: 20px; padding: 25px; margin-bottom: 25px; box-shadow: var(--glass-shadow); }
        .grid-2 { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; }
        .meal-box { background: rgba(3,7,18,0.8); border: 1px solid rgba(255,107,0,0.3); border-radius: 16px; padding: 20px; margin-bottom: 15px; }
        .meal-title { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: var(--accent-primary); font-size: 15px; font-weight: 800; margin-bottom: 8px; }
        .tag-diet { display: inline-block; background: rgba(16,185,129,0.2); color: #10b981; border: 1px solid #10b981; padding: 2px 8px; border-radius: 8px; font-size: 10px; font-weight: bold; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; margin-right: 6px; }
    </style>
</head>
<body>
    <?php include 'nav.php'; ?>

    <div style="max-width: 1300px; margin: 0 auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <div>
                <h2 style="font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: var(--accent-primary); margin: 0;">🍛 INDIAN NUTRITION &amp; DIET PLANNER</h2>
                <div style="color: var(--text-muted); font-size: 13px; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">SUDARSHAN FITNESS v2.0 • INDIA-FOCUSED MACRO DATABASE</div>
            </div>
            <a href="index.php" style="background: rgba(255,107,0,0.15); color: var(--accent-primary); border: 1px solid var(--glass-border); padding: 8px 18px; border-radius: 12px; text-decoration: none; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-weight: 800; font-size: 12px;">← DASHBOARD</a>
        </div>

        <!-- Category Selector -->
        <div class="card">
            <h3 style="font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: var(--text-main); margin-top: 0;">🥗 Select Diet Preference</h3>
            <div style="display: flex; gap: 12px; flex-wrap: wrap;" id="dietFilters">
                <button type="button" data-filter="all" onclick="filterMeals('all', this)" style="background: var(--accent-primary); color: #ffffff; border: 1px solid var(--accent-primary); padding: 10px 20px; border-radius: 12px; font-weight: 800; cursor: pointer;">✨ ALL PLANS</button>
                <button type="button" data-filter="veg" onclick="filterMeals('veg', this)" style="background: rgba(255,107,0,0.15); color: var(--accent-primary); border: 1px solid var(--glass-border); padding: 10px 20px; border-radius: 12px; font-weight: 800; cursor: pointer;">🌱 VEGETARIAN (PANEER/DAL)</button>
                <button type="button" data-filter="nonveg" onclick="filterMeals('nonveg', this)" style="background: rgba(255,107,0,0.15); color: var(--accent-primary); border: 1px solid var(--glass-border); padding: 10px 20px; border-radius: 12px; font-weight: 800; cursor: pointer;">🍗 HIGH PROTEIN NON-VEG</button>
                <button type="button" data-filter="jain" onclick="filterMeals('jain', this)" style="background: rgba(255,107,0,0.15); color: var(--accent-primary); border: 1px solid var(--glass-border); padding: 10px 20px; border-radius: 12px; font-weight: 800; cursor: pointer;">🌿 JAIN NUTRITION</button>
                <button type="button" data-filter="vegan" onclick="filterMeals('vegan', this)" style="background: rgba(255,107,0,0.15); color: var(--accent-primary); border: 1px solid var(--glass-border); padding: 10px 20px; border-radius: 12px; font-weight: 800; cursor: pointer;">🌾 VEGAN MEAL PLAN</button>
            </div>
        </div>

        <!-- Indian Meal Structure -->
        <div class="grid-2">
            <div class="card">
                <h3 style="font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: var(--text-main); margin-top: 0;">🌅 Morning &amp; Breakfast Options</h3>
                
                <div class="meal-box" data-diet="nonveg">
                    <div class="meal-title">🍳 High-Protein Egg Paratha &amp; Oats</div>
                    <div><span class="tag-diet">HIGH PROTEIN</span><span class="tag-diet">NON-VEG</span></div>
                    <p style="font-size: 13px; color: var(--text-main); margin-top: 8px;">3 Whole Eggs + 1 Wheat Paratha + 50g Oats in Almond Milk &amp; Nuts.</p>
                    <div style="margin-top: 8px; font-size: 11px; color: var(--accent-primary);">MACROS: 480 kcal • 32g Protein • 52g Carbs • 14g Fat</div>
                </div>

                <div class="meal-box" data-diet="veg jain">
                    <div class="meal-title">🌱 Sprouts &amp; Paneer Poha</div>
                    <div><span class="tag-diet">VEGETARIAN</span><span class="tag-diet">JAIN FRIENDLY</span></div>
                    <p style="font-size: 13px; color: var(--text-main); margin-top: 8px;">1 Bowl Vegetable Poha (no onion/garlic option) + 100g Sautéed Paneer Cubes + 1 Glass Buttermilk (Chaas).</p>
                    <div style="margin-top: 8px; font-size: 11px; color: var(--accent-primary);">MACROS: 420 kcal • 24g Protein • 58g Carbs • 12g Fat</div>
                </div>

                <div class="meal-box" data-diet="veg vegan jain">
                    <div class="meal-title">🥞 Moong Dal Chilla &amp; Tofu Bhurji</div>
                    <div><span class="tag-diet">VEGAN</span><span class="tag-diet">JAIN FRIENDLY</span></div>
                    <p style="font-size: 13px; color: var(--text-main); margin-top: 8px;">2 Moong Dal Chillas + 100g Spiced Tofu Bhurji + Mint Chutney.</p>
                    <div style="margin-top: 8px; font-size: 11px; color: var(--accent-primary);">MACROS: 390 kcal • 26g Protein • 46g Carbs • 10g Fat</div>
                </div>
            </div>

            <div class="card">
                <h3 style="font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: var(--text-main); margin-top: 0;">🍛 Lunch &amp; Dinner Options</h3>
                
                <div class="meal-box" data-diet="nonveg">
                    <div class="meal-title">🍗 Chicken Tikka &amp; Brown Rice</div>
                    <div><span class="tag-diet">HIGH PROTEIN</span><span class="tag-diet">FAT LOSS</span></div>
                    <p style="font-size: 13px; color: var(--text-main); margin-top: 8px;">200g Tandoori Chicken Breast + 150g Steamed Rice + Cucumber Tomato Salad.</p>
                    <div style="margin-top: 8px; font-size: 11px; color: var(--accent-primary);">MACROS: 580 kcal • 52g Protein • 48g Carbs • 10g Fat</div>
                </div>

                <div class="meal-box" data-diet="veg jain">
                    <div class="meal-title">🍲 Dal Tadka, Paneer &amp; 2 Roti</div>
                    <div><span class="tag-diet">VEGETARIAN</span><span class="tag-diet">MUSCLE GAIN</span></div>
                    <p style="font-size: 13px; color: var(--text-main); margin-top: 8px;">1 Large Bowl Moong/Toor Dal + 120g Matar Paneer + 2 Multigrain Roti.</p>
                    <div style="margin-top: 8px; font-size: 11px; color: var(--accent-primary);">MACROS: 610 kcal • 30g Protein • 72g Carbs • 18g Fat</div>
                </div>

                <div class="meal-box" data-diet="veg vegan jain">
                    <div class="meal-title">🥣 Soya Chunks Pulao &amp; Rajma</div>
                    <div><span class="tag-diet">VEGAN</span><span class="tag-diet">HIGH PROTEIN</span></div>
                    <p style="font-size: 13px; color: var(--text-main); margin-top: 8px;">50g Boiled Soya Chunks + 1 Bowl Rajma Masala + Brown Rice + Cucumber Salad.</p>
                    <div style="margin-top: 8px; font-size: 11px; color: var(--accent-primary);">MACROS: 540 kcal • 36g Protein • 74g Carbs • 8g Fat</div>
                </div>
            </div>
        </div>
    </div>
    <script>
        function filterMeals(category, btn) {
            document.querySelectorAll('#dietFilters button').forEach(function(b) {
                b.style.background = 'rgba(255,107,0,0.15)';
                b.style.color = 'var(--accent-primary)';
                b.style.borderColor = 'var(--glass-border)';
            });
            btn.style.background = 'var(--accent-primary)';
            btn.style.color = '#ffffff';
            btn.style.borderColor = 'var(--accent-primary)';
            document.querySelectorAll('.meal-box').forEach(function(box) {
                var tags = (box.getAttribute('data-diet') || '').split(/\s+/);
                box.style.display = (category === 'all' || tags.indexOf(category) !== -1) ? 'block' : 'none';
            });
        }
    </script>
</body>
</html>
