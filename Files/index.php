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
    <title><?php echo htmlspecialchars($gym['gym_name']); ?> | Login</title>
    <link rel="manifest" href="manifest.json">
	<link rel="stylesheet" type="text/css" href="./css/entypo.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
        }

        body {
            height: 100vh;
            width: 100vw;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #2b1b3d 0%, #3a2a4b 50%, #4b5a7b 100%);
            background-size: cover;
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            top: -10%;
            left: 20%;
            width: 70vw;
            height: 70vw;
            background: radial-gradient(circle, rgba(140, 50, 70, 0.4) 0%, rgba(0,0,0,0) 60%);
            z-index: 0;
        }
        body::after {
            content: '';
            position: absolute;
            bottom: -20%;
            right: -10%;
            width: 60vw;
            height: 60vw;
            background: radial-gradient(circle, rgba(60, 80, 140, 0.5) 0%, rgba(0,0,0,0) 60%);
            z-index: 0;
        }

        .login-wrapper {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 420px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 30px;
            padding: 40px;
            box-shadow: 0 25px 45px rgba(0,0,0,0.2);
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .avatar-circle {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 40px;
            color: rgba(255,255,255,0.3);
            font-size: 50px;
            overflow: hidden;
        }

        .avatar-circle i {
            margin-top: 10px;
        }

        form {
            width: 100%;
        }

        .input-group {
            position: relative;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.8);
            padding-bottom: 8px;
        }

        .input-group i {
            color: rgba(255, 255, 255, 0.8);
            font-size: 16px;
            margin-right: 12px;
        }

        .input-group input, .input-group select {
            background: transparent;
            border: none;
            outline: none;
            color: #fff;
            font-size: 15px;
            width: 100%;
            font-weight: 300;
        }
        
        .input-group select option {
            background: #2b1b3d;
            color: #fff;
        }

        .input-group input::placeholder {
            color: rgba(255, 255, 255, 0.8);
        }

        .form-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 35px;
            font-size: 12px;
        }

        .checkbox-container {
            display: flex;
            align-items: center;
            gap: 8px;
            color: rgba(255, 255, 255, 0.6);
            cursor: pointer;
        }

        .checkbox-container input {
            appearance: none;
            width: 14px;
            height: 14px;
            background: rgba(0,0,0,0.3);
            border-radius: 3px;
            position: relative;
            cursor: pointer;
        }

        .checkbox-container input:checked::after {
            content: '✓';
            position: absolute;
            color: white;
            font-size: 10px;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .forgot-link {
            color: rgba(255, 255, 255, 0.6);
            text-decoration: none;
            font-style: italic;
        }

        .forgot-link:hover {
            color: #fff;
        }

        .login-btn {
            width: 100%;
            padding: 15px;
            border-radius: 12px;
            border: none;
            background: linear-gradient(to right, #401030, #5c6bc0);
            color: white;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 2px;
            cursor: pointer;
            transition: opacity 0.3s;
        }

        .login-btn:hover {
            opacity: 0.9;
        }

        .extra-buttons {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 5px;
        }

        .btn-outline {
            width: 100%;
            padding: 12px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: rgba(255, 255, 255, 0.8);
            font-size: 12px;
            font-weight: 500;
            text-align: center;
            text-decoration: none;
            transition: all 0.2s;
            backdrop-filter: blur(10px);
        }

        .btn-outline:hover {
            background: rgba(255, 255, 255, 0.15);
            color: #fff;
        }

        .btn-outline i {
            margin-right: 5px;
        }
        
        .brand-text {
            text-align: center;
            color: rgba(255,255,255,0.4);
            font-size: 11px;
            margin-top: 15px;
        }
    </style>
</head>
<body>

    <div class="login-wrapper">
        <div class="glass-card">
            
            <div class="avatar-circle">
                <i class="entypo-user"></i>
            </div>

            <form action="secure_login.php" method="post">
                <div class="input-group">
                    <i class="entypo-users"></i>
                    <select name="login_role" required>
                        <option value="member" <?php if($selected_role=='member') echo 'selected'; ?>>Gym Member</option>
                        <option value="reception" <?php if($selected_role=='reception') echo 'selected'; ?>>Reception</option>
                        <option value="trainer" <?php if($selected_role=='trainer') echo 'selected'; ?>>Trainer</option>
                        <option value="owner" <?php if($selected_role=='owner') echo 'selected'; ?>>Owner</option>
                        <option value="auditor" <?php if($selected_role=='auditor') echo 'selected'; ?>>Auditor</option>
                        <option value="super_admin" <?php if($selected_role=='super_admin') echo 'selected'; ?>>App Developer</option>
                    </select>
                </div>

                <div class="input-group">
                    <i class="entypo-mail"></i>
                    <input type="text" name="user_id_auth" placeholder="Email ID / User ID" required>
                </div>

                <div class="input-group">
                    <i class="entypo-lock"></i>
                    <input type="password" name="pass_key" placeholder="Password" required>
                </div>

                <div class="form-actions">
                    <label class="checkbox-container">
                        <input type="checkbox">
                        Remember me
                    </label>
                    <a href="forgot_password.php" class="forgot-link">Forgot Password?</a>
                </div>

                <button type="submit" name="btnLogin" class="login-btn">LOGIN</button>
            </form>
        </div>

        <div class="extra-buttons">
            <?php
            $cnt_res = mysqli_query($con, "SELECT COUNT(*) as cnt FROM users");
            $cnt_row = mysqli_fetch_assoc($cnt_res);
            $total_registered = intval($cnt_row['cnt']);
            
            if ($total_registered >= 100):
            ?>
                <a href="register.php" class="btn-outline">
                    <i class="entypo-user-add"></i> Member Self Registration
                </a>
            <?php else: ?>
                <a href="prebook.php" class="btn-outline">
                    <i class="entypo-star"></i> Pre-book Membership
                </a>
            <?php endif; ?>

            <a href="https://play.google.com/apps/test/com.sudarshanfitness.portal/1" target="_blank" class="btn-outline">
                <i class="entypo-mobile"></i> Download Android App
            </a>
        </div>
        
        <div class="brand-text">
            <?php echo htmlspecialchars($gym['gym_name']); ?>
        </div>
    </div>

    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('sw.js')
                    .then(registration => console.log('ServiceWorker registered:', registration))
                    .catch(error => console.log('ServiceWorker registration failed:', error));
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
    <div id="intruder-alert-overlay" style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: #0c0c0e; z-index: 999999; display: flex; flex-direction: column; justify-content: center; align-items: center; color: #fff; font-family: 'Inter', sans-serif; overflow: hidden;">
        <div style="text-align: center; max-width: 600px; padding: 40px; border: 2px solid #ef4444; border-radius: 20px; background: rgba(18, 18, 22, 0.95); box-shadow: 0 0 50px rgba(239, 68, 68, 0.4); backdrop-filter: blur(15px); position: relative; animation: alert-pulse 1.5s infinite alternate; margin: 20px;">
            <h1 style="color: #ef4444; font-size: 32px; font-weight: 800; text-transform: uppercase; margin: 0 0 10px 0; letter-spacing: 2px;">Access Restricted</h1>
            <p style="color: #94a3b8; font-size: 12px; margin: 0 0 30px 0; line-height: 1.5;">
                Unauthorized login attempts are monitored. Your connection parameters have been logged.
            </p>
            <div style="display: inline-block; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 50%; width: 100px; height: 100px; line-height: 100px; margin-bottom: 10px;">
                <span id="countdown-timer-val" style="font-size: 48px; font-weight: 800; color: #ef4444; font-family: monospace;">10</span>
            </div>
            <div style="font-size: 11px; text-transform: uppercase; color: #94a3b8; letter-spacing: 1px; margin-top: 5px;">Temporary Lockout Active</div>
        </div>
    </div>
    
    <style>
        @keyframes alert-pulse {
            0% { box-shadow: 0 0 30px rgba(239, 68, 68, 0.2); border-color: rgba(239, 68, 68, 0.4); }
            100% { box-shadow: 0 0 60px rgba(239, 68, 68, 0.8); border-color: rgba(239, 68, 68, 0.8); }
        }
    </style>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var seconds = 10;
            var countdown = setInterval(function() {
                seconds--;
                var timerEl = document.getElementById('countdown-timer-val');
                if (timerEl) timerEl.innerText = seconds;
                if (seconds <= 0) {
                    clearInterval(countdown);
                    window.location.href = 'index.php';
                }
            }, 1000);
        });
    </script>
    <?php endif; ?>
</body>
</html>
