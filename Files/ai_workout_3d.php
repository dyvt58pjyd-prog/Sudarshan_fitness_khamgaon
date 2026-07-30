<?php
require_once __DIR__ . '/include/db_conn.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$gym = get_gym_details($con);
$user_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Member';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>3D AI Virtual Coach &amp; Workout Guide | <?php echo htmlspecialchars($gym['gym_name']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <!-- Three.js for 3D Graphics -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/controls/OrbitControls.js"></script>
    <style>
        :root {
            --bg-dark: #070a12;
            --card-bg: rgba(15, 23, 42, 0.85);
            --accent: #ff6b00;
            --accent-green: #10b981;
            --accent-blue: #3b82f6;
            --accent-purple: #8b5cf6;
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
            max-width: 1700px;
            margin: 0 auto;
            width: 100%;
        }

        @media (max-width: 1200px) {
            .app-layout {
                grid-template-columns: 1fr;
            }
        }

        /* Sidebar Panels */
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

        /* Category Tabs */
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

        /* Exercise List */
        .ex-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
            overflow-y: auto;
            max-height: 520px;
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

        /* 3D Viewport Main Stage */
        .viewport-container {
            background: radial-gradient(circle at 50% 50%, #1e293b 0%, #070a12 100%);
            border: 2px solid rgba(255, 107, 0, 0.3);
            border-radius: 28px;
            position: relative;
            min-height: 550px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 30px 60px rgba(0,0,0,0.8);
        }

        #webgl-canvas {
            width: 100%;
            height: 100%;
            display: block;
            flex: 1;
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
        }

        /* Form Tips Side Details */
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

        /* Rep Counter Overlay */
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
                <div class="page-title">🤖 3D AI Workout Coach</div>
                <div style="font-size: 11px; color: var(--text-muted);">Interactive Anatomical Gym Execution Guide</div>
            </div>
        </div>
        <a href="javascript:history.back()" class="btn-back">← Back to Dashboard</a>
    </header>

    <div class="app-layout">
        
        <!-- Left Panel: Exercise Selector -->
        <div class="panel">
            <div class="panel-title">
                <span>🏋️ Select Exercise</span>
                <span style="font-size: 11px; color: var(--accent);" id="ex-count">25 Available</span>
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
                <!-- Dynamically Populated -->
            </div>
        </div>

        <!-- Middle Panel: Interactive 3D WebGL Canvas Stage -->
        <div class="viewport-container">
            <div class="vp-overlay-top">
                <div class="vp-title-box">
                    <span id="active-cat">CHEST WORKOUT</span>
                    <h3 id="active-ex-name">Barbell Bench Press</h3>
                </div>
                <button class="voice-btn" onclick="speakFormInstruction()">
                    🔊 Audio AI Coach
                </button>
            </div>

            <canvas id="webgl-canvas"></canvas>

            <!-- 3D Controls Bar -->
            <div class="vp-controls-bar">
                <button class="ctrl-btn active" id="btn-play" onclick="toggleAnimation()" title="Play / Pause 3D Animation">⏯️</button>
                <button class="ctrl-btn" onclick="reset3DCamera()" title="Reset 3D Camera Angle">🎥</button>
                <button class="ctrl-btn" onclick="toggleMuscleHighlight()" title="Toggle Muscle Highlight Glow">🔥</button>
                <button class="ctrl-btn" onclick="speed3DMotion()" title="Speed Up Motion">⚡</button>
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
                    <span class="muscle-tag">Anterior Deltoids</span>
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
        // EXERCISE DATABASE WITH ANATOMICAL DETAILS & INSTRUCTIONS
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
                modelColor: 0xff6b00
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
                modelColor: 0xff8800
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
                modelColor: 0x8b5cf6
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
                modelColor: 0xa855f7
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
                modelColor: 0xeab308
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
                modelColor: 0x10b981
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
                modelColor: 0x3b82f6
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
                modelColor: 0xec4899
            }
        ];

        let activeEx = EXERCISES[0];
        let isAnimating = true;

        // Populate exercise list UI
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

            // Target muscles
            const mContainer = document.getElementById('target-muscles-container');
            mContainer.innerHTML = ex.muscles.map(m => `<span class="muscle-tag">${m}</span>`).join('');

            // Tips
            document.getElementById('tip-1').textContent = ex.tip1;
            document.getElementById('tip-2').textContent = ex.tip2;
            document.getElementById('tip-3').textContent = ex.tip3;

            // Update 3D mannequin highlight color
            update3DMannequinColor(ex.modelColor);
        }

        // Voice Coach
        function speakFormInstruction() {
            if ('speechSynthesis' in window) {
                window.speechSynthesis.cancel();
                const msg = new SpeechSynthesisUtterance(activeEx.speech);
                msg.rate = 0.95;
                msg.pitch = 1.0;
                window.speechSynthesis.speak(msg);
            } else {
                alert("AI Audio Coach: " + activeEx.speech);
            }
        }

        // Timer
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

        // -------------------------------------------------------------
        // THREE.JS REAL-TIME 3D GRAPHICS & HUMAN ANATOMICAL ANIMATION
        // -------------------------------------------------------------
        let scene, camera, renderer, controls;
        let humanGroup, torsoMesh, headMesh, leftArm, rightArm, leftLeg, rightLeg, barbellMesh;
        let animClock = 0;

        function init3DStage() {
            const container = document.querySelector('.viewport-container');
            const width = container.clientWidth;
            const height = container.clientHeight;

            scene = new THREE.Scene();
            scene.fog = new THREE.FogExp2(0x070a12, 0.05);

            camera = new THREE.PerspectiveCamera(45, width / height, 0.1, 1000);
            camera.position.set(0, 1.8, 4.5);

            renderer = new THREE.WebGLRenderer({ canvas: document.getElementById('webgl-canvas'), antialias: true, alpha: true });
            renderer.setSize(width, height);
            renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
            renderer.shadowMap.enabled = true;

            // Lights
            const ambientLight = new THREE.AmbientLight(0xffffff, 0.7);
            scene.add(ambientLight);

            const dirLight = new THREE.DirectionalLight(0xff6b00, 1.5);
            dirLight.position.set(5, 10, 7);
            scene.add(dirLight);

            const blueLight = new THREE.PointLight(0x3b82f6, 2, 10);
            blueLight.position.set(-4, 3, -2);
            scene.add(blueLight);

            // Metallic Gym Floor Grid
            const gridHelper = new THREE.GridHelper(20, 20, 0xff6b00, 0x1e293b);
            gridHelper.position.y = -1;
            scene.add(gridHelper);

            // Construct 3D Anatomical Humanoid Model
            createHumanModel();

            // Orbit Controls
            controls = new THREE.OrbitControls(camera, renderer.domElement);
            controls.enableDamping = true;
            controls.dampingFactor = 0.05;
            controls.maxPolarAngle = Math.PI / 2 + 0.1;

            window.addEventListener('resize', onWindowResize);
            animate3D();
        }

        function createHumanModel() {
            humanGroup = new THREE.Group();

            const matBody = new THREE.MeshStandardMaterial({
                color: 0x334155,
                roughness: 0.3,
                metalness: 0.5,
                wireframe: false
            });

            const matMuscle = new THREE.MeshStandardMaterial({
                color: 0xff6b00,
                emissive: 0xff6b00,
                emissiveIntensity: 0.5,
                roughness: 0.2
            });

            // Torso / Chest
            const torsoGeo = new THREE.CylinderGeometry(0.35, 0.25, 0.9, 16);
            torsoMesh = new THREE.Mesh(torsoGeo, matMuscle);
            torsoMesh.position.y = 0.45;
            humanGroup.add(torsoMesh);

            // Head
            const headGeo = new THREE.SphereGeometry(0.18, 16, 16);
            headMesh = new THREE.Mesh(headGeo, matBody);
            headMesh.position.y = 1.05;
            humanGroup.add(headMesh);

            // Left Arm
            const armGeo = new THREE.CylinderGeometry(0.08, 0.07, 0.7, 12);
            leftArm = new THREE.Mesh(armGeo, matBody);
            leftArm.position.set(-0.45, 0.5, 0);
            humanGroup.add(leftArm);

            // Right Arm
            rightArm = new THREE.Mesh(armGeo, matBody);
            rightArm.position.set(0.45, 0.5, 0);
            humanGroup.add(rightArm);

            // Legs
            const legGeo = new THREE.CylinderGeometry(0.1, 0.08, 0.9, 12);
            leftLeg = new THREE.Mesh(legGeo, matBody);
            leftLeg.position.set(-0.18, -0.45, 0);
            humanGroup.add(leftLeg);

            rightLeg = new THREE.Mesh(legGeo, matBody);
            rightLeg.position.set(0.18, -0.45, 0);
            humanGroup.add(rightLeg);

            // 3D Gym Barbell Prop
            const barGeo = new THREE.CylinderGeometry(0.03, 0.03, 1.8, 12);
            const matBar = new THREE.MeshStandardMaterial({ color: 0x94a3b8, metalness: 0.9, roughness: 0.1 });
            barbellMesh = new THREE.Mesh(barGeo, matBar);
            barbellMesh.rotation.z = Math.PI / 2;
            barbellMesh.position.set(0, 0.7, 0.3);
            humanGroup.add(barbellMesh);

            scene.add(humanGroup);
        }

        function update3DMannequinColor(colorHex) {
            if (torsoMesh && torsoMesh.material) {
                torsoMesh.material.color.setHex(colorHex);
                torsoMesh.material.emissive.setHex(colorHex);
            }
        }

        function toggleAnimation() {
            isAnimating = !isAnimating;
            document.getElementById('btn-play').classList.toggle('active', isAnimating);
        }

        function reset3DCamera() {
            camera.position.set(0, 1.8, 4.5);
            controls.target.set(0, 0, 0);
        }

        function toggleMuscleHighlight() {
            if (torsoMesh) {
                torsoMesh.material.wireframe = !torsoMesh.material.wireframe;
            }
        }

        function animate3D() {
            requestAnimationFrame(animate3D);

            if (isAnimating) {
                animClock += 0.03;
                const sinMotion = Math.sin(animClock) * 0.25;

                if (activeEx.category === 'chest') {
                    // Bench press motion
                    barbellMesh.position.y = 0.5 + sinMotion;
                    leftArm.position.y = 0.4 + sinMotion * 0.5;
                    rightArm.position.y = 0.4 + sinMotion * 0.5;
                } else if (activeEx.category === 'legs') {
                    // Squat motion
                    humanGroup.position.y = sinMotion * 0.8;
                    barbellMesh.position.y = 0.95;
                } else if (activeEx.category === 'arms') {
                    // Bicep curl motion
                    leftArm.rotation.x = Math.abs(Math.sin(animClock)) * 1.2;
                    rightArm.rotation.x = Math.abs(Math.sin(animClock)) * 1.2;
                } else {
                    // General pulsing motion
                    humanGroup.rotation.y = Math.sin(animClock * 0.5) * 0.2;
                }
            }

            controls.update();
            renderer.render(scene, camera);
        }

        function onWindowResize() {
            const container = document.querySelector('.viewport-container');
            if (!container) return;
            const width = container.clientWidth;
            const height = container.clientHeight;
            camera.aspect = width / height;
            camera.updateProjectionMatrix();
            renderer.setSize(width, height);
        }

        // Initialize UI and 3D Scene
        window.addEventListener('DOMContentLoaded', () => {
            renderExerciseList('all');
            selectExercise(EXERCISES[0], null);
            init3DStage();
        });
    </script>
</body>
</html>
