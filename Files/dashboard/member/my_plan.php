<?php
require '../../include/db_conn.php';
page_protect();

$uid = $_SESSION['userid'];
$query = "SELECT fitness_goal, username FROM users WHERE userid='$uid'";
$result = mysqli_query($con, $query);
$row = mysqli_fetch_assoc($result);

$goal_key = !empty($row['fitness_goal']) ? $row['fitness_goal'] : 'general';

$plans_json = file_get_contents('../../api/fitness_plans.json');
$plans = json_decode($plans_json, true);

if (!isset($plans[$goal_key])) {
    $goal_key = 'general';
}

$my_plan = $plans[$goal_key];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>SUDARSHAN FITNESS | My Smart Plan</title>
    <link rel="stylesheet" href="../../css/style.css" id="style-resource-5">
    <script type="text/javascript" src="../../js/Script.js"></script>
    <link rel="stylesheet" href="../../css/dashMain.css">
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
    <link rel="stylesheet" href="../../css/premium.css">
    
    <style>
        .page-container .sidebar-menu #main-menu li#myplan > a {
            background-color: #2b303a;
            color: #ffffff;
        }
        .plan-header {
            background: linear-gradient(135deg, rgba(255,107,0,0.2) 0%, rgba(0,0,0,0.8) 100%);
            border: 1px solid rgba(255,107,0,0.3);
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            text-align: center;
        }
        .plan-header h2 {
            color: #ff6b00;
            font-size: 32px;
            font-weight: 800;
            margin-top: 0;
            text-transform: uppercase;
            letter-spacing: 2px;
            text-shadow: 0 0 15px rgba(255,107,0,0.4);
        }
        .plan-header p {
            color: #e2e8f0;
            font-size: 16px;
            max-width: 700px;
            margin: 10px auto 0;
            line-height: 1.6;
        }
        .split-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: var(--glass-shadow);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .split-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
            border-color: rgba(255,107,0,0.5);
        }
        .day-badge {
            display: inline-block;
            background: rgba(255,107,0,0.15);
            color: #ff6b00;
            padding: 5px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }
        .workout-focus {
            font-size: 18px;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 8px;
        }
        .workout-details {
            color: #a3a3a3;
            font-size: 14px;
            line-height: 1.5;
        }
        .nutrition-card {
            background: linear-gradient(135deg, rgba(16,185,129,0.1) 0%, rgba(0,0,0,0.4) 100%);
            border: 1px solid rgba(16,185,129,0.3);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
        }
        .macro-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .macro-box {
            background: rgba(0,0,0,0.3);
            border: 1px solid rgba(255,255,255,0.1);
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        .macro-value {
            font-size: 16px;
            font-weight: 700;
            color: #10b981;
            margin-top: 5px;
        }
        .tips-list {
            list-style: none;
            padding: 0;
            margin-top: 20px;
        }
        .tips-list li {
            padding: 8px 0;
            color: #e2e8f0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .tips-list li i {
            color: #10b981;
        }
    </style>
</head>
<body class="page-body page-fade" onload="collapseSidebar()">

    <div class="page-container sidebar-collapsed" id="navbarcollapse">    
        <div class="sidebar-menu">
            <header class="logo-env">
                <div class="logo">
                    <a href="main.php">
                        <img src="../../images/logo.png" alt="" width="192" height="80" />
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
                        <li><a href="logout.php">Log Out <i class="entypo-logout right"></i></a></li>
                    </ul>
                </div>
            </div>

            <div class="plan-header">
                <h2><?php echo htmlspecialchars($my_plan['title']); ?></h2>
                <p><?php echo htmlspecialchars($my_plan['description']); ?></p>
            </div>
            
            <div class="row">
                <!-- NUTRITION & MACROS -->
                <div class="col-md-4">
                    <div class="nutrition-card">
                        <h3 style="margin-top: 0; color: #10b981; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                            <i class="entypo-leaf"></i> Nutrition Core
                        </h3>
                        
                        <div class="macro-grid">
                            <div class="macro-box">
                                <div style="font-size: 11px; color: #a3a3a3; text-transform: uppercase;">Calories</div>
                                <div class="macro-value"><?php echo htmlspecialchars($my_plan['diet']['calories']); ?></div>
                            </div>
                            <div class="macro-box">
                                <div style="font-size: 11px; color: #a3a3a3; text-transform: uppercase;">Protein</div>
                                <div class="macro-value" style="color: #60a5fa;"><?php echo htmlspecialchars($my_plan['diet']['protein']); ?></div>
                            </div>
                            <div class="macro-box">
                                <div style="font-size: 11px; color: #a3a3a3; text-transform: uppercase;">Carbs</div>
                                <div class="macro-value" style="color: #fbbf24;"><?php echo htmlspecialchars($my_plan['diet']['carbs']); ?></div>
                            </div>
                            <div class="macro-box">
                                <div style="font-size: 11px; color: #a3a3a3; text-transform: uppercase;">Fats</div>
                                <div class="macro-value" style="color: #f43f5e;"><?php echo htmlspecialchars($my_plan['diet']['fats']); ?></div>
                            </div>
                        </div>
                        
                        <h4 style="margin-top: 25px; color: #fff; font-size: 14px; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 10px;">Pro Tips</h4>
                        <ul class="tips-list">
                            <?php foreach ($my_plan['diet']['tips'] as $tip): ?>
                                <li><i class="entypo-check"></i> <?php echo htmlspecialchars($tip); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <!-- 7-DAY WORKOUT SPLIT -->
                <div class="col-md-5">
                    <h3 style="margin-top: 0; margin-bottom: 20px; color: #fff; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                        <i class="entypo-calendar"></i> 7-Day Workout Split
                    </h3>
                    
                    <?php foreach ($my_plan['workout'] as $workout): ?>
                        <div class="split-card workout-item" data-focus="<?php echo htmlspecialchars(strtolower($workout['focus'])); ?>" onmouseenter="highlightMuscle(this.dataset.focus)" onmouseleave="resetMuscle()">
                            <div class="day-badge"><?php echo htmlspecialchars($workout['day']); ?></div>
                            <div class="workout-focus"><?php echo htmlspecialchars($workout['focus']); ?></div>
                            <div class="workout-details"><?php echo htmlspecialchars($workout['details']); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- INTERACTIVE MUSCLE MAP -->
                <div class="col-md-3">
                    <div style="background: rgba(0,0,0,0.5); border: 1px solid rgba(255,107,0,0.2); border-radius: 12px; padding: 20px; text-align: center; position: sticky; top: 20px;">
                        <h4 style="color: #ff6b00; font-weight: bold; margin-top: 0; text-transform: uppercase; font-size: 12px; letter-spacing: 1px;">Target Muscle Tracker</h4>
                        <p style="font-size: 11px; color: #a3a3a3; margin-bottom: 20px;">Hover over a workout day to see the targeted muscle group.</p>
                        
                        <svg viewBox="0 0 100 200" style="width: 100%; max-height: 400px; filter: drop-shadow(0 0 10px rgba(0,0,0,0.5));">
                            <!-- Base Body -->
                            <path d="M40,20 C40,10 60,10 60,20 C60,30 40,30 40,20 Z" fill="#333" /> <!-- Head -->
                            <path d="M30,30 C50,25 50,25 70,30 L75,70 L65,70 L65,100 L35,100 L35,70 L25,70 Z" fill="#222" /> <!-- Torso Base -->
                            
                            <!-- Muscle Groups (Interactive) -->
                            <path id="muscle-shoulders" d="M25,30 C35,25 65,25 75,30 L75,40 C65,35 35,35 25,40 Z" fill="#444" style="transition: all 0.3s;" />
                            <path id="muscle-chest" d="M35,40 C50,45 50,45 65,40 L65,55 C50,60 50,60 35,55 Z" fill="#444" style="transition: all 0.3s;" />
                            <path id="muscle-back" d="M30,40 L35,40 L35,70 L30,60 Z M65,40 L70,40 L70,60 L65,70 Z" fill="#444" style="transition: all 0.3s;" />
                            <path id="muscle-arms" d="M25,40 L15,80 L20,80 L30,40 Z M75,40 L85,80 L80,80 L70,40 Z" fill="#444" style="transition: all 0.3s;" />
                            <path id="muscle-core" d="M40,60 C50,65 50,65 60,60 L60,95 C50,90 50,90 40,95 Z" fill="#444" style="transition: all 0.3s;" />
                            <path id="muscle-legs" d="M35,100 L45,100 L45,180 L35,180 Z M55,100 L65,100 L65,180 L55,180 Z" fill="#444" style="transition: all 0.3s;" />
                            <path id="muscle-cardio" d="M45,45 C55,45 55,55 45,55 C35,55 35,45 45,45 Z" fill="transparent" stroke="#444" stroke-width="2" style="transition: all 0.3s;" /> <!-- Heart symbol -->
                        </svg>
                    </div>
                </div>
            </div>

            <script>
                function highlightMuscle(focusStr) {
                    const str = focusStr.toLowerCase();
                    const muscles = {
                        chest: ['chest'],
                        back: ['back'],
                        arm: ['arms'],
                        tricep: ['arms'],
                        bicep: ['arms'],
                        shoulder: ['shoulders'],
                        leg: ['legs'],
                        quad: ['legs'],
                        hamstring: ['legs'],
                        calf: ['legs'],
                        core: ['core'],
                        ab: ['core'],
                        full: ['chest', 'back', 'arms', 'shoulders', 'legs', 'core'],
                        cardio: ['cardio'],
                        hiit: ['cardio']
                    };
                    
                    let found = false;
                    Object.keys(muscles).forEach(key => {
                        if (str.includes(key)) {
                            found = true;
                            muscles[key].forEach(m => {
                                const el = document.getElementById('muscle-' + m);
                                if (el) {
                                    el.style.fill = (m === 'cardio') ? 'transparent' : '#ff6b00';
                                    if (m === 'cardio') el.style.stroke = '#ff6b00';
                                    el.style.filter = 'drop-shadow(0 0 8px #ff6b00)';
                                }
                            });
                        }
                    });
                }
                
                function resetMuscle() {
                    const allMuscles = ['chest', 'back', 'arms', 'shoulders', 'core', 'legs', 'cardio'];
                    allMuscles.forEach(m => {
                        const el = document.getElementById('muscle-' + m);
                        if (el) {
                            el.style.fill = (m === 'cardio') ? 'transparent' : '#444';
                            if (m === 'cardio') el.style.stroke = '#444';
                            el.style.filter = 'none';
                        }
                    });
                }
            </script>

            <?php include('../admin/footer.php'); ?>
        </div>
    </div>
</body>
</html>
