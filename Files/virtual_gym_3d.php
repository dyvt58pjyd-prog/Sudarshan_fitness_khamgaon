<?php
require_once __DIR__ . '/include/db_conn.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$gym = get_gym_details($con);
$member_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Member';
$user_gender = 'Male';
$user_weight = 70;
$user_height = 170;

if (isset($_SESSION['user_data'])) {
    $uid_esc = mysqli_real_escape_string($con, $_SESSION['user_data']);
    $qu = mysqli_query($con, "SELECT username, gender, height, weight FROM users WHERE userid='$uid_esc'");
    if ($qu && mysqli_num_rows($qu) > 0) {
        $row_u = mysqli_fetch_assoc($qu);
        if (!empty($row_u['username'])) $member_name = $row_u['username'];
        if (!empty($row_u['gender']) && strtolower($row_u['gender']) === 'female') $user_gender = 'Female';
        if (!empty($row_u['weight'])) $user_weight = floatval($row_u['weight']);
        if (!empty($row_u['height'])) $user_height = floatval($row_u['height']);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Interactive 3D Virtual Gym Simulator | <?php echo htmlspecialchars($gym['gym_name']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <!-- Three.js 3D WebGL Library -->
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
            grid-template-columns: 320px 1fr 340px;
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

        /* Panel Cards */
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

        /* Zone Selector Buttons */
        .zone-card {
            background: rgba(30, 41, 59, 0.5);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 14px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .zone-card:hover, .zone-card.active {
            background: rgba(255, 107, 0, 0.15);
            border-color: var(--accent);
            transform: translateX(4px);
        }

        .zone-icon {
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
        }

        /* 3D Viewport Stage */
        .viewport-container {
            background: radial-gradient(circle at 50% 40%, #1e293b 0%, #070a12 100%);
            border: 2px solid rgba(255, 107, 0, 0.3);
            border-radius: 28px;
            position: relative;
            min-height: 600px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 30px 60px rgba(0,0,0,0.8);
        }

        #webgl-canvas {
            width: 100%;
            height: 100%;
            display: block;
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

        /* AI Dialogue Box (from virtual-gym repository) */
        .dialogue-box {
            background: rgba(15, 23, 42, 0.95);
            border: 2px solid var(--accent-blue);
            border-radius: 20px;
            padding: 16px;
            margin-top: 15px;
            box-shadow: 0 10px 25px rgba(59,130,246,0.3);
        }

        .dialogue-speaker {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
        }

        .speaker-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: var(--accent-blue);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .dialogue-text {
            font-size: 13px;
            color: #cbd5e1;
            line-height: 1.5;
        }

        .btn-store-link {
            width: 100%;
            background: linear-gradient(135deg, #10b981, #059669);
            color: #fff;
            border: none;
            padding: 14px;
            border-radius: 14px;
            font-size: 14px;
            font-weight: 800;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 8px 20px rgba(16,185,129,0.4);
            margin-top: 15px;
            transition: all 0.2s ease;
        }

        .btn-store-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(16,185,129,0.6);
        }
    </style>
</head>
<body>

    <header>
        <div class="gym-brand">
            <img src="<?php echo htmlspecialchars($gym['gym_logo']); ?>" class="gym-logo" alt="Gym Logo">
            <div>
                <div class="page-title">🎮 3D VIRTUAL GYM ENVIRONMENT</div>
                <div style="font-size: 11px; color: var(--text-muted);">Sudarshan Fitness Virtual Floor &amp; Dialogue Simulator</div>
            </div>
        </div>

        <a href="javascript:history.back()" class="btn-back">← Back to Dashboard</a>
    </header>

    <div class="app-layout">
        
        <!-- Left Panel: Virtual Gym Floor Zones -->
        <div class="panel">
            <div class="panel-title">
                <span>📍 Gym Floor Zones</span>
            </div>

            <div class="zone-card active" onclick="switchGymZone('weights', this)">
                <div class="zone-icon">🏋️</div>
                <div>
                    <h4 style="color:#fff; font-size:14px; font-weight:800;">Free Weights Zone</h4>
                    <p style="color:var(--text-muted); font-size:11px;">Dumbbells, Barbells &amp; Benches</p>
                </div>
            </div>

            <div class="zone-card" onclick="switchGymZone('cardio', this)">
                <div class="zone-icon">🏃</div>
                <div>
                    <h4 style="color:#fff; font-size:14px; font-weight:800;">Cardio Zone</h4>
                    <p style="color:var(--text-muted); font-size:11px;">Treadmills &amp; Ellipticals</p>
                </div>
            </div>

            <div class="zone-card" onclick="switchGymZone('machines', this)">
                <div class="zone-icon">🦵</div>
                <div>
                    <h4 style="color:#fff; font-size:14px; font-weight:800;">Heavy Machine Zone</h4>
                    <p style="color:var(--text-muted); font-size:11px;">Leg Press &amp; Cable Crossover</p>
                </div>
            </div>

            <div class="zone-card" onclick="switchGymZone('functional', this)">
                <div class="zone-icon">🧘</div>
                <div>
                    <h4 style="color:#fff; font-size:14px; font-weight:800;">Functional &amp; Core Floor</h4>
                    <p style="color:var(--text-muted); font-size:11px;">Mats, Kettlebells &amp; Plyo Boxes</p>
                </div>
            </div>

            <div class="zone-card" onclick="switchGymZone('shop', this)">
                <div class="zone-icon">🥤</div>
                <div>
                    <h4 style="color:#fff; font-size:14px; font-weight:800;">Supplement &amp; Nutrition Bar</h4>
                    <p style="color:var(--text-muted); font-size:11px;">Protein Shakes &amp; Gear</p>
                </div>
            </div>

            <a href="virtual_store.php" class="btn-store-link">
                🛒 Open Virtual Gym Supplement Shop ➔
            </a>
        </div>

        <!-- Middle Panel: 3D Interactive WebGL Stage Simulator -->
        <div class="viewport-container">
            <div class="vp-overlay-top">
                <div class="vp-title-box">
                    <span id="zone-subtitle">ZONE 1 • FREE WEIGHTS AREA</span>
                    <h3 id="zone-title">Dumbbells &amp; Olympic Barbell Floor</h3>
                </div>
            </div>

            <canvas id="webgl-canvas"></canvas>
        </div>

        <!-- Right Panel: Virtual Trainer & Nutritionist Dialogue System -->
        <div class="panel">
            <div class="panel-title">
                <span>🤖 Virtual AI Trainer Dialogue</span>
            </div>

            <!-- Interactive Dialogue Box -->
            <div class="dialogue-box">
                <div class="dialogue-speaker">
                    <div class="speaker-avatar">👨‍🏋️</div>
                    <div>
                        <strong style="color:#fff; font-size:13px;">Coach Anurag (Virtual Trainer)</strong>
                        <div style="font-size:10px; color:#60a5fa;">Sudarshan Fitness AI Assistant</div>
                    </div>
                </div>
                <div class="dialogue-text" id="dialogue-content">
                    "Welcome <strong><?php echo htmlspecialchars($member_name); ?></strong>! You are currently exploring the <strong>Free Weights Zone</strong>. Would you like a chest workout recommendation or nutrition advice?"
                </div>
            </div>

            <!-- Dialogue Interaction Options -->
            <div style="display:flex; flex-direction:column; gap:8px; margin-top:15px;">
                <button onclick="askCoach('chest')" style="background:rgba(255,107,0,0.15); border:1px solid #ff6b00; color:#ff6b00; padding:10px; border-radius:12px; font-weight:800; font-size:12px; cursor:pointer; text-align:left;">
                    💪 "Give me a heavy chest workout plan"
                </button>
                <button onclick="askCoach('fatloss')" style="background:rgba(16,185,129,0.15); border:1px solid #10b981; color:#10b981; padding:10px; border-radius:12px; font-weight:800; font-size:12px; cursor:pointer; text-align:left;">
                    🔥 "How to burn fat effectively in Cardio Zone?"
                </button>
                <button onclick="askCoach('protein')" style="background:rgba(139,92,246,0.15); border:1px solid #8b5cf6; color:#a78bfa; padding:10px; border-radius:12px; font-weight:800; font-size:12px; cursor:pointer; text-align:left;">
                    🥤 "Which supplements should I take post-workout?"
                </button>
            </div>
        </div>

    </div>

    <script>
        // -------------------------------------------------------------
        // THREE.JS 3D VIRTUAL GYM ENVIRONMENT (Inspired by virtual-gym repo)
        // -------------------------------------------------------------
        let scene, camera, renderer, controls;
        let gymFloor, dumbbellRack, treadmillMesh, machineMesh;

        function init3DVirtualGym() {
            const container = document.querySelector('.viewport-container');
            const width = container.clientWidth;
            const height = container.clientHeight;

            scene = new THREE.Scene();
            scene.fog = new THREE.FogExp2(0x070a12, 0.03);

            camera = new THREE.PerspectiveCamera(45, width / height, 0.1, 1000);
            camera.position.set(0, 3.5, 7.5);

            renderer = new THREE.WebGLRenderer({ canvas: document.getElementById('webgl-canvas'), antialias: true });
            renderer.setSize(width, height);
            renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));

            // Ambient & Spotlight
            const ambient = new THREE.AmbientLight(0xffffff, 0.7);
            scene.add(ambient);

            const spot1 = new THREE.SpotLight(0xff6b00, 2);
            spot1.position.set(0, 10, 5);
            scene.add(spot1);

            const spot2 = new THREE.SpotLight(0x38bdf8, 1.5);
            spot2.position.set(-6, 8, -5);
            scene.add(spot2);

            // Metallic Gym Floor
            const floorGeo = new THREE.PlaneGeometry(30, 30);
            const floorMat = new THREE.MeshStandardMaterial({ color: 0x0f172a, roughness: 0.2, metalness: 0.5 });
            gymFloor = new THREE.Mesh(floorGeo, floorMat);
            gymFloor.rotation.x = -Math.PI / 2;
            scene.add(gymFloor);

            // Grid Lines
            const grid = new THREE.GridHelper(30, 30, 0xff6b00, 0x334155);
            scene.add(grid);

            // Create Gym Equipment Props
            buildGymEquipment();

            // Orbit Controls
            controls = new THREE.OrbitControls(camera, renderer.domElement);
            controls.enableDamping = true;
            controls.dampingFactor = 0.05;

            window.addEventListener('resize', onWindowResize);
            animate();
        }

        function buildGymEquipment() {
            // Dumbbell Rack
            const rackGeo = new THREE.BoxGeometry(3, 1, 0.6);
            const rackMat = new THREE.MeshStandardMaterial({ color: 0xff6b00, roughness: 0.3 });
            dumbbellRack = new THREE.Mesh(rackGeo, rackMat);
            dumbbellRack.position.set(-3, 0.5, -2);
            scene.add(dumbbellRack);

            // Treadmill
            const tmGeo = new THREE.BoxGeometry(1.2, 0.3, 2.5);
            const tmMat = new THREE.MeshStandardMaterial({ color: 0x38bdf8, roughness: 0.4 });
            treadmillMesh = new THREE.Mesh(tmGeo, tmMat);
            treadmillMesh.position.set(3, 0.15, -2);
            scene.add(treadmillMesh);

            // Heavy Leg Press Machine
            const mGeo = new THREE.BoxGeometry(2, 2.2, 2);
            const mMat = new THREE.MeshStandardMaterial({ color: 0x8b5cf6, roughness: 0.4 });
            machineMesh = new THREE.Mesh(mGeo, mMat);
            machineMesh.position.set(0, 1.1, -5);
            scene.add(machineMesh);
        }

        function switchGymZone(zone, cardEl) {
            document.querySelectorAll('.zone-card').forEach(c => c.classList.remove('active'));
            if (cardEl) cardEl.classList.add('active');

            const title = document.getElementById('zone-title');
            const sub = document.getElementById('zone-subtitle');
            const dialogue = document.getElementById('dialogue-content');

            if (zone === 'weights') {
                sub.textContent = 'ZONE 1 • FREE WEIGHTS AREA';
                title.textContent = 'Dumbbells & Olympic Barbell Floor';
                camera.position.set(-3, 2.5, 2.5);
                controls.target.set(-3, 0.5, -2);
                dialogue.innerHTML = '"You are in the <strong>Free Weights Zone</strong>! Focus on compound movements like Bench Press and Dumbbell Shoulder Press for strength."';
            } else if (zone === 'cardio') {
                sub.textContent = 'ZONE 2 • CARDIO & HIIT';
                title.textContent = 'Treadmills & Elliptical Runners';
                camera.position.set(3, 2.5, 2.5);
                controls.target.set(3, 0.15, -2);
                dialogue.innerHTML = '"Welcome to the <strong>Cardio Zone</strong>! Maintain your heart rate at 65-75% max HR for optimal fat burning."';
            } else if (zone === 'machines') {
                sub.textContent = 'ZONE 3 • HEAVY MACHINES';
                title.textContent = 'Leg Press & Cable Crossover Towers';
                camera.position.set(0, 3, -1);
                controls.target.set(0, 1.1, -5);
                dialogue.innerHTML = '"Here in the <strong>Machine Zone</strong>, isolate muscle groups safely with controlled resistance."';
            } else if (zone === 'functional') {
                sub.textContent = 'ZONE 4 • FUNCTIONAL CORE';
                title.textContent = 'Ab Mats & Core Functional Area';
                camera.position.set(0, 4, 3);
                controls.target.set(0, 0, 0);
                dialogue.innerHTML = '"Core & Functional Floor! Planks and leg raises here will build rock-solid core stability."';
            } else if (zone === 'shop') {
                sub.textContent = 'ZONE 5 • SUPPLEMENT BAR';
                title.textContent = 'Sudarshan Fitness Nutrition Bar';
                camera.position.set(0, 2, 5);
                dialogue.innerHTML = '"Welcome to the <strong>Supplement Bar</strong>! Grab your Whey Protein, Creatine, or Pre-workout for peak recovery."';
            }
        }

        function askCoach(topic) {
            const dialogue = document.getElementById('dialogue-content');
            if (topic === 'chest') {
                dialogue.innerHTML = '"For chest hypertrophy: 4 Sets of Flat Bench Press (8-10 reps) + 3 Sets of Incline Dumbbell Press (12 reps). Maintain strict control on the downward motion!"';
            } else if (topic === 'fatloss') {
                dialogue.innerHTML = '"For rapid fat loss: 20 Minutes of Treadmill HIIT intervals (30s sprint, 30s walk) twice a week, paired with a 300 kcal calorie deficit."';
            } else if (topic === 'protein') {
                dialogue.innerHTML = '"Take 1 Scoop of Whey Protein Isolate with water within 30 minutes after your workout, plus 5g Creatine Monohydrate daily for strength!"';
            }
        }

        function animate() {
            requestAnimationFrame(animate);
            if (dumbbellRack) dumbbellRack.rotation.y += 0.005;
            if (treadmillMesh) treadmillMesh.rotation.y += 0.005;
            controls.update();
            renderer.render(scene, camera);
        }

        function onWindowResize() {
            const container = document.querySelector('.viewport-container');
            if (!container) return;
            camera.aspect = container.clientWidth / container.clientHeight;
            camera.updateProjectionMatrix();
            renderer.setSize(container.clientWidth, container.clientHeight);
        }

        window.addEventListener('DOMContentLoaded', () => {
            init3DVirtualGym();
        });
    </script>
</body>
</html>
