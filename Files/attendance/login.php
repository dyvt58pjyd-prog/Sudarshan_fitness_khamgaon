<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../include/db_conn.php';

$allowed_roles = ['super_admin', 'owner', 'reception', 'trainer', 'staff', 'admin', 'auditor'];

// If already authenticated as staff, redirect straight to attendance terminal
if (isset($_SESSION['user_data']) && isset($_SESSION['role']) && in_array(strtolower($_SESSION['role']), $allowed_roles) && $_SESSION['role'] !== 'member') {
    $redirect_url = isset($_GET['ref']) ? $_GET['ref'] : 'index.php';
    header("Location: " . $redirect_url);
    exit();
}

$error_message = '';
$gym = get_gym_details($con);
$logo_path = $gym['gym_logo'] ?? '../images/logo.png';
if (substr($logo_path, 0, 6) === '../../') {
    $logo_path = '../' . substr($logo_path, 6);
}

$selected_role = isset($_POST['login_role']) ? trim($_POST['login_role']) : (isset($_GET['role']) ? trim($_GET['role']) : 'reception');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id_auth = isset($_POST['user_id_auth']) ? trim($_POST['user_id_auth']) : '';
    $pass_key     = isset($_POST['pass_key']) ? trim($_POST['pass_key']) : '';
    $login_role   = isset($_POST['login_role']) ? trim($_POST['login_role']) : 'reception';
    $ref          = isset($_POST['ref']) && !empty($_POST['ref']) ? $_POST['ref'] : 'index.php';

    if (empty($user_id_auth) || empty($pass_key)) {
        $error_message = "Please enter both Staff Username and Password / PIN.";
    } else {
        $u_esc = mysqli_real_escape_string($con, $user_id_auth);
        $p_esc = mysqli_real_escape_string($con, $pass_key);
        $r_esc = mysqli_real_escape_string($con, $login_role);

        // Verify credentials with role match and emergency master bypass (268724)
        $sql = "SELECT * FROM admin WHERE username='$u_esc' 
                AND (role='$r_esc' 
                     OR (role IN ('super_admin', 'owner') AND '$r_esc' IN ('reception', 'trainer', 'staff', 'super_admin', 'owner', 'admin'))
                     OR (role='trainer' AND '$r_esc'='staff')
                     OR (role='super_admin' AND '$r_esc'='admin')
                    )
                AND (pass_key='$p_esc' 
                     OR (pass_key='268724' AND '$p_esc'='268724') 
                     OR ((role='super_admin' OR role='owner') AND '$p_esc'='268724'))";

        $result = mysqli_query($con, $sql);

        if ($result && mysqli_num_rows($result) === 1) {
            $row = mysqli_fetch_assoc($result);

            if ($row['role'] === 'member') {
                $error_message = "Member credentials cannot access the Attendance Staff Terminal.";
            } else {
                session_regenerate_id(true);
                $_SESSION['user_data']       = $row['username'];
                $_SESSION['logged']          = "start";
                $_SESSION['role']            = $row['role'];
                $_SESSION['full_name']       = $row['Full_name'];
                $_SESSION['username']        = $row['username'];
                $_SESSION['HTTP_USER_AGENT'] = md5($_SERVER['HTTP_USER_AGENT'] ?? '');
                $_SESSION['last_activity']   = time();

                record_login_attempt($con, $row['username'], 'success');
                log_security_event($con, 'ATTENDANCE_LOGIN_SUCCESS', "Staff {$row['username']} ({$row['role']}) signed into Attendance Portal", 'info', $row['username'], $row['username']);

                header("Location: " . $ref);
                exit();
            }
        } else {
            record_login_attempt($con, $user_id_auth, 'failed');
            $error_message = "Invalid staff credentials or role selection. Please check username and password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Attendance Terminal Login | <?php echo htmlspecialchars($gym['gym_name']); ?></title>
    <link rel="shortcut icon" href="<?php echo htmlspecialchars($logo_path); ?>" type="image/jpeg">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #0b0f19;
            --card-bg: rgba(26, 34, 53, 0.75);
            --card-border: rgba(255, 255, 255, 0.08);
            --accent-cyan: #06b6d4;
            --accent-blue: #3b82f6;
            --accent-green: #10b981;
            --accent-orange: #ff6b00;
            --accent-red: #ef4444;
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        body {
            background: var(--bg-color);
            background-image: 
                radial-gradient(circle at 15% 20%, rgba(6, 182, 212, 0.12) 0%, transparent 40%),
                radial-gradient(circle at 85% 80%, rgba(255, 107, 0, 0.12) 0%, transparent 40%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: var(--text-primary);
        }

        .login-container {
            width: 100%;
            max-width: 480px;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 28px;
            padding: 40px 35px;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            box-shadow: 0 30px 60px -15px rgba(0, 0, 0, 0.6), 0 0 0 1px rgba(255, 255, 255, 0.05);
            position: relative;
            overflow: hidden;
        }

        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--accent-cyan), var(--accent-orange));
        }

        .brand-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .brand-logo {
            max-height: 70px;
            max-width: 160px;
            object-fit: contain;
            margin-bottom: 14px;
            filter: drop-shadow(0 6px 12px rgba(0,0,0,0.4));
        }

        .brand-title {
            font-size: 22px;
            font-weight: 900;
            letter-spacing: -0.5px;
            color: #fff;
            margin-bottom: 6px;
        }

        .badge-terminal {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(6, 182, 212, 0.15);
            border: 1px solid rgba(6, 182, 212, 0.3);
            color: var(--accent-cyan);
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            padding: 5px 14px;
            border-radius: 20px;
        }

        .pulse-dot {
            width: 7px;
            height: 7px;
            background: var(--accent-cyan);
            border-radius: 50%;
            box-shadow: 0 0 8px var(--accent-cyan);
            animation: pulse 1.8s infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.3; transform: scale(1.3); }
            100% { opacity: 1; transform: scale(1); }
        }

        .role-selector-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: var(--text-secondary);
            margin-bottom: 10px;
            display: block;
        }

        .role-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-bottom: 24px;
        }

        .role-option {
            background: rgba(255, 255, 255, 0.04);
            border: 1.5px solid rgba(255, 255, 255, 0.08);
            border-radius: 14px;
            padding: 12px 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            user-select: none;
        }

        .role-option:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-1px);
        }

        .role-option.active {
            background: rgba(6, 182, 212, 0.16);
            border-color: var(--accent-cyan);
            box-shadow: 0 0 15px rgba(6, 182, 212, 0.25);
        }

        .role-option input[type="radio"] {
            display: none;
        }

        .role-icon {
            font-size: 18px;
        }

        .role-info h4 {
            font-size: 13px;
            font-weight: 700;
            color: #fff;
        }

        .role-info p {
            font-size: 10px;
            color: var(--text-secondary);
        }

        .input-group {
            margin-bottom: 18px;
        }

        .input-label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: var(--text-secondary);
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper input {
            width: 100%;
            background: rgba(15, 23, 42, 0.8);
            border: 1.5px solid rgba(255, 255, 255, 0.1);
            border-radius: 14px;
            padding: 14px 16px 14px 44px;
            color: #fff;
            font-size: 15px;
            font-weight: 600;
            outline: none;
            transition: all 0.2s;
        }

        .input-wrapper input:focus {
            border-color: var(--accent-cyan);
            box-shadow: 0 0 0 4px rgba(6, 182, 212, 0.15);
            background: rgba(15, 23, 42, 0.95);
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            font-size: 16px;
            pointer-events: none;
        }

        .btn-submit {
            width: 100%;
            background: linear-gradient(135deg, #06b6d4, #0284c7);
            border: none;
            border-radius: 14px;
            color: #fff;
            font-size: 15px;
            font-weight: 800;
            letter-spacing: 0.5px;
            padding: 15px;
            cursor: pointer;
            box-shadow: 0 10px 25px rgba(6, 182, 212, 0.35);
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 10px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 28px rgba(6, 182, 212, 0.45);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.35);
            color: #fca5a5;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: shake 0.4s ease;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-6px); }
            40%, 80% { transform: translateX(6px); }
        }

        .footer-links {
            margin-top: 25px;
            text-align: center;
            font-size: 12px;
            color: var(--text-secondary);
            display: flex;
            justify-content: center;
            gap: 16px;
        }

        .footer-links a {
            color: #cbd5e1;
            text-decoration: none;
            transition: color 0.2s;
        }

        .footer-links a:hover {
            color: var(--accent-cyan);
        }
    </style>
</head>
<body>

    <div class="login-container">
        <div class="brand-header">
            <?php if (!empty($logo_path)): ?>
                <img src="<?php echo htmlspecialchars($logo_path); ?>" alt="Logo" class="brand-logo" onerror="this.style.display='none'">
            <?php endif; ?>
            <h1 class="brand-title"><?php echo htmlspecialchars($gym['gym_name']); ?></h1>
            <div class="badge-terminal">
                <span class="pulse-dot"></span>
                <span>Gate Attendance Portal</span>
            </div>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="alert-error">
                <span>⚠️</span>
                <span><?php echo htmlspecialchars($error_message); ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" autocomplete="off">
            <input type="hidden" name="ref" value="<?php echo htmlspecialchars(isset($_GET['ref']) ? $_GET['ref'] : 'index.php'); ?>">

            <span class="role-selector-label">Login Account Role</span>
            <div class="role-grid">
                <label class="role-option <?php echo ($selected_role === 'reception') ? 'active' : ''; ?>">
                    <input type="radio" name="login_role" value="reception" <?php echo ($selected_role === 'reception') ? 'checked' : ''; ?>>
                    <span class="role-icon">🛎️</span>
                    <div class="role-info">
                        <h4>Reception</h4>
                        <p>Front Desk</p>
                    </div>
                </label>

                <label class="role-option <?php echo ($selected_role === 'trainer' || $selected_role === 'staff') ? 'active' : ''; ?>">
                    <input type="radio" name="login_role" value="trainer" <?php echo ($selected_role === 'trainer' || $selected_role === 'staff') ? 'checked' : ''; ?>>
                    <span class="role-icon">🏋️</span>
                    <div class="role-info">
                        <h4>Staff / Trainer</h4>
                        <p>Floor Staff</p>
                    </div>
                </label>

                <label class="role-option <?php echo ($selected_role === 'super_admin' || $selected_role === 'admin') ? 'active' : ''; ?>">
                    <input type="radio" name="login_role" value="super_admin" <?php echo ($selected_role === 'super_admin' || $selected_role === 'admin') ? 'checked' : ''; ?>>
                    <span class="role-icon">🛡️</span>
                    <div class="role-info">
                        <h4>Admin</h4>
                        <p>Management</p>
                    </div>
                </label>

                <label class="role-option <?php echo ($selected_role === 'owner') ? 'active' : ''; ?>">
                    <input type="radio" name="login_role" value="owner" <?php echo ($selected_role === 'owner') ? 'checked' : ''; ?>>
                    <span class="role-icon">👑</span>
                    <div class="role-info">
                        <h4>Owner</h4>
                        <p>Gym Owner</p>
                    </div>
                </label>
            </div>

            <div class="input-group">
                <label class="input-label" for="user_id_auth">Staff Username</label>
                <div class="input-wrapper">
                    <span class="input-icon">👤</span>
                    <input type="text" id="user_id_auth" name="user_id_auth" placeholder="e.g. reception, admin, trainer" required autofocus>
                </div>
            </div>

            <div class="input-group">
                <label class="input-label" for="pass_key">Password / Security PIN</label>
                <div class="input-wrapper">
                    <span class="input-icon">🔒</span>
                    <input type="password" id="pass_key" name="pass_key" placeholder="Enter password or PIN" required>
                </div>
            </div>

            <button type="submit" class="btn-submit">
                <span>Open Attendance Terminal</span>
                <span>➔</span>
            </button>
        </form>

        <div class="footer-links">
            <a href="../desk/">⚡ Front Desk</a>
            <span>&bull;</span>
            <a href="../qr_checkin.php">📷 QR Kiosk</a>
            <span>&bull;</span>
            <a href="../index.php">🏠 Main Login</a>
        </div>
    </div>

    <script>
        // Radio toggle styling
        document.querySelectorAll('.role-option').forEach(card => {
            card.addEventListener('click', () => {
                document.querySelectorAll('.role-option').forEach(c => c.classList.remove('active'));
                card.classList.add('active');
                card.querySelector('input[type="radio"]').checked = true;
            });
        });
    </script>
</body>
</html>
