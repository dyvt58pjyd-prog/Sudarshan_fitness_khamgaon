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
    <style>
    /* Option 2: Premium Fitness Minimalist (Apple-like) Theme */
    #titan-login-body {
        background-color: #f5f5f7 !important;
        background-image: linear-gradient(135deg, #f5f5f7 0%, #e8e8ed 100%) !important;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif !important;
    }

    #titan-login-container {
        max-width: 500px !important;
        width: 100% !important;
        background: rgba(255, 255, 255, 0.75) !important;
        backdrop-filter: blur(25px) saturate(200%) !important;
        -webkit-backdrop-filter: blur(25px) saturate(200%) !important;
        border: 1px solid rgba(255, 255, 255, 0.4) !important;
        border-radius: 24px !important;
        padding: 45px 40px !important;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08), 0 1px 3px rgba(0,0,0,0.05) !important;
        position: relative;
        z-index: 10;
        margin: 0 20px;
    }

    #titan-login-body .login-categories {
        display: grid !important;
        grid-template-columns: repeat(3, 1fr) !important;
        gap: 12px !important;
        margin-bottom: 30px !important;
    }

    #titan-login-body .category-tab {
        background: rgba(255, 255, 255, 0.5) !important;
        border: 1px solid rgba(0, 0, 0, 0.05) !important;
        border-radius: 12px !important;
        padding: 12px 6px !important;
        text-align: center !important;
        cursor: pointer !important;
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1) !important;
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 8px !important;
    }

    #titan-login-body .category-tab:hover {
        background: rgba(255, 255, 255, 0.9) !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.05) !important;
    }

    #titan-login-body .category-tab.active {
        background: #ffffff !important;
        border-color: rgba(0,0,0,0.1) !important;
        box-shadow: 0 8px 16px rgba(0,0,0,0.08) !important;
        transform: translateY(-2px);
    }

    #titan-login-body .category-tab i {
        font-size: 22px !important;
        color: #86868b !important;
        transition: color 0.3s ease;
    }
    
    #titan-login-body .category-tab.active i {
        color: #1d1d1f !important;
    }

    #titan-login-body .category-tab span {
        font-size: 11px !important;
        font-weight: 600 !important;
        color: #86868b !important;
        letter-spacing: 0.2px !important;
        transition: color 0.3s ease;
    }

    #titan-login-body .category-tab.active span {
        color: #1d1d1f !important;
    }

    #titan-login-body .form-control {
        background: rgba(255, 255, 255, 0.8) !important;
        border: 1px solid rgba(0, 0, 0, 0.1) !important;
        color: #1d1d1f !important;
        border-radius: 12px !important;
        padding: 16px 18px !important;
        font-size: 15px !important;
        margin-bottom: 20px !important;
        font-weight: 500;
        box-shadow: inset 0 1px 2px rgba(0,0,0,0.02) !important;
        transition: all 0.3s ease;
    }

    #titan-login-body .form-control::placeholder {
        color: #86868b !important;
        font-weight: 400;
    }

    #titan-login-body .form-control:focus {
        background: #ffffff !important;
        border-color: #0071e3 !important;
        box-shadow: 0 0 0 4px rgba(0, 113, 227, 0.15) !important;
        outline: none;
    }

    #titan-login-body .input-group-addon {
        background: rgba(255, 255, 255, 0.8) !important;
        border: 1px solid rgba(0, 0, 0, 0.1) !important;
        border-right: none !important;
        color: #86868b !important;
        border-radius: 12px 0 0 12px !important;
    }

    #titan-login-body .btn-primary {
        background: #1d1d1f !important;
        color: #ffffff !important;
        border: none !important;
        padding: 16px !important;
        border-radius: 14px !important;
        font-weight: 600 !important;
        font-size: 16px !important;
        width: 100% !important;
        box-shadow: 0 4px 12px rgba(29, 29, 31, 0.2) !important;
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1) !important;
    }

    #titan-login-body .btn-primary:hover {
        background: #000000 !important;
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.3) !important;
        transform: translateY(-1px);
    }
    
    #titan-login-body .industrial-title {
        color: #1d1d1f !important;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif !important;
        font-weight: 700 !important;
        font-size: 26px !important;
        letter-spacing: -0.5px !important;
        margin-top: 15px !important;
        margin-bottom: 5px !important;
    }
    </style>
</head>
<body id="titan-login-body" class="page-body login-page login-form-fall">

    <div id="container">
        <div id="titan-login-container" class="login-container">
            <div class="login-header login-caret">
                <div class="login-content" style="text-align: center;">
                    
                    <div style="margin: 0 auto 15px auto; text-align: center;">
                        <img src="./images/ganesha_gym.jpg" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid rgba(0,0,0,0.05); box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                    </div>

                    <a href="#" class="logo">
                        <img src="<?php echo htmlspecialchars($logo_path); ?>" alt="Gym Logo" style="max-height: 50px; width: auto; margin-bottom: 5px;" />
                    </a>

                    <div class="industrial-title">
                        Welcome to <?php echo htmlspecialchars($gym['gym_name']); ?>
                    </div>
                    <p class="description" style="color: #86868b; font-size: 14px; font-weight: 500; margin-bottom: 25px;">
                        Secure Access Portal
                    </p>
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
                        <div class="form-group">
                            <div class="input-group">
                                <div class="input-group-addon">
                                    <i class="entypo-user"></i>
                                </div>
                                <input type="text" placeholder="Member ID or Email" class="form-control" name="user_id_auth" id="textfield" required>
                            </div>
                        </div>

                        <!-- Password input -->
                        <div class="form-group">
                            <div class="input-group">
                                <div class="input-group-addon">
                                    <i class="entypo-lock"></i>
                                </div>
                                <input type="password" name="pass_key" id="pwfield" class="form-control" required placeholder="Password">
                            </div>
                        </div>

                        <div class="form-group" style="margin-top: 30px;">
                            <button type="submit" name="btnLogin" class="btn btn-primary" style="margin-bottom: 15px;">
                                Sign In
                            </button>
                            
                            <!-- Action Grid for Self Registration & Quick Portals -->
                            <div style="margin-top: 15px;">
                                <a href="register.php" style="background: rgba(255, 107, 0, 0.2); color: #ff6b00; border: 1.5px solid #ff6b00; font-family: 'Orbitron', sans-serif; font-weight: 900; font-size: 13px; text-decoration: none; text-align: center; padding: 13px; border-radius: 12px; display: block; box-shadow: 0 0 20px rgba(255,107,0,0.3); margin-bottom: 10px;">
                                    ✍️ SELF REGISTRATION (JOIN ACADEMY)
                                </a>
                                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
                                    <a href="guest_enquiry.php" style="background: rgba(255, 183, 3, 0.15); color: #ffb703; border: 1px solid #ffb703; font-family: 'Orbitron', sans-serif; font-weight: 800; font-size: 11px; text-decoration: none; text-align: center; padding: 11px 6px; border-radius: 12px; display: block; box-shadow: 0 0 15px rgba(255,183,3,0.2);">
                                        🎁 Free Trial
                                    </a>
                                    <a href="prebook.php" style="background: rgba(16, 185, 129, 0.15); color: #10b981; border: 1px solid #10b981; font-family: 'Orbitron', sans-serif; font-weight: 800; font-size: 11px; text-decoration: none; text-align: center; padding: 11px 6px; border-radius: 12px; display: block; box-shadow: 0 0 15px rgba(16,185,129,0.2);">
                                        ⚡ Pre-Book Slot
                                    </a>
                                </div>
                            </div>

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
