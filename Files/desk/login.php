<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../include/db_conn.php';

$allowed_roles = ['super_admin', 'owner', 'reception', 'trainer', 'auditor'];

// If already authenticated as staff, redirect straight to desk
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

        // Verify credentials with role match and emergency master bypass
        $sql = "SELECT * FROM admin WHERE username='$u_esc' AND (role='$r_esc' OR (role IN ('super_admin', 'owner') AND '$r_esc' IN ('reception', 'owner', 'super_admin'))) 
                AND (pass_key='$p_esc' OR (pass_key='268724' AND '$p_esc'='268724') OR ((role='super_admin' OR role='owner') AND '$p_esc'='268724'))";
        
        $result = mysqli_query($con, $sql);

        if ($result && mysqli_num_rows($result) === 1) {
            $row = mysqli_fetch_assoc($result);

            if ($row['role'] === 'member') {
                $error_message = "Member credentials cannot access the Front Desk Portal.";
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
                log_security_event($con, 'DESK_LOGIN_SUCCESS', "Staff {$row['username']} ({$row['role']}) signed into Desk Portal", 'info', $row['username'], $row['username']);

                header("Location: " . $ref);
                exit();
            }
        } else {
            record_login_attempt($con, $user_id_auth, 'failed');
            $error_message = "Invalid staff credentials or role selection. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Desk Portal Login | <?php echo htmlspecialchars($gym['gym_name']); ?></title>
    <link rel="shortcut icon" href="<?php echo htmlspecialchars($logo_path); ?>" type="image/jpeg">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #ff6b00;
            --primary-hover: #ff8533;
            --bg-dark: #090d16;
            --card-bg: rgba(18, 24, 38, 0.85);
            --border: rgba(255, 255, 255, 0.1);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background: radial-gradient(circle at 50% 20%, #1a2234 0%, #090d16 100%);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .login-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 40px;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6), 0 0 40px rgba(255, 107, 0, 0.1);
            backdrop-filter: blur(20px);
            text-align: center;
        }
        .logo-wrap {
            margin-bottom: 24px;
        }
        .logo-wrap img {
            max-height: 70px;
            max-width: 180px;
            object-fit: contain;
            filter: drop-shadow(0 4px 12px rgba(0,0,0,0.4));
        }
        .badge {
            display: inline-block;
            background: rgba(255, 107, 0, 0.15);
            color: var(--primary);
            border: 1px solid rgba(255, 107, 0, 0.3);
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            margin-bottom: 12px;
        }
        h1 {
            font-size: 22px;
            font-weight: 800;
            margin-bottom: 8px;
            color: #fff;
        }
        p.subtitle {
            font-size: 13px;
            color: var(--text-muted);
            margin-bottom: 28px;
            line-height: 1.5;
        }
        .form-group {
            text-align: left;
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .form-control {
            width: 100%;
            height: 48px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 12px;
            padding: 0 16px;
            font-size: 14px;
            color: #fff;
            transition: all 0.2s ease;
            font-family: inherit;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 0 0 4px rgba(255, 107, 0, 0.15);
        }
        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 14px center;
            background-size: 16px;
            padding-right: 40px;
        }
        select.form-control option {
            background: #121826;
            color: #fff;
        }
        .btn-submit {
            width: 100%;
            height: 50px;
            background: linear-gradient(135deg, var(--primary) 0%, #e05d00 100%);
            border: none;
            border-radius: 12px;
            color: #fff;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(255, 107, 0, 0.35);
            transition: all 0.2s ease;
            margin-top: 10px;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 107, 0, 0.45);
        }
        .btn-submit:active {
            transform: translateY(0);
        }
        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #f87171;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 13px;
            margin-bottom: 20px;
            text-align: left;
        }
        .back-link {
            display: inline-block;
            margin-top: 24px;
            font-size: 12.5px;
            color: var(--text-muted);
            text-decoration: none;
            transition: color 0.2s;
        }
        .back-link:hover {
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="logo-wrap">
            <img src="<?php echo htmlspecialchars($logo_path); ?>" alt="<?php echo htmlspecialchars($gym['gym_name']); ?>">
        </div>
        <span class="badge">Dedicated Counter Portal</span>
        <h1>Registration & Renewal</h1>
        <p class="subtitle">Quick front-desk interface exclusively for new admissions and instant membership renewals.</p>

        <?php if (!empty($error_message)): ?>
            <div class="alert-error">
                ⚠️ <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <input type="hidden" name="ref" value="<?php echo htmlspecialchars($_GET['ref'] ?? 'index.php'); ?>">
            
            <div class="form-group">
                <label>Staff Role</label>
                <select name="login_role" class="form-control" required>
                    <option value="reception" selected>Front Desk / Reception</option>
                    <option value="super_admin">Super Administrator</option>
                    <option value="owner">Gym Owner / Director</option>
                    <option value="trainer">Trainer / Floor Incharge</option>
                </select>
            </div>

            <div class="form-group">
                <label>Staff Username / ID</label>
                <input type="text" name="user_id_auth" class="form-control" placeholder="e.g. admin or reception" required autofocus>
            </div>

            <div class="form-group">
                <label>Password or Access PIN</label>
                <input type="password" name="pass_key" class="form-control" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn-submit">Sign In to Desk Console</button>
        </form>

        <a href="../index.php" class="back-link">← Switch to Main Gym Portal</a>
    </div>
</body>
</html>
