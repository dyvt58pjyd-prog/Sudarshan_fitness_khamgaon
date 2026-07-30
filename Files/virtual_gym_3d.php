<?php
require_once __DIR__ . '/include/db_conn.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$gym = get_gym_details($con);
$member_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Hunter';
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
    <title>[SYSTEM PORTAL] 3D Virtual Gym Simulator | <?php echo htmlspecialchars($gym['gym_name']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;600;700;800;900&family=Outfit:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">
    <!-- Three.js 3D WebGL Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/controls/OrbitControls.js"></script>
    <style>
        :root {
            --bg-dark: #030712;
            --card-bg: rgba(9, 14, 28, 0.9);
            --system-cyan: #00f0ff;
            --system-blue: #0077ff;
            --monarch-purple: #7000ff;
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

        .zone-card {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(0, 240, 255, 0.15);
            border-radius: 14px;
            padding: 14px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .zone-card:hover, .zone-card.active {
            background: rgba(0, 240, 255, 0.15);
            border-color: var(--system-cyan);
            box-shadow: 0 0 20px rgba(0, 240, 255, 0.3);
            transform: translateX(4px);
        }

        .zone-icon {
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
            border: 1px solid var(--system-cyan);
        }

        .viewport-container {
            background: radial-gradient(circle at 50% 40%, #0b1329 0%, #030712 100%);
            border: 2px solid var(--system-cyan);
            border-radius: 28px;
            position: relative;
            min-height: 600px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 0 50px rgba(0, 240, 255, 0.25);
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

        .dialogue-box {
            background: rgba(5, 9, 20, 0.95);
            border: 2px solid var(--system-cyan);
            border-radius: 20px;
            padding: 16px;
            margin-top: 15px;
            box-shadow: 0 0 25px rgba(0,240,255,0.3);
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
            background: var(--system-cyan);
            color: #030712;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: 900;
        }

        .dialogue-text {
            font-size: 13px;
            color: #cbd5e1;
            line-height: 1.5;
        }

        .btn-store-link {
            width: 100%;
            background: linear-gradient(135deg, var(--monarch-purple), #480094);
            color: #fff;
            border: 1px solid var(--monarch-purple);
            padding: 14px;
            border-radius: 14px;
            font-size: 13px;
            font-weight: 900;
            font-family: 'Orbitron', sans-serif;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 0 25px rgba(112,0,255,0.5);
            margin-top: 15px;
            transition: all 0.2s ease;
        }

        .btn-store-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 0 35px rgba(112,0,255,0.8);
        }
    </style>
</head>
<body>

    <header>
        <div class="gym-brand">
            <img src="<?php echo htmlspecialchars($gym['gym_logo']); ?>" class="gym-logo" alt="Gym Logo">
            <div>
                <div class="page-title">[ SYSTEM FLOOR NAVIGATOR ]</div>
                <div style="font-size: 11px; color: var(--system-cyan); font-family: 'Orbitron'; font-weight: 700;">SOLO LEVELING MONARCH ENVIRONMENT</div>
            </div>
        </div>

        <a href="javascript:history.back()" class="btn-back">← RETURN TO BASE</a>
    </header>

    <div class="app-layout">
        
        <!-- Left Panel: Virtual Gym Floor Zones -->
        <div class="panel">
            <div class="panel-title">
                <span>📍 SYSTEM ZONES</span>
            </div>

            <div class="zone-card active" onclick="switchGymZone('weights', this)">
                <div class="zone-icon">🏋️</div>
                <div>
                    <h4 style="color:#fff; font-size:14px; font-weight:800;">Free Weights Zone</h4>
                    <p style="color:var(--text-muted); font-size:11px;">Dumbbells &amp; Monarch Barbells</p>
                </div>
            </div>

            <div class="zone-card" onclick="switchGymZone('cardio', this)">
                <div class="zone-icon">🏃</div>
                <div>
                    <h4 style="color:#fff; font-size:14px; font-weight:800;">Cardio Zone</h4>
                    <p style="color:var(--text-muted); font-size:11px;">High Speed Runners</p>
                </div>
            </div>

            <div class="zone-card" onclick="switchGymZone('machines', this)">
                <div class="zone-icon">🦵</div>
                <div>
                    <h4 style="color:#fff; font-size:14px; font-weight:800;">Heavy Machine Zone</h4>
                    <p style="color:var(--text-muted); font-size:11px;">Leg Press &amp; Cable Towers</p>
                </div>
            </div>

            <div class="zone-card" onclick="switchGymZone('functional', this)">
                <div class="zone-icon">🧘</div>
                <div>
                    <h4 style="color:#fff; font-size:14px; font-weight:800;">Functional Floor</h4>
                    <p style="color:var(--text-muted); font-size:11px;">Mats &amp; Ab Stations</p>
                </div>
            </div>

            <div class="zone-card" onclick="switchGymZone('shop', this)">
                <div class="zone-icon">🥤</div>
                <div>
                    <h4 style="color:#fff; font-size:14px; font-weight:800;">Supplement Bar</h4>
                    <p style="color:var(--text-muted); font-size:11px;">Elixirs &amp; Monarch Gear</p>
                </div>
            </div>

            <a href="virtual_store.php" class="btn-store-link">
                🛒 SYSTEM ITEM SHOP ➔
            </a>
        </div>

        <!-- Middle Panel: 3D Stage Simulator -->
        <div class="viewport-container">
            <div class="vp-overlay-top">
                <div class="vp-title-box">
                    <span id="zone-subtitle">ZONE 1 • FREE WEIGHTS AREA</span>
                    <h3 id="zone-title">Dumbbells &amp; Olympic Barbell Floor</h3>
                </div>
            </div>

            <canvas id="webgl-canvas"></canvas>
        </div>

        <!-- Right Panel: Virtual Trainer Dialogue System -->
        <div class="panel">
            <div class="panel-title">
                <span>🤖 SYSTEM DIALOGUE WINDOW</span>
            </div>

            <!-- Interactive Dialogue Box -->
            <div class="dialogue-box">
                <div class="dialogue-speaker">
                    <div class="speaker-avatar">⚔️</div>
                    <div>
                        <strong style="color:#fff; font-size:13px; font-family:'Orbitron';">System AI Coach</strong>
                        <div style="font-size:10px; color:var(--system-cyan);">Shadow Monarch Guide</div>
                    </div>
                </div>
                <div class="dialogue-text" id="dialogue-content">
                    "Welcome <strong>Hunter <?php echo htmlspecialchars($member_name); ?></strong>! You have entered the <strong>Free Weights Zone</strong>. Select a quest or ask for stat boost guidance!"
                </div>
            </div>

            <div style="display:flex; flex-direction:column; gap:8px; margin-top:15px;">
                <button onclick="askCoach('chest')" style="background:rgba(0,240,255,0.1); border:1px solid var(--system-cyan); color:var(--system-cyan); padding:10px; border-radius:12px; font-weight:800; font-size:11px; cursor:pointer; text-align:left; font-family:'Orbitron';">
                    💪 "System Quest: Chest Hypertrophy Plan"
                </button>
                <button onclick="askCoach('fatloss')" style="background:rgba(112,0,255,0.15); border:1px solid var(--monarch-purple); color:#a78bfa; padding:10px; border-radius:12px; font-weight:800; font-size:11px; cursor:pointer; text-align:left; font-family:'Orbitron';">
                    🔥 "System Quest: Cardio Stamina Protocol"
                </button>
                <button onclick="askCoach('protein')" style="background:rgba(255,183,3,0.15); border:1px solid var(--quest-gold); color:var(--quest-gold); padding:10px; border-radius:12px; font-weight:800; font-size:11px; cursor:pointer; text-align:left; font-family:'Orbitron';">
                    🥤 "Item Shop: Recovery Elixir Recommendations"
                </button>
            </div>
        </div>

    </div>

    <script>
        let scene, camera, renderer, controls;
        let gymFloor, dumbbellRack, treadmillMesh, machineMesh;

        function init3DVirtualGym() {
            const container = document.querySelector('.viewport-container');
            const width = container.clientWidth;
            const height = container.clientHeight;

            scene = new THREE.Scene();
            scene.fog = new THREE.FogExp2(0x030712, 0.03);

            camera = new THREE.PerspectiveCamera(45, width / height, 0.1, 1000);
            camera.position.set(0, 3.5, 7.5);

            renderer = new THREE.WebGLRenderer({ canvas: document.getElementById('webgl-canvas'), antialias: true });
            renderer.setSize(width, height);
            renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));

            const ambient = new THREE.AmbientLight(0xffffff, 0.6);
            scene.add(ambient);

            const spot1 = new THREE.SpotLight(0x00f0ff, 2.5);
            spot1.position.set(0, 10, 5);
            scene.add(spot1);

            const spot2 = new THREE.SpotLight(0x7000ff, 2);
            spot2.position.set(-6, 8, -5);
            scene.add(spot2);

            const floorGeo = new THREE.PlaneGeometry(30, 30);
            const floorMat = new THREE.MeshStandardMaterial({ color: 0x090e1c, roughness: 0.2, metalness: 0.6 });
            gymFloor = new THREE.Mesh(floorGeo, floorMat);
            gymFloor.rotation.x = -Math.PI / 2;
            scene.add(gymFloor);

            const grid = new THREE.GridHelper(30, 30, 0x00f0ff, 0x7000ff);
            scene.add(grid);

            buildGymEquipment();

            controls = new THREE.OrbitControls(camera, renderer.domElement);
            controls.enableDamping = true;
            controls.dampingFactor = 0.05;

            window.addEventListener('resize', onWindowResize);
            animate();
        }

        function buildGymEquipment() {
            const rackGeo = new THREE.BoxGeometry(3, 1, 0.6);
            const rackMat = new THREE.MeshStandardMaterial({ color: 0x00f0ff, roughness: 0.3 });
            dumbbellRack = new THREE.Mesh(rackGeo, rackMat);
            dumbbellRack.position.set(-3, 0.5, -2);
            scene.add(dumbbellRack);

            const tmGeo = new THREE.BoxGeometry(1.2, 0.3, 2.5);
            const tmMat = new THREE.MeshStandardMaterial({ color: 0x7000ff, roughness: 0.4 });
            treadmillMesh = new THREE.Mesh(tmGeo, tmMat);
            treadmillMesh.position.set(3, 0.15, -2);
            scene.add(treadmillMesh);

            const mGeo = new THREE.BoxGeometry(2, 2.2, 2);
            const mMat = new THREE.MeshStandardMaterial({ color: 0xffb703, roughness: 0.4 });
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
                title.textContent = 'Dumbbells & Monarch Barbell Floor';
                camera.position.set(-3, 2.5, 2.5);
                controls.target.set(-3, 0.5, -2);
                dialogue.innerHTML = '"You are in the <strong>Free Weights Zone</strong>! Heavy compound movements increase strength stats."';
            } else if (zone === 'cardio') {
                sub.textContent = 'ZONE 2 • CARDIO & HIIT';
                title.textContent = 'High Speed Treadmills';
                camera.position.set(3, 2.5, 2.5);
                controls.target.set(3, 0.15, -2);
                dialogue.innerHTML = '"Welcome to the <strong>Cardio Zone</strong>! High intensity sprinting boosts agility and stamina."';
            } else if (zone === 'machines') {
                sub.textContent = 'ZONE 3 • HEAVY MACHINES';
                title.textContent = 'Leg Press & Cable Towers';
                camera.position.set(0, 3, -1);
                controls.target.set(0, 1.1, -5);
                dialogue.innerHTML = '"Machine Zone activated! Controlled resistance for isolated muscle awakening."';
            } else if (zone === 'functional') {
                sub.textContent = 'ZONE 4 • FUNCTIONAL CORE';
                title.textContent = 'Ab Mats & Functional Area';
                camera.position.set(0, 4, 3);
                controls.target.set(0, 0, 0);
                dialogue.innerHTML = '"Functional Floor! Building core stability fortifies your defense stats."';
            } else if (zone === 'shop') {
                sub.textContent = 'ZONE 5 • SUPPLEMENT BAR';
                title.textContent = 'Monarch Nutrition Item Shop';
                camera.position.set(0, 2, 5);
                dialogue.innerHTML = '"Welcome to the <strong>Item Shop</strong>! Purchase recovery elixirs and gear to level up!"';
            }
        }

        function askCoach(topic) {
            const dialogue = document.getElementById('dialogue-content');
            if (topic === 'chest') {
                dialogue.innerHTML = '"Chest Hypertrophy Quest: 4 Sets Barbell Bench Press + 3 Sets Incline Dumbbell Flyes. Maintain strict eccentric control!"';
            } else if (topic === 'fatloss') {
                dialogue.innerHTML = '"Stamina Quest: 20 Minutes Treadmill HIIT intervals (30s sprint, 30s walk) to burn calories!"';
            } else if (topic === 'protein') {
                dialogue.innerHTML = '"System Recovery: 1 Scoop Whey Protein Isolate + 5g Creatine Monohydrate post-workout for maximum muscle recovery!"';
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
