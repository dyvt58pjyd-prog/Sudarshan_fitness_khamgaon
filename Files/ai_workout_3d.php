<?php
require_once __DIR__ . '/include/db_conn.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$gym = get_gym_details($con);
$user_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Member';

// Fetch User Gender from Database
$user_gender = 'Male';
if (isset($_SESSION['user_data'])) {
    $uid_esc = mysqli_real_escape_string($con, $_SESSION['user_data']);
    $qu = mysqli_query($con, "SELECT gender FROM users WHERE userid='$uid_esc'");
    if ($qu && mysqli_num_rows($qu) > 0) {
        $row_u = mysqli_fetch_assoc($qu);
        if (!empty($row_u['gender']) && strtolower($row_u['gender']) === 'female') {
            $user_gender = 'Female';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>3D Realistic AI Virtual Coach &amp; Workout Guide | <?php echo htmlspecialchars($gym['gym_name']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-dark: #070a12;
            --card-bg: rgba(15, 23, 42, 0.85);
            --accent: #ff6b00;
            --accent-green: #10b981;
            --accent-blue: #3b82f6;
            --accent-purple: #8b5cf6;
            --accent-pink: #ec4899;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --glass-border: rgba(255, 255, 255, 0.12);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Outfit', sans-serif; }

        body {
            background: var(--bg-dark);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        header {
            background: rgba(11, 15, 25, 0.95);
            border-bottom: 1px solid var(--glass-border);
            padding: 15px 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(15px);
        }

        .gym-brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .gym-logo {
            max-height: 45px;
        }

        .page-title {
            font-size: 18px;
            font-weight: 900;
            background: linear-gradient(135deg, #ff6b00, #ff8800);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .header-controls {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .gender-switch {
            background: rgba(15, 23, 42, 0.9);
            border: 1px solid var(--glass-border);
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
        }

        .gender-btn.active.male {
            background: var(--accent-blue);
            color: #fff;
            box-shadow: 0 4px 15px rgba(59,130,246,0.5);
        }

        .gender-btn.active.female {
            background: var(--accent-pink);
            color: #fff;
            box-shadow: 0 4px 15px rgba(236,72,153,0.5);
        }

        .btn-back {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            border: 1px solid var(--glass-border);
            padding: 8px 18px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .btn-back:hover {
            background: var(--accent);
            border-color: var(--accent);
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

        /* Panels */
        .panel {
            background: var(--card-bg);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 22px;
            backdrop-filter: blur(20px);
            display: flex;
            flex-direction: column;
            box-shadow: 0 20px 40px rgba(0,0,0,0.5);
        }

        .panel-title {
            font-size: 16px;
            font-weight: 800;
            color: #fff;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid var(--glass-border);
            padding-bottom: 12px;
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
            background: rgba(255,255,255,0.06);
            border: 1px solid var(--glass-border);
            color: var(--text-muted);
            padding: 8px 14px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.2s ease;
        }

        .cat-pill.active {
            background: var(--accent);
            color: #fff;
            border-color: var(--accent);
            box-shadow: 0 4px 15px rgba(255,107,0,0.4);
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
            background: rgba(30, 41, 59, 0.5);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .ex-card:hover, .ex-card.active {
            background: rgba(255, 107, 0, 0.15);
            border-color: var(--accent);
            transform: translateX(4px);
        }

        .ex-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: rgba(255,107,0,0.2);
            color: var(--accent);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: 900;
            flex-shrink: 0;
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

        /* Realistic Stage Container */
        .viewport-container {
            background: radial-gradient(circle at 50% 40%, #1e293b 0%, #070a12 100%);
            border: 2px solid rgba(255, 107, 0, 0.3);
            border-radius: 28px;
            position: relative;
            min-height: 600px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            box-shadow: 0 30px 60px rgba(0,0,0,0.8);
        }

        .human-stage-canvas {
            width: 100%;
            height: 100%;
            position: absolute;
            top: 0;
            left: 0;
            display: flex;
            align-items: center;
            justify-content: center;
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
            background: rgba(15, 23, 42, 0.85);
            border: 1px solid var(--glass-border);
            backdrop-filter: blur(15px);
            padding: 12px 20px;
            border-radius: 16px;
            pointer-events: auto;
        }

        .vp-title-box h3 {
            font-size: 18px;
            font-weight: 900;
            color: #fff;
        }

        .vp-title-box span {
            font-size: 12px;
            color: var(--accent);
            font-weight: 700;
            text-transform: uppercase;
        }

        .model-badge {
            background: rgba(255, 107, 0, 0.15);
            border: 1px solid #ff6b00;
            color: #ff6b00;
            padding: 6px 14px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 800;
            margin-top: 4px;
            display: inline-block;
        }

        .vp-controls-bar {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(15, 23, 42, 0.9);
            border: 1px solid var(--glass-border);
            backdrop-filter: blur(20px);
            padding: 10px 20px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            z-index: 10;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }

        .ctrl-btn {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            border: none;
            width: 44px;
            height: 44px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .ctrl-btn:hover, .ctrl-btn.active {
            background: var(--accent);
            box-shadow: 0 5px 15px rgba(255,107,0,0.4);
        }

        .voice-btn {
            background: linear-gradient(135deg, #10b981, #059669);
            color: #fff;
            border: none;
            padding: 10px 18px;
            border-radius: 14px;
            font-size: 13px;
            font-weight: 800;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 5px 15px rgba(16,185,129,0.4);
            pointer-events: auto;
        }

        /* Side Tips & Anatomical Info */
        .tip-card {
            background: rgba(30, 41, 59, 0.4);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 14px;
            margin-bottom: 12px;
        }

        .tip-card h5 {
            font-size: 13px;
            font-weight: 800;
            color: var(--accent);
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
            background: rgba(139, 92, 246, 0.2);
            color: #a78bfa;
            border: 1px solid rgba(139, 92, 246, 0.4);
            padding: 4px 10px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 700;
            margin-right: 6px;
            margin-bottom: 6px;
        }

        /* Rep Counter Box */
        .rep-counter-box {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid #10b981;
            border-radius: 16px;
            padding: 15px;
            text-align: center;
            margin-top: 15px;
        }

        .rep-number {
            font-size: 32px;
            font-weight: 900;
            color: #10b981;
        }
    </style>
</head>
<body>

    <header>
        <div class="gym-brand">
            <img src="<?php echo htmlspecialchars($gym['gym_logo']); ?>" class="gym-logo" alt="Gym Logo">
            <div>
                <div class="page-title">🏋️ REALISTIC MALE &amp; FEMALE AI WORKOUT COACH</div>
                <div style="font-size: 11px; color: var(--text-muted);">High-Definition Anatomical Gym Exercise Demonstrator</div>
            </div>
        </div>

        <div class="header-controls">
            <!-- Gender Switcher -->
            <div class="gender-switch">
                <button class="gender-btn <?php echo ($user_gender === 'Male') ? 'active male' : ''; ?>" id="btn-gender-male" onclick="setCoachGender('Male')">👨 Male Athlete</button>
                <button class="gender-btn <?php echo ($user_gender === 'Female') ? 'active female' : ''; ?>" id="btn-gender-female" onclick="setCoachGender('Female')">👩 Female Athlete</button>
            </div>

            <a href="javascript:history.back()" class="btn-back">← Back to Dashboard</a>
        </div>
    </header>

    <div class="app-layout">
        
        <!-- Left Panel: Exercise Selector -->
        <div class="panel">
            <div class="panel-title">
                <span>🏋️ Select Exercise</span>
                <span style="font-size: 11px; color: var(--accent);" id="ex-count">8 Exercises</span>
            </div>

            <!-- Categories Filter -->
            <div class="cat-pills">
                <button class="cat-pill active" onclick="filterCategory('all', this)">All</button>
                <button class="cat-pill" onclick="filterCategory('chest', this)">Chest</button>
                <button class="cat-pill" onclick="filterCategory('back', this)">Back</button>
                <button class="cat-pill" onclick="filterCategory('legs', this)">Legs</button>
                <button class="cat-pill" onclick="filterCategory('shoulders', this)">Shoulders</button>
                <button class="cat-pill" onclick="filterCategory('arms', this)">Arms</button>
                <button class="cat-pill" onclick="filterCategory('core', this)">Core</button>
            </div>

            <!-- Exercise List -->
            <div class="ex-list" id="exercise-list">
                <!-- Populated dynamically -->
            </div>
        </div>

        <!-- Middle Panel: Realistic Human Fitness Stage -->
        <div class="viewport-container">
            <div class="vp-overlay-top">
                <div class="vp-title-box">
                    <span id="active-cat">CHEST WORKOUT</span>
                    <h3 id="active-ex-name">Barbell Bench Press</h3>
                    <div class="model-badge" id="active-model-badge">👨 REALISTIC MALE ATHLETE DEMO</div>
                </div>
                <button class="voice-btn" onclick="speakFormInstruction()">
                    🔊 Audio AI Coach
                </button>
            </div>

            <!-- Realistic Canvas Visualizer Stage -->
            <div class="human-stage-canvas">
                <canvas id="humanCanvas" width="700" height="550"></canvas>
            </div>

            <!-- Controls Bar -->
            <div class="vp-controls-bar">
                <button class="ctrl-btn active" id="btn-play" onclick="toggleAnimation()" title="Play / Pause Motion">⏯️</button>
                <button class="ctrl-btn" onclick="toggleGlow()" title="Toggle Muscle Highlight Glow">🔥</button>
                <button class="ctrl-btn" onclick="toggleViewMode()" title="Toggle Motion View">🔄</button>
            </div>
        </div>

        <!-- Right Panel: Form Guidance & Anatomical Breakdown -->
        <div class="panel">
            <div class="panel-title">
                <span>🎯 Muscle &amp; Form Guide</span>
            </div>

            <div style="margin-bottom: 15px;">
                <div style="font-size: 12px; font-weight: 700; color: var(--text-muted); margin-bottom: 6px;">TARGET MUSCLES</div>
                <div id="target-muscles-container">
                    <span class="muscle-tag">Pectoralis Major</span>
                    <span class="muscle-tag">Anterior Deltoid</span>
                    <span class="muscle-tag">Triceps Brachii</span>
                </div>
            </div>

            <div class="tip-card">
                <h5>📌 Step 1: Setup &amp; Grip</h5>
                <p id="tip-1">Lie flat on the bench, grip the barbell slightly wider than shoulder-width, and retract your shoulder blades firmly into the pad.</p>
            </div>

            <div class="tip-card">
                <h5>📌 Step 2: Concentric &amp; Eccentric Phase</h5>
                <p id="tip-2">Inhale deeply as you lower the bar in a controlled path to mid-chest. Pause briefly without bouncing off your sternum.</p>
            </div>

            <div class="tip-card">
                <h5>📌 Step 3: Drive &amp; Lockout</h5>
                <p id="tip-3">Exhale forcefully as you press the bar upwards over your chest until elbows extend safely. Maintain leg drive against the floor.</p>
            </div>

            <!-- Live Workout Rep Counter -->
            <div class="rep-counter-box">
                <div style="font-size: 11px; font-weight: 800; color: #10b981; text-transform: uppercase;">Set 1 of 4 • Target 12 Reps</div>
                <div class="rep-number" id="rep-display">12 REPS</div>
                <button onclick="startRepTimer()" style="background: #10b981; color: #fff; border: none; padding: 8px 16px; border-radius: 10px; font-weight: 800; cursor: pointer; margin-top: 6px;">
                    ▶️ Start Set Timer
                </button>
            </div>
        </div>

    </div>

    <script>
        // -------------------------------------------------------------
        // EXERCISE DATABASE
        // -------------------------------------------------------------
        const EXERCISES = [
            {
                id: 'bench_press',
                name: 'Barbell Bench Press',
                category: 'chest',
                icon: '🏋️',
                muscles: ['Pectoralis Major', 'Anterior Deltoid', 'Triceps'],
                tip1: 'Lie flat on the bench, grip the barbell slightly wider than shoulder-width, and pin your shoulder blades back.',
                tip2: 'Inhale deeply as you lower the bar under control to lower-mid chest level.',
                tip3: 'Exhale forcefully and press the bar upwards in a straight trajectory to arm extension.',
                speech: 'Barbell Bench Press: Keep shoulder blades retracted into the bench, lower the bar smoothly to your mid-chest, and press up with power!',
                glowColor: '#ff6b00'
            },
            {
                id: 'pushup',
                name: 'Standard Push-ups',
                category: 'chest',
                icon: '🤸',
                muscles: ['Chest', 'Core', 'Triceps', 'Serratus'],
                tip1: 'Place hands slightly wider than shoulders, form a rigid straight plank from head to heels.',
                tip2: 'Lower body until chest is an inch off the floor, elbows tucked at 45 degrees.',
                tip3: 'Press back up until arms extend fully without arching your lower back.',
                speech: 'Push ups: Keep your core tight like a rigid board, tuck your elbows at forty five degrees, and push the floor away!',
                glowColor: '#ff8800'
            },
            {
                id: 'deadlift',
                name: 'Barbell Conventional Deadlift',
                category: 'back',
                icon: '⚡',
                muscles: ['Glutes', 'Hamstrings', 'Erector Spinae', 'Latissimus'],
                tip1: 'Stand with mid-foot under the bar, bend and take a shoulder-width double overhand or mixed grip.',
                tip2: 'Pull chest up, flatten lower back, engage lats, and drive through feet to stand up straight.',
                tip3: 'Lower the bar by hinging at the hips first, maintaining a rigid spine.',
                speech: 'Deadlift: Keep the barbell tight against your shins, engage your lats, and drive through your heels to stand up strong!',
                glowColor: '#8b5cf6'
            },
            {
                id: 'lat_pulldown',
                name: 'Wide-Grip Lat Pulldown',
                category: 'back',
                icon: '🏋️‍♂️',
                muscles: ['Latissimus Dorsi', 'Rhomboids', 'Biceps'],
                tip1: 'Grip the bar wide, lock thighs securely under pads, pull chest slightly upward.',
                tip2: 'Pull bar down towards upper chest by driving elbows straight down and back.',
                tip3: 'Extend arms back up under resistance for a full lat stretch.',
                speech: 'Lat pulldown: Lean back slightly, drive your elbows straight down to your upper chest, and squeeze your back muscles.',
                glowColor: '#a855f7'
            },
            {
                id: 'squats',
                name: 'Barbell Back Squat',
                category: 'legs',
                icon: '🦵',
                muscles: ['Quadriceps', 'Gluteus Maximus', 'Core'],
                tip1: 'Rest bar on upper traps, feet shoulder-width apart with toes turned slightly outward.',
                tip2: 'Hinge hips back and bend knees to lower until thighs are parallel to floor or lower.',
                tip3: 'Push through heels and mid-foot to explode back up to starting position.',
                speech: 'Barbell squat: Keep your chest upright, descend until your thighs break parallel, and push through your feet to stand!',
                glowColor: '#eab308'
            },
            {
                id: 'overhead_press',
                name: 'Standing Shoulder Press',
                category: 'shoulders',
                icon: '💥',
                muscles: ['Anterior & Lateral Deltoids', 'Triceps', 'Upper Traps'],
                tip1: 'Hold dumbbells or bar at shoulder height with elbows directly under wrists.',
                tip2: 'Press weights directly overhead until arms are fully locked out overhead.',
                tip3: 'Lower weights smoothly back to collarbone level with tight core.',
                speech: 'Shoulder press: Squeeze your glutes and core tight, press straight overhead, and lower controlled to shoulder height.',
                glowColor: '#10b981'
            },
            {
                id: 'bicep_curl',
                name: 'Standing Dumbbell Bicep Curls',
                category: 'arms',
                icon: '💪',
                muscles: ['Biceps Brachii', 'Brachialis'],
                tip1: 'Stand tall with dumbbells at sides, palms facing forward, elbows locked close to torso.',
                tip2: 'Curl weights upward toward shoulders while contracting biceps tightly at peak.',
                tip3: 'Slowly lower weights down under full muscular control.',
                speech: 'Bicep curls: Pin your elbows to your sides, avoid swinging your hips, and squeeze hard at the top of the curl!',
                glowColor: '#3b82f6'
            },
            {
                id: 'plank',
                name: 'Isometric Core Plank',
                category: 'core',
                icon: '🛡️',
                muscles: ['Rectus Abdominis', 'Transverse Abdominis', 'Obliques'],
                tip1: 'Rest on forearms and toes, elbows positioned directly underneath shoulders.',
                tip2: 'Maintain straight line from shoulders to ankles with glutes and abs flexed.',
                tip3: 'Hold position steadily without sagging hips or lifting glutes in air.',
                speech: 'Plank: Engage your core and glutes tightly, maintain a flat spine, and breathe steadily!',
                glowColor: '#ec4899'
            }
        ];

        let currentGender = <?php echo json_encode($user_gender); ?>;
        let activeEx = EXERCISES[0];
        let isAnimating = true;
        let showGlow = true;

        function setCoachGender(gender) {
            currentGender = gender;
            document.getElementById('btn-gender-male').classList.toggle('active', gender === 'Male');
            document.getElementById('btn-gender-male').classList.toggle('male', gender === 'Male');
            document.getElementById('btn-gender-female').classList.toggle('active', gender === 'Female');
            document.getElementById('btn-gender-female').classList.toggle('female', gender === 'Female');

            document.getElementById('active-model-badge').textContent = (gender === 'Male') ? '👨 REALISTIC MALE ATHLETE DEMO' : '👩 REALISTIC FEMALE ATHLETE DEMO';
        }

        function renderExerciseList(filter = 'all') {
            const container = document.getElementById('exercise-list');
            container.innerHTML = '';
            
            const filtered = filter === 'all' ? EXERCISES : EXERCISES.filter(e => e.category === filter);
            document.getElementById('ex-count').textContent = `${filtered.length} Exercises`;

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
            document.querySelectorAll('.ex-card').forEach(c => c.classList.remove('active'));
            if (cardEl) cardEl.classList.add('active');

            document.getElementById('active-cat').textContent = `${ex.category.toUpperCase()} WORKOUT`;
            document.getElementById('active-ex-name').textContent = ex.name;

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
                alert("AI Audio Coach: " + activeEx.speech);
            }
        }

        function startRepTimer() {
            let count = 12;
            const display = document.getElementById('rep-display');
            const interval = setInterval(() => {
                count--;
                if (count >= 0) {
                    display.textContent = `${count} REPS`;
                } else {
                    clearInterval(interval);
                    display.textContent = `SET COMPLETE! 🎉`;
                }
            }, 1000);
        }

        function toggleAnimation() {
            isAnimating = !isAnimating;
            document.getElementById('btn-play').classList.toggle('active', isAnimating);
        }

        function toggleGlow() {
            showGlow = !showGlow;
        }

        function toggleViewMode() {
            window.viewModeFront = !window.viewModeFront;
        }

        // -------------------------------------------------------------
        // HIGH-DEFINITION REALISTIC ANATOMICAL HUMAN RENDERER
        // -------------------------------------------------------------
        const canvas = document.getElementById('humanCanvas');
        const ctx = canvas.getContext('2d');
        let animTick = 0;

        function drawRealisticHuman() {
            requestAnimationFrame(drawRealisticHuman);

            ctx.clearRect(0, 0, canvas.width, canvas.height);

            const isFemale = (currentGender === 'Female');
            const skinTone = isFemale ? '#e0a98b' : '#c68a5c';
            const shadowSkin = isFemale ? '#b88266' : '#9e643a';
            const outfitColor = isFemale ? '#ec4899' : '#1e293b';
            const glowColor = activeEx ? activeEx.glowColor : '#ff6b00';

            if (isAnimating) {
                animTick += 0.04;
            }

            const sinVal = Math.sin(animTick);
            const posSin = (sinVal + 1) / 2;

            const cx = canvas.width / 2;
            const cy = canvas.height / 2;

            // Draw Gym Floor Shadow & Grid
            ctx.save();
            ctx.fillStyle = 'rgba(255, 107, 0, 0.04)';
            ctx.beginPath();
            ctx.ellipse(cx, cy + 220, 200, 40, 0, 0, Math.PI * 2);
            ctx.fill();
            ctx.restore();

            ctx.save();

            if (activeEx.category === 'chest') {
                if (activeEx.id === 'bench_press') {
                    // -------------------------------------------------
                    // REALISTIC BENCH PRESS DEMO (Horizontal Bench)
                    // -------------------------------------------------
                    // Bench Frame
                    ctx.fillStyle = '#334155';
                    ctx.fillRect(cx - 160, cy + 30, 320, 25);
                    ctx.fillRect(cx - 140, cy + 55, 20, 100);
                    ctx.fillRect(cx + 120, cy + 55, 20, 100);

                    // Human Torso Lying Flat
                    ctx.fillStyle = outfitColor;
                    ctx.beginPath();
                    ctx.roundRect(cx - 120, cy - 10, 240, 45, 15);
                    ctx.fill();

                    // Pectoral Muscle Glow Overlay
                    if (showGlow) {
                        ctx.shadowColor = glowColor;
                        ctx.shadowBlur = 25;
                        ctx.fillStyle = glowColor;
                        ctx.beginPath();
                        ctx.roundRect(cx - 40, cy - 8, 80, 25, 10);
                        ctx.fill();
                        ctx.shadowBlur = 0;
                    }

                    // Head
                    ctx.fillStyle = skinTone;
                    ctx.beginPath();
                    ctx.arc(cx - 140, cy + 10, 22, 0, Math.PI * 2);
                    ctx.fill();

                    // Arms Holding Barbell
                    const barY = cy - 70 - sinVal * 45;
                    ctx.strokeStyle = skinTone;
                    ctx.lineWidth = isFemale ? 14 : 18;
                    ctx.lineCap = 'round';

                    // Left & Right Arm Drives
                    ctx.beginPath();
                    ctx.moveTo(cx - 70, cy);
                    ctx.lineTo(cx - 90, barY + 15);
                    ctx.stroke();

                    ctx.beginPath();
                    ctx.moveTo(cx + 70, cy);
                    ctx.lineTo(cx + 90, barY + 15);
                    ctx.stroke();

                    // Olympic Barbell & Weight Plates
                    ctx.strokeStyle = '#e2e8f0';
                    ctx.lineWidth = 8;
                    ctx.beginPath();
                    ctx.moveTo(cx - 180, barY);
                    ctx.lineTo(cx + 180, barY);
                    ctx.stroke();

                    ctx.fillStyle = '#0f172a';
                    ctx.fillRect(cx - 175, barY - 35, 16, 70);
                    ctx.fillRect(cx + 159, barY - 35, 16, 70);

                } else {
                    // Pushups
                    const pushY = cy + sinVal * 30;
                    // Torso
                    ctx.fillStyle = outfitColor;
                    ctx.beginPath();
                    ctx.roundRect(cx - 100, pushY, 200, 50, 15);
                    ctx.fill();

                    // Chest Glow
                    if (showGlow) {
                        ctx.shadowColor = glowColor;
                        ctx.shadowBlur = 20;
                        ctx.fillStyle = glowColor;
                        ctx.beginPath();
                        ctx.arc(cx - 30, pushY + 25, 25, 0, Math.PI * 2);
                        ctx.fill();
                        ctx.shadowBlur = 0;
                    }

                    // Head
                    ctx.fillStyle = skinTone;
                    ctx.beginPath();
                    ctx.arc(cx - 125, pushY + 25, 22, 0, Math.PI * 2);
                    ctx.fill();
                }
            } else if (activeEx.category === 'legs') {
                // -------------------------------------------------
                // REALISTIC SQUATS DEMO (Standing / Squatting)
                // -------------------------------------------------
                const squatY = cy - 40 + posSin * 60;

                // Torso & Upper Body
                ctx.fillStyle = skinTone;
                ctx.beginPath();
                ctx.roundRect(cx - 45, squatY - 110, 90, 110, 16);
                ctx.fill();

                // Quad / Leg Muscles Glow
                ctx.fillStyle = outfitColor;
                ctx.beginPath();
                ctx.roundRect(cx - 40, squatY, 35, 120, 12);
                ctx.roundRect(cx + 5, squatY, 35, 120, 12);
                ctx.fill();

                if (showGlow) {
                    ctx.shadowColor = glowColor;
                    ctx.shadowBlur = 25;
                    ctx.fillStyle = glowColor;
                    ctx.beginPath();
                    ctx.roundRect(cx - 38, squatY + 10, 31, 60, 10);
                    ctx.roundRect(cx + 7, squatY + 10, 31, 60, 10);
                    ctx.fill();
                    ctx.shadowBlur = 0;
                }

                // Head
                ctx.fillStyle = skinTone;
                ctx.beginPath();
                ctx.arc(cx, squatY - 140, 24, 0, Math.PI * 2);
                ctx.fill();

                // Barbell on Traps
                ctx.strokeStyle = '#e2e8f0';
                ctx.lineWidth = 10;
                ctx.beginPath();
                ctx.moveTo(cx - 150, squatY - 115);
                ctx.lineTo(cx + 150, squatY - 115);
                ctx.stroke();

                ctx.fillStyle = '#0f172a';
                ctx.fillRect(cx - 150, squatY - 145, 16, 60);
                ctx.fillRect(cx + 134, squatY - 145, 16, 60);

            } else if (activeEx.category === 'arms') {
                // -------------------------------------------------
                // REALISTIC BICEP CURLS DEMO
                // -------------------------------------------------
                // Standing Body
                ctx.fillStyle = skinTone;
                ctx.beginPath();
                ctx.roundRect(cx - 50, cy - 120, 100, 130, 20);
                ctx.fill();

                // Shorts
                ctx.fillStyle = outfitColor;
                ctx.fillRect(cx - 45, cy + 10, 90, 70);

                // Head
                ctx.beginPath();
                ctx.arc(cx, cy - 150, 25, 0, Math.PI * 2);
                ctx.fill();

                // Bicep Arms Curling
                const curlAngle = posSin * Math.PI * 0.7;
                
                // Left & Right Arms
                ctx.strokeStyle = skinTone;
                ctx.lineWidth = isFemale ? 16 : 20;
                ctx.lineCap = 'round';

                const handY = cy - 20 - posSin * 60;
                ctx.beginPath();
                ctx.moveTo(cx - 60, cy - 90);
                ctx.lineTo(cx - 70, handY);
                ctx.stroke();

                ctx.beginPath();
                ctx.moveTo(cx + 60, cy - 90);
                ctx.lineTo(cx + 70, handY);
                ctx.stroke();

                // Glowing Bicep Muscles
                if (showGlow) {
                    ctx.shadowColor = glowColor;
                    ctx.shadowBlur = 25;
                    ctx.fillStyle = glowColor;
                    ctx.beginPath();
                    ctx.arc(cx - 65, handY + 15, 18, 0, Math.PI * 2);
                    ctx.arc(cx + 65, handY + 15, 18, 0, Math.PI * 2);
                    ctx.fill();
                    ctx.shadowBlur = 0;
                }

                // Dumbbells
                ctx.fillStyle = '#0f172a';
                ctx.fillRect(cx - 95, handY - 15, 45, 16);
                ctx.fillRect(cx + 50, handY - 15, 45, 16);

            } else {
                // -------------------------------------------------
                // GENERAL STANDING HUMAN ATHLETE (Shoulders, Back, Core)
                // -------------------------------------------------
                // Torso
                ctx.fillStyle = skinTone;
                ctx.beginPath();
                ctx.roundRect(cx - 50, cy - 100, 100, 130, 20);
                ctx.fill();

                // Outfit
                ctx.fillStyle = outfitColor;
                ctx.fillRect(cx - 45, cy + 30, 90, 80);

                // Target Muscle Glow Overlay
                if (showGlow) {
                    ctx.shadowColor = glowColor;
                    ctx.shadowBlur = 25;
                    ctx.fillStyle = glowColor;
                    ctx.beginPath();
                    ctx.roundRect(cx - 40, cy - 90, 80, 60, 15);
                    ctx.fill();
                    ctx.shadowBlur = 0;
                }

                // Head
                ctx.fillStyle = skinTone;
                ctx.beginPath();
                ctx.arc(cx, cy - 135, 25, 0, Math.PI * 2);
                ctx.fill();

                // Overhead Barbell / Dumbbell Press Motion
                const pressY = cy - 120 - sinVal * 40;
                ctx.strokeStyle = '#e2e8f0';
                ctx.lineWidth = 8;
                ctx.beginPath();
                ctx.moveTo(cx - 120, pressY);
                ctx.lineTo(cx + 120, pressY);
                ctx.stroke();
            }

            ctx.restore();
        }

        // Initialize UI & High-Def Renderer
        window.addEventListener('DOMContentLoaded', () => {
            renderExerciseList('all');
            selectExercise(EXERCISES[0], null);
            setCoachGender(currentGender);
            drawRealisticHuman();
        });
    </script>
</body>
</html>
