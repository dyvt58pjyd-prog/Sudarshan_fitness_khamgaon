<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../include/db_conn.php';

$sanitize_ref = function($raw_ref) {
    $r = trim((string)$raw_ref);
    if ($r === '' || preg_match('#^(https?:)?//#i', $r) || strpos($r, "\n") !== false || strpos($r, "\r") !== false) {
        return 'index.php';
    }
    return $r;
};

$allowed_roles = ['super_admin', 'owner', 'reception'];
if (isset($_SESSION['user_data'], $_SESSION['logged'], $_SESSION['role']) && in_array(strtolower(trim((string)$_SESSION['role'])), $allowed_roles, true)) {
    $redirect_url = $sanitize_ref($_GET['ref'] ?? 'index.php');
    header("Location: " . $redirect_url);
    exit();
}

$gym = get_gym_details($con);
$logo_path = resolve_gym_logo_url($gym, '../', false);
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username   = trim((string)($_POST['username'] ?? ''));
    $pass_key   = trim((string)($_POST['pass_key'] ?? ''));
    $login_role = strtolower(trim((string)($_POST['login_role'] ?? 'reception')));
    $ref        = $sanitize_ref($_POST['ref'] ?? 'index.php');

    if ($username === '' || $pass_key === '') {
        $error_message = 'Please enter your staff username and password.';
    } elseif (!in_array($login_role, $allowed_roles, true)) {
        $error_message = 'Invalid portal role selected.';
    } else {
        $u_esc = mysqli_real_escape_string($con, $username);
        $p_esc = mysqli_real_escape_string($con, $pass_key);
        $r_esc = mysqli_real_escape_string($con, $login_role);

        $sql = "SELECT * FROM admin 
                WHERE username = '$u_esc' 
                  AND pass_key = '$p_esc'
                  AND (role = '$r_esc' OR role IN ('super_admin', 'owner'))
                  AND role IN ('super_admin', 'owner', 'reception')
                LIMIT 1";
        $res = mysqli_query($con, $sql);

        if ($res && mysqli_num_rows($res) === 1) {
            $row = mysqli_fetch_assoc($res);
            session_regenerate_id(true);
            $_SESSION['user_data']       = $row['username'];
            $_SESSION['logged']          = 'start';
            $_SESSION['role']            = strtolower(trim((string)$row['role']));
            $_SESSION['full_name']       = $row['Full_name'] ?: $row['username'];
            $_SESSION['username']        = $row['username'];
            $_SESSION['HTTP_USER_AGENT'] = md5($_SERVER['HTTP_USER_AGENT'] ?? '');
            $_SESSION['last_activity']   = time();

            if (function_exists('record_login_attempt')) {
                record_login_attempt($con, $row['username'], 'success');
            }
            if (function_exists('log_security_event')) {
                log_security_event($con, 'WALLET_PORTAL_LOGIN', "Staff {$row['username']} ({$row['role']}) signed into Wallet Pass Portal", 'info', $row['username'], $row['username']);
            }
            header("Location: " . $ref);
            exit();
        } else {
            if (function_exists('record_login_attempt')) {
                record_login_attempt($con, $username, 'failed');
            }
            $error_message = 'Invalid staff credentials or insufficient role permissions.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Staff Login | Wallet Pass Portal — <?php echo htmlspecialchars($gym['gym_name']); ?></title>
    <link rel="shortcut icon" href="<?php echo htmlspecialchars($logo_path); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; }
        body {
            margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px;
            background: radial-gradient(circle at top right, rgba(255,107,0,0.18), transparent 45%), #0b0f19; color: #f8fafc;
        }
        .login-card {
            width: 100%; max-width: 410px; background: rgba(15, 23, 42, 0.94); border: 1px solid rgba(255,255,255,0.12);
            border-radius: 24px; padding: 34px 28px; box-shadow: 0 25px 60px rgba(0,0,0,0.65);
        }
        .brand { text-align: center; margin-bottom: 24px; }
        .brand img { width: 78px; height: 78px; object-fit: cover; border-radius: 50%; border: 2px solid #ff6b00; background: #fff; box-shadow: 0 8px 25px rgba(255,107,0,0.3); }
        .brand h1 { font-size: 20px; font-weight: 900; margin: 12px 0 4px; }
        .brand p { font-size: 12px; color: #94a3b8; margin: 0; font-weight: 600; }
        .form-group { margin-bottom: 16px; }
        label { display: block; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.7px; color: #94a3b8; margin-bottom: 6px; }
        .input {
            width: 100%; padding: 12px 14px; border-radius: 12px; border: 1px solid #334155; background: #0f172a;
            color: #fff; font-size: 14px; outline: none; transition: border-color .2s;
        }
        .input:focus { border-color: #ff6b00; }
        .btn-submit {
            width: 100%; padding: 13px; border-radius: 12px; border: none; margin-top: 6px;
            background: linear-gradient(135deg, #ff6b00, #ea580c); color: #fff; font-size: 14px; font-weight: 800; cursor: pointer;
        }
        .error-box {
            background: rgba(239,68,68,0.14); border: 1px solid rgba(239,68,68,0.4); color: #fca5a5;
            padding: 11px 13px; border-radius: 12px; font-size: 12.5px; font-weight: 600; margin-bottom: 16px;
        }
        .links { display: flex; justify-content: space-between; margin-top: 18px; font-size: 12px; }
        .links a { color: #38bdf8; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="brand">
            <img src="<?php echo htmlspecialchars($logo_path); ?>" alt="Gym Logo">
            <h1><?php echo htmlspecialchars($gym['gym_name']); ?></h1>
            <p>🎟️ Apple &amp; Android Wallet Pass Issuance Portal</p>
        </div>

        <?php if ($error_message !== ''): ?>
            <div class="error-box"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="ref" value="<?php echo htmlspecialchars($_GET['ref'] ?? 'index.php'); ?>">
            <div class="form-group">
                <label>Staff Role</label>
                <select name="login_role" class="input">
                    <option value="reception">Reception Desk</option>
                    <option value="owner">Gym Owner</option>
                    <option value="super_admin">Super Admin</option>
                </select>
            </div>
            <div class="form-group">
                <label>Staff Username</label>
                <input type="text" name="username" class="input" placeholder="Enter staff username" required autofocus>
            </div>
            <div class="form-group">
                <label>Password / Pass Key</label>
                <input type="password" name="pass_key" class="input" placeholder="Enter password" required>
            </div>
            <button type="submit" class="btn-submit">Unlock Pass Issuance Portal →</button>
        </form>

        <div class="links">
            <a href="../desk/login.php">⚡ Desk Portal</a>
            <a href="../index.php">← Main Login</a>
        </div>
    </div>
</body>
</html>
