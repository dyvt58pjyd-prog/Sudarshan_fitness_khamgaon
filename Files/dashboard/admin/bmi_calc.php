<?php
require '../../include/db_conn.php';
page_protect();

$selected_uid = isset($_GET['uid']) ? mysqli_real_escape_string($con, $_GET['uid']) : '';
$search_query = isset($_POST['search_query']) ? mysqli_real_escape_string($con, $_POST['search_query']) : '';
$search_results = [];

// If search query is posted, search members
if (!empty($search_query)) {
    $q_search = "SELECT userid, username, mobile, email FROM users 
                 WHERE username LIKE '%$search_query%' 
                    OR userid = '$search_query' 
                    OR mobile = '$search_query' 
                 ORDER BY username ASC LIMIT 10";
    $res_search = mysqli_query($con, $q_search);
    if ($res_search) {
        while ($row = mysqli_fetch_assoc($res_search)) {
            $search_results[] = $row;
        }
    }
}

// Fetch selected member info and their existing health status
$member = null;
$health = null;
if (!empty($selected_uid)) {
    $q_mem = "SELECT userid, username, mobile, email, photo FROM users WHERE userid = '$selected_uid'";
    $res_mem = mysqli_query($con, $q_mem);
    if ($res_mem && mysqli_num_rows($res_mem) > 0) {
        $member = mysqli_fetch_assoc($res_mem);
        
        // Fetch or initialize health status row
        $q_health = "SELECT * FROM health_status WHERE uid = '$selected_uid'";
        $res_health = mysqli_query($con, $q_health);
        if ($res_health && mysqli_num_rows($res_health) > 0) {
            $health = mysqli_fetch_assoc($res_health);
        } else {
            // Auto-insert if missing
            mysqli_query($con, "INSERT IGNORE INTO health_status (uid) VALUES ('$selected_uid')");
            $health = [
                'uid' => $selected_uid,
                'height' => '',
                'weight' => '',
                'calorie' => '',
                'fat' => '',
                'remarks' => ''
            ];
        }
    }
}

// Handle Form Submission to save data
$success_msg = "";
$error_msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_bmi'])) {
    $uid = mysqli_real_escape_string($con, $_POST['uid']);
    $height = mysqli_real_escape_string($con, $_POST['height']);
    $weight = mysqli_real_escape_string($con, $_POST['weight']);
    $calorie = mysqli_real_escape_string($con, $_POST['calorie']);
    $fat = mysqli_real_escape_string($con, $_POST['fat']);
    $remarks = mysqli_real_escape_string($con, $_POST['remarks']);
    
    // Ensure row exists
    mysqli_query($con, "INSERT IGNORE INTO health_status (uid) VALUES ('$uid')");
    
    $q_update = "UPDATE health_status 
                 SET height = '$height', weight = '$weight', calorie = '$calorie', fat = '$fat', remarks = '$remarks' 
                 WHERE uid = '$uid'";
    
    if (mysqli_query($con, $q_update)) {
        echo "<script>alert('BMI and Health Metrics successfully saved!'); window.location.href = 'bmi_calc.php?uid=" . urlencode($uid) . "';</script>";
        exit();
    } else {
        $error_msg = "Error updating database: " . mysqli_error($con);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>SUDARSHAN FITNESS | Member BMI Calculator</title>
    <link rel="stylesheet" href="../../css/style.css" id="style-resource-5">
    <script type="text/javascript" src="../../js/Script.js"></script>
    <link rel="stylesheet" href="../../css/dashMain.css">
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
    <link rel="stylesheet" href="../../css/premium.css">
    <link href="a1style.css" type="text/css" rel="stylesheet">
    <style>
        .page-container .sidebar-menu #main-menu li#bmicalc > a {
            background-color: #2b303a;
            color: #ffffff;
        }
        #boxx {
            width: 100% !important;
            box-sizing: border-box !important;
        }
        textarea#boxx {
            height: auto !important;
            background: rgba(15, 23, 42, 0.6) !important;
            color: #ffffff !important;
            border: 1px solid rgba(255, 107, 0, 0.3) !important;
            border-radius: 8px !important;
            padding: 8px 12px !important;
            transition: all 0.2s ease-in-out !important;
        }
        textarea#boxx:focus {
            border-color: #ff6b00 !important;
            outline: none !important;
            box-shadow: 0 0 8px rgba(255, 107, 0, 0.3) !important;
        }
        .a1-container table td {
            padding: 10px 0 !important;
            vertical-align: middle !important;
        }
        .bmi-display-card {
            background: rgba(15, 23, 42, 0.7);
            border: 1px solid rgba(255, 107, 0, 0.25);
            border-radius: 12px;
            padding: 25px;
            margin-top: 20px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        }
        .bmi-num {
            font-size: 54px;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 8px;
            text-shadow: 0 0 15px currentColor;
        }
        .bmi-category {
            font-size: 16px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 4px 12px;
            border-radius: 20px;
            display: inline-block;
            margin-bottom: 15px;
        }
        .bmi-scale-bar {
            height: 10px;
            background: linear-gradient(to right, #38bdf8 0%, #10b981 35%, #ffb000 65%, #ef4444 100%);
            border-radius: 5px;
            position: relative;
            margin: 20px 0 10px 0;
        }
        .bmi-pointer {
            width: 16px;
            height: 16px;
            background: #ffffff;
            border: 3px solid #ff6b00;
            border-radius: 50%;
            position: absolute;
            top: -3px;
            transform: translateX(-50%);
            box-shadow: 0 0 8px rgba(255,107,0,0.8);
            transition: left 0.3s ease;
        }
        .search-results-list {
            margin: 15px 0;
            padding: 0;
            list-style: none;
        }
        .search-result-item {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,107,0,0.15);
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .search-result-item:hover {
            background: rgba(255,107,0,0.1);
            border-color: #ff6b00;
            box-shadow: 0 0 8px rgba(255,107,0,0.2);
        }
    </style>
</head>
<body class="page-body page-fade" onload="collapseSidebar()">

    <div class="page-container sidebar-collapsed" id="navbarcollapse">	
        <div class="sidebar-menu">
            <header class="logo-env">
                <div class="logo">
                    <a href="main.php">
                        <?php 
                        $sidebar_logo = $gym_settings_data["gym_logo"] ?? "../../images/logo.png";
                        ?>
                        <img src="<?php echo htmlspecialchars($sidebar_logo); ?>" alt="Gym Logo" style="max-height: 80px; max-width: 192px;" />
                    </a>
                </div>
                <div class="sidebar-collapse" onclick="collapseSidebar()">
                    <a href="#" class="sidebar-collapse-icon with-animation">
                        <i class="entypo-menu"></i>
                    </a>
                </div>
            </header>
            <?php include('nav.php'); ?>
        </div>

        <div class="main-content">
            <div class="row">
                <div class="col-md-6 col-sm-8 clearfix"></div>
                <div class="col-md-6 col-sm-4 clearfix hidden-xs">
                    <ul class="list-inline links-list pull-right">
                        <li>Welcome <?php echo $_SESSION['full_name']; ?></li>
                        <li>
                            <a href="logout.php">
                                Log Out <i class="entypo-logout right"></i>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <h3>Member Health & BMI Calculator</h3>
            <hr />

            <div style="display: flex; gap: 30px; flex-wrap: wrap;">
                
                <!-- Left Column: Search Section -->
                <div style="flex: 1; min-width: 320px;">
                    <div class="a1-card-8 a1-light-gray" style="border-radius: 12px; overflow: hidden; border: 1px solid rgba(255, 107, 0, 0.2); box-shadow: 0 4px 20px rgba(0,0,0,0.4); padding: 20px; background: rgba(0,0,0,0.2);">
                        <h4 style="color: #ff6b00; border-bottom: 1px solid rgba(255,107,0,0.2); padding-bottom: 10px; margin-top:0;">Find Member</h4>
                        <form method="post" action="bmi_calc.php">
                            <table width="100%" border="0">
                                <tr>
                                    <td>
                                        <input type="text" name="search_query" id="boxx" placeholder="Enter Member Name, ID, or Mobile..." value="<?php echo htmlspecialchars($search_query); ?>" required />
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding-top: 15px;">
                                        <input class="a1-btn a1-blue" type="submit" name="search" value="Search Member" style="width: 100%;" />
                                    </td>
                                </tr>
                            </table>
                        </form>

                        <!-- Search Results -->
                        <?php if (!empty($search_query)): ?>
                            <h5 style="margin-top: 25px; color: #a3a3a3; font-weight: bold;">Search Results (<?php echo count($search_results); ?>)</h5>
                            <?php if (count($search_results) > 0): ?>
                                <ul class="search-results-list">
                                    <?php foreach ($search_results as $res): ?>
                                        <li class="search-result-item" onclick="window.location.href='bmi_calc.php?uid=<?php echo urlencode($res['userid']); ?>'">
                                            <div>
                                                <strong style="color: #ffffff;"><?php echo htmlspecialchars($res['username']); ?></strong>
                                                <div style="font-size: 11px; color: #a3a3a3; margin-top:2px;">ID: <?php echo htmlspecialchars($res['userid']); ?></div>
                                            </div>
                                            <span style="font-size: 12px; color: var(--accent-primary); font-weight: bold;"><?php echo htmlspecialchars($res['mobile']); ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p style="font-size: 13px; color: var(--text-muted); margin-top: 10px;">No members found matching that search query.</p>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Right Column: BMI Calculator & Health Status Form -->
                <div style="flex: 1.5; min-width: 380px;">
                    <?php if ($member): ?>
                        <div class="a1-card-8 a1-light-gray" style="border-radius: 12px; overflow: hidden; border: 1px solid rgba(255, 107, 0, 0.2); box-shadow: 0 4px 20px rgba(0,0,0,0.4); padding: 20px;">
                            <div style="display: flex; align-items: center; gap: 15px; border-bottom: 1px solid rgba(255,107,0,0.2); padding-bottom: 15px; margin-bottom: 20px;">
                                <?php if (!empty($member['photo'])): ?>
                                    <img src="<?php echo htmlspecialchars($member['photo']); ?>" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 1px solid var(--accent-primary);" />
                                <?php endif; ?>
                                <div>
                                    <h4 style="color: #ffffff; margin: 0; font-weight: 700;"><?php echo htmlspecialchars($member['username']); ?></h4>
                                    <span style="font-size: 12px; color: #a3a3a3;">Membership ID: <?php echo htmlspecialchars($member['userid']); ?></span>
                                </div>
                            </div>

                            <form method="post" action="bmi_calc.php">
                                <input type="hidden" name="uid" value="<?php echo htmlspecialchars($member['userid']); ?>" />
                                <table width="100%" border="0">
                                    <tr>
                                        <td width="40%" height="35">HEIGHT (cm):</td>
                                        <td height="35">
                                            <input type="number" step="0.1" name="height" id="height_input" value="<?php echo htmlspecialchars($health['height'] ?? ''); ?>" placeholder="e.g. 175" onkeyup="recalculateBMI()" onchange="recalculateBMI()" required />
                                        </td>
                                    </tr>
                                    <tr>
                                        <td height="35">WEIGHT (kg):</td>
                                        <td height="35">
                                            <input type="number" step="0.1" name="weight" id="weight_input" value="<?php echo htmlspecialchars($health['weight'] ?? ''); ?>" placeholder="e.g. 70" onkeyup="recalculateBMI()" onchange="recalculateBMI()" required />
                                        </td>
                                    </tr>
                                    <tr>
                                        <td height="35">DAILY CALORIES (kcal):</td>
                                        <td height="35">
                                            <input type="number" name="calorie" id="boxx" value="<?php echo htmlspecialchars($health['calorie'] ?? ''); ?>" placeholder="e.g. 2500" />
                                        </td>
                                    </tr>
                                    <tr>
                                        <td height="35">BODY FAT (%):</td>
                                        <td height="35">
                                            <input type="number" step="0.1" name="fat" id="boxx" value="<?php echo htmlspecialchars($health['fat'] ?? ''); ?>" placeholder="e.g. 15.5" />
                                        </td>
                                    </tr>
                                    <tr>
                                        <td height="35" valign="top">REMARKS / HEALTH GOALS:</td>
                                        <td height="35">
                                            <textarea name="remarks" id="boxx" rows="3" placeholder="Diet guidelines, physical warnings, target weight..."><?php echo htmlspecialchars($health['remarks'] ?? ''); ?></textarea>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td height="35">&nbsp;</td>
                                        <td style="padding-top: 15px;">
                                            <input class="a1-btn a1-blue" type="submit" name="save_bmi" value="Save Health & BMI Data" style="width: 100%;" />
                                        </td>
                                    </tr>
                                </table>
                            </form>

                            <!-- Interactive BMI Display Card -->
                            <div class="bmi-display-card" id="bmi_card" style="display: none;">
                                <div style="font-size: 11px; text-transform: uppercase; color: #a3a3a3; letter-spacing: 2px; font-weight: bold;">Calculated Body Mass Index (BMI)</div>
                                <div class="bmi-num" id="bmi_value">--</div>
                                <div class="bmi-category" id="bmi_category">--</div>
                                
                                <div class="bmi-scale-bar">
                                    <div class="bmi-pointer" id="bmi_pointer" style="left: 0%;"></div>
                                </div>
                                <div style="display: flex; justify-content: space-between; font-size: 10px; color: #a3a3a3; margin-bottom: 20px;">
                                    <span>&lt; 18.5 (Under)</span>
                                    <span>18.5 - 25 (Normal)</span>
                                    <span>25 - 30 (Over)</span>
                                    <span>30+ (Obese)</span>
                                </div>

                                <div style="border-top: 1px solid rgba(255, 107, 0, 0.15); padding-top: 20px; text-align: left;">
                                    <h5 style="color: #ff6b00; font-weight: 700; margin-top: 0; font-size: 14px;">Goal: <span id="bmi_goal" style="color: #ffffff; font-weight: 500;">--</span></h5>
                                    
                                    <div style="margin-top: 15px;">
                                        <strong style="color: var(--accent-primary); font-size: 12px; text-transform: uppercase; display: block; margin-bottom: 5px; letter-spacing: 0.5px;">Recommended Workouts:</strong>
                                        <p id="bmi_workouts" style="font-size: 13px; line-height: 1.5; color: #e2e8f0; margin: 0; white-space: pre-line;">--</p>
                                    </div>
                                    
                                    <div style="margin-top: 15px; display: flex; gap: 15px; flex-wrap: wrap;">
                                        <div style="flex: 1; min-width: 180px; background: rgba(0,0,0,0.3); padding: 12px; border-radius: 8px; border: 1px solid rgba(255,107,0,0.15);">
                                            <strong style="color: #10b981; font-size: 12px; text-transform: uppercase; display: block; margin-bottom: 5px; letter-spacing: 0.5px;">Vegetarian Diet:</strong>
                                            <p id="bmi_veg_diet" style="font-size: 12px; line-height: 1.5; color: #cbd5e1; margin: 0; white-space: pre-line;">--</p>
                                        </div>
                                        <div style="flex: 1; min-width: 180px; background: rgba(0,0,0,0.3); padding: 12px; border-radius: 8px; border: 1px solid rgba(255,107,0,0.15);">
                                            <strong style="color: #ef4444; font-size: 12px; text-transform: uppercase; display: block; margin-bottom: 5px; letter-spacing: 0.5px;">Non-Vegetarian Diet:</strong>
                                            <p id="bmi_nonveg_diet" style="font-size: 12px; line-height: 1.5; color: #cbd5e1; margin: 0; white-space: pre-line;">--</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <script>
                            const suggestions = {
                                underweight: {
                                    goal: "Healthy weight gain and lean muscle hypertrophy.",
                                    workouts: "• Focus on strength training / hypertrophy workouts (3-4 sessions per week).\n• Restrict excessive cardio workouts to 1-2 low-intensity sessions.\n• Prioritize compound exercises (squats, bench press, deadlifts) with progressive overload.",
                                    veg: "• Protein: Paneer (low-fat), Tofu, Soya chunks, Chickpeas, Lentils, and Greek Yogurt.\n• Carbs & Fats: Almonds, walnuts, peanut butter, bananas, oats, potatoes, and sweet potatoes.\n• Calorie Goal: Maintain a caloric surplus of 300-500 kcal per day.",
                                    nonveg: "• Protein: Chicken breast, whole eggs, lean beef, fish (Salmon/Tuna), and Whey protein.\n• Carbs & Fats: White/Brown rice, sweet potatoes, avocados, nuts, seeds, and extra virgin olive oil.\n• Calorie Goal: Maintain a caloric surplus of 300-500 kcal per day."
                                },
                                normal: {
                                    goal: "Fitness maintenance, cardiovascular conditioning, and body toning.",
                                    workouts: "• Balanced program: 3 days of resistance/strength training, 2 days of aerobic cardio (running, cycling).\n• Incorporate core stability exercises and flexibility training (stretching or yoga).",
                                    veg: "• Protein: Sprouts, pulses, lentils, low-fat paneer, chia seeds, and quinoa.\n• Complex Carbs & Fiber: Oats, brown rice, whole wheat bread, green vegetables, and fresh fruits.\n• Calorie Goal: Maintain status-quo (caloric maintenance level).",
                                    nonveg: "• Protein: Grilled fish, egg whites, turkey breast, chicken stir-fry, and Whey protein.\n• Complex Carbs & Fiber: Quinoa, oats, broccoli, asparagus, spinach, and sweet potatoes.\n• Calorie Goal: Maintain status-quo (caloric maintenance level)."
                                },
                                overweight: {
                                    goal: "Fat loss, metabolic conditioning, and endurance enhancement.",
                                    workouts: "• Focus on HIIT (High Intensity Interval Training) and Circuit training (3-4 times a week).\n• Maintain strength training to preserve muscle mass while burning fat.\n• Aim for 10,000 steps daily.",
                                    veg: "• Diet Strategy: Caloric deficit (reduce intake by 300-500 kcal).\n• Foods: Salads, clear vegetable soups, sprouts, boiled chana, roasted makhana, and low-fat curd.\n• Avoid: Refined sugars, soft drinks, processed snacks, and white flour (maida).",
                                    nonveg: "• Diet Strategy: Caloric deficit (reduce intake by 300-500 kcal).\n• Foods: Baked or boiled chicken breast, steamed fish, egg white omelet, green salads, and broccoli.\n• Avoid: Deep-fried meats, sugary sauces, fast food, and white bread."
                                },
                                obese: {
                                    goal: "Structured weight reduction, joint health protection, and cardiovascular stamina.",
                                    workouts: "• Focus on low-impact cardio: walking, water aerobics, swimming, and stationary cycling.\n• Avoid high-impact joint loading exercises (like running on hard surfaces or heavy jumping).\n• Perform light functional mobility and basic resistance exercises.",
                                    veg: "• Diet Strategy: Strict caloric deficit under supervision. Avoid all processed fats.\n• Foods: High-fiber leafy greens (spinach, lettuce, cucumber), boiled lentils, vegetable stews, and green tea.\n• Focus: Portion control and drinking 3-4 liters of water daily.",
                                    nonveg: "• Diet Strategy: Strict caloric deficit under supervision. Avoid all processed fats.\n• Foods: Poached egg whites, grilled skinless chicken, baked fish, cucumber salads, and lemon water.\n• Focus: High-protein, zero-sugar, low-carb structure. Drink 3-4 liters of water daily."
                                }
                            };

                            function recalculateBMI() {
                                var height = parseFloat(document.getElementById('height_input').value);
                                var weight = parseFloat(document.getElementById('weight_input').value);
                                var card = document.getElementById('bmi_card');
                                var valueText = document.getElementById('bmi_value');
                                var catText = document.getElementById('bmi_category');
                                var pointer = document.getElementById('bmi_pointer');
                                
                                var goalText = document.getElementById('bmi_goal');
                                var workoutsText = document.getElementById('bmi_workouts');
                                var vegText = document.getElementById('bmi_veg_diet');
                                var nonvegText = document.getElementById('bmi_nonveg_diet');
                                
                                if (height > 0 && weight > 0) {
                                    card.style.display = 'block';
                                    var heightM = height / 100;
                                    var bmi = weight / (heightM * heightM);
                                    bmi = Math.round(bmi * 10) / 10;
                                    
                                    valueText.innerHTML = bmi;
                                    
                                    var category = "";
                                    var color = "";
                                    var sugKey = "";
                                    var position = 0; // % from left on scale bar
                                    
                                    if (bmi < 18.5) {
                                        category = "Underweight";
                                        color = "#38bdf8"; // Light Blue
                                        sugKey = "underweight";
                                        position = Math.max(5, ((bmi - 10) / 8.5) * 35);
                                    } else if (bmi < 25) {
                                        category = "Normal Weight";
                                        color = "#10b981"; // Green
                                        sugKey = "normal";
                                        position = 35 + ((bmi - 18.5) / 6.5) * 30;
                                    } else if (bmi < 30) {
                                        category = "Overweight";
                                        color = "#ffb000"; // Orange
                                        sugKey = "overweight";
                                        position = 65 + ((bmi - 25) / 5) * 20;
                                    } else {
                                        category = "Obese";
                                        color = "#ef4444"; // Red
                                        sugKey = "obese";
                                        position = 85 + Math.min(10, ((bmi - 30) / 15) * 10);
                                    }
                                    
                                    catText.innerHTML = category;
                                    catText.style.backgroundColor = color + '22'; // 13% opacity background
                                    catText.style.color = color;
                                    catText.style.border = '1px solid ' + color + '55';
                                    valueText.style.color = color;
                                    pointer.style.left = position + '%';
                                    pointer.style.borderColor = color;
                                    
                                    // Set suggestions
                                    goalText.innerHTML = suggestions[sugKey].goal;
                                    workoutsText.innerHTML = suggestions[sugKey].workouts;
                                    vegText.innerHTML = suggestions[sugKey].veg;
                                    nonvegText.innerHTML = suggestions[sugKey].nonveg;
                                } else {
                                    card.style.display = 'none';
                                }
                            }
                            // Call once on page load if height and weight prefilled
                            document.addEventListener('DOMContentLoaded', recalculateBMI);
                        </script>
                    <?php else: ?>
                        <div style="background: rgba(255,255,255,0.02); border: 1px dashed rgba(255,107,0,0.2); border-radius: 12px; padding: 40px; text-align: center; color: var(--text-muted);">
                            <i class="entypo-chart-bar" style="font-size: 48px; color: rgba(255,107,0,0.4); display: block; margin-bottom: 15px;"></i>
                            <h4>No Member Selected</h4>
                            <p style="font-size: 13px;">Find a member using the search panel on the left to calculate, log, and view their BMI details.</p>
                        </div>
                    <?php endif; ?>
                </div>

            </div>

            <?php include('footer.php'); ?>
        </div>
    </div>

</body>
</html>
