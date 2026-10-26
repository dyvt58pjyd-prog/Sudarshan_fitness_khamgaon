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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="./css/style.css"/>
	<link rel="stylesheet" type="text/css" href="./css/entypo.css">
	<link rel="stylesheet" href="./css/premium.css"/>
    <script src="./js/theme_engine.js"></script>
    <style>
    /* Naruto Theme Login Aesthetic */
    #titan-login-body {
        background-color: var(--bg-dark) !important;
        background-image: 
            radial-gradient(circle at 50% 10%, rgba(255, 123, 0, 0.2) 0%, transparent 60%),
            radial-gradient(circle at 90% 80%, rgba(30, 144, 255, 0.15) 0%, transparent 50%) !important;
        background-attachment: fixed !important;
        background-size: cover !important;
        min-height: 100vh;
        display: block;
        margin: 0;
        padding: 40px 20px;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif, sans-serif !important;
        box-sizing: border-box;
    }

    #titan-login-container {
        max-width: 480px !important;
        width: 100% !important;
        margin: 0 auto;
        background: var(--glass-bg) !important;
        backdrop-filter: blur(15px) !important;
        -webkit-backdrop-filter: blur(15px) !important;
        border: 1px solid var(--glass-border) !important;
        border-radius: 20px !important;
        padding: 40px 35px !important;
        box-shadow: var(--glass-shadow) !important;
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
        background: rgba(255, 123, 0, 0.05) !important;
        border: 1px solid rgba(255, 123, 0, 0.2) !important;
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
        background: rgba(255, 123, 0, 0.1) !important;
        transform: scale(1.02);
    }

    #titan-login-body .category-tab.active {
        background: rgba(255, 123, 0, 0.2) !important;
        border-color: var(--accent-primary) !important;
        box-shadow: 0 4px 12px rgba(255, 123, 0, 0.3) !important;
        transform: scale(1.05);
    }

    #titan-login-body .category-tab i {
        font-size: 20px !important;
        color: var(--text-muted) !important;
        transition: color 0.3s ease;
    }
    
    #titan-login-body .category-tab.active i {
        color: var(--accent-primary) !important;
    }

    #titan-login-body .category-tab span {
        font-size: 10px !important;
        font-weight: 700 !important;
        color: var(--text-muted) !important;
        letter-spacing: 0px !important;
        transition: color 0.3s ease;
    }

    #titan-login-body .category-tab.active span {
        color: var(--text-main) !important;
    }

    /* Override input group styles completely */
    #titan-login-body .input-group {
        display: flex !important;
        align-items: center !important;
        background: rgba(0, 0, 0, 0.4) !important;
        border: 1.5px solid rgba(255, 123, 0, 0.2) !important;
        border-radius: 16px !important;
        margin-bottom: 16px !important;
        padding: 4px 12px !important;
        transition: all 0.3s ease !important;
        box-shadow: 0 2px 6px rgba(0,0,0,0.2) !important;
    }

    #titan-login-body .input-group:focus-within {
        border-color: var(--accent-primary) !important;
        box-shadow: 0 0 0 4px rgba(255, 123, 0, 0.15) !important;
        background: rgba(0, 0, 0, 0.6) !important;
    }

    #titan-login-body .input-group-addon {
        background: transparent !important;
        border: none !important;
        padding: 0 10px 0 4px !important;
        color: var(--text-muted) !important;
        font-size: 18px !important;
    }

    #titan-login-body .form-control {
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
        color: var(--text-main) !important;
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
        color: var(--text-muted) !important;
        font-weight: 400 !important;
    }

    #titan-login-body .btn-primary {
        background: var(--accent-primary) !important;
        color: #ffffff !important;
        border: none !important;
        padding: 16px !important;
        border-radius: 16px !important;
        font-weight: 600 !important;
        font-size: 16px !important;
        width: 100% !important;
        box-shadow: 0 8px 20px rgba(255, 123, 0, 0.3) !important;
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1) !important;
        margin-top: 8px !important;
    }

    #titan-login-body .btn-primary:hover {
        background: var(--accent-hover) !important;
        box-shadow: 0 10px 25px rgba(255, 123, 0, 0.4) !important;
        transform: translateY(-2px);
    }
    
    #titan-login-body .industrial-title {
        color: var(--text-main) !important;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif, sans-serif !important;
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
        border: 1px solid rgba(255,123,0,0.2) !important;
        transition: all 0.3s ease !important;
    }

    /* Ultra-Fast Luxury Apple Face ID Styles */
    .btn-faceid-scan {
        width: 100% !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 10px !important;
        margin-top: 14px !important;
        background: linear-gradient(135deg, rgba(255, 107, 0, 0.12), rgba(15, 23, 42, 0.75)) !important;
        border: 1.5px solid rgba(255, 107, 0, 0.35) !important;
        color: #ffffff !important;
        padding: 13px 20px !important;
        border-radius: 14px !important;
        font-family: 'Inter', sans-serif !important;
        font-size: 13.5px !important;
        font-weight: 700 !important;
        letter-spacing: 0.5px !important;
        cursor: pointer !important;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
        box-shadow: 0 4px 18px rgba(255, 107, 0, 0.12) !important;
        backdrop-filter: blur(8px) !important;
    }
    .btn-faceid-scan:hover {
        background: linear-gradient(135deg, rgba(255, 107, 0, 0.25), rgba(15, 23, 42, 0.95)) !important;
        border-color: #ff6b00 !important;
        transform: translateY(-2px) !important;
        box-shadow: 0 8px 24px rgba(255, 107, 0, 0.25) !important;
    }
    .faceid-glyph {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #ff6b00;
        transition: transform 0.3s ease;
    }
    .btn-faceid-scan:hover .faceid-glyph {
        transform: scale(1.1);
        color: #ff9500;
    }

    /* Face ID Scanning Overlay HUD */
    #faceScanContainer {
        display: none;
        position: fixed;
        inset: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(8, 12, 22, 0.94);
        backdrop-filter: blur(24px);
        -webkit-backdrop-filter: blur(24px);
        z-index: 99999;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        padding: 20px;
    }

    .faceid-hud-card {
        background: rgba(15, 23, 42, 0.88);
        border: 1.5px solid rgba(255, 255, 255, 0.12);
        border-radius: 28px;
        padding: 35px 30px;
        max-width: 420px;
        width: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.8), 0 0 40px rgba(255, 107, 0, 0.15);
        position: relative;
    }

    .faceid-hud-title {
        color: #ffffff;
        font-size: 18px;
        font-weight: 800;
        letter-spacing: -0.3px;
        margin: 0 0 6px 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .faceid-hud-subtitle {
        color: #94a3b8;
        font-size: 12px;
        margin: 0 0 16px 0;
    }

    .faceid-viewfinder-wrap {
        position: relative;
        width: 240px;
        height: 240px;
        border-radius: 50%;
        margin: 10px auto 20px auto;
        overflow: hidden;
        border: 3px solid rgba(255, 107, 0, 0.4);
        box-shadow: 0 0 30px rgba(255, 107, 0, 0.25);
        transition: all 0.3s ease;
    }
    .faceid-viewfinder-wrap.success {
        border-color: #10b981 !important;
        box-shadow: 0 0 40px rgba(16, 185, 129, 0.5) !important;
    }
    .faceid-viewfinder-wrap.error {
        border-color: #ef4444 !important;
        box-shadow: 0 0 40px rgba(239, 68, 68, 0.5) !important;
    }

    .faceid-laser-sweep {
        position: absolute;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, transparent, #ff6b00, #38bdf8, transparent);
        box-shadow: 0 0 12px #ff6b00;
        animation: laserScan 2s ease-in-out infinite alternate;
        z-index: 5;
    }
    @keyframes laserScan {
        0% { top: 8%; opacity: 0.3; }
        50% { opacity: 1; }
        100% { top: 92%; opacity: 0.3; }
    }

    .faceid-status-text {
        color: #e2e8f0;
        font-size: 13.5px;
        font-weight: 600;
        margin-bottom: 20px;
        min-height: 20px;
    }

    .btn-faceid-cancel {
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.15);
        color: #94a3b8;
        padding: 9px 24px;
        border-radius: 12px;
        font-size: 12.5px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }
    .btn-faceid-cancel:hover {
        background: rgba(255, 255, 255, 0.15);
        color: #ffffff;
    }
    </style>
</head>
<body id="titan-login-body" class="page-body login-page login-form-fall">

    <div id="container">
        <div id="titan-login-container" class="login-container">
            <div class="login-header login-caret">
                <div class="login-content" style="text-align: center;">

                    <div style="display: flex; justify-content: center; align-items: center; margin-bottom: 15px;">
                        <img src="<?php echo htmlspecialchars($logo_path); ?>" alt="Gym Logo" style="height: 60px; width: auto; border-radius: 8px; z-index: 1;" />
                    </div>

                    <div class="industrial-title">
                        <?php echo htmlspecialchars($gym['gym_name']); ?>
                    </div>
                    <p class="description" style="color: var(--text-muted); font-size: 13px; font-weight: 600; margin-bottom: 20px; text-transform: uppercase; letter-spacing: 1px;">
                        Secure Access Portal
                    </p>
                    
                    <!-- Quick Theme Selector Pill -->
                    <div style="margin-bottom: 20px;">
                        <select id="sf-theme-select-login" onchange="SFThemeEngine.setThemeMode(this.value)" style="background: rgba(255, 123, 0, 0.1); color: #fff; border: 1.5px solid #ff7b00; border-radius: 12px; padding: 6px 14px; font-size: 12px; font-weight: 700; cursor: pointer; outline: none; box-shadow: 0 4px 12px rgba(255,123,0,0.3);">
                            <option value="dark" style="color: #000;">🌙 Dark Mode (Naruto)</option>
                            <option value="system" style="color: #000;">💻 System Mode</option>
                            <option value="light" style="color: #000;">☀️ Light Mode</option>
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
                            
                            <!-- Action Button for Fast In-App Face ID -->
                            <button type="button" id="faceIdLoginBtn" class="btn-faceid-scan" onclick="loginWithFaceID()">
                                <span class="faceid-glyph">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/>
                                        <circle cx="9" cy="9" r="1" fill="currentColor"/>
                                        <circle cx="15" cy="9" r="1" fill="currentColor"/>
                                        <path d="M9.5 15a3.5 3.5 0 0 0 5 0M12 11v2.5"/>
                                    </svg>
                                </span>
                                <span>Sign In with Face ID</span>
                            </button>
                        </div>

                    <!-- Face-api.js script -->
                    <script defer src="js/face-api/face-api.min.js"></script>

                    <!-- Face Scan UI Container (Apple Executive Biometric HUD) -->
                    <div id="faceScanContainer">
                        <div class="faceid-hud-card">
                            <div class="faceid-hud-title">
                                <span class="faceid-glyph" style="color:#ff6b00;">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"/>
                                        <circle cx="9" cy="9" r="1" fill="currentColor"/>
                                        <circle cx="15" cy="9" r="1" fill="currentColor"/>
                                        <path d="M9.5 15a3.5 3.5 0 0 0 5 0M12 11v2.5"/>
                                    </svg>
                                </span>
                                <span>Face ID Verification</span>
                            </div>
                            <div class="faceid-hud-subtitle">Look directly into the sensor circle</div>

                            <div class="faceid-viewfinder-wrap" id="faceViewfinder">
                                <video id="loginVideo" autoplay muted playsinline style="width: 100%; height: 100%; object-fit: cover; transform: scaleX(-1);"></video>
                                <div class="faceid-laser-sweep" id="faceLaser"></div>
                            </div>

                            <div id="loginStatusMsg" class="faceid-status-text">⚡ Sensors Ready • Position face in circle</div>
                            <button type="button" class="btn-faceid-cancel" onclick="cancelFaceLogin()">Cancel</button>
                        </div>
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

                    // Blazing-Fast Local Face Model Loader (Zero CDN Lag)
                    async function loadLoginModels() {
                        if (loginModelsLoaded || loginModelsLoading) return;
                        loginModelsLoading = true;
                        const localUri = 'js/face-api/models_v2';
                        try {
                            if (typeof faceapi === 'undefined') {
                                await new Promise(r => setTimeout(r, 150));
                            }
                            if (typeof faceapi !== 'undefined') {
                                await Promise.all([
                                    faceapi.nets.tinyFaceDetector.loadFromUri(localUri),
                                    faceapi.nets.faceLandmark68TinyNet.loadFromUri(localUri),
                                    faceapi.nets.faceRecognitionNet.loadFromUri(localUri)
                                ]);
                                loginModelsLoaded = true;
                                console.log("⚡ Face ID local neural models loaded successfully.");
                            }
                        } catch (err) {
                            console.warn("Local face model load error:", err);
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
                        pwfield.placeholder = 'Enter 6-Digit Security PIN';

                        const faceBtn = document.getElementById('faceIdLoginBtn');
                        if (faceBtn) faceBtn.style.display = 'flex';
                        loadLoginModels();
                    }

                    async function loginWithFaceID() {
                        const scanContainer = document.getElementById('faceScanContainer');
                        const video = document.getElementById('loginVideo');
                        const statusMsg = document.getElementById('loginStatusMsg');
                        const viewfinder = document.getElementById('faceViewfinder');

                        if (viewfinder) viewfinder.className = 'faceid-viewfinder-wrap';
                        scanContainer.style.display = 'flex';
                        statusMsg.innerHTML = '⚡ Initializing camera sensor...';

                        try {
                            if (!loginModelsLoaded) {
                                statusMsg.innerHTML = '⚡ Priming optical neural models...';
                                await loadLoginModels();
                            }

                            loginStream = await navigator.mediaDevices.getUserMedia({ 
                                video: { 
                                    width: { ideal: 480 }, 
                                    height: { ideal: 480 }, 
                                    facingMode: 'user' 
                                },
                                audio: false
                            });
                            video.srcObject = loginStream;
                            statusMsg.innerHTML = '🔍 Align your face inside the circle...';

                            let isProcessing = false;
                            loginScanInterval = setInterval(async () => {
                                if (!video.paused && !video.ended && !isProcessing) {
                                    isProcessing = true;
                                    try {
                                        // High-speed tiny detector running at 224x224
                                        const options = new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.45 });
                                        const detection = await faceapi.detectSingleFace(video, options).withFaceLandmarks(true).withFaceDescriptor();
                                        
                                        if (detection) {
                                            statusMsg.innerHTML = '<span style="color:#ff9500;">⚡ Face detected! Verifying biometric identity...</span>';
                                            clearInterval(loginScanInterval);
                                            
                                            const descriptorArray = Array.from(detection.descriptor);
                                            const roleVal = document.getElementById('login_role').value || 'member';
                                            
                                            fetch('face_login_verify.php', {
                                                method: 'POST',
                                                headers: { 'Content-Type': 'application/json' },
                                                body: JSON.stringify({ 
                                                    descriptor: descriptorArray,
                                                    requested_role: roleVal
                                                })
                                            })
                                            .then(res => res.json())
                                            .then(data => {
                                                if (data.status === 'success') {
                                                    if (viewfinder) viewfinder.classList.add('success');
                                                    statusMsg.innerHTML = '<span style="color:#10b981; font-weight:800;">✅ Verified: Welcome, ' + (data.member_name || 'Member') + '!</span>';
                                                    setTimeout(() => {
                                                        cancelFaceLogin();
                                                        window.location.href = data.redirect || './dashboard/member/';
                                                    }, 500);
                                                } else {
                                                    if (viewfinder) viewfinder.classList.add('error');
                                                    statusMsg.innerHTML = '<span style="color:#ef4444; font-weight:700;">❌ ' + (data.message || 'Face Not Recognized') + '</span>';
                                                    setTimeout(() => {
                                                        if (viewfinder) viewfinder.classList.remove('error');
                                                        // Resume scanning if modal still active
                                                        if (scanContainer.style.display !== 'none') {
                                                            loginWithFaceID();
                                                        }
                                                    }, 2200);
                                                }
                                            })
                                            .catch(err => {
                                                if (viewfinder) viewfinder.classList.add('error');
                                                statusMsg.innerHTML = '<span style="color:#ef4444;">Verification network error.</span>';
                                                setTimeout(cancelFaceLogin, 2500);
                                            });
                                        }
                                    } catch (e) {
                                        console.warn("Scan frame error:", e);
                                    } finally {
                                        isProcessing = false;
                                    }
                                }
                            }, 120);

                        } catch (err) {
                            alert('Camera Notice: ' + err.message + '\nPlease grant camera permissions to use Face ID login.');
                            cancelFaceLogin();
                        }
                    }

                    function cancelFaceLogin() {
                        if (loginScanInterval) {
                            clearInterval(loginScanInterval);
                            loginScanInterval = null;
                        }
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
                            <h3 style="color: #ffffff; font-size: 20px; font-weight: 800; margin: 0 0 10px 0; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif, sans-serif;">INSTALL SUDARSHAN APP</h3>
                            <p style="color: #94a3b8; font-size: 13px; line-height: 1.5; margin-bottom: 25px;">
                                Install Sudarshan Fitness directly to your phone home screen for 1-click access, instant biometric check-ins, and live workout tracking!
                            </p>
                            <button id="pwaDirectInstallBtn" onclick="triggerChromeInstall()" style="width: 100%; background: linear-gradient(135deg, #ff6b00, #ffd700); color: #ffffff; border: none; padding: 15px 20px; border-radius: 12px; font-weight: 800; font-size: 14px; cursor: pointer; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif, sans-serif; letter-spacing: 0.5px; box-shadow: 0 5px 20px rgba(255,107,0,0.5); margin-bottom: 10px;">
                                📲 INSTALL CHROME PWA APP
                            </button>
                            <a href="download_app.php" style="width: 100%; box-sizing: border-box; background: linear-gradient(135deg, #10b981, #059669); color: #ffffff; text-decoration: none; display: block; padding: 15px 20px; border-radius: 12px; font-weight: 800; font-size: 14px; font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif, sans-serif; letter-spacing: 0.5px; box-shadow: 0 5px 20px rgba(16,185,129,0.4); margin-bottom: 12px;">
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
