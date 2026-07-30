<?php
require_once __DIR__ . '/include/db_conn.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle AJAX Save Workout Routine Log
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_workout_log') {
    header('Content-Type: application/json');
    if (!isset($_SESSION['user_data'])) {
        echo json_encode(['status' => 'error', 'message' => 'System session expired. Please re-enter portal.']);
        exit();
    }
    $uid_esc = mysqli_real_escape_string($con, $_SESSION['user_data']);
    $ex_name = isset($_POST['ex_name']) ? mysqli_real_escape_string($con, trim($_POST['ex_name'])) : 'Exercise';
    $reps_done = isset($_POST['reps_done']) ? intval($_POST['reps_done']) : 12;

    $workout_entry = date('d-M-Y H:i') . " - [SYSTEM QUEST CLEAR] " . $ex_name . " (" . $reps_done . " Reps)";
    
    // Insert into member_routines table
    $q_ins = "INSERT INTO member_routines (uid, workout_plan) VALUES ('$uid_esc', '$workout_entry')";
    if (mysqli_query($con, $q_ins)) {
        // Award +50 XP Points
        if (function_exists('add_member_xp')) {
            add_member_xp($con, $uid_esc, 50);
        }
        echo json_encode(['status' => 'success', 'message' => '[SYSTEM QUEST CLEARED] +50 EXP AWARDED & STATS LOGGED!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'System Error: ' . mysqli_error($con)]);
    }
    exit();
}

$gym = get_gym_details($con);
$member_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Hunter';

// Fetch Member Details from Database
$user_gender = 'Male';
$user_height = 170; // cm default
$user_weight = 70; // kg default
$fitness_goal = 'Monarch Strength Awakening';
$trainer_name = 'Sudarshan System AI';
$user_xp = 0;
$user_rank = 'S-Rank Hunter';

if (isset($_SESSION['user_data'])) {
    $uid_esc = mysqli_real_escape_string($con, $_SESSION['user_data']);
    $qu = mysqli_query($con, "SELECT username, gender, height, weight, fitness_goal, trainer_id, xp_points, gym_rank FROM users WHERE userid='$uid_esc'");
    if ($qu && mysqli_num_rows($qu) > 0) {
        $row_u = mysqli_fetch_assoc($qu);
        if (!empty($row_u['username'])) $member_name = $row_u['username'];
        if (!empty($row_u['gender']) && strtolower($row_u['gender']) === 'female') {
            $user_gender = 'Female';
        }
        if (!empty($row_u['height']) && floatval($row_u['height']) > 0) $user_height = floatval($row_u['height']);
        if (!empty($row_u['weight']) && floatval($row_u['weight']) > 0) $user_weight = floatval($row_u['weight']);
        if (!empty($row_u['fitness_goal'])) $fitness_goal = $row_u['fitness_goal'];
        $user_xp = intval($row_u['xp_points']);
        if (!empty($row_u['gym_rank'])) $user_rank = $row_u['gym_rank'];

        if (!empty($row_u['trainer_id'])) {
            $tr_id = mysqli_real_escape_string($con, $row_u['trainer_id']);
            $qtr = mysqli_query($con, "SELECT Full_name FROM admin WHERE username='$tr_id'");
            if ($qtr && mysqli_num_rows($qtr) > 0) {
                $rtr = mysqli_fetch_assoc($qtr);
                $trainer_name = $rtr['Full_name'];
            }
        }
    }
}

// Calculate Personalized Health & Nutrition Metrics
$bmr = ($user_gender === 'Female') 
    ? (10 * $user_weight) + (6.25 * $user_height) - (5 * 25) - 161
    : (10 * $user_weight) + (6.25 * $user_height) - (5 * 25) + 5;
$bmr = round($bmr);
$tdee = round($bmr * 1.55);
$protein_g = round($user_weight * 2.0); // 2g per kg
$water_l = round($user_weight * 0.04, 1);
$level = floor($user_xp / 100) + 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>[SYSTEM WINDOW] Solo Leveling AI Trainer | <?php echo htmlspecialchars($gym['gym_name']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;600;700;800;900&family=Outfit:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- MediaPipe Pose & Camera Utils -->
    <script src="https://cdn.jsdelivr.net/npm/@mediapipe/camera_utils/camera_utils.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/@mediapipe/pose/pose.js" crossorigin="anonymous"></script>
    
    <style>
        :root {
            --bg-dark: #030712;
            --card-bg: rgba(9, 14, 28, 0.9);
            --system-cyan: #00f0ff;
            --system-blue: #0077ff;
            --monarch-purple: #7000ff;
            --monarch-glow: rgba(112, 0, 255, 0.4);
            --quest-gold: #ffb703;
            --text-main: #f8fafc;
            --text-muted: #64748b;
            --system-border: rgba(0, 240, 255, 0.35);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Outfit', sans-serif; }

        body {
            background: var(--bg-dark);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
            background-image: 
                radial-gradient(circle at 50% 20%, rgba(112, 0, 255, 0.15) 0%, transparent 60%),
                radial-gradient(circle at 80% 80%, rgba(0, 240, 255, 0.1) 0%, transparent 50%);
        }

        header {
            background: rgba(5, 9, 20, 0.95);
            border-bottom: 2px solid var(--system-border);
            padding: 15px 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(20px);
            box-shadow: 0 4px 30px rgba(0, 240, 255, 0.2);
        }

        .gym-brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .gym-logo {
            max-height: 45px;
            filter: drop-shadow(0 0 10px rgba(0,240,255,0.5));
        }

        .page-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 18px;
            font-weight: 900;
            background: linear-gradient(135deg, #00f0ff, #0077ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-transform: uppercase;
            letter-spacing: 2px;
            text-shadow: 0 0 20px rgba(0, 240, 255, 0.5);
        }

        .header-controls {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .gender-switch {
            background: rgba(9, 14, 28, 0.95);
            border: 1px solid var(--system-border);
            border-radius: 14px;
            padding: 4px;
            display: flex;
            gap: 4px;
        }

        .gender-btn {
            background: transparent;
            color: var(--text-muted);
            border: none;
            padding: 8px 16px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.2s ease;
            font-family: 'Orbitron', sans-serif;
        }

        .gender-btn.active.male {
            background: var(--system-cyan);
            color: #030712;
            box-shadow: 0 0 20px rgba(0,240,255,0.6);
        }

        .gender-btn.active.female {
            background: var(--monarch-purple);
            color: #fff;
            box-shadow: 0 0 20px rgba(112,0,255,0.6);
        }

        .btn-back {
            background: rgba(0, 240, 255, 0.1);
            color: var(--system-cyan);
            border: 1px solid var(--system-border);
            padding: 8px 18px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 800;
            text-decoration: none;
            font-family: 'Orbitron', sans-serif;
            transition: all 0.2s ease;
        }

        .btn-back:hover {
            background: var(--system-cyan);
            color: #030712;
            box-shadow: 0 0 20px rgba(0, 240, 255, 0.6);
        }

        .app-layout {
            display: grid;
            grid-template-columns: 360px 1fr 340px;
            gap: 20px;
            padding: 20px;
            flex: 1;
            max-width: 1750px;
            margin: 0 auto;
            width: 100%;
        }

        @media (max-width: 1200px) {
            .app-layout {
                grid-template-columns: 1fr;
            }
        }

        /* Solo Leveling System Panels */
        .panel {
            background: var(--card-bg);
            border: 1px solid var(--system-border);
            border-radius: 20px;
            padding: 22px;
            backdrop-filter: blur(20px);
            display: flex;
            flex-direction: column;
            box-shadow: 0 0 35px rgba(0, 240, 255, 0.15);
            position: relative;
        }

        .panel::before {
            content: '';
            position: absolute;
            top: -1px;
            left: 20px;
            right: 20px;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--system-cyan), transparent);
        }

        .panel-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 14px;
            font-weight: 900;
            color: var(--system-cyan);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            border-bottom: 1px solid var(--system-border);
            padding-bottom: 12px;
        }

        /* Hunter Status Window */
        .status-window {
            background: linear-gradient(135deg, rgba(0, 240, 255, 0.1) 0%, rgba(112, 0, 255, 0.15) 100%);
            border: 1px solid var(--system-cyan);
            border-radius: 16px;
            padding: 16px;
            margin-bottom: 20px;
            box-shadow: 0 0 25px rgba(0, 240, 255, 0.2);
            position: relative;
        }

        .status-tag {
            font-family: 'Orbitron', sans-serif;
            font-size: 10px;
            color: var(--system-cyan);
            font-weight: 900;
            letter-spacing: 2px;
            margin-bottom: 4px;
        }

        .hunter-name {
            font-family: 'Orbitron', sans-serif;
            font-size: 18px;
            font-weight: 900;
            color: #fff;
            text-shadow: 0 0 10px var(--system-cyan);
        }

        /* Category Filter Pills */
        .cat-pills {
            display: flex;
            gap: 8px;
            overflow-x: auto;
            padding-bottom: 10px;
            margin-bottom: 15px;
            scrollbar-width: none;
        }

        .cat-pill {
            background: rgba(0, 240, 255, 0.05);
            border: 1px solid rgba(0, 240, 255, 0.2);
            color: var(--text-muted);
            padding: 8px 14px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 800;
            cursor: pointer;
            white-space: nowrap;
            font-family: 'Orbitron', sans-serif;
            transition: all 0.2s ease;
        }

        .cat-pill.active {
            background: var(--system-cyan);
            color: #030712;
            border-color: var(--system-cyan);
            box-shadow: 0 0 20px rgba(0, 240, 255, 0.6);
        }

        /* Exercise Cards List */
        .ex-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
            overflow-y: auto;
            max-height: 550px;
            padding-right: 5px;
        }

        .ex-card {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(0, 240, 255, 0.15);
            border-radius: 14px;
            padding: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .ex-card:hover, .ex-card.active {
            background: rgba(0, 240, 255, 0.15);
            border-color: var(--system-cyan);
            box-shadow: 0 0 20px rgba(0, 240, 255, 0.3);
            transform: translateX(4px);
        }

        .ex-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: rgba(0,240,255,0.15);
            color: var(--system-cyan);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: 900;
            flex-shrink: 0;
            border: 1px solid var(--system-cyan);
        }

        .ex-info h4 {
            font-size: 14px;
            font-weight: 800;
            color: #fff;
            margin-bottom: 2px;
        }

        .ex-info p {
            font-size: 11px;
            color: var(--text-muted);
        }

        /* Stage Container */
        .viewport-container {
            background: radial-gradient(circle at 50% 40%, #0b1329 0%, #030712 100%);
            border: 2px solid var(--system-cyan);
            border-radius: 28px;
            position: relative;
            min-height: 600px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 50px rgba(0, 240, 255, 0.25);
        }

        .stage-media-box {
            width: 100%;
            height: 100%;
            position: absolute;
            top: 0;
            left: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #030712;
        }

        .human-demo-gif {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            border-radius: 20px;
            box-shadow: 0 0 40px rgba(0,240,255,0.3);
        }

        .webcam-container {
            display: none;
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #000;
            z-index: 5;
        }

        #webcamVideo {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transform: scaleX(-1);
        }

        #poseCanvas {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 6;
            transform: scaleX(-1);
        }

        .vp-overlay-top {
            position: absolute;
            top: 20px;
            left: 20px;
            right: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            pointer-events: none;
            z-index: 10;
        }

        .vp-title-box {
            background: rgba(5, 9, 20, 0.95);
            border: 1px solid var(--system-cyan);
            backdrop-filter: blur(15px);
            padding: 12px 20px;
            border-radius: 16px;
            pointer-events: auto;
            box-shadow: 0 0 20px rgba(0,240,255,0.3);
        }

        .vp-title-box h3 {
            font-family: 'Orbitron', sans-serif;
            font-size: 18px;
            font-weight: 900;
            color: #fff;
        }

        .vp-title-box span {
            font-size: 11px;
            color: var(--system-cyan);
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }

        .model-badge {
            background: rgba(0, 240, 255, 0.15);
            border: 1px solid var(--system-cyan);
            color: var(--system-cyan);
            padding: 6px 14px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 800;
            margin-top: 4px;
            display: inline-block;
            font-family: 'Orbitron', sans-serif;
        }

        .vp-controls-bar {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(5, 9, 20, 0.95);
            border: 1px solid var(--system-cyan);
            backdrop-filter: blur(20px);
            padding: 10px 20px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            z-index: 10;
            box-shadow: 0 0 30px rgba(0,240,255,0.4);
        }

        .ctrl-btn {
            background: rgba(0, 240, 255, 0.1);
            color: #fff;
            border: 1px solid var(--system-border);
            padding: 10px 18px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            font-weight: 800;
            cursor: pointer;
            font-family: 'Orbitron', sans-serif;
            transition: all 0.2s ease;
        }

        .ctrl-btn:hover, .ctrl-btn.active {
            background: var(--system-cyan);
            color: #030712;
            box-shadow: 0 0 20px rgba(0,240,255,0.6);
        }

        .webcam-btn {
            background: linear-gradient(135deg, #00f0ff, #0077ff);
            color: #030712;
            border: none;
            padding: 10px 18px;
            border-radius: 14px;
            font-size: 12px;
            font-weight: 900;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-family: 'Orbitron', sans-serif;
            box-shadow: 0 0 20px rgba(0,240,255,0.5);
            pointer-events: auto;
        }

        .voice-btn {
            background: linear-gradient(135deg, #7000ff, #480094);
            color: #fff;
            border: none;
            padding: 10px 18px;
            border-radius: 14px;
            font-size: 12px;
            font-weight: 900;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-family: 'Orbitron', sans-serif;
            box-shadow: 0 0 20px rgba(112,0,255,0.5);
            pointer-events: auto;
        }

        .tip-card {
            background: rgba(15, 23, 42, 0.4);
            border: 1px solid var(--system-border);
            border-radius: 16px;
            padding: 14px;
            margin-bottom: 12px;
        }

        .tip-card h5 {
            font-family: 'Orbitron', sans-serif;
            font-size: 12px;
            font-weight: 800;
            color: var(--system-cyan);
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .tip-card p {
            font-size: 12px;
            color: var(--text-muted);
            line-height: 1.5;
        }

        .muscle-tag {
            display: inline-block;
            background: rgba(112, 0, 255, 0.25);
            color: #a78bfa;
            border: 1px solid var(--monarch-purple);
            padding: 4px 10px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 800;
            font-family: 'Orbitron', sans-serif;
            margin-right: 6px;
            margin-bottom: 6px;
        }

        .rep-counter-box {
            background: rgba(0, 240, 255, 0.1);
            border: 1px solid var(--system-cyan);
            border-radius: 16px;
            padding: 15px;
            text-align: center;
            margin-top: 15px;
            box-shadow: 0 0 25px rgba(0,240,255,0.2);
        }

        .rep-number {
            font-family: 'Orbitron', sans-serif;
            font-size: 36px;
            font-weight: 900;
            color: var(--system-cyan);
            text-shadow: 0 0 20px var(--system-cyan);
        }

        .angle-badge {
            background: rgba(0, 240, 255, 0.15);
            border: 1px solid var(--system-cyan);
            color: var(--system-cyan);
            font-size: 11px;
            font-weight: 800;
            padding: 4px 10px;
            border-radius: 8px;
            display: inline-block;
            margin-top: 6px;
            font-family: 'Orbitron', sans-serif;
        }

        .btn-save-log {
            width: 100%;
            background: linear-gradient(135deg, var(--system-cyan), #0077ff);
            color: #030712;
            border: none;
            padding: 14px;
            border-radius: 12px;
            font-weight: 900;
            font-size: 13px;
            cursor: pointer;
            font-family: 'Orbitron', sans-serif;
            box-shadow: 0 0 25px rgba(0,240,255,0.6);
            margin-top: 10px;
            transition: all 0.2s ease;
        }

        .btn-save-log:hover {
            transform: translateY(-2px);
            box-shadow: 0 0 35px rgba(0,240,255,0.9);
        }
    </style>
</head>
<body>

    <!-- Solo Leveling System Background Particles Canvas -->
    <canvas id="systemParticleCanvas" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 1; opacity: 0.5;"></canvas>

    <!-- Solo Leveling Quest Clear Level-Up Modal -->
    <div id="questClearModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(3, 7, 18, 0.95); z-index: 10000; align-items: center; justify-content: center; flex-direction: column;">
        <div style="border: 2px solid var(--system-cyan); background: rgba(9, 14, 28, 0.95); padding: 40px; border-radius: 24px; text-align: center; max-width: 500px; width: 90%; box-shadow: 0 0 60px rgba(0, 240, 255, 0.8), 0 0 100px rgba(112, 0, 255, 0.6); animation: levelup-gold-glow 1.5s ease-in-out infinite alternate;">
            <div style="font-family: 'Orbitron'; color: var(--quest-gold); font-size: 14px; font-weight: 900; letter-spacing: 3px; margin-bottom: 8px;">[ QUEST CLEAR! ]</div>
            <h2 style="font-family: 'Orbitron'; color: #00f0ff; font-size: 28px; font-weight: 900; text-shadow: 0 0 20px #00f0ff; margin-bottom: 10px;">LEVEL UP! +50 EXP</h2>
            <p style="color: #cbd5e1; font-size: 14px; margin-bottom: 25px; line-height: 1.5;">Daily Quest Mandate Cleared! Your Hunter Stats &amp; Routine Log have been recorded in the System Database!</p>
            <button onclick="closeQuestModal()" style="background: linear-gradient(135deg, #00f0ff, #0077ff); color: #030712; border: none; padding: 14px 30px; border-radius: 14px; font-family: 'Orbitron'; font-weight: 900; font-size: 14px; cursor: pointer; box-shadow: 0 0 30px #00f0ff;">
                CLAIM REWARDS &amp; CONTINUE ➔
            </button>
        </div>
    </div>

    <header style="position: relative; z-index: 10;">
        <div class="gym-brand">
            <img src="<?php echo htmlspecialchars($gym['gym_logo']); ?>" class="gym-logo" alt="Gym Logo">
            <div>
                <div class="page-title">[ SYSTEM NOTIFICATION: SOLO LEVELING MODE ]</div>
                <div style="font-size: 11px; color: var(--system-cyan); font-family: 'Orbitron'; font-weight: 700;">DAILY QUEST SYSTEM • SHADOW MONARCH AWAKENING</div>
            </div>
        </div>

        <div class="header-controls">
            <!-- Gender Switcher -->
            <div class="gender-switch">
                <button class="gender-btn <?php echo ($user_gender === 'Male') ? 'active male' : ''; ?>" id="btn-gender-male" onclick="setCoachGender('Male')">👨 MALE MONARCH</button>
                <button class="gender-btn <?php echo ($user_gender === 'Female') ? 'active female' : ''; ?>" id="btn-gender-female" onclick="setCoachGender('Female')">👩 FEMALE MONARCH</button>
            </div>

            <a href="javascript:history.back()" class="btn-back">← RETURN TO BASE</a>
        </div>
    </header>

    <div class="app-layout">
        
        <!-- Left Panel: Exercise Selector & Hunter Status Window -->
        <div class="panel">
            <!-- Hunter Status Window -->
            <div class="status-window">
                <div class="status-tag">[ HUNTER STATUS WINDOW ]</div>
                <div class="hunter-name">PLAYER: <?php echo htmlspecialchars($member_name); ?></div>
                <div style="font-size: 11px; color: #94a3b8; margin-top: 4px;">
                    CLASS: <strong style="color: var(--system-cyan);">SHADOW MONARCH</strong><br>
                    RANK: <strong style="color: var(--quest-gold);"><?php echo htmlspecialchars($user_rank); ?></strong><br>
                    LEVEL: <strong style="color: #10b981; font-weight: 900;"><?php echo $level; ?></strong> (<?php echo $user_xp; ?> EXP)
                </div>
            </div>

            <div class="panel-title">
                <span>⚔️ SELECT DAILY QUEST</span>
                <span style="font-size: 11px; color: var(--system-cyan);" id="ex-count">5 QUESTS</span>
            </div>

            <!-- Categories Filter -->
            <div class="cat-pills">
                <button class="cat-pill active" onclick="filterCategory('all', this)">ALL</button>
                <button class="cat-pill" onclick="filterCategory('chest', this)">PUSH-UP</button>
                <button class="cat-pill" onclick="filterCategory('back', this)">PULL-UP</button>
                <button class="cat-pill" onclick="filterCategory('legs', this)">SQUAT</button>
                <button class="cat-pill" onclick="filterCategory('core', this)">SIT-UP</button>
                <button class="cat-pill" onclick="filterCategory('cardio', this)">WALK</button>
            </div>

            <!-- Exercise List -->
            <div class="ex-list" id="exercise-list">
                <!-- Populated dynamically -->
            </div>
        </div>

        <!-- Middle Panel: Stage & Real Human Demonstration -->
        <div class="viewport-container">
            <div class="vp-overlay-top">
                <div class="vp-title-box">
                    <span id="active-cat">PUSH-UP QUEST</span>
                    <h3 id="active-ex-name">Push-Up Exercise</h3>
                    <div class="model-badge" id="active-model-badge">👨 SHADOW MONARCH DEMO</div>
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button class="webcam-btn" onclick="toggleWebcamPoseTracking()">
                        📷 SYSTEM AI CAMERA
                    </button>
                    <button class="voice-btn" onclick="speakFormInstruction()">
                        🔊 SYSTEM VOICE COACH
                    </button>
                </div>
            </div>

            <!-- Real Human Demonstration GIF Box -->
            <div class="stage-media-box" id="demoMediaBox">
                <img id="demoGif" src="https://github.com/itzThillaiC/AI-Fitness-trainer/raw/main/output/output%20push-up.gif" class="human-demo-gif" alt="Real Human Workout Demo">
            </div>

            <!-- Live Webcam Container -->
            <div class="webcam-container" id="webcamBox">
                <video id="webcamVideo" autoplay playsinline muted></video>
                <canvas id="poseCanvas"></canvas>
            </div>

            <!-- Stage Controls Bar -->
            <div class="vp-controls-bar">
                <button class="ctrl-btn active" id="btn-toggle-demo" onclick="toggleDemoView()">🎥 DEMO MODE</button>
                <button class="ctrl-btn" id="btn-toggle-cam" onclick="toggleWebcamPoseTracking()">📷 AI POSE DETECTOR</button>
            </div>
        </div>

        <!-- Right Panel: Form Guidance, AI Rep Counter & Personalized Nutrition -->
        <div class="panel">
            <div class="panel-title">
                <span>🎯 QUEST EXECUTION GUIDE</span>
            </div>

            <div style="margin-bottom: 15px;">
                <div style="font-size: 11px; font-weight: 800; color: var(--text-muted); margin-bottom: 6px; font-family: 'Orbitron';">TARGET MUSCLE MATRIX</div>
                <div id="target-muscles-container">
                    <span class="muscle-tag">Chest</span>
                    <span class="muscle-tag">Triceps</span>
                    <span class="muscle-tag">Core</span>
                </div>
            </div>

            <div class="tip-card">
                <h5>📌 Step 1: Alignment &amp; Setup</h5>
                <p id="tip-1">Form a rigid straight line from head to heels with hands placed slightly wider than shoulder-width.</p>
            </div>

            <div class="tip-card">
                <h5>📌 Step 2: Movement Range</h5>
                <p id="tip-2">Lower body until chest is an inch off the floor, keeping elbows tucked at 45 degrees.</p>
            </div>

            <div class="tip-card">
                <h5>📌 Step 3: Joint Angle Tracked</h5>
                <p id="tip-3">Elbow Joint Angle < 90° triggers DOWN position. Extension > 160° completes 1 REQUISITE REP.</p>
                <div class="angle-badge" id="live-angle-text">Live Angle: 175° (UP)</div>
            </div>

            <!-- Live AI Rep Counter & Posture Evaluator -->
            <div class="rep-counter-box">
                <div style="font-size: 11px; font-weight: 800; color: var(--system-cyan); text-transform: uppercase; font-family: 'Orbitron';">SYSTEM REPETITION COUNTER</div>
                <div class="rep-number" id="rep-display">0 REPS</div>
                <div style="font-size: 12px; color: #94a3b8; margin-top: 4px;" id="ai-stage-status">Position: UP • Stand in front of camera</div>
                <div style="font-size: 12px; margin-top: 6px;" id="posture-status">Form Status: <span style="color: #10b981; font-weight: bold;">Good Posture 🟢</span></div>
                
                <button class="btn-save-log" onclick="saveWorkoutLogToDatabase()">
                    💾 CLAIM QUEST REWARD (+50 EXP)
                </button>
            </div>

            <!-- Personalized AI Nutrition & Macro Assistant -->
            <div class="tip-card" style="margin-top: 15px; background: rgba(112, 0, 255, 0.15); border-color: var(--monarch-purple);">
                <h5 style="color: #a78bfa;">🥗 SYSTEM RECOVERY &amp; NUTRITION</h5>
                <div style="font-size: 11px; color: #94a3b8; margin-bottom: 8px;">Calculated for <?php echo htmlspecialchars($member_name); ?> (<?php echo $user_weight; ?>kg • <?php echo $user_height; ?>cm)</div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; font-size: 11px; text-align: center;">
                    <div style="background: rgba(0,0,0,0.4); padding: 8px; border-radius: 8px; border: 1px solid var(--monarch-purple);">
                        <span style="color: #94a3b8; display: block; font-size: 9px; font-family: 'Orbitron';">PROTEIN INTAKE</span>
                        <strong style="color: #10b981; font-size: 14px;"><?php echo $protein_g; ?>g / day</strong>
                    </div>
                    <div style="background: rgba(0,0,0,0.4); padding: 8px; border-radius: 8px; border: 1px solid var(--monarch-purple);">
                        <span style="color: #94a3b8; display: block; font-size: 9px; font-family: 'Orbitron';">FLUID INTAKE</span>
                        <strong style="color: var(--system-cyan); font-size: 14px;"><?php echo $water_l; ?> Liters</strong>
                    </div>
                </div>

                <div style="font-size: 11px; color: #cbd5e1; margin-top: 10px; line-height: 1.4;">
                    <strong style="color: var(--quest-gold);">BMR: <?php echo number_format($bmr); ?> kcal</strong> • <strong style="color: #a78bfa;">TDEE: <?php echo number_format($tdee); ?> kcal</strong><br>
                    <small style="color: #94a3b8;">System recovery protocol: Consuming 30g Protein Shake + 5g Creatine unlocks max muscular restoration!</small>
                </div>
            </div>
        </div>

    </div>

    <script>
        const EXERCISES = [
            {
                id: 'pushup',
                name: 'Push-Up Quest',
                category: 'chest',
                icon: '🤸',
                muscles: ['Chest (Pectorals)', 'Triceps', 'Core (Abs)'],
                gif: 'https://github.com/itzThillaiC/AI-Fitness-trainer/raw/main/output/output%20push-up.gif',
                tip1: 'Form a rigid straight line from head to heels with hands placed slightly wider than shoulder-width.',
                tip2: 'Lower body until chest is an inch off the floor, keeping elbows tucked at 45 degrees.',
                tip3: 'Elbow Joint Angle < 90° triggers DOWN position. Extension > 160° completes 1 REQUISITE REP.',
                speech: 'System Quest Push Up: Maintain tight core, lower chest smoothly, and press up with power.',
                type: 'push-up'
            },
            {
                id: 'squats',
                name: 'Squat Quest',
                category: 'legs',
                icon: '🦵',
                muscles: ['Quadriceps', 'Glutes', 'Hamstrings'],
                gif: 'https://github.com/itzThillaiC/AI-Fitness-trainer/raw/main/output/output%20squat.gif',
                tip1: 'Stand with feet shoulder-width apart, chest lifted, and core tight.',
                tip2: 'Lower hips backward and down until knee angle reaches 90 degrees or below.',
                tip3: 'Knee Joint Angle < 90° triggers SQUAT DOWN. Extension > 160° completes 1 SQUAT REP.',
                speech: 'System Quest Squat: Lower hips below parallel and explode back up.',
                type: 'squat'
            },
            {
                id: 'pullup',
                name: 'Pull-Up Quest',
                category: 'back',
                icon: '🏋️‍♂️',
                muscles: ['Latissimus Dorsi', 'Biceps', 'Rhomboids'],
                gif: 'https://github.com/itzThillaiC/AI-Fitness-trainer/raw/main/output/output%20pull-up.gif',
                tip1: 'Grip pull-up bar with hands shoulder-width apart and suspend body.',
                tip2: 'Pull up until chin clears the bar level by driving elbows down to torso.',
                tip3: 'Elbow Joint Angle < 70° triggers CHIN UP. Full descent > 150° completes 1 PULL-UP REP.',
                speech: 'System Quest Pull Up: Pull body vertically until chin clears bar.',
                type: 'pull-up'
            },
            {
                id: 'situp',
                name: 'Sit-Up Quest',
                category: 'core',
                icon: '🛡️',
                muscles: ['Rectus Abdominis', 'Hip Flexors', 'Obliques'],
                gif: 'https://github.com/itzThillaiC/AI-Fitness-trainer/raw/main/output/output%20sit-up.gif',
                tip1: 'Lie on your back with knees bent and feet flat on the floor.',
                tip2: 'Engage core to lift torso up towards knees in a full range of motion.',
                tip3: 'Hip Joint Angle < 65° triggers SIT UP. Lowering to floor completes 1 SIT-UP REP.',
                speech: 'System Quest Sit Up: Engage abdominal muscles to raise torso.',
                type: 'sit-up'
            },
            {
                id: 'walk',
                name: 'Walk Endurance Quest',
                category: 'cardio',
                icon: '🏃',
                muscles: ['Cardiovascular System', 'Calves', 'Glutes'],
                gif: 'https://github.com/itzThillaiC/AI-Fitness-trainer/raw/main/output/output%20walking%20exercise.gif',
                tip1: 'Maintain upright posture with shoulders relaxed and gaze forward.',
                tip2: 'Swing arms naturally while taking rhythmic heel-to-toe strides.',
                tip3: 'Alternating stride knee angles track active step counts & calorie burn.',
                speech: 'System Quest Walking: Maintain steady rhythmic cadence.',
                type: 'walk'
            }
        ];

        let currentGender = <?php echo json_encode($user_gender); ?>;
        let activeEx = EXERCISES[0];
        let repCount = 0;
        let exerciseState = 'UP';

        function setCoachGender(gender) {
            currentGender = gender;
            document.getElementById('btn-gender-male').classList.toggle('active', gender === 'Male');
            document.getElementById('btn-gender-male').classList.toggle('male', gender === 'Male');
            document.getElementById('btn-gender-female').classList.toggle('active', gender === 'Female');
            document.getElementById('btn-gender-female').classList.toggle('female', gender === 'Female');

            document.getElementById('active-model-badge').textContent = (gender === 'Male') ? '👨 SHADOW MONARCH DEMO' : '👩 FEMALE MONARCH DEMO';
        }

        function renderExerciseList(filter = 'all') {
            const container = document.getElementById('exercise-list');
            container.innerHTML = '';
            
            const filtered = filter === 'all' ? EXERCISES : EXERCISES.filter(e => e.category === filter);
            document.getElementById('ex-count').textContent = `${filtered.length} QUESTS`;

            filtered.forEach(ex => {
                const card = document.createElement('div');
                card.className = `ex-card ${ex.id === activeEx.id ? 'active' : ''}`;
                card.onclick = () => selectExercise(ex, card);
                card.innerHTML = `
                    <div class="ex-icon">${ex.icon}</div>
                    <div class="ex-info">
                        <h4>${ex.name}</h4>
                        <p>${ex.category.toUpperCase()} • ${ex.muscles[0]}</p>
                    </div>
                `;
                container.appendChild(card);
            });
        }

        function filterCategory(cat, btn) {
            document.querySelectorAll('.cat-pill').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            renderExerciseList(cat);
        }

        function selectExercise(ex, cardEl) {
            activeEx = ex;
            repCount = 0;
            exerciseState = 'UP';
            document.getElementById('rep-display').textContent = `0 REPS`;

            document.querySelectorAll('.ex-card').forEach(c => c.classList.remove('active'));
            if (cardEl) cardEl.classList.add('active');

            document.getElementById('active-cat').textContent = `${ex.category.toUpperCase()} QUEST`;
            document.getElementById('active-ex-name').textContent = ex.name;

            document.getElementById('demoGif').src = ex.gif;

            const mContainer = document.getElementById('target-muscles-container');
            mContainer.innerHTML = ex.muscles.map(m => `<span class="muscle-tag">${m}</span>`).join('');

            document.getElementById('tip-1').textContent = ex.tip1;
            document.getElementById('tip-2').textContent = ex.tip2;
            document.getElementById('tip-3').textContent = ex.tip3;
        }

        function speakFormInstruction() {
            if ('speechSynthesis' in window) {
                window.speechSynthesis.cancel();
                const msg = new SpeechSynthesisUtterance(activeEx.speech);
                msg.rate = 0.95;
                msg.pitch = currentGender === 'Female' ? 1.25 : 0.95;
                window.speechSynthesis.speak(msg);
            } else {
                alert("System Voice Coach: " + activeEx.speech);
            }
        }

        function toggleDemoView() {
            document.getElementById('webcamBox').style.display = 'none';
            document.getElementById('demoMediaBox').style.display = 'flex';
            document.getElementById('btn-toggle-demo').classList.add('active');
            document.getElementById('btn-toggle-cam').classList.remove('active');
        }

        function saveWorkoutLogToDatabase() {
            const formData = new FormData();
            formData.append('action', 'save_workout_log');
            formData.append('ex_name', activeEx.name);
            formData.append('reps_done', repCount > 0 ? repCount : 12);

            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    showQuestClearModal();
                } else {
                    alert('⚠️ ' + data.message);
                }
            })
            .catch(err => {
                alert('⚠️ System Error: ' + err.message);
            });
        }

        function showQuestClearModal() {
            document.getElementById('questClearModal').style.display = 'flex';
            if ('speechSynthesis' in window) {
                const msg = new SpeechSynthesisUtterance("Quest Clear! Level Up! Plus 50 EXP awarded!");
                msg.rate = 1.0;
                window.speechSynthesis.speak(msg);
            }
        }

        function closeQuestModal() {
            document.getElementById('questClearModal').style.display = 'none';
        }

        // Solo Leveling System Background Particles Engine
        function initSystemParticles() {
            const pCanvas = document.getElementById('systemParticleCanvas');
            if (!pCanvas) return;
            const pCtx = pCanvas.getContext('2d');
            let width = pCanvas.width = window.innerWidth;
            let height = pCanvas.height = window.innerHeight;

            const particles = [];
            for (let i = 0; i < 40; i++) {
                particles.push({
                    x: Math.random() * width,
                    y: Math.random() * height,
                    radius: Math.random() * 2 + 1,
                    color: Math.random() > 0.5 ? '#00f0ff' : '#7000ff',
                    vy: -(Math.random() * 0.8 + 0.3),
                    vx: (Math.random() - 0.5) * 0.4,
                    alpha: Math.random() * 0.8 + 0.2
                });
            }

            function renderParticles() {
                pCtx.clearRect(0, 0, width, height);
                particles.forEach(p => {
                    p.y += p.vy;
                    p.x += p.vx;
                    if (p.y < 0) p.y = height;
                    if (p.x < 0) p.x = width;
                    if (p.x > width) p.x = 0;

                    pCtx.save();
                    pCtx.globalAlpha = p.alpha;
                    pCtx.fillStyle = p.color;
                    pCtx.shadowColor = p.color;
                    pCtx.shadowBlur = 8;
                    pCtx.beginPath();
                    pCtx.arc(p.x, p.y, p.radius, 0, Math.PI * 2);
                    pCtx.fill();
                    pCtx.restore();
                });
                requestAnimationFrame(renderParticles);
            }

            window.addEventListener('resize', () => {
                width = pCanvas.width = window.innerWidth;
                height = pCanvas.height = window.innerHeight;
            });

            renderParticles();
        }

        // MediaPipe Real-Time Tracker
        let poseDetector = null;
        let cameraStream = null;
        let isWebcamActive = false;

        function calculateAngle(a, b, c) {
            let radians = Math.atan2(c.y - b.y, c.x - b.x) - Math.atan2(a.y - b.y, a.x - b.x);
            let angle = Math.abs(radians * 180.0 / Math.PI);
            if (angle > 180.0) {
                angle = 360.0 - angle;
            }
            return Math.round(angle);
        }

        function toggleWebcamPoseTracking() {
            if (!isWebcamActive) {
                startWebcamPoseTracking();
            } else {
                stopWebcamPoseTracking();
            }
        }

        async function startWebcamPoseTracking() {
            const webcamBox = document.getElementById('webcamBox');
            const demoMediaBox = document.getElementById('demoMediaBox');
            const video = document.getElementById('webcamVideo');

            webcamBox.style.display = 'block';
            demoMediaBox.style.display = 'none';
            document.getElementById('btn-toggle-cam').classList.add('active');
            document.getElementById('btn-toggle-demo').classList.remove('active');

            isWebcamActive = true;

            poseDetector = new Pose({
                locateFile: (file) => `https://cdn.jsdelivr.net/npm/@mediapipe/pose/${file}`
            });

            poseDetector.setOptions({
                modelComplexity: 1,
                smoothLandmarks: true,
                minDetectionConfidence: 0.5,
                minTrackingConfidence: 0.5
            });

            poseDetector.onResults(onPoseResults);

            cameraStream = new Camera(video, {
                onFrame: async () => {
                    if (isWebcamActive && video) {
                        await poseDetector.send({ image: video });
                    }
                },
                width: 640,
                height: 480
            });

            cameraStream.start();
        }

        function stopWebcamPoseTracking() {
            isWebcamActive = false;
            document.getElementById('webcamBox').style.display = 'none';
            document.getElementById('demoMediaBox').style.display = 'flex';
            document.getElementById('btn-toggle-cam').classList.remove('active');
            document.getElementById('btn-toggle-demo').classList.add('active');

            if (cameraStream) {
                cameraStream.stop();
            }
        }

        function onPoseResults(results) {
            const canvas = document.getElementById('poseCanvas');
            const ctx = canvas.getContext('2d');
            canvas.width = canvas.clientWidth;
            canvas.height = canvas.clientHeight;

            ctx.clearRect(0, 0, canvas.width, canvas.height);

            if (!results.poseLandmarks) {
                document.getElementById('ai-stage-status').textContent = 'No body detected in camera view';
                return;
            }

            const lm = results.poseLandmarks;

            ctx.fillStyle = '#00f0ff';
            ctx.strokeStyle = '#7000ff';
            ctx.lineWidth = 4;

            lm.forEach((pt) => {
                ctx.beginPath();
                ctx.arc(pt.x * canvas.width, pt.y * canvas.height, 6, 0, Math.PI * 2);
                ctx.fill();
            });

            let mainAngle = 180;

            if (activeEx.id === 'squats') {
                const hip = lm[24], knee = lm[26], ankle = lm[28];
                if (hip && knee && ankle) {
                    mainAngle = calculateAngle(hip, knee, ankle);

                    if (mainAngle < 95 && exerciseState === 'UP') {
                        exerciseState = 'DOWN';
                        document.getElementById('ai-stage-status').textContent = 'Position: SQUAT DOWN';
                        document.getElementById('posture-status').innerHTML = 'Form Status: <span style="color: #00f0ff; font-weight: bold;">Good Posture 🟢 (Optimal Depth)</span>';
                    }
                    if (mainAngle > 155 && exerciseState === 'DOWN') {
                        exerciseState = 'UP';
                        repCount++;
                        document.getElementById('rep-display').textContent = `${repCount} REPS`;
                        document.getElementById('ai-stage-status').textContent = `Position: UP • SQUAT REP ${repCount}!`;
                        document.getElementById('posture-status').innerHTML = 'Form Status: <span style="color: #00f0ff; font-weight: bold;">Good Posture 🟢</span>';
                        speakRepCount(repCount);
                    }
                    if (mainAngle > 95 && mainAngle < 130 && exerciseState === 'UP') {
                        document.getElementById('posture-status').innerHTML = 'Form Status: <span style="color: #ff0054; font-weight: bold;">Fix Your Form ⚠️ (Descend Deeper)</span>';
                    }
                }
            } else if (activeEx.id === 'pushup') {
                const shoulder = lm[12], elbow = lm[14], wrist = lm[16];
                if (shoulder && elbow && wrist) {
                    mainAngle = calculateAngle(shoulder, elbow, wrist);

                    if (mainAngle < 90 && exerciseState === 'UP') {
                        exerciseState = 'DOWN';
                        document.getElementById('ai-stage-status').textContent = 'Position: PUSHUP DOWN';
                        document.getElementById('posture-status').innerHTML = 'Form Status: <span style="color: #00f0ff; font-weight: bold;">Good Posture 🟢 (Full Range)</span>';
                    }
                    if (mainAngle > 160 && exerciseState === 'DOWN') {
                        exerciseState = 'UP';
                        repCount++;
                        document.getElementById('rep-display').textContent = `${repCount} REPS`;
                        document.getElementById('ai-stage-status').textContent = `Position: UP • PUSHUP REP ${repCount}!`;
                        document.getElementById('posture-status').innerHTML = 'Form Status: <span style="color: #00f0ff; font-weight: bold;">Good Posture 🟢</span>';
                        speakRepCount(repCount);
                    }
                    if (mainAngle > 90 && mainAngle < 130 && exerciseState === 'UP') {
                        document.getElementById('posture-status').innerHTML = 'Form Status: <span style="color: #ff0054; font-weight: bold;">Fix Your Form ⚠️ (Touch Chest Lower)</span>';
                    }
                }
            }

            document.getElementById('live-angle-text').textContent = `Live Angle: ${mainAngle}° (${exerciseState})`;
        }

        function speakRepCount(count) {
            if ('speechSynthesis' in window) {
                const msg = new SpeechSynthesisUtterance(`${count}`);
                msg.rate = 1.1;
                window.speechSynthesis.speak(msg);
            }
        }

        window.addEventListener('DOMContentLoaded', () => {
            renderExerciseList('all');
            selectExercise(EXERCISES[0], null);
            setCoachGender(currentGender);
            initSystemParticles();
        });
    </script>
</body>
</html>
