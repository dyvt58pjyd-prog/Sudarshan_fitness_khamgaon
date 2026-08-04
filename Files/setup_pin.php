<?php
require_once __DIR__ . '/include/db_conn.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Security Check: Must be logged in as super_admin or owner
if (!isset($_SESSION['user_data']) || !isset($_SESSION['role']) || !in_array($_SESSION['role'], ['super_admin', 'owner'])) {
    header("Location: index.php");
    exit();
}

$gym = get_gym_details($con);
$error_msg = '';
$username = $_SESSION['user_data'];
$role = $_SESSION['role'];
$full_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : $username;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'setup_pin') {
    $new_pin = isset($_POST['new_pin']) ? trim($_POST['new_pin']) : '';
    $confirm_pin = isset($_POST['confirm_pin']) ? trim($_POST['confirm_pin']) : '';

    if (!ctype_digit($new_pin)) {
        $error_msg = "Invalid PIN: Your Security PIN must contain numbers only (0-9).";
    } elseif (strlen($new_pin) < 4 || strlen($new_pin) > 6) {
        $error_msg = "Invalid PIN Length: Your Security PIN must be between 4 and 6 digits.";
    } elseif ($new_pin === '1234' || $new_pin === '123456') {
        $error_msg = "Security Alert: You cannot use weak default PINs like '1234'. Please choose a secure PIN.";
    } elseif ($new_pin !== $confirm_pin) {
        $error_msg = "Mismatch Error: The New PIN and Confirm PIN do not match. Please re-enter.";
    } else {
        $new_pin_esc = mysqli_real_escape_string($con, $new_pin);
        $user_esc = mysqli_real_escape_string($con, $username);

        $update_sql = "UPDATE admin SET pass_key = '$new_pin_esc', pin_setup_completed = 1 WHERE username = '$user_esc'";
        if (mysqli_query($con, $update_sql)) {
            $_SESSION['require_pin_setup'] = false;
            unset($_SESSION['require_pin_setup']);
            session_regenerate_id(true);

            header("Location: ./dashboard/admin/index.php?pin_success=1");
            exit();
        } else {
            $error_msg = "Database Error: " . mysqli_error($con);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Mandatory Security PIN Setup | <?php echo htmlspecialchars($gym['gym_name']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #0b0f19;
            --card-bg: rgba(30, 41, 59, 0.85);
            --accent: #ff6b00;
            --accent-green: #10b981;
            --text-main: #ffffff;
            --text-muted: #94a3b8;
            --glass-border: rgba(255, 255, 255, 0.12);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Outfit', sans-serif; }

        body {
            background: var(--bg-color);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background-image: radial-gradient(circle at 50% 0%, rgba(255,107,0,0.15) 0%, transparent 60%);
        }

        .pin-card {
            background: var(--card-bg);
            border: 2px solid var(--accent);
            border-radius: 28px;
            padding: 35px 30px;
            max-width: 450px;
            width: 100%;
            backdrop-filter: blur(20px);
            box-shadow: 0 25px 50px rgba(0,0,0,0.6);
            text-align: center;
        }

        .header-logo {
            max-height: 60px;
            margin-bottom: 12px;
        }

        .badge-role {
            display: inline-block;
            background: rgba(255,107,0,0.15);
            color: var(--accent);
            border: 1px solid var(--accent);
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 15px;
        }

        .title {
            font-size: 22px;
            font-weight: 900;
            color: #fff;
            margin-bottom: 6px;
        }

        .subtitle {
            font-size: 13px;
            color: var(--text-muted);
            margin-bottom: 25px;
            line-height: 1.5;
        }

        .error-alert {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid #ef4444;
            color: #f87171;
            padding: 12px;
            border-radius: 14px;
            font-size: 13px;
            font-weight: 700;
            margin-bottom: 20px;
            text-align: left;
        }

        .input-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .input-group label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: var(--text-muted);
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .pin-input {
            width: 100%;
            padding: 14px;
            border-radius: 14px;
            border: 1px solid var(--glass-border);
            background: rgba(15, 23, 42, 0.9);
            color: #fff;
            font-size: 22px;
            font-weight: 800;
            text-align: center;
            letter-spacing: 6px;
            outline: none;
            transition: all 0.2s ease;
        }

        .pin-input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 15px rgba(255,107,0,0.3);
        }

        .btn-submit {
            width: 100%;
            background: linear-gradient(135deg, var(--accent), #ff8800);
            color: #fff;
            border: none;
            padding: 16px;
            border-radius: 16px;
            font-size: 16px;
            font-weight: 800;
            cursor: pointer;
            box-shadow: 0 10px 25px rgba(255,107,0,0.4);
            transition: all 0.2s ease;
            margin-top: 10px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 30px rgba(255,107,0,0.6);
        }

        .logout-link {
            display: inline-block;
            margin-top: 20px;
            color: var(--text-muted);
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
        }

        .logout-link:hover { color: #ef4444; }
    </style>
</head>
<body>

    <div class="pin-card">
        <img src="<?php echo htmlspecialchars($gym['gym_logo']); ?>" class="header-logo" alt="Gym Logo"><br>
        <div class="badge-role">🔒 Mandatory First-Time PIN Setup</div>

        <div class="title">Set Your Security PIN</div>
        <div class="subtitle">
            Welcome, <strong><?php echo htmlspecialchars($full_name); ?></strong>!<br>
            Please create a new <strong>4 to 6 digit Security PIN</strong> to replace your initial code for instant secure access.
        </div>

        <?php if (!empty($error_msg)): ?>
            <div class="error-alert">
                ⚠️ <?php echo htmlspecialchars($error_msg); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="action" value="setup_pin">

            <div class="input-group">
                <label>New 4-6 Digit Security PIN *</label>
                <input type="password" name="new_pin" id="new_pin" class="pin-input" maxlength="6" pattern="[0-9]*" inputmode="numeric" placeholder="••••••" required autofocus>
            </div>

            <div class="input-group">
                <label>Confirm Security PIN *</label>
                <input type="password" name="confirm_pin" id="confirm_pin" class="pin-input" maxlength="6" pattern="[0-9]*" inputmode="numeric" placeholder="••••••" required>
            </div>

            <button type="submit" class="btn-submit">Save Security PIN &amp; Proceed ➔</button>
        </form>

        <a href="logout.php" class="logout-link">← Cancel &amp; Log Out</a>
    </div>

</body>
</html>
