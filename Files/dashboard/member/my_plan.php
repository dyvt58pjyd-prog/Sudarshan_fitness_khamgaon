<?php
require '../../include/db_conn.php';
page_protect();

$uid = $_SESSION['user_data'];
$gym_settings_data = get_gym_details($con);

// Fetch current stats to pre-fill form
$sql_stats = "SELECT height, weight FROM health_status WHERE uid = '$uid' ORDER BY hid DESC LIMIT 1";
$res_stats = mysqli_query($con, $sql_stats);
$stats = ($res_stats && mysqli_num_rows($res_stats) > 0) ? mysqli_fetch_assoc($res_stats) : null;

$height = ($stats && !empty($stats['height'])) ? floatval($stats['height']) : '';
$weight = ($stats && !empty($stats['weight'])) ? floatval($stats['weight']) : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo htmlspecialchars($gym_settings_data['gym_name']); ?> | AI Smart Plan</title>
    <link rel="stylesheet" href="../../css/style.css">
    <script type="text/javascript" src="../../js/Script.js"></script>
    <link rel="stylesheet" href="../../css/dashMain.css">
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
    <link rel="stylesheet" href="../../css/premium.css">
    
    <style>
        .page-container .sidebar-menu #main-menu li#myplan > a {
            background-color: rgba(59, 130, 246, 0.1) !important;
            color: var(--accent-primary) !important;
            font-weight: 600 !important;
            box-shadow: inset 3px 0 0 var(--accent-primary);
        }
        .ai-header {
            background: linear-gradient(135deg, rgba(255,107,0,0.1) 0%, rgba(0,0,0,0.4) 100%);
            border: 1px solid rgba(255,107,0,0.2);
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 25px;
            text-align: center;
        }
        .ai-header h2 {
            color: var(--accent-primary);
            font-weight: 800;
            margin-top: 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .form-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 30px;
        }
        .form-control-premium {
            background: rgba(15, 23, 42, 0.6) !important;
            border: 1px solid var(--glass-border) !important;
            border-radius: 10px !important;
            color: var(--text-main) !important;
            padding: 12px !important;
            width: 100%;
            margin-bottom: 20px;
        }
        
        /* Results Styling */
        #ai-results { display: none; }
        
        .stat-box {
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            margin-bottom: 15px;
        }
        .stat-box h4 { color: var(--text-muted); font-size: 13px; margin: 0 0 5px 0; text-transform: uppercase;}
        .stat-box .val { color: var(--text-main); font-size: 24px; font-weight: bold; }
        
        .split-card {
            background: rgba(255,255,255,0.02);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            transition: transform 0.2s;
        }
        .split-card:hover {
            transform: translateY(-2px);
            border-color: rgba(255,107,0,0.4);
        }
        .day-badge {
            display: inline-block;
            background: rgba(255,107,0,0.15);
            color: #ff6b00;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        .diet-item {
            padding: 12px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .diet-item:last-child { border-bottom: none; }
        .diet-item strong { color: var(--success); display: block; margin-bottom: 3px; font-size: 13px;}
        
        #loading-overlay {
            display: none;
            text-align: center;
            padding: 40px;
        }
        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid rgba(255,107,0,0.2);
            border-top: 4px solid var(--accent-primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px auto;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body class="page-body page-fade" onload="collapseSidebar()">
    <div class="page-container sidebar-collapsed" id="navbarcollapse">
        <div class="sidebar-menu">
            <header class="logo-env">
                <div class="logo">
                    <a href="index.php">
                        <img src="<?php echo htmlspecialchars($gym_settings_data['gym_logo']); ?>" alt="" style="max-height: 60px;" />
                    </a>
                </div>
            </header>
            <?php include('nav.php'); ?>
        </div>

        <div class="main-content">
            <div class="ai-header">
                <h2><i class="entypo-rocket"></i> AI Fitness Counselor</h2>
                <p style="color: var(--text-muted); font-size: 15px;">Generate a custom, science-based workout and diet regimen tailored instantly to your unique body metrics and medical constraints.</p>
            </div>

            <div class="form-card" id="ai-form-container">
                <form id="ai-form" onsubmit="generatePlan(event)">
                    <div class="row">
                        <div class="col-md-6">
                            <label>Primary Goal</label>
                            <select id="goal" class="form-control-premium" required>
                                <option value="Fat Loss">Fat Loss (Caloric Deficit)</option>
                                <option value="Muscle Gain">Muscle Gain (Caloric Surplus)</option>
                                <option value="Lean Builder">Lean Builder (Recomp)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label>Dietary Preference</label>
                            <select id="diet" class="form-control-premium" required>
                                <option value="Vegetarian">Vegetarian</option>
                                <option value="Non-Vegetarian">Non-Vegetarian</option>
                                <option value="Vegan">Vegan</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <label>Current Weight (kg)</label>
                            <input type="number" id="weight" class="form-control-premium" value="<?php echo $weight; ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label>Height (cm)</label>
                            <input type="number" id="height" class="form-control-premium" value="<?php echo $height; ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <label>Medical Constraints / Injuries (Optional)</label>
                            <input type="text" id="medical" class="form-control-premium" placeholder="e.g., Knee pain, lower back injury, shoulder issues... (The AI will adjust your plan)">
                        </div>
                    </div>
                    <div style="text-align: right; margin-top: 10px;">
                        <button type="submit" class="btn btn-primary" style="font-weight: bold; padding: 10px 25px; font-size: 15px;">
                            <i class="entypo-light-down"></i> Generate My Plan
                        </button>
                    </div>
                </form>
            </div>
            
            <div id="loading-overlay">
                <div class="spinner"></div>
                <h4 style="color: var(--accent-primary);">Analyzing metrics and generating customized plan...</h4>
            </div>

            <div id="ai-results">
                <!-- Metrics -->
                <h3 style="color: #fff; margin-bottom: 20px;"><i class="entypo-chart-bar"></i> Your Body Metrics</h3>
                <div class="row">
                    <div class="col-sm-3 col-xs-6"><div class="stat-box"><h4>Current BMI</h4><div class="val" id="res-bmi"></div><div id="res-bmi-cat" style="font-size: 12px; color: var(--text-muted);"></div></div></div>
                    <div class="col-sm-3 col-xs-6"><div class="stat-box"><h4>Maintenance Cal (BMR)</h4><div class="val" id="res-bmr"></div></div></div>
                    <div class="col-sm-3 col-xs-6"><div class="stat-box"><h4>Target Cal (Goal)</h4><div class="val" id="res-target" style="color: var(--accent-primary);"></div></div></div>
                    <div class="col-sm-3 col-xs-6"><div class="stat-box"><h4>Protein Goal</h4><div class="val" id="res-protein"></div></div></div>
                </div>

                <div class="row" style="margin-top: 20px;">
                    <!-- Workout Plan -->
                    <div class="col-md-6">
                        <h3 style="color: #fff; margin-bottom: 20px;"><i class="entypo-flash"></i> Generated Workout Split</h3>
                        <div id="workout-container"></div>
                    </div>
                    <!-- Diet Plan -->
                    <div class="col-md-6">
                        <h3 style="color: var(--success); margin-bottom: 20px;"><i class="entypo-leaf"></i> Customized Nutrition</h3>
                        <div class="form-card" style="padding: 10px;">
                            <div id="diet-container"></div>
                        </div>
                    </div>
                </div>
                
                <div style="text-align: center; margin-top: 30px;">
                    <button class="btn btn-default" onclick="document.getElementById('ai-results').style.display='none'; document.getElementById('ai-form-container').style.display='block';">
                        <i class="entypo-ccw"></i> Recalculate Plan
                    </button>
                </div>
            </div>

            <?php include('footer.php'); ?>
        </div>
    </div>
    
    <script>
    async function generatePlan(e) {
        e.preventDefault();
        
        document.getElementById('ai-form-container').style.display = 'none';
        document.getElementById('ai-results').style.display = 'none';
        document.getElementById('loading-overlay').style.display = 'block';
        
        const payload = {
            goal: document.getElementById('goal').value,
            diet: document.getElementById('diet').value,
            weight: document.getElementById('weight').value,
            height: document.getElementById('height').value,
            medical: document.getElementById('medical').value
        };
        
        try {
            const response = await fetch('../../api/get_ai_plan.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await response.json();
            
            // Populate Metrics
            document.getElementById('res-bmi').innerText = data.metrics.bmi;
            document.getElementById('res-bmi-cat').innerText = data.metrics.bmi_category;
            document.getElementById('res-bmr').innerText = data.metrics.tdee + ' kcal';
            document.getElementById('res-target').innerText = data.metrics.target_calories + ' kcal';
            document.getElementById('res-protein').innerText = data.metrics.macros.protein;
            
            // Populate Workout
            let wHtml = '';
            for (const [day, desc] of Object.entries(data.workout_plan)) {
                if(day === 'Notes') {
                    wHtml += `<div style="margin-top:15px; color:var(--text-muted); font-size:12px;"><strong>Coach Note:</strong> ${desc}</div>`;
                } else {
                    wHtml += `<div class="split-card"><div class="day-badge">${day}</div><div style="color:#fff; font-size:15px;">${desc}</div></div>`;
                }
            }
            document.getElementById('workout-container').innerHTML = wHtml;
            
            // Populate Diet
            let dHtml = '';
            for (const [meal, item] of Object.entries(data.diet_plan)) {
                dHtml += `<div class="diet-item"><strong>${meal}</strong><span style="color:#e2e8f0;">${item}</span></div>`;
            }
            document.getElementById('diet-container').innerHTML = dHtml;
            
            // Show Results
            document.getElementById('loading-overlay').style.display = 'none';
            document.getElementById('ai-results').style.display = 'block';
            
        } catch (err) {
            alert('Failed to connect to the AI engine. Please try again.');
            console.error(err);
            document.getElementById('loading-overlay').style.display = 'none';
            document.getElementById('ai-form-container').style.display = 'block';
        }
    }
    </script>
</body>
</html>
