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
$valid_roles = ['member', 'reception', 'trainer', 'owner', 'auditor', 'super_admin', 'nutrition_partner'];
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
    <meta property="og:description" content="Sudarshan Fitness | Access your premium Sudarshan Fitness portal.">
    <meta property="og:image" content="<?php echo htmlspecialchars($logo_path); ?>">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">
	<title>[SUDARSHAN FITNESS] <?php echo htmlspecialchars($gym['gym_name']); ?> | Gate Login</title>
	<link rel="shortcut icon" href="<?php echo htmlspecialchars($logo_path); ?>" type="image/jpeg">
    <link rel="manifest" href="manifest.json">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;600;700;800;900&family=Outfit:wght@300;400;600;700;800;900&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="./css/style.css"/>
	<link rel="stylesheet" type="text/css" href="./css/entypo.css">
	<link rel="stylesheet" href="./css/premium.css"/>
    <script src="./js/theme_engine.js"></script>
    <style>
    /* Energetic Navratri & Garba Surge Login Aesthetic */
    [data-theme="festive"] #titan-login-body,
    body.festive-theme-active #titan-login-body,
    [data-theme="festive"] {
        background-color: #0c071e !important;
        background-image: 
            radial-gradient(at 0% 0%, rgba(42, 8, 69, 0.95) 0, transparent 55%), 
            radial-gradient(at 100% 0%, rgba(233, 30, 99, 0.35) 0, transparent 50%), 
            radial-gradient(at 50% 100%, rgba(255, 87, 34, 0.3) 0, transparent 60%) !important;
        background-attachment: fixed !important;
        background-size: cover !important;
    }

    [data-theme="festive"] #titan-login-container,
    body.festive-theme-active #titan-login-container {
        background: rgba(24, 13, 56, 0.88) !important;
        backdrop-filter: blur(30px) saturate(180%) !important;
        -webkit-backdrop-filter: blur(30px) saturate(180%) !important;
        border: 1.5px solid rgba(255, 193, 7, 0.4) !important;
        box-shadow: 0 30px 60px rgba(233, 30, 99, 0.3), 0 0 30px rgba(255, 87, 34, 0.2) !important;
    }

    [data-theme="festive"] .industrial-title,
    body.festive-theme-active .industrial-title {
        color: #fffdf5 !important;
        text-shadow: 0 0 10px rgba(255, 193, 7, 0.4) !important;
    }

    [data-theme="festive"] .category-tab,
    body.festive-theme-active .category-tab {
        background: rgba(255, 255, 255, 0.06) !important;
        border: 1px solid rgba(255, 87, 34, 0.2) !important;
    }

    [data-theme="festive"] .category-tab.active,
    body.festive-theme-active .category-tab.active {
        background: linear-gradient(135deg, #FF5722 0%, #E91E63 100%) !important;
        border-color: #FFC107 !important;
        box-shadow: 0 4px 15px rgba(255, 87, 34, 0.5) !important;
    }

    [data-theme="festive"] .category-tab i,
    [data-theme="festive"] .category-tab span,
    body.festive-theme-active .category-tab i,
    body.festive-theme-active .category-tab span {
        color: #e2b8ff !important;
    }

    [data-theme="festive"] .category-tab.active i,
    [data-theme="festive"] .category-tab.active span,
    body.festive-theme-active .category-tab.active i,
    body.festive-theme-active .category-tab.active span {
        color: #ffffff !important;
    }

    [data-theme="festive"] .input-group,
    body.festive-theme-active .input-group {
        background: rgba(35, 18, 77, 0.9) !important;
        border: 1px solid rgba(255, 87, 34, 0.4) !important;
    }

    [data-theme="festive"] .input-group:focus-within,
    body.festive-theme-active .input-group:focus-within {
        border-color: #FFC107 !important;
        box-shadow: 0 0 18px rgba(255, 87, 34, 0.6) !important;
    }

    [data-theme="festive"] .input-group-addon,
    [data-theme="festive"] .form-control,
    body.festive-theme-active .input-group-addon,
    body.festive-theme-active .form-control {
        color: #ffffff !important;
    }

    [data-theme="festive"] .btn-primary,
    body.festive-theme-active .btn-primary {
        background: linear-gradient(135deg, #FF5722 0%, #E91E63 50%, #FFC107 100%) !important;
        color: #ffffff !important;
        font-weight: 800 !important;
        box-shadow: 0 8px 25px rgba(255, 87, 34, 0.5) !important;
    }

    /* Standard Apple Minimalist Default */
    #titan-login-body {

        /* MacOS style soft mesh gradient */
        background-color: #f5f5f7 !important;
        background-image: 
            radial-gradient(at 0% 0%, hsla(253,16%,7%,1) 0, transparent 50%), 
            radial-gradient(at 50% 0%, hsla(225,39%,30%,1) 0, transparent 50%), 
            radial-gradient(at 100% 0%, hsla(339,49%,30%,1) 0, transparent 50%) !important;
        background-attachment: fixed !important;
        background-size: cover !important;
        min-height: 100vh;
        display: block;
        margin: 0;
        padding: 40px 20px;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif !important;
        box-sizing: border-box;
    }

    #titan-login-container {
        max-width: 480px !important;
        width: 100% !important;
        margin: 0 auto;
        background: rgba(255, 255, 255, 0.85) !important;
        backdrop-filter: blur(40px) saturate(200%) !important;
        -webkit-backdrop-filter: blur(40px) saturate(200%) !important;
        border: 1px solid rgba(255, 255, 255, 0.5) !important;
        border-radius: 32px !important;
        padding: 40px 35px !important;
        box-shadow: 0 30px 60px rgba(0, 0, 0, 0.2), 0 0 0 1px rgba(255,255,255,0.2) inset !important;
        position: relative;
        z-index: 10;
    }

    #titan-login-body .login-categories {
        display: grid !important;
        grid-template-columns: repeat(4, 1fr) !important;
        gap: 8px !important;
        margin-bottom: 25px !important;
    }

    #titan-login-body .category-tab {
        background: rgba(0, 0, 0, 0.03) !important;
        border: 1px solid transparent !important;
        border-radius: 16px !important;
        padding: 10px 4px !important;
        text-align: center !important;
        cursor: pointer !important;
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1) !important;
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 6px !important;
    }

    #titan-login-body .category-tab:hover {
        background: rgba(0, 0, 0, 0.06) !important;
        transform: scale(1.02);
    }

    #titan-login-body .category-tab.active {
        background: #ffffff !important;
        border-color: rgba(0,0,0,0.05) !important;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08) !important;
        transform: scale(1.05);
    }

    #titan-login-body .category-tab i {
        font-size: 20px !important;
        color: #86868b !important;
        transition: color 0.3s ease;
    }
    
    #titan-login-body .category-tab.active i {
        color: #0071e3 !important;
    }

    #titan-login-body .category-tab span {
        font-size: 10px !important;
        font-weight: 700 !important;
        color: #86868b !important;
        letter-spacing: 0px !important;
        transition: color 0.3s ease;
    }

    #titan-login-body .category-tab.active span {
        color: #1d1d1f !important;
    }

    /* Override input group styles completely */
    #titan-login-body .input-group {
        display: flex !important;
        align-items: center !important;
        background: rgba(255, 255, 255, 0.9) !important;
        border: 1.5px solid rgba(0, 0, 0, 0.08) !important;
        border-radius: 16px !important;
        margin-bottom: 16px !important;
        padding: 4px 12px !important;
        transition: all 0.3s ease !important;
        box-shadow: 0 2px 6px rgba(0,0,0,0.02) !important;
    }

    #titan-login-body .input-group:focus-within {
        border-color: #0071e3 !important;
        box-shadow: 0 0 0 4px rgba(0, 113, 227, 0.15) !important;
        background: #ffffff !important;
    }

    #titan-login-body .input-group-addon {
        background: transparent !important;
        border: none !important;
        padding: 0 10px 0 4px !important;
        color: #86868b !important;
        font-size: 18px !important;
    }

    #titan-login-body .form-control {
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
        color: #1d1d1f !important;
        padding: 12px 0 !important;
        font-size: 16px !important;
        font-weight: 500 !important;
        width: 100% !important;
        margin: 0 !important;
    }
    
    #titan-login-body .form-control:focus {
        outline: none !important;
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
    }

    #titan-login-body .form-control::placeholder {
        color: #a1a1a6 !important;
        font-weight: 400 !important;
    }

    #titan-login-body .btn-primary {
        background: #1d1d1f !important;
        color: #ffffff !important;
        border: none !important;
        padding: 16px !important;
        border-radius: 16px !important;
        font-weight: 600 !important;
        font-size: 16px !important;
        width: 100% !important;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15) !important;
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1) !important;
        margin-top: 8px !important;
    }

    #titan-login-body .btn-primary:hover {
        background: #000000 !important;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.25) !important;
        transform: translateY(-2px);
    }
    
    #titan-login-body .industrial-title {
        color: #1d1d1f !important;
        font-weight: 800 !important;
        font-size: 24px !important;
        letter-spacing: -0.5px !important;
        margin-top: 10px !important;
        margin-bottom: 2px !important;
    }
    
    #titan-login-body .btn-action {
        border-radius: 12px !important;
        font-weight: 600 !important;
        font-size: 13px !important;
        padding: 12px !important;
        border: 1px solid rgba(0,0,0,0.05) !important;
        transition: all 0.3s ease !important;
    }
    </style>
</head>
<body id="titan-login-body" class="page-body login-page login-form-fall">

    <div id="container">
        <div id="titan-login-container" class="login-container">
            <div class="login-header login-caret">
                <div class="login-content" style="text-align: center;">
                    
                    <div style="margin-bottom: 14px;">
                        <div style="background: linear-gradient(135deg, #FF5722 0%, #E91E63 50%, #FFC107 100%); color: #ffffff; padding: 6px 14px; border-radius: 20px; font-size: 11px; font-weight: 800; display: inline-block; letter-spacing: 0.5px; box-shadow: 0 4px 15px rgba(255, 87, 34, 0.4); text-transform: uppercase;">
                            ✨ 🔱 NAVRATRI & GARBA SURGE EDITION 🔱 ✨
                        </div>
                    </div>

                    <div style="display: flex; justify-content: center; align-items: center; gap: -10px; margin-bottom: 15px;">
                        <img src="./images/ganesha_gym.jpg" style="width: 70px; height: 70px; border-radius: 50%; object-fit: cover; border: 4px solid #fff; box-shadow: 0 8px 16px rgba(0,0,0,0.1); z-index: 2;">
                        <img src="<?php echo htmlspecialchars($logo_path); ?>" alt="Gym Logo" style="height: 50px; width: auto; margin-left: -15px; border-radius: 8px; z-index: 1;" />
                    </div>

                    <div class="industrial-title">
                        <?php echo htmlspecialchars($gym['gym_name']); ?>
                    </div>
                    <p class="description" style="color: #d8b4fe; font-size: 13px; font-weight: 600; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 1px;">
                        Secure Access Portal
                    </p>
                    
                    <!-- Quick Theme Selector Pill -->
                    <div style="margin-bottom: 20px;">
                        <select id="sf-theme-select-login" onchange="SFThemeEngine.setThemeMode(this.value)" style="background: rgba(35, 18, 77, 0.85); color: #FFC107; border: 1.5px solid #FF5722; border-radius: 12px; padding: 6px 14px; font-size: 12px; font-weight: 700; cursor: pointer; outline: none; box-shadow: 0 4px 12px rgba(255,87,34,0.3);">
                            <option value="festive">🔱 Theme 2: Navratri & Garba Surge</option>
                            <option value="dark">🌙 Dark Mode</option>
                            <option value="light">☀️ Light Mode</option>
                        </select>
                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                var sel = document.getElementById('sf-theme-select-login');
                                if (sel) sel.value = SFThemeEngine.getThemeMode();
                            });
                        </script>
                    </div>
                </div>
            </div>

            <div class="login-form">
                <div class="login-content">
                    <?php if (isset($_GET['error'])): ?>
                        <?php 
                        $err = $_GET['error'];
                        $msg = 'ACCESS DENIED. INVALID CREDENTIALS.';
                        if ($err === 'ip_locked') $msg = 'SYSTEM SECURITY LOCK. TOO MANY FAILED ATTEMPTS. TRY AGAIN IN 15 MINUTES.';
                        if ($err === 'intruder_alert') $msg = 'INTRUDER ALERT. SECURITY LOCKDOWN ACTIVATED. IP LOGGED.';
                        ?>
                        <div style="background: rgba(255, 59, 48, 0.1); border: 1px solid rgba(255, 59, 48, 0.2); padding: 14px; margin-bottom: 24px; border-radius: 12px; color: #ff3b30; font-weight: 500; font-size: 13px; text-align: left;">
                            <i class="entypo-attention" style="margin-right: 6px;"></i> <?php echo htmlspecialchars($msg); ?>
                        </div>
                    <?php endif; ?>
                    <form action="secure_login.php" method="post" id="bb">
                        <!-- Hidden Input for selected role -->
                        <input type="hidden" name="login_role" id="login_role" value="<?php echo htmlspecialchars($selected_role); ?>">

                        <!-- Visual Grid of 6 Login Categories -->
                        <div class="login-categories">
                            <div class="category-tab <?php echo ($selected_role === 'member') ? 'active' : ''; ?>" data-role="member" onclick="selectRole('member')">
                                <i class="entypo-user"></i>
                                <span>Member</span>
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
                                <span>Developer</span>
                            </div>
                            <div class="category-tab <?php echo ($selected_role === 'nutrition_partner') ? 'active' : ''; ?>" data-role="nutrition_partner" onclick="selectRole('nutrition_partner')">
                                <i class="entypo-basket"></i>
                                <span>Store</span>
                            </div>
                        </div>

                        <!-- Username/UserID input -->
                        <div class="form-group" style="margin-bottom: 0;">
                            <div class="input-group">
                                <div class="input-group-addon">
                                    <i class="entypo-user"></i>
                                </div>
                                <input type="text" placeholder="Member ID or Email" class="form-control" name="user_id_auth" id="textfield" required>
                            </div>
                        </div>

                        <!-- Password input -->
                        <div class="form-group" style="margin-bottom: 0;">
                            <div class="input-group">
                                <div class="input-group-addon">
                                    <i class="entypo-lock"></i>
                                </div>
                                <input type="password" name="pass_key" id="pwfield" class="form-control" required placeholder="Password">
                            </div>
                        </div>

                        <div class="form-group" style="margin-top: 20px;">
                            <button type="submit" name="btnLogin" class="btn btn-primary" style="margin-bottom: 20px;">
                                Sign In
                            </button>
                            
                            <!-- Action Grid for Self Registration & Quick Portals -->
                            <button type="button" id="faceIdLoginBtn" class="btn btn-success" style="width: 100%; display: block; margin-top: 12px; background: linear-gradient(135deg, #ffd700, #ff6b00); border: 1px solid #ff6b00; font-family: 'Orbitron', sans-serif; font-weight: 900; box-shadow: 0 0 25px rgba(255,215,0,0.6);" onclick="loginWithFaceID()">
                                <i class="entypo-camera"></i>
                                SHARINGAN BIOMETRIC SCAN
                            </button>
                        </div>

                    <!-- Add face-api.js script -->
                    <script defer src="js/face-api/face-api.min.js"></script>

                    <!-- Face Scan UI Container -->
                    <div id="faceScanContainer" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15,10,5,0.95); z-index: 9999; justify-content: center; align-items: center; flex-direction: column;">
                        <h2 style="color: #ff6b00; margin-bottom: 20px; font-family: 'Orbitron';">[ SHARINGAN BIOMETRIC SCAN ]</h2>
                        <div style="position: relative; width: 300px; height: 300px; border-radius: 50%; overflow: hidden; border: 4px solid #ff6b00; box-shadow: 0 0 40px #ff6b00;">
                            <video id="loginVideo" autoplay muted playsinline style="width: 100%; height: 100%; object-fit: cover; transform: scaleX(-1);"></video>
                        </div>
                        <p id="loginStatusMsg" style="color: #cbd5e1; margin-top: 20px; font-size: 16px; font-family: 'Orbitron';">Awakening Divine...</p>
                        <button type="button" class="btn btn-danger" style="margin-top: 20px; font-family: 'Orbitron';" onclick="cancelFaceLogin()">CANCEL JUTSU</button>
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
                                color: Math.random() > 0.5 ? '#ff6b00' : '#ffd700',
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
                        const cdnUri = 'https://justadudewhohacks.github.io/face-api.js/models';
                        const localUri = 'js/face-api/models_v2';
                        try {
                            await Promise.all([
                                faceapi.nets.tinyFaceDetector.loadFromUri(cdnUri),
                                faceapi.nets.faceLandmark68TinyNet.loadFromUri(cdnUri),
                                faceapi.nets.faceRecognitionNet.loadFromUri(cdnUri)
                            ]);
                            loginModelsLoaded = true;
                        } catch (err) {
                            console.warn("CDN primary load failed, trying local fallback...", err);
                            try {
                                await Promise.all([
                                    faceapi.nets.tinyFaceDetector.loadFromUri(localUri),
                                    faceapi.nets.faceLandmark68TinyNet.loadFromUri(localUri),
                                    faceapi.nets.faceRecognitionNet.loadFromUri(localUri)
                                ]);
                                loginModelsLoaded = true;
                            } catch (e) {
                                console.warn("Models load error:", e);
                            }
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
                        statusMsg.textContent = '⚡ Activating Ultra-Fast Sensors...';

                        try {
                            if (!loginModelsLoaded) {
                                statusMsg.textContent = '⚡ Loading System Face Sensors...';
                                await loadLoginModels();
                            }

                            loginStream = await navigator.mediaDevices.getUserMedia({ video: { width: { ideal: 640 }, height: { ideal: 480 }, facingMode: 'user' } });
                            video.srcObject = loginStream;
                            statusMsg.textContent = '🔍 Scanning Face Biometrics... Position face in frame.';

                            let isProcessing = false;
                            loginScanInterval = setInterval(async () => {
                                if (!video.paused && !video.ended && !isProcessing) {
                                    isProcessing = true;
                                    try {
                                        const options = new faceapi.SsdMobilenetv1Options({ minConfidence: 0.2 });
                                        const detection = await faceapi.detectSingleFace(video, options).withFaceLandmarks().withFaceDescriptor();
                                        if (detection) {
                                            statusMsg.textContent = '⚡ Face Detected! Verifying Identity...';
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
                                                    statusMsg.textContent = '✅ Access Granted! Entering System...';
                                                    setTimeout(() => {
                                                        cancelFaceLogin();
                                                        window.location.href = data.redirect || './dashboard/member/';
                                                    }, 300);
                                                } else {
                                                    statusMsg.textContent = '❌ ' + (data.message || 'Face Not Verified');
                                                    setTimeout(() => {
                                                        cancelFaceLogin();
                                                    }, 2000);
                                                }
                                            })
                                            .catch(err => {
                                                statusMsg.textContent = 'System Verification Error.';
                                                setTimeout(cancelFaceLogin, 2000);
                                            });
                                        }
                                    } catch (e) {
                                        console.warn("Scan frame error:", e);
                                    } finally {
                                        isProcessing = false;
                                    }
                                }
                            }, 150);

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

                    let deferredPwaPrompt = null;
                    window.addEventListener('beforeinstallprompt', (e) => {
                        e.preventDefault();
                        deferredPwaPrompt = e;
                        const btn = document.getElementById('pwaDirectInstallBtn');
                        if (btn) btn.style.display = 'inline-block';
                    });

                    function triggerChromeInstall() {
                        if (deferredPwaPrompt) {
                            deferredPwaPrompt.prompt();
                            deferredPwaPrompt.userChoice.then((choiceResult) => {
                                if (choiceResult.outcome === 'accepted') {
                                    alert('✅ Sudarshan Fitness Application installed successfully!');
                                    closePwaModal();
                                }
                                deferredPwaPrompt = null;
                            });
                        } else {
                            alert("📲 TO INSTALL ON CHROME:\n\n1. Tap Chrome Menu (⋮) at top right of browser.\n2. Tap 'Add to Home screen' or 'Install App'.\n3. The app icon will appear on your phone home screen!");
                        }
                    }

                    function closePwaModal() {
                        const modal = document.getElementById('pwaInstallModal');
                        if (modal) modal.style.display = 'none';
                    }

                    document.addEventListener('DOMContentLoaded', () => {
                        selectRole('<?php echo $selected_role; ?>');
                        setTimeout(loadLoginModels, 500);

                        const urlParams = new URLSearchParams(window.location.search);
                        if (urlParams.has('install_pwa')) {
                            const modal = document.getElementById('pwaInstallModal');
                            if (modal) modal.style.display = 'flex';
                        }
                    });
                    </script>

                    <!-- PWA Installation Modal -->
                    <div id="pwaInstallModal" style="display: none; position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.85); backdrop-filter: blur(10px); z-index: 99999; align-items: center; justify-content: center; padding: 20px;">
                        <div style="background: #111827; border: 2px solid #ff6b00; border-radius: 20px; max-width: 440px; width: 100%; padding: 30px; text-align: center; box-shadow: 0 0 35px rgba(255,107,0,0.5); animation: pulseGlow 2s infinite alternate;">
                            <img src="logo192.png" style="width: 80px; height: 80px; border-radius: 18px; margin-bottom: 15px; border: 2px solid #ff6b00; box-shadow: 0 4px 15px rgba(255,107,0,0.4);" alt="App Logo" />
                            <h3 style="color: #ffffff; font-size: 20px; font-weight: 800; margin: 0 0 10px 0; font-family: 'Orbitron', sans-serif;">INSTALL SUDARSHAN APP</h3>
                            <p style="color: #94a3b8; font-size: 13px; line-height: 1.5; margin-bottom: 25px;">
                                Install Sudarshan Fitness directly to your phone home screen for 1-click access, instant biometric check-ins, and live workout tracking!
                            </p>
                            <button id="pwaDirectInstallBtn" onclick="triggerChromeInstall()" style="width: 100%; background: linear-gradient(135deg, #ff6b00, #ffd700); color: #ffffff; border: none; padding: 15px 20px; border-radius: 12px; font-weight: 800; font-size: 14px; cursor: pointer; font-family: 'Orbitron', sans-serif; letter-spacing: 0.5px; box-shadow: 0 5px 20px rgba(255,107,0,0.5); margin-bottom: 10px;">
                                📲 INSTALL CHROME PWA APP
                            </button>
                            <a href="download_app.php" style="width: 100%; box-sizing: border-box; background: linear-gradient(135deg, #10b981, #059669); color: #ffffff; text-decoration: none; display: block; padding: 15px 20px; border-radius: 12px; font-weight: 800; font-size: 14px; font-family: 'Orbitron', sans-serif; letter-spacing: 0.5px; box-shadow: 0 5px 20px rgba(16,185,129,0.4); margin-bottom: 12px;">
                                ⬇️ DOWNLOAD DIRECT ANDROID APK (5.3 MB)
                            </a>
                            <button onclick="closePwaModal()" style="background: transparent; color: #64748b; border: none; font-size: 12px; cursor: pointer; text-decoration: underline;">
                                Continue in Browser
                            </button>
                        </div>
                    </div>

                    <!-- Official Copyright Footer -->
                    <div style="text-align: center; margin-top: 25px; border-top: 1px solid var(--card-border); padding-top: 15px;">
                        <div style="font-size: 12px; color: var(--text-muted); font-family: 'Inter', sans-serif; letter-spacing: 0.5px;">
                            © <?php echo date('Y'); ?> <?php echo htmlspecialchars($gym['gym_name']); ?>. All Rights Reserved.
                        </div>
                        <div style="font-size: 11px; color: var(--accent-primary); font-weight: 700; font-family: 'Inter', sans-serif; margin-top: 6px; letter-spacing: 1px;">
                            POWERED BY SUDARSHAN FITNESS v2.0
                        </div>
                    </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php include './include/dev_credit.php'; ?>
</body>
</html>
