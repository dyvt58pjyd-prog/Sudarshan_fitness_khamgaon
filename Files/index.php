<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

include './include/db_conn.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if(isset($_SESSION["user_data"]))
{
    if (isset($_SESSION['require_pin_setup']) && $_SESSION['require_pin_setup'] === true) {
        header("location: ./setup_pin.php");
        exit();
    }
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'member') {
        header("location: ./dashboard/member/");
    } else {
        header("location: ./dashboard/admin/");
    }
    exit();
}

$gym = get_gym_details($con);

$selected_role = isset($_GET['role']) ? $_GET['role'] : 'member';
$valid_roles = ['member', 'reception', 'trainer', 'owner', 'super_admin'];
if (!in_array($selected_role, $valid_roles)) {
    $selected_role = 'member';
}

$logo_path = $gym['gym_logo'];
if (substr($logo_path, 0, 6) === '../../') {
    $logo_path = './' . substr($logo_path, 6);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <!-- OpenGraph SEO -->
    <meta property="og:title" content="<?php echo htmlspecialchars($gym['gym_name']); ?> | System Portal">
    <meta property="og:description" content="Solo Leveling System Gate | Access your premium Sudarshan Fitness portal.">
    <meta property="og:image" content="<?php echo htmlspecialchars($logo_path); ?>">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">
	<title>[SYSTEM PORTAL] <?php echo htmlspecialchars($gym['gym_name']); ?> | Gate Login</title>
	<link rel="shortcut icon" href="<?php echo htmlspecialchars($logo_path); ?>" type="image/jpeg">
    <link rel="manifest" href="manifest.json">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;600;700;800;900&family=Outfit:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="./css/style.css"/>
	<link rel="stylesheet" type="text/css" href="./css/entypo.css">
	<link rel="stylesheet" href="./css/premium.css"/>
    <style>
    /* Solo Leveling Monarch System Gate Background */
    body.login-page {
        background: #030712 !important;
        position: relative;
        overflow-x: hidden;
        background-image: 
            radial-gradient(circle at 50% 20%, rgba(112, 0, 255, 0.25) 0%, transparent 60%),
            radial-gradient(circle at 50% 80%, rgba(0, 240, 255, 0.18) 0%, transparent 50%) !important;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0;
    }

    /* Rotating Holographic Portal Ring Behind Container */
    .portal-ring-background {
        position: absolute;
        width: 750px;
        height: 750px;
        border-radius: 50%;
        border: 2px dashed rgba(0, 240, 255, 0.4);
        box-shadow: 0 0 50px rgba(0, 240, 255, 0.3), inset 0 0 50px rgba(112, 0, 255, 0.3);
        animation: portal-ring-spin 25s linear infinite;
        pointer-events: none;
        z-index: 1;
    }

    .portal-ring-inner {
        position: absolute;
        width: 550px;
        height: 550px;
        border-radius: 50%;
        border: 2px solid rgba(112, 0, 255, 0.4);
        box-shadow: 0 0 40px rgba(112, 0, 255, 0.4);
        animation: portal-ring-spin-reverse 18s linear infinite;
        pointer-events: none;
        z-index: 1;
    }

    @keyframes portal-ring-spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    @keyframes portal-ring-spin-reverse {
        0% { transform: rotate(360deg); }
        100% { transform: rotate(0deg); }
    }

    @keyframes system-lightning-flash {
        0% { background-color: rgba(0, 240, 255, 0.15); filter: brightness(1.6); }
        20% { background-color: transparent; filter: none; }
        40% { background-color: rgba(112, 0, 255, 0.15); filter: brightness(1.8); }
        60% { background-color: transparent; filter: none; }
        100% { background-color: transparent; filter: none; }
    }

    .lightning-active {
        animation: system-lightning-flash 0.5s ease-out;
    }

    /* System Gate Window Container */
    .login-container {
        max-width: 650px !important;
        width: 95% !important;
        background: rgba(15, 7, 18, 0.95) !important;
        border: 2px solid #ff003c !important;
        border-radius: 28px !important;
        padding: 35px 30px !important;
        box-shadow: 0 0 50px rgba(255, 0, 60, 0.35) !important;
        position: relative;
        z-index: 10;
        animation: system-hologram-pulse 6s ease-in-out infinite alternate !important;
    }

    .login-categories {
        display: grid !important;
        grid-template-columns: repeat(3, 1fr) !important;
        gap: 12px !important;
        margin-bottom: 25px !important;
    }

    @media (max-width: 550px) {
        .login-categories {
            grid-template-columns: repeat(3, 1fr) !important;
            gap: 8px !important;
        }
        .category-tab {
            padding: 12px 6px !important;
        }
        .category-tab i {
            font-size: 20px !important;
        }
        .category-tab span {
            font-size: 9px !important;
        }
    }

    .category-tab {
        background: rgba(255, 0, 60, 0.04) !important;
        border: 1px solid rgba(255, 0, 60, 0.25) !important;
        border-radius: 14px !important;
        padding: 14px 6px !important;
        text-align: center !important;
        cursor: pointer !important;
        transition: all 0.25s ease !important;
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 6px !important;
    }

    .category-tab[data-role="member"]:hover, .category-tab[data-role="member"].active {
        border-color: #ff003c !important;
        background: rgba(255, 0, 60, 0.22) !important;
        box-shadow: 0 0 25px rgba(255, 0, 60, 0.6) !important;
        transform: scale(1.04);
    }
    .category-tab[data-role="reception"]:hover, .category-tab[data-role="reception"].active {
        border-color: #ffb703 !important;
        background: rgba(255, 183, 3, 0.22) !important;
        box-shadow: 0 0 25px rgba(255, 183, 3, 0.6) !important;
        transform: scale(1.04);
    }
    .category-tab[data-role="trainer"]:hover, .category-tab[data-role="trainer"].active {
        border-color: #7000ff !important;
        background: rgba(112, 0, 255, 0.25) !important;
        box-shadow: 0 0 25px rgba(112, 0, 255, 0.6) !important;
        transform: scale(1.04);
    }
    .category-tab[data-role="owner"]:hover, .category-tab[data-role="owner"].active {
        border-color: #a855f7 !important;
        background: rgba(168, 85, 247, 0.25) !important;
        box-shadow: 0 0 25px rgba(168, 85, 247, 0.6) !important;
        transform: scale(1.04);
    }
    .category-tab[data-role="auditor"]:hover, .category-tab[data-role="auditor"].active {
        border-color: #00f0ff !important;
        background: rgba(0, 240, 255, 0.22) !important;
        box-shadow: 0 0 25px rgba(0, 240, 255, 0.6) !important;
        transform: scale(1.04);
    }
    .category-tab[data-role="super_admin"]:hover, .category-tab[data-role="super_admin"].active {
        border-color: #10b981 !important;
        background: rgba(16, 185, 129, 0.25) !important;
        box-shadow: 0 0 25px rgba(16, 185, 129, 0.6) !important;
        transform: scale(1.04);
    }

    .category-tab i {
        font-size: 24px !important;
    }
    .category-tab span {
        font-size: 10px !important;
        font-weight: 800 !important;
        font-family: 'Orbitron', sans-serif !important;
        color: rgba(255, 255, 255, 0.8) !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
    }

    .category-tab.active span {
        color: #ffffff !important;
    }
    .category-tab[data-role="member"].active i { color: #ff003c !important; }
    .category-tab[data-role="reception"].active i { color: #ffb703 !important; }
    .category-tab[data-role="trainer"].active i { color: #7000ff !important; }
    .category-tab[data-role="owner"].active i { color: #a855f7 !important; }
    .category-tab[data-role="super_admin"].active i { color: #10b981 !important; }

    .form-control {
        background: rgba(3, 7, 18, 0.85) !important;
        border: 1px solid rgba(255, 0, 60, 0.35) !important;
        color: #ffffff !important;
        border-radius: 14px !important;
        padding: 14px 18px !important;
        font-size: 14px !important;
    }

    .form-control:focus {
        border-color: #ff003c !important;
        box-shadow: 0 0 25px rgba(255, 0, 60, 0.5) !important;
    }

    .btn-primary {
        background: linear-gradient(135deg, #ff003c, #d90429) !important;
        color: #ffffff !important;
        border: none !important;
        padding: 15px !important;
        border-radius: 14px !important;
        font-family: 'Orbitron', sans-serif !important;
        font-weight: 900 !important;
        font-size: 14px !important;
        letter-spacing: 1px !important;
        box-shadow: 0 0 35px rgba(255, 0, 60, 0.7) !important;
        transition: all 0.2s ease !important;
    }

    .btn-primary:hover {
        transform: translateY(-2px) !important;
        box-shadow: 0 0 50px rgba(255, 0, 60, 0.95) !important;
    }
    </style>
</head>
<body class="page-body login-page login-form-fall">

    <!-- Solo Leveling Floating Energy Particles Canvas -->
    <canvas id="loginParticlesCanvas" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 2; opacity: 0.6;"></canvas>

    <!-- Rotating Holographic Portal Rings -->
    <div class="portal-ring-background"></div>
    <div class="portal-ring-inner"></div>

    <div id="container">
        <div class="login-container">
            <div class="login-header login-caret">
                <div class="login-content" style="text-align: center;">
                    <div style="font-family: 'Orbitron', sans-serif; font-size: 11px; font-weight: 900; color: #ff003c; letter-spacing: 3px; text-transform: uppercase; margin-bottom: 12px; text-shadow: 0 0 12px rgba(255,0,60,0.5);">[ SYSTEM GATE PORTAL ]</div>
                    <a href="#" class="logo">
                        <img src="<?php echo htmlspecialchars($logo_path); ?>" alt="Gym Logo" style="filter: drop-shadow(0 0 25px rgba(255,0,60,0.7)); max-height: 95px; width: auto;" />
                    </a>
                    <p class="description" style="color: #64748b; font-size: 12px; font-weight: 600; margin-top: 8px;">
                        Select your category to access your <?php echo htmlspecialchars($gym['gym_name']); ?> account.
                    </p>
                </div>
            </div>

            <div class="login-form">
                <div class="login-content">
                    <form action="secure_login.php" method="post" id="bb">
                        <!-- Hidden Input for selected role -->
                        <input type="hidden" name="login_role" id="login_role" value="<?php echo htmlspecialchars($selected_role); ?>">

                        <!-- Visual Grid of 6 Login Categories -->
                        <div class="login-categories">
                            <div class="category-tab <?php echo ($selected_role === 'member') ? 'active' : ''; ?>" data-role="member" onclick="selectRole('member')">
                                <i class="entypo-user"></i>
                                <span>Gym Member</span>
                            </div>
                            <div class="category-tab <?php echo ($selected_role === 'reception') ? 'active' : ''; ?>" data-role="reception" onclick="selectRole('reception')">
                                <i class="entypo-address"></i>
                                <span>Reception</span>
                            </div>
                            <div class="category-tab <?php echo ($selected_role === 'trainer') ? 'active' : ''; ?>" data-role="trainer" onclick="selectRole('trainer')">
                                <i class="entypo-flash"></i>
                                <span>Trainer</span>
                            </div>
                            <div class="category-tab <?php echo ($selected_role === 'owner') ? 'active' : ''; ?>" data-role="owner" onclick="selectRole('owner')">
                                <i class="entypo-briefcase"></i>
                                <span>Owner</span>
                            </div>
                            <div class="category-tab <?php echo ($selected_role === 'auditor') ? 'active' : ''; ?>" data-role="auditor" onclick="selectRole('auditor')">
                                <i class="entypo-book-open"></i>
                                <span>Auditor</span>
                            </div>
                            <div class="category-tab <?php echo ($selected_role === 'super_admin') ? 'active' : ''; ?>" data-role="super_admin" onclick="selectRole('super_admin')">
                                <i class="entypo-cog"></i>
                                <span>App Developer</span>
                            </div>
                        </div>

                        <!-- Username/UserID input -->
                        <div class="form-group">
                            <div class="input-group">
                                <div class="input-group-addon" style="background: rgba(255,0,60,0.15); border-color: rgba(255,0,60,0.35); color: #ff003c;">
                                    <i class="entypo-user"></i>
                                </div>
                                <input type="text" placeholder="User ID / Username" class="form-control" name="user_id_auth" id="textfield" required>
                            </div>
                        </div>

                        <!-- Password input -->
                        <div class="form-group">
                            <div class="input-group">
                                <div class="input-group-addon" style="background: rgba(255,0,60,0.15); border-color: rgba(255,0,60,0.35); color: #ff003c;">
                                    <i class="entypo-key"></i>
                                </div>
                                <input type="password" name="pass_key" id="pwfield" class="form-control" required placeholder="SYSTEM ACCESS PIN">
                            </div>
                        </div>

                        <div class="form-group" style="margin-top: 25px;">
                            <button type="submit" name="btnLogin" class="btn btn-primary" style="width: 100%; margin-bottom: 15px;">
                                ENTER SYSTEM GATE ➔
                                <i class="entypo-login"></i>
                            </button>
                            
                            <!-- Action Grid for Self Registration & Quick Portals -->
                            <div style="margin-top: 15px;">
                                <a href="register.php" style="background: rgba(255, 0, 60, 0.2); color: #ff003c; border: 1.5px solid #ff003c; font-family: 'Orbitron', sans-serif; font-weight: 900; font-size: 13px; text-decoration: none; text-align: center; padding: 13px; border-radius: 12px; display: block; box-shadow: 0 0 20px rgba(255,0,60,0.3); margin-bottom: 10px;">
                                    ✍️ SELF REGISTRATION (JOIN GYM)
                                </a>
                                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
                                    <a href="guest_enquiry.php" style="background: rgba(255, 183, 3, 0.15); color: #ffb703; border: 1px solid #ffb703; font-family: 'Orbitron', sans-serif; font-weight: 800; font-size: 11px; text-decoration: none; text-align: center; padding: 11px 6px; border-radius: 12px; display: block; box-shadow: 0 0 15px rgba(255,183,3,0.2);">
                                        🎁 Free Gym Trial
                                    </a>
                                    <a href="prebook.php" style="background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid #10b981; font-family: 'Orbitron', sans-serif; font-weight: 800; font-size: 11px; text-decoration: none; text-align: center; padding: 11px 6px; border-radius: 12px; display: block; box-shadow: 0 0 15px rgba(16,185,129,0.2);">
                                        ⚡ Pre-Book Slot
                                    </a>
                                </div>
                            </div>

                            <button type="button" id="faceIdLoginBtn" class="btn btn-success" style="width: 100%; display: block; margin-top: 12px; background: linear-gradient(135deg, #7000ff, #ff003c); border: 1px solid #ff003c; font-family: 'Orbitron', sans-serif; font-weight: 900; box-shadow: 0 0 25px rgba(112,0,255,0.6);" onclick="loginWithFaceID()">
                                <i class="entypo-camera"></i>
                                SYSTEM BIOMETRIC SCAN
                            </button>
                        </div>

                    <!-- Add face-api.js script -->
                    <script defer src="js/face-api/face-api.min.js"></script>

                    <!-- Face Scan UI Container -->
                    <div id="faceScanContainer" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(3,7,18,0.95); z-index: 9999; justify-content: center; align-items: center; flex-direction: column;">
                        <h2 style="color: #00f0ff; margin-bottom: 20px; font-family: 'Orbitron';">[ SYSTEM BIOMETRIC SCAN ]</h2>
                        <div style="position: relative; width: 300px; height: 300px; border-radius: 50%; overflow: hidden; border: 4px solid #00f0ff; box-shadow: 0 0 40px #00f0ff;">
                            <video id="loginVideo" autoplay muted playsinline style="width: 100%; height: 100%; object-fit: cover; transform: scaleX(-1);"></video>
                        </div>
                        <p id="loginStatusMsg" style="color: #cbd5e1; margin-top: 20px; font-size: 16px; font-family: 'Orbitron';">Initializing System Sensors...</p>
                        <button type="button" class="btn btn-danger" style="margin-top: 20px; font-family: 'Orbitron';" onclick="cancelFaceLogin()">CANCEL SCAN</button>
                    </div>

                    <script>
                    let loginModelsLoaded = false;
                    let loginModelsLoading = false;
                    let loginStream = null;
                    let loginScanInterval = null;

                    // Periodic System Lightning Flash FX
                    setInterval(() => {
                        if (Math.random() > 0.4) {
                            document.body.classList.add('lightning-active');
                            setTimeout(() => {
                                document.body.classList.remove('lightning-active');
                            }, 500);
                        }
                    }, 12000);

                    // Login Floating Energy Particles FX
                    function initLoginParticles() {
                        const canvas = document.getElementById('loginParticlesCanvas');
                        if (!canvas) return;
                        const ctx = canvas.getContext('2d');
                        let w = canvas.width = window.innerWidth;
                        let h = canvas.height = window.innerHeight;

                        const particles = [];
                        for (let i = 0; i < 45; i++) {
                            particles.push({
                                x: Math.random() * w,
                                y: Math.random() * h,
                                size: Math.random() * 2 + 1,
                                color: Math.random() > 0.5 ? '#00f0ff' : '#7000ff',
                                vy: -(Math.random() * 0.7 + 0.3),
                                vx: (Math.random() - 0.5) * 0.5,
                                alpha: Math.random() * 0.8 + 0.2
                            });
                        }

                        function draw() {
                            ctx.clearRect(0, 0, w, h);
                            particles.forEach(p => {
                                p.y += p.vy;
                                p.x += p.vx;
                                if (p.y < 0) p.y = h;
                                if (p.x < 0) p.x = w;
                                if (p.x > w) p.x = 0;

                                ctx.save();
                                ctx.globalAlpha = p.alpha;
                                ctx.fillStyle = p.color;
                                ctx.shadowColor = p.color;
                                ctx.shadowBlur = 10;
                                ctx.beginPath();
                                ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
                                ctx.fill();
                                ctx.restore();
                            });
                            requestAnimationFrame(draw);
                        }

                        window.addEventListener('resize', () => {
                            w = canvas.width = window.innerWidth;
                            h = canvas.height = window.innerHeight;
                        });

                        draw();
                    }

                    async function loadLoginModels() {
                        if (loginModelsLoaded || loginModelsLoading) return;
                        loginModelsLoading = true;
                        try {
                            await Promise.all([
                                faceapi.nets.ssdMobilenetv1.loadFromUri('js/face-api/models_v2'),
                                faceapi.nets.faceLandmark68Net.loadFromUri('js/face-api/models_v2'),
                                faceapi.nets.faceRecognitionNet.loadFromUri('js/face-api/models_v2')
                            ]);
                            loginModelsLoaded = true;
                        } catch (err) {
                            console.warn("Face models failed to load:", err);
                        } finally {
                            loginModelsLoading = false;
                        }
                    }

                    function selectRole(role) {
                        document.getElementById('login_role').value = role;
                        
                        document.querySelectorAll('.category-tab').forEach(tab => {
                            tab.classList.remove('active');
                        });
                        
                        const activeTab = document.querySelector(`.category-tab[data-role="${role}"]`);
                        if (activeTab) {
                            activeTab.classList.add('active');
                        }

                        const pwfield = document.getElementById('pwfield');
                        if (role === 'member') {
                            pwfield.placeholder = 'Enter 6-Digit Security PIN';
                        } else {
                            pwfield.placeholder = 'Enter 6-Digit Security PIN';
                        }

                        const faceBtn = document.getElementById('faceIdLoginBtn');
                        if (faceBtn) faceBtn.style.display = 'block';
                        loadLoginModels();
                    }

                    async function loginWithFaceID() {
                        const scanContainer = document.getElementById('faceScanContainer');
                        const video = document.getElementById('loginVideo');
                        const statusMsg = document.getElementById('loginStatusMsg');

                        scanContainer.style.display = 'flex';
                        statusMsg.textContent = 'Activating System Camera...';

                        try {
                            if (!loginModelsLoaded) {
                                statusMsg.textContent = 'Loading System Sensors...';
                                await loadLoginModels();
                            }

                            loginStream = await navigator.mediaDevices.getUserMedia({ video: true });
                            video.srcObject = loginStream;
                            statusMsg.textContent = 'Scanning Hunter Features... Position face in center.';

                            loginScanInterval = setInterval(async () => {
                                if (!video.paused && !video.ended) {
                                    const detection = await faceapi.detectSingleFace(video).withFaceLandmarks().withFaceDescriptor();
                                    if (detection) {
                                        statusMsg.textContent = 'Hunter Matched! Verifying System Credentials...';
                                        clearInterval(loginScanInterval);
                                        
                                        const descriptorArray = Array.from(detection.descriptor);
                                        
                                        fetch('face_login_verify.php', {
                                            method: 'POST',
                                            headers: { 'Content-Type': 'application/json' },
                                            body: JSON.stringify({ descriptor: descriptorArray })
                                        })
                                        .then(res => res.json())
                                        .then(data => {
                                            if (data.status === 'success') {
                                                statusMsg.textContent = 'Access Granted! Entering Portal...';
                                                setTimeout(() => {
                                                    cancelFaceLogin();
                                                    window.location.href = data.redirect || './dashboard/member/';
                                                }, 500);
                                            } else {
                                                statusMsg.textContent = 'Verification Failed: ' + (data.message || 'Unknown Hunter');
                                                setTimeout(() => {
                                                    cancelFaceLogin();
                                                }, 2500);
                                            }
                                        })
                                        .catch(err => {
                                            statusMsg.textContent = 'System Verification Error.';
                                            setTimeout(cancelFaceLogin, 2000);
                                        });
                                    }
                                }
                            }, 800);

                        } catch (err) {
                            alert('Camera Access Error: ' + err.message);
                            cancelFaceLogin();
                        }
                    }

                    function cancelFaceLogin() {
                        if (loginScanInterval) clearInterval(loginScanInterval);
                        if (loginStream) {
                            loginStream.getTracks().forEach(track => track.stop());
                            loginStream = null;
                        }
                        const scanContainer = document.getElementById('faceScanContainer');
                        if (scanContainer) scanContainer.style.display = 'none';
                    }

                    document.addEventListener('DOMContentLoaded', () => {
                        selectRole('<?php echo $selected_role; ?>');
                        initLoginParticles();
                    });
                    </script>

                    <!-- Official Copyright Footer -->
                    <div style="text-align: center; margin-top: 25px; border-top: 1px solid rgba(255, 0, 60, 0.25); padding-top: 15px;">
                        <div style="font-size: 11px; color: var(--text-muted); font-family: 'Orbitron', sans-serif; letter-spacing: 0.5px;">
                            © <?php echo date('Y'); ?> <?php echo htmlspecialchars($gym['gym_name']); ?>. All Rights Reserved.
                        </div>
                        <div style="font-size: 10px; color: #ff003c; font-weight: 800; font-family: 'Orbitron', sans-serif; margin-top: 4px; letter-spacing: 1.5px;">
                            POWERED BY SUDARSHAN FITNESS v2.0
                        </div>
                    </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
