<?php
include './include/db_conn.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if(isset($_SESSION["user_data"]))
{
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'member') {
        header("location:./dashboard/member/");
    } else {
        header("location:./dashboard/admin/");
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
    <meta property="og:title" content="<?php echo htmlspecialchars($gym['gym_name']); ?> | Login">
    <meta property="og:description" content="Welcome to <?php echo htmlspecialchars($gym['gym_name']); ?>. Access your premium dashboard.">
    <meta property="og:image" content="<?php echo htmlspecialchars($logo_path); ?>">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">
	<title><?php echo htmlspecialchars($gym['gym_name']); ?> | Login</title>
	<link rel="shortcut icon" href="<?php echo htmlspecialchars($logo_path); ?>" type="image/jpeg">
    <link rel="manifest" href="manifest.json">
	<link rel="stylesheet" href="./css/style.css"/>
	<link rel="stylesheet" type="text/css" href="./css/entypo.css">
	<link rel="stylesheet" href="./css/premium.css"/>
    <style>
    /* Advanced Animated Background with Glowing Mesh & Lightning Flash */
    body.login-page {
        background: #000000 !important;
        position: relative;
        overflow-x: hidden;
        background-image: 
            radial-gradient(circle at 50% -20%, rgba(255, 107, 0, 0.18) 0%, transparent 50%),
            radial-gradient(circle at 0% 100%, rgba(255, 107, 0, 0.08) 0%, transparent 45%) !important;
        animation: global-glow 15s ease-in-out infinite alternate;
        transition: background-color 0.15s ease, filter 0.15s ease !important;
    }

    @keyframes global-glow {
        0% { background-color: #000000; }
        50% { background-color: #050505; }
        100% { background-color: #000000; }
    }

    /* Periodic Lightning Strike Flash Effect */
    .lightning-flash-active {
        animation: screen-lightning 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards !important;
    }

    @keyframes screen-lightning {
        0% {
            background-color: rgba(255, 255, 255, 0.12);
            filter: brightness(1.8);
        }
        10% {
            background-color: rgba(255, 107, 0, 0.08);
            filter: brightness(1.4);
        }
        15% {
            background-color: rgba(255, 255, 255, 0.15);
            filter: brightness(2.0);
        }
        30% {
            background-color: rgba(255, 107, 0, 0.04);
            filter: brightness(1.2);
        }
        100% {
            background-color: transparent;
            filter: none;
        }
    }

    /* Container electrical surge pulse */
    .login-container {
        max-width: 650px !important;
        width: 95% !important;
        border-radius: 28px !important;
        padding: 45px 35px 35px 35px !important;
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1) !important;
    }
    
    .login-container.surge-active {
        box-shadow: 0 0 50px rgba(255, 107, 0, 0.45), var(--glass-shadow) !important;
        border-color: rgba(255, 107, 0, 0.5) !important;
        transform: scale(1.01) !important;
    }

    .login-categories {
        display: grid !important;
        grid-template-columns: repeat(5, 1fr) !important;
        gap: 12px !important;
        margin-bottom: 30px !important;
    }

    @media (max-width: 600px) {
        .login-categories {
            grid-template-columns: repeat(3, 1fr) !important;
        }
    }
    @media (max-width: 440px) {
        .login-categories {
            grid-template-columns: repeat(2, 1fr) !important;
        }
    }

    .category-tab {
        background: rgba(255, 255, 255, 0.02) !important;
        border: 2px solid rgba(255, 255, 255, 0.08) !important;
        border-radius: 16px !important;
        padding: 20px 8px !important;
        text-align: center !important;
        cursor: pointer !important;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 10px !important;
        flex: none !important;
        min-width: 0 !important;
    }

    /* Category individual colors */
    .category-tab[data-role="member"]:hover, .category-tab[data-role="member"].active {
        border-color: #ff6b00 !important;
        background: linear-gradient(135deg, rgba(255, 107, 0, 0.15) 0%, rgba(255, 107, 0, 0.03) 100%) !important;
        box-shadow: 0 8px 20px rgba(255, 107, 0, 0.15) !important;
    }
    .category-tab[data-role="reception"]:hover, .category-tab[data-role="reception"].active {
        border-color: #ec4899 !important;
        background: linear-gradient(135deg, rgba(236, 72, 153, 0.15) 0%, rgba(236, 72, 153, 0.03) 100%) !important;
        box-shadow: 0 8px 20px rgba(236, 72, 153, 0.15) !important;
    }
    .category-tab[data-role="trainer"]:hover, .category-tab[data-role="trainer"].active {
        border-color: #0ea5e9 !important;
        background: linear-gradient(135deg, rgba(14, 165, 233, 0.15) 0%, rgba(14, 165, 233, 0.03) 100%) !important;
        box-shadow: 0 8px 20px rgba(14, 165, 233, 0.15) !important;
    }
    .category-tab[data-role="owner"]:hover, .category-tab[data-role="owner"].active {
        border-color: #a855f7 !important;
        background: linear-gradient(135deg, rgba(168, 85, 247, 0.15) 0%, rgba(168, 85, 247, 0.03) 100%) !important;
        box-shadow: 0 8px 20px rgba(168, 85, 247, 0.15) !important;
    }
    .category-tab[data-role="super_admin"]:hover, .category-tab[data-role="super_admin"].active {
        border-color: #10b981 !important;
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.15) 0%, rgba(16, 185, 129, 0.03) 100%) !important;
        box-shadow: 0 8px 20px rgba(16, 185, 129, 0.15) !important;
    }

    /* Category hover transforms */
    .category-tab:hover {
        transform: scale(1.05) !important;
    }

    /* Category icon/span size styling overrides */
    .category-tab i {
        font-size: 28px !important;
        transition: all 0.25s ease !important;
    }
    .category-tab span {
        font-size: 11px !important;
        font-weight: 800 !important;
        color: rgba(255, 255, 255, 0.7) !important;
        text-transform: uppercase !important;
        letter-spacing: 0.5px !important;
    }

    /* Apply active state colors to icons and spans */
    .category-tab.active span {
        color: #ffffff !important;
    }
    .category-tab[data-role="member"].active i { color: #ff6b00 !important; }
    .category-tab[data-role="reception"].active i { color: #ec4899 !important; }
    .category-tab[data-role="trainer"].active i { color: #0ea5e9 !important; }
    .category-tab[data-role="owner"].active i { color: #a855f7 !important; }
    .category-tab[data-role="super_admin"].active i { color: #10b981 !important; }

    /* Inactive icon colors */
    .category-tab:not(.active) i {
        color: rgba(255, 255, 255, 0.4) !important;
    }
    .category-tab:not(.active):hover i {
        color: rgba(255, 255, 255, 0.9) !important;
    }
    </style>
</head>
<body class="page-body login-page login-form-fall">
    <!-- Electrical Lightning Canvas Background -->
    <canvas id="lightning-canvas" style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; z-index: -1; pointer-events: none; opacity: 0.85;"></canvas>
    <div id="container">
        <div class="login-container">
            <div class="login-header login-caret">
                <div class="login-content">
                    <a href="#" class="logo">
                        <img src="<?php echo htmlspecialchars($logo_path); ?>" alt="Gym Logo" />
                    </a>
                    <p class="description">Select your category to access your <?php echo htmlspecialchars($gym['gym_name']); ?> account.</p>
                </div>
            </div>

            <div class="login-form">
                <div class="login-content">
                    <form action="secure_login.php" method="post" id="bb">
                        <!-- Hidden Input for selected role -->
                        <input type="hidden" name="login_role" id="login_role" value="<?php echo htmlspecialchars($selected_role); ?>">

                        <!-- Visual Grid of Login Categories -->
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
                            <div class="category-tab <?php echo ($selected_role === 'super_admin') ? 'active' : ''; ?>" data-role="super_admin" onclick="selectRole('super_admin')">
                                <i class="entypo-cog"></i>
                                <span>App Developer</span>
                            </div>
                        </div>

                        <!-- Username/UserID input -->
                        <div class="form-group">
                            <div class="input-group">
                                <div class="input-group-addon">
                                    <i class="entypo-user"></i>
                                </div>
                                <input type="text" placeholder="User ID / Username" class="form-control" name="user_id_auth" id="textfield" required>
                            </div>
                        </div>

                        <!-- Password input -->
                        <div class="form-group">
                            <div class="input-group">
                                <div class="input-group-addon">
                                    <i class="entypo-key"></i>
                                </div>
                                <input type="password" name="pass_key" id="pwfield" class="form-control" required placeholder="Password">
                            </div>
                        </div>

                        <div class="form-group" style="margin-top: 30px;">
                            <button type="submit" name="btnLogin" class="btn btn-primary">
                                Login In
                                <i class="entypo-login"></i>
                            </button>
                        </div>
                    </form>

                    <div class="login-bottom-links" style="text-align: center; margin-top: 15px; margin-bottom: 20px;">
                        <a href="forgot_password.php" class="link" style="font-size: 13px; color: var(--text-muted);">Forgot your password?</a>
                    </div>
                    
                    <style>
                        @keyframes intense-pulse {
                            0% { box-shadow: 0 0 10px #ff00ff, 0 0 20px #00ffff; transform: scale(1); }
                            50% { box-shadow: 0 0 30px #ff00ff, 0 0 50px #00ffff, 0 0 70px #ffeb3b; transform: scale(1.05); }
                            100% { box-shadow: 0 0 10px #ff00ff, 0 0 20px #00ffff; transform: scale(1); }
                        }
                        @keyframes color-shift {
                            0% { background-position: 0% 50%; }
                            50% { background-position: 100% 50%; }
                            100% { background-position: 0% 50%; }
                        }
                    </style>
                    <?php
                    // Check if 100 members target has been reached
                    $cnt_res = mysqli_query($con, "SELECT COUNT(*) as cnt FROM users");
                    $cnt_row = mysqli_fetch_assoc($cnt_res);
                    $total_registered = intval($cnt_row['cnt']);
                    
                    if ($total_registered >= 100):
                    ?>
                        <div style="text-align: center; margin-top: 20px; margin-bottom: 20px;">
                            <a href="register.php" class="btn" style="display: block; width: 100%; background: linear-gradient(270deg, #10b981, #059669, #10b981); background-size: 200% 200%; color: white !important; font-size: 20px; font-weight: 900; text-transform: uppercase; letter-spacing: 2px; padding: 18px 20px; border-radius: 12px; border: none; text-decoration: none; animation: intense-pulse 1.5s infinite alternate, color-shift 3s ease infinite;">
                                ⚡ SELF REGISTRATION ⚡
                            </a>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; margin-top: 20px; margin-bottom: 20px;">
                            <a href="prebook.php" class="btn" style="display: block; width: 100%; background: linear-gradient(270deg, #ff007f, #7928ca, #ff007f); background-size: 200% 200%; color: white !important; font-size: 20px; font-weight: 900; text-transform: uppercase; letter-spacing: 2px; padding: 18px 20px; border-radius: 12px; border: none; text-decoration: none; animation: intense-pulse 1.5s infinite alternate, color-shift 3s ease infinite;">
                                ⚡ PRE-BOOK NOW! ⚡
                            </a>
                        </div>
                    <?php endif; ?>

                    <div style="margin-top: 25px; text-align: center;">
                        <a href="https://drive.google.com/uc?export=download&id=1dPIrSbq6eTvq4fWcz2CnfTFAWtSoTGvl" target="_blank" class="btn btn-primary" style="background: linear-gradient(135deg, #10b981, #059669) !important; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3) !important; width: 100% !important; border: none !important;">
                            <i class="entypo-mobile" style="margin-right: 5px; font-size: 16px;"></i> Download Android App
                        </a>
                    </div>
                    
                    <div style="margin-top: 30px; text-align: center; color: var(--text-muted); font-size: 12px; font-weight: 500;">
                        System Engineered by <strong>Anurag Bawaskar</strong> <br>
                        <a href="tel:8459962390" style="color: #ff6b00; text-decoration: none;">📞 8459962390</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Script to handle dynamic tab switching -->
    <script>
        function selectRole(role) {
            document.getElementById('login_role').value = role;
            var tabs = document.querySelectorAll('.category-tab');
            tabs.forEach(function(tab) {
                if (tab.getAttribute('data-role') === role) {
                    tab.classList.add('active');
                } else {
                    tab.classList.remove('active');
                }
            });
        }
    </script>

    <?php if (isset($_GET['error']) && $_GET['error'] === 'wrong_password'): ?>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            alert("Wrong password / invalid credentials for the selected category! Please try again.");
        });
    </script>
    <?php endif; ?>

    <?php if (isset($_GET['error']) && $_GET['error'] === 'intruder_alert'): ?>
    <!-- Full-screen Intruder Alert Lock Overlay -->
    <div id="intruder-alert-overlay" style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: #0c0c0e; z-index: 999999; display: flex; flex-direction: column; justify-content: center; align-items: center; color: #fff; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; overflow: hidden;">
        <div style="text-align: center; max-width: 600px; padding: 40px; border: 2px solid #ef4444; border-radius: 20px; background: rgba(18, 18, 22, 0.95); box-shadow: 0 0 50px rgba(239, 68, 68, 0.4); backdrop-filter: blur(15px); position: relative; animation: alert-pulse 1.5s infinite alternate; margin: 20px;">
            
            <!-- Security Shield SVG Icon -->
            <div style="margin-bottom: 25px;">
                <svg width="100" height="100" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="filter: drop-shadow(0 0 15px rgba(239, 68, 68, 0.5));">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                    <line x1="12" y1="9" x2="12" y2="13" stroke-width="2"></line>
                    <circle cx="12" cy="17" r="1" fill="#ef4444"></circle>
                </svg>
            </div>
            
            <h1 style="color: #ef4444; font-size: 32px; font-weight: 800; text-transform: uppercase; margin: 0 0 10px 0; letter-spacing: 2px; text-shadow: 0 0 15px rgba(239, 68, 68, 0.3);">Access Restricted</h1>
            <h3 style="color: #f8fafc; font-size: 15px; margin: 0 0 25px 0; font-weight: 700; letter-spacing: 1px; text-transform: uppercase;">Department of Defence Facility Portal</h3>
            
            <div style="text-align: left; background: rgba(0,0,0,0.5); padding: 20px; border-radius: 12px; border: 1px solid rgba(239, 68, 68, 0.3); font-size: 13px; line-height: 1.6; margin-bottom: 25px; font-family: monospace; color: #94a3b8;">
                <span style="color: #ef4444; font-weight: bold; display: block; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 1px;">Access Audit Log Parameters:</span>
                IP Address : <span style="color: #fff; font-weight: bold;"><?php echo htmlspecialchars($_GET['ip'] ?? ''); ?></span><br>
                Timestamp  : <span style="color: #fff; font-weight: bold;"><?php echo htmlspecialchars($_GET['time'] ?? ''); ?></span><br>
                Action     : <span style="color: #fbbf24; font-weight: bold; animation: text-blink 1s infinite;">Recording connection metrics & initiating temporary lockout...</span>
            </div>
            
            <p style="color: #94a3b8; font-size: 12px; margin: 0 0 30px 0; line-height: 1.5;">
                Unauthorized login attempts on facility portals are monitored. Your connection parameters, device profile, and login attempt metadata have been logged and transmitted to system administration.
            </p>
            
            <!-- Descending countdown timer -->
            <div style="display: inline-block; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 50%; width: 100px; height: 100px; line-height: 100px; margin-bottom: 10px;">
                <span id="countdown-timer-val" style="font-size: 48px; font-weight: 800; color: #ef4444; text-shadow: 0 0 10px rgba(239, 68, 68, 0.4); font-family: monospace;">10</span>
            </div>
            <div style="font-size: 11px; text-transform: uppercase; color: #94a3b8; letter-spacing: 1px; margin-top: 5px;">Temporary Lockout Active</div>
        </div>
    </div>
    
    <style>
        @keyframes alert-pulse {
            0% { box-shadow: 0 0 30px rgba(239, 68, 68, 0.2); border-color: rgba(239, 68, 68, 0.4); }
            100% { box-shadow: 0 0 60px rgba(239, 68, 68, 0.8); border-color: rgba(239, 68, 68, 0.8); }
        }
        @keyframes text-blink {
            0%, 100% { opacity: 0.5; }
            50% { opacity: 1; }
        }
    </style>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Trigger speech alert if supported
            if ('speechSynthesis' in window) {
                var speechText = "Access restricted. Department of Defence facility portal login attempt has failed.";
                var msg = new SpeechSynthesisUtterance(speechText);
                msg.rate = 1.0;
                msg.pitch = 0.9;
                window.speechSynthesis.speak(msg);
            }
            
            var seconds = 10;
            var countdown = setInterval(function() {
                seconds--;
                var timerEl = document.getElementById('countdown-timer-val');
                if (timerEl) {
                    timerEl.innerText = seconds;
                }
                if (seconds <= 0) {
                    clearInterval(countdown);
                    var overlay = document.getElementById('intruder-alert-overlay');
                    if (overlay) {
                        overlay.style.transition = 'opacity 0.6s ease';
                        overlay.style.opacity = 0;
                        setTimeout(function() {
                            window.location.href = 'index.php?role=<?php echo urlencode($_GET['role'] ?? 'member'); ?>';
                        }, 600);
                    }
                }
            }, 1000);
        });
    </script>
    <?php endif; ?>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const canvas = document.getElementById('lightning-canvas');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        
        let width = canvas.width = window.innerWidth;
        let height = canvas.height = window.innerHeight;
        
        window.addEventListener('resize', function() {
            width = canvas.width = window.innerWidth;
            height = canvas.height = window.innerHeight;
        });

        const particles = [];
        const maxParticles = 45;
        
        class Particle {
            constructor() {
                this.x = Math.random() * width;
                this.y = Math.random() * height;
                this.vx = (Math.random() - 0.5) * 0.8;
                this.vy = (Math.random() - 0.5) * 0.8;
                this.radius = Math.random() * 2 + 1;
            }
            update() {
                this.x += this.vx;
                this.y += this.vy;
                if (this.x < 0 || this.x > width) this.vx *= -1;
                if (this.y < 0 || this.y > height) this.vy *= -1;
            }
            draw() {
                ctx.beginPath();
                ctx.arc(this.x, this.y, this.radius, 0, Math.PI * 2);
                ctx.fillStyle = 'rgba(255, 107, 0, 0.4)';
                ctx.fill();
            }
        }

        for (let i = 0; i < maxParticles; i++) {
            particles.push(new Particle());
        }

        // Function to draw jagged lightning bolt between two points
        function drawLightningBolt(x1, y1, x2, y2, segments = 5, color = 'rgba(255, 150, 0, 0.85)', widthVal = 1.5) {
            ctx.beginPath();
            ctx.moveTo(x1, y1);
            
            let currX = x1;
            let currY = y1;
            
            for (let i = 1; i < segments; i++) {
                const ratio = i / segments;
                const targetX = x1 + (x2 - x1) * ratio;
                const targetY = y1 + (y2 - y1) * ratio;
                
                // Add jitter perpendicular to line
                const offsetRange = 25 * (1 - Math.abs(ratio - 0.5) * 2);
                const jitterX = (Math.random() - 0.5) * offsetRange;
                const jitterY = (Math.random() - 0.5) * offsetRange;
                
                currX = targetX + jitterX;
                currY = targetY + jitterY;
                ctx.lineTo(currX, currY);
            }
            
            ctx.lineTo(x2, y2);
            ctx.strokeStyle = color;
            ctx.lineWidth = widthVal;
            ctx.shadowColor = '#ff6b00';
            ctx.shadowBlur = 10;
            ctx.stroke();
            
            // Reset shadow
            ctx.shadowBlur = 0;
        }

        let activeBolts = [];

        // Trigger electrical strike and flash
        function triggerLightning() {
            // Apply flash style to body
            document.body.classList.add('lightning-flash-active');
            const container = document.querySelector('.login-container');
            if (container) {
                container.classList.add('surge-active');
            }
            
            setTimeout(() => {
                document.body.classList.remove('lightning-flash-active');
                if (container) {
                    container.classList.remove('surge-active');
                }
            }, 600);

            // Generate 3-5 random lightning arcs
            const boltsCount = Math.floor(Math.random() * 3) + 2;
            activeBolts = [];
            
            for (let i = 0; i < boltsCount; i++) {
                const startNode = particles[Math.floor(Math.random() * particles.length)];
                const endNode = particles[Math.floor(Math.random() * particles.length)];
                if (startNode !== endNode) {
                    activeBolts.push({
                        x1: startNode.x,
                        y1: startNode.y,
                        x2: endNode.x,
                        y2: endNode.y,
                        segments: Math.floor(Math.random() * 4) + 4,
                        width: Math.random() * 2 + 1,
                        life: 15
                    });
                }
            }
        }

        // Set interval for periodic lightning strikes (every 8 seconds average)
        setInterval(() => {
            if (Math.random() > 0.3) {
                triggerLightning();
            }
        }, 8000);

        // Also trigger on click/tap for awesome user interaction!
        window.addEventListener('click', (e) => {
            // Only trigger if click is outside login container
            const container = document.querySelector('.login-container');
            if (container && !container.contains(e.target)) {
                triggerLightning();
            }
        });

        function animate() {
            ctx.clearRect(0, 0, width, height);
            
            // Update and draw nodes
            particles.forEach(p => {
                p.update();
                p.draw();
            });
            
            // Draw mesh lines
            for (let i = 0; i < particles.length; i++) {
                for (let j = i + 1; j < particles.length; j++) {
                    const dist = Math.hypot(particles[i].x - particles[j].x, particles[i].y - particles[j].y);
                    if (dist < 130) {
                        ctx.beginPath();
                        ctx.moveTo(particles[i].x, particles[i].y);
                        ctx.lineTo(particles[j].x, particles[j].y);
                        const alpha = (1 - dist / 130) * 0.15;
                        ctx.strokeStyle = `rgba(255, 107, 0, ${alpha})`;
                        ctx.lineWidth = 1;
                        ctx.stroke();
                    }
                }
            }
            
            // Draw active lightning bolts
            activeBolts.forEach((bolt, idx) => {
                drawLightningBolt(bolt.x1, bolt.y1, bolt.x2, bolt.y2, bolt.segments, `rgba(255, 175, 50, ${bolt.life / 15})`, bolt.width);
                bolt.life--;
                if (bolt.life <= 0) {
                    activeBolts.splice(idx, 1);
                }
            });
            
            requestAnimationFrame(animate);
        }
        
        animate();
    });
    </script>
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('sw.js')
                    .then(registration => console.log('ServiceWorker registered:', registration))
                    .catch(error => console.log('ServiceWorker registration failed:', error));
            });
        }
    </script>
</body>
</html>
