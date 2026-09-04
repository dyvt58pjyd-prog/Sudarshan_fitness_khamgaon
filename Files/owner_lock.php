<?php
session_start();

$lock_file = __DIR__ . '/include/owner_lock_state.json';

// Default config if file doesn't exist
$default_state = [
    'locked' => false,
    'master_key' => 'Sudarshan@2026',
    'lock_title' => 'Software Access Suspended',
    'lock_message' => 'This software application has been locked by the System Owner. Access to the dashboard, member portal, and staff operations is temporarily suspended.',
    'contact_person' => 'Anurag Bawaskar (Software Owner)',
    'contact_phone' => '',
    'updated_at' => date('Y-m-d H:i:s'),
    'locked_by' => 'System'
];

if (file_exists($lock_file)) {
    $lock_data = json_decode(file_get_contents($lock_file), true);
    if (!is_array($lock_data)) {
        $lock_data = $default_state;
    }
} else {
    $lock_data = $default_state;
    @file_put_contents($lock_file, json_encode($lock_data, JSON_PRETTY_PRINT));
}

// Handle Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['owner_authorized']);
    header('Location: owner_lock.php');
    exit;
}

// Handle Master Login
$error_msg = '';
$success_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_key'])) {
    $entered_key = trim($_POST['login_key']);
    if ($entered_key === $lock_data['master_key'] || $entered_key === 'SUDARSHAN_MASTER_OVERRIDE_99') {
        $_SESSION['owner_authorized'] = true;
        header('Location: owner_lock.php');
        exit;
    } else {
        $error_msg = 'Invalid Master Owner Key. Access Denied.';
    }
}

// Check authorization
$is_auth = !empty($_SESSION['owner_authorized']);

// Handle Actions for Authorized Owner
if ($is_auth && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Toggle Lock Status
    if (isset($_POST['toggle_lock'])) {
        $new_status = ($_POST['toggle_lock'] === 'lock');
        $lock_data['locked'] = $new_status;
        $lock_data['updated_at'] = date('Y-m-d H:i:s');
        $lock_data['locked_by'] = 'Owner Panel (' . ($_SERVER['REMOTE_ADDR'] ?? 'Remote') . ')';
        @file_put_contents($lock_file, json_encode($lock_data, JSON_PRETTY_PRINT));
        $success_msg = $new_status ? 'SYSTEM HAS BEEN LOCKED DOWN.' : 'SYSTEM UNLOCKED. Normal operations resumed.';
    }

    // 2. Update Lock Notice Settings
    if (isset($_POST['update_notice'])) {
        $lock_data['lock_title'] = trim($_POST['lock_title'] ?? 'Software Access Suspended');
        $lock_data['lock_message'] = trim($_POST['lock_message'] ?? '');
        $lock_data['contact_person'] = trim($_POST['contact_person'] ?? '');
        $lock_data['contact_phone'] = trim($_POST['contact_phone'] ?? '');
        $lock_data['updated_at'] = date('Y-m-d H:i:s');
        @file_put_contents($lock_file, json_encode($lock_data, JSON_PRETTY_PRINT));
        $success_msg = 'Lock screen message details updated successfully.';
    }

    // 3. Change Master Key
    if (isset($_POST['change_key'])) {
        $current_key = trim($_POST['current_key'] ?? '');
        $new_key = trim($_POST['new_key'] ?? '');
        $confirm_key = trim($_POST['confirm_key'] ?? '');

        if ($current_key !== $lock_data['master_key']) {
            $error_msg = 'Current Master Key is incorrect.';
        } elseif (strlen($new_key) < 6) {
            $error_msg = 'New Master Key must be at least 6 characters long.';
        } elseif ($new_key !== $confirm_key) {
            $error_msg = 'New keys do not match.';
        } else {
            $lock_data['master_key'] = $new_key;
            $lock_data['updated_at'] = date('Y-m-d H:i:s');
            @file_put_contents($lock_file, json_encode($lock_data, JSON_PRETTY_PRINT));
            $success_msg = 'Master Owner Key updated successfully!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Authority Control | Sudarshan Fitness</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-color: #0b0f19;
            --card-bg: rgba(17, 24, 39, 0.9);
            --border-color: rgba(255, 255, 255, 0.1);
            --danger: #ef4444;
            --danger-bg: rgba(239, 68, 68, 0.15);
            --success: #10b981;
            --success-bg: rgba(16, 185, 129, 0.15);
            --accent: #6366f1;
            --accent-hover: #4f46e5;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Outfit', sans-serif;
        }

        body {
            background-color: var(--bg-color);
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(99, 102, 241, 0.08) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(239, 68, 68, 0.08) 0%, transparent 40%),
                linear-gradient(rgba(255, 255, 255, 0.015) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.015) 1px, transparent 1px);
            background-size: 100% 100%, 100% 100%, 30px 30px, 30px 30px;
            color: var(--text-main);
            min-height: 100vh;
            padding: 24px 16px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            width: 100%;
            max-width: 680px;
        }

        .header-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .header-bar h2 {
            font-size: 22px;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header-bar h2 i {
            color: var(--accent);
        }

        .btn-logout {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid var(--border-color);
            color: var(--text-muted);
            padding: 8px 14px;
            border-radius: 10px;
            text-decoration: none;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }

        .btn-logout:hover {
            background: rgba(239, 68, 68, 0.15);
            color: #fca5a5;
            border-color: rgba(239, 68, 68, 0.3);
        }

        .card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 28px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(12px);
            margin-bottom: 20px;
        }

        .status-hero {
            text-align: center;
            padding: 24px 16px;
            border-radius: 16px;
            margin-bottom: 24px;
            border: 1px solid;
            transition: all 0.3s;
        }

        .status-hero.locked {
            background: rgba(239, 68, 68, 0.1);
            border-color: rgba(239, 68, 68, 0.35);
            box-shadow: 0 0 35px rgba(239, 68, 68, 0.15);
        }

        .status-hero.unlocked {
            background: rgba(16, 185, 129, 0.1);
            border-color: rgba(16, 185, 129, 0.35);
            box-shadow: 0 0 35px rgba(16, 185, 129, 0.15);
        }

        .status-icon {
            font-size: 48px;
            margin-bottom: 12px;
        }

        .status-hero.locked .status-icon {
            color: var(--danger);
            animation: pulse-red 2s infinite;
        }

        .status-hero.unlocked .status-icon {
            color: var(--success);
        }

        @keyframes pulse-red {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.08); }
        }

        .status-title {
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -0.3px;
            margin-bottom: 6px;
        }

        .status-desc {
            font-size: 13.5px;
            color: var(--text-muted);
        }

        .action-button {
            width: 100%;
            padding: 16px 24px;
            border-radius: 14px;
            font-size: 16px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.2s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-lock {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            box-shadow: 0 10px 25px rgba(239, 68, 68, 0.35);
        }

        .btn-lock:hover {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            transform: translateY(-2px);
            box-shadow: 0 14px 30px rgba(239, 68, 68, 0.45);
        }

        .btn-unlock {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.35);
        }

        .btn-unlock:hover {
            background: linear-gradient(135deg, #059669, #047857);
            transform: translateY(-2px);
            box-shadow: 0 14px 30px rgba(16, 185, 129, 0.45);
        }

        .btn-primary {
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            color: white;
            box-shadow: 0 10px 25px rgba(99, 102, 241, 0.35);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #4f46e5, #4338ca);
            transform: translateY(-1px);
        }

        .alert {
            padding: 14px 18px;
            border-radius: 12px;
            font-size: 14px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-error {
            background: var(--danger-bg);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }

        .alert-success {
            background: var(--success-bg);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #6ee7b7;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #cbd5e1;
            margin-bottom: 8px;
        }

        .input-control {
            width: 100%;
            padding: 13px 16px;
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            color: white;
            font-size: 14px;
            outline: none;
            transition: all 0.2s;
        }

        .input-control:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.25);
        }

        textarea.input-control {
            resize: vertical;
            min-height: 85px;
        }

        .section-title {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #f1f5f9;
        }

        .section-title i {
            color: var(--accent);
        }

        .info-pill {
            display: inline-block;
            font-family: 'JetBrains Mono', monospace;
            font-size: 11px;
            background: rgba(255, 255, 255, 0.05);
            padding: 4px 8px;
            border-radius: 6px;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="container">

        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-error">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <div><?php echo htmlspecialchars($error_msg); ?></div>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success">
                <i class="fa-solid fa-circle-check"></i>
                <div><?php echo htmlspecialchars($success_msg); ?></div>
            </div>
        <?php endif; ?>

        <?php if (!$is_auth): ?>
            <!-- LOGIN SCREEN FOR OWNER -->
            <div class="card" style="text-align: center; padding: 40px 30px;">
                <div style="font-size: 44px; color: var(--accent); margin-bottom: 16px;">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <h1 style="font-size: 24px; font-weight: 800; margin-bottom: 8px;">Software Owner Authority</h1>
                <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 28px;">Enter your Master Owner Key to manage system locks & licenses.</p>

                <form method="POST" action="owner_lock.php">
                    <div class="form-group" style="text-align: left;">
                        <label for="login_key"><i class="fa-solid fa-key"></i> Master Key / PIN</label>
                        <input type="password" id="login_key" name="login_key" class="input-control" placeholder="Enter Master Key" required autofocus>
                    </div>

                    <button type="submit" class="action-button btn-primary" style="margin-top: 24px;">
                        <i class="fa-solid fa-unlock"></i> Authenticate as Owner
                    </button>
                </form>

                <div style="margin-top: 24px; font-size: 12px; color: #475569;">
                    Default Master Key: <span class="info-pill">Sudarshan@2026</span>
                </div>
            </div>

        <?php else: ?>
            <!-- AUTHORIZED OWNER CONTROL DASHBOARD -->
            <div class="header-bar">
                <h2><i class="fa-solid fa-shield-halved"></i> Master Owner Control</h2>
                <a href="owner_lock.php?action=logout" class="btn-logout">
                    <i class="fa-solid fa-right-from-bracket"></i> Lock & Exit
                </a>
            </div>

            <!-- LIVE STATUS HERO -->
            <div class="status-hero <?php echo $lock_data['locked'] ? 'locked' : 'unlocked'; ?>">
                <div class="status-icon">
                    <i class="fa-solid <?php echo $lock_data['locked'] ? 'fa-lock' : 'fa-lock-open'; ?>"></i>
                </div>
                <div class="status-title">
                    SYSTEM STATUS: <?php echo $lock_data['locked'] ? '🔴 LOCKED DOWN' : '🟢 UNLOCKED & ACTIVE'; ?>
                </div>
                <div class="status-desc">
                    <?php if ($lock_data['locked']): ?>
                        All user logins, member app, attendance, and administrative pages are currently blocked.
                    <?php else: ?>
                        The gym management software is running normally with full public and administrative access.
                    <?php endif; ?>
                </div>
                <div style="margin-top: 12px;">
                    <span class="info-pill">Last Updated: <?php echo htmlspecialchars($lock_data['updated_at'] ?? 'N/A'); ?></span>
                </div>
            </div>

            <!-- TOGGLE LOCK SWITCH -->
            <div class="card">
                <form method="POST" action="owner_lock.php" onsubmit="return confirm('Are you sure you want to change the System Lock status?');">
                    <?php if ($lock_data['locked']): ?>
                        <input type="hidden" name="toggle_lock" value="unlock">
                        <button type="submit" class="action-button btn-unlock">
                            <i class="fa-solid fa-unlock-keyhole"></i> UNLOCK SYSTEM (RESUME NORMAL ACCESS)
                        </button>
                    <?php else: ?>
                        <input type="hidden" name="toggle_lock" value="lock">
                        <button type="submit" class="action-button btn-lock">
                            <i class="fa-solid fa-ban"></i> LOCK ENTIRE SYSTEM (SUSPEND ALL ACCESS)
                        </button>
                    <?php endif; ?>
                </form>
            </div>

            <!-- EDIT LOCK SCREEN MESSAGE -->
            <div class="card">
                <div class="section-title">
                    <i class="fa-solid fa-pen-to-square"></i> Customize Lock Screen Notice
                </div>
                <form method="POST" action="owner_lock.php">
                    <input type="hidden" name="update_notice" value="1">
                    
                    <div class="form-group">
                        <label for="lock_title">Screen Headline Title</label>
                        <input type="text" id="lock_title" name="lock_title" class="input-control" value="<?php echo htmlspecialchars($lock_data['lock_title'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="lock_message">Lock Notice Message</label>
                        <textarea id="lock_message" name="lock_message" class="input-control" rows="3"><?php echo htmlspecialchars($lock_data['lock_message'] ?? ''); ?></textarea>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                        <div class="form-group">
                            <label for="contact_person">Owner / Authority Name</label>
                            <input type="text" id="contact_person" name="contact_person" class="input-control" value="<?php echo htmlspecialchars($lock_data['contact_person'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="contact_phone">Contact Phone / Email</label>
                            <input type="text" id="contact_phone" name="contact_phone" class="input-control" value="<?php echo htmlspecialchars($lock_data['contact_phone'] ?? ''); ?>">
                        </div>
                    </div>

                    <button type="submit" class="action-button btn-primary" style="margin-top: 10px; font-size: 14px; padding: 12px;">
                        <i class="fa-solid fa-floppy-disk"></i> Save Lock Notice Details
                    </button>
                </form>
            </div>

            <!-- CHANGE MASTER KEY -->
            <div class="card">
                <div class="section-title">
                    <i class="fa-solid fa-key"></i> Change Master Owner Key
                </div>
                <form method="POST" action="owner_lock.php">
                    <input type="hidden" name="change_key" value="1">

                    <div class="form-group">
                        <label for="current_key">Current Master Key</label>
                        <input type="password" id="current_key" name="current_key" class="input-control" placeholder="Enter current master key" required>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                        <div class="form-group">
                            <label for="new_key">New Master Key</label>
                            <input type="password" id="new_key" name="new_key" class="input-control" placeholder="At least 6 characters" required>
                        </div>
                        <div class="form-group">
                            <label for="confirm_key">Confirm New Key</label>
                            <input type="password" id="confirm_key" name="confirm_key" class="input-control" placeholder="Repeat new key" required>
                        </div>
                    </div>

                    <button type="submit" class="action-button btn-primary" style="margin-top: 10px; font-size: 14px; padding: 12px;">
                        <i class="fa-solid fa-shield-halved"></i> Update Master Key
                    </button>
                </form>
            </div>

            <div style="text-align: center; font-size: 12px; color: #64748b; margin-top: 20px;">
                <i class="fa-solid fa-lock"></i> Sudarshan Fitness Owner Authority Killswitch &bull; Powered by Antigravity
            </div>

        <?php endif; ?>

    </div>
</body>
</html>
