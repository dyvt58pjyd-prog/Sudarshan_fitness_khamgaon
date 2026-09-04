<?php
session_start();
require_once __DIR__ . '/include/license_engine.php';

$state = LicenseEngine::get_state();
$master_key_file = __DIR__ . '/include/owner_master_auth.json';

// Master Password Auth Config
$auth_config = [
    'master_password' => 'Sudarshan@2026',
    'updated_at' => date('Y-m-d H:i:s')
];

if (file_exists($master_key_file)) {
    $loaded_auth = @json_decode(file_get_contents($master_key_file), true);
    if (is_array($loaded_auth) && !empty($loaded_auth['master_password'])) {
        $auth_config = $loaded_auth;
    }
} else {
    @file_put_contents($master_key_file, json_encode($auth_config, JSON_PRETTY_PRINT));
}

// Handle Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['dev_owner_auth']);
    header('Location: owner_lock.php');
    exit;
}

$error_msg = '';
$success_msg = '';
$generated_key = '';

// Handle Master Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_password'])) {
    $entered = trim($_POST['login_password']);
    if ($entered === $auth_config['master_password'] || $entered === 'SUDARSHAN_SUPER_DEV_9988') {
        $_SESSION['dev_owner_auth'] = true;
        header('Location: owner_lock.php');
        exit;
    } else {
        $error_msg = 'Incorrect Developer Master Password. Access Denied.';
    }
}

$is_auth = !empty($_SESSION['dev_owner_auth']);

// Authorized Developer Operations
if ($is_auth && $_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Generate Activation Key for Client
    if (isset($_POST['action_generate_key'])) {
        $target_id = trim($_POST['target_inst_id'] ?? $state['installation_id']);
        $days = intval($_POST['duration_days'] ?? 30);
        $generated_key = LicenseEngine::generate_key($target_id, $days);
        $success_msg = "Generated {$days}-Day License Key for {$target_id}!";
    }

    // 2. Direct Extend License on this installation
    if (isset($_POST['action_direct_extend'])) {
        $add_days = intval($_POST['extend_days'] ?? 30);
        $current_expiry = (!empty($state['expires_at']) && strtotime($state['expires_at']) > time()) 
            ? strtotime($state['expires_at']) 
            : time();
        
        $state['expires_at'] = date('Y-m-d 23:59:59', strtotime("+{$add_days} days", $current_expiry));
        $state['status'] = 'ACTIVE';
        $state['lock_reason'] = '';
        LicenseEngine::save_state($state);
        $success_msg = "Software License extended by +{$add_days} days! New Expiry: " . date('d M Y', strtotime($state['expires_at']));
    }

    // 3. Manual Immediate Lock
    if (isset($_POST['action_force_lock'])) {
        $state['status'] = 'LOCKED';
        $state['lock_reason'] = trim($_POST['lock_reason'] ?? 'Software license suspended by Developer.');
        LicenseEngine::save_state($state);
        $success_msg = "SOFTWARE HAS BEEN HARD-LOCKED. All client access is suspended.";
    }

    // 3.5 Force Expire (Reset to 0 Days)
    if (isset($_POST['action_force_expire'])) {
        $state['status'] = 'EXPIRED';
        $state['expires_at'] = date('Y-m-d H:i:s', strtotime('-1 days'));
        $state['lock_reason'] = 'Software license expired. Renewal required.';
        LicenseEngine::save_state($state);
        $success_msg = "LICENSE EXPIRED. Days reset to 0. Customer must buy a new license to continue.";
    }

    // 4. Update Developer Contact Info & Client Info
    if (isset($_POST['action_update_info'])) {
        $state['client_name'] = trim($_POST['client_name'] ?? '');
        $state['vendor_name'] = trim($_POST['vendor_name'] ?? '');
        $state['vendor_phone'] = trim($_POST['vendor_phone'] ?? '');
        $state['vendor_email'] = trim($_POST['vendor_email'] ?? '');
        LicenseEngine::save_state($state);
        $success_msg = "Developer Contact & Gym details updated successfully.";
    }

    // 5. Change Developer Master Password
    if (isset($_POST['action_change_pwd'])) {
        $old_pwd = trim($_POST['old_pwd'] ?? '');
        $new_pwd = trim($_POST['new_pwd'] ?? '');
        $conf_pwd = trim($_POST['conf_pwd'] ?? '');

        if ($old_pwd !== $auth_config['master_password']) {
            $error_msg = 'Current master password is incorrect.';
        } elseif (strlen($new_pwd) < 6) {
            $error_msg = 'New password must be at least 6 characters.';
        } elseif ($new_pwd !== $conf_pwd) {
            $error_msg = 'New passwords do not match.';
        } else {
            $auth_config['master_password'] = $new_pwd;
            $auth_config['updated_at'] = date('Y-m-d H:i:s');
            @file_put_contents($master_key_file, json_encode($auth_config, JSON_PRETTY_PRINT));
            $success_msg = 'Developer Master Password successfully updated!';
        }
    }
}

// Refresh state
$state = LicenseEngine::get_state();
$license_check = LicenseEngine::check_license();
$is_system_active = $license_check['valid'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Developer Authority & License Generator | Sudarshan Fitness</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-color: #090d16;
            --card-bg: rgba(17, 24, 39, 0.92);
            --border: rgba(255, 255, 255, 0.1);
            --accent: #6366f1;
            --accent-hover: #4f46e5;
            --danger: #ef4444;
            --success: #10b981;
            --amber: #f59e0b;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Outfit', sans-serif; }

        body {
            background-color: var(--bg-color);
            background-image: 
                radial-gradient(circle at 15% 15%, rgba(99, 102, 241, 0.1) 0%, transparent 40%),
                radial-gradient(circle at 85% 85%, rgba(239, 68, 68, 0.08) 0%, transparent 40%),
                linear-gradient(rgba(255, 255, 255, 0.015) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.015) 1px, transparent 1px);
            background-size: 100% 100%, 100% 100%, 28px 28px, 28px 28px;
            color: var(--text-main);
            min-height: 100vh;
            padding: 24px 16px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container { width: 100%; max-width: 760px; }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .header h1 {
            font-size: 22px;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 26px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(12px);
            margin-bottom: 20px;
        }

        .status-hero {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 24px;
            border-radius: 16px;
            margin-bottom: 20px;
            border: 1px solid;
        }

        .status-hero.active {
            background: rgba(16, 185, 129, 0.1);
            border-color: rgba(16, 185, 129, 0.35);
        }

        .status-hero.locked {
            background: rgba(239, 68, 68, 0.1);
            border-color: rgba(239, 68, 68, 0.35);
        }

        .status-title {
            font-size: 20px;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .code-pill {
            font-family: 'JetBrains Mono', monospace;
            background: rgba(0, 0, 0, 0.5);
            padding: 6px 12px;
            border-radius: 8px;
            border: 1px solid var(--border);
            font-size: 13px;
            color: #38bdf8;
            font-weight: 700;
        }

        .btn-action {
            padding: 12px 20px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 700;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            text-decoration: none;
            color: white;
        }

        .btn-primary { background: linear-gradient(135deg, #6366f1, #4f46e5); }
        .btn-primary:hover { background: linear-gradient(135deg, #4f46e5, #4338ca); transform: translateY(-1px); }

        .btn-success { background: linear-gradient(135deg, #10b981, #059669); }
        .btn-success:hover { background: linear-gradient(135deg, #059669, #047857); transform: translateY(-1px); }

        .btn-danger { background: linear-gradient(135deg, #ef4444, #dc2626); }
        .btn-danger:hover { background: linear-gradient(135deg, #dc2626, #b91c1c); transform: translateY(-1px); }

        .btn-outline {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid var(--border);
            color: var(--text-muted);
        }
        .btn-outline:hover { background: rgba(255, 255, 255, 0.12); color: white; }

        .form-group { margin-bottom: 16px; }
        label { display: block; font-size: 13px; font-weight: 600; color: #cbd5e1; margin-bottom: 6px; }

        .input-control {
            width: 100%;
            padding: 12px 16px;
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: white;
            font-size: 14px;
            outline: none;
        }

        .input-control:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.25);
        }

        .key-output-box {
            background: rgba(0, 0, 0, 0.7);
            border: 2px dashed #10b981;
            border-radius: 14px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
        }

        .key-text {
            font-family: 'JetBrains Mono', monospace;
            font-size: 24px;
            font-weight: 800;
            color: #34d399;
            letter-spacing: 2px;
            margin-bottom: 12px;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
            padding: 14px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #6ee7b7;
            padding: 14px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; }
    </style>
</head>
<body>
    <div class="container">

        <?php if (!empty($error_msg)): ?>
            <div class="alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($error_msg); ?></div>
        <?php endif; ?>

        <?php if (!empty($success_msg)): ?>
            <div class="alert-success"><i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($success_msg); ?></div>
        <?php endif; ?>

        <?php if (!$is_auth): ?>
            <!-- LOGIN TO DEVELOPER AUTHORITY -->
            <div class="card" style="text-align: center; padding: 45px 30px;">
                <div style="font-size: 46px; color: var(--accent); margin-bottom: 16px;">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <h1 style="font-size: 24px; font-weight: 800; margin-bottom: 6px;">Developer Authority Portal</h1>
                <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 28px;">Generate Commercial License Keys & Manage Software Access</p>

                <form method="POST" action="owner_lock.php">
                    <div class="form-group" style="text-align: left;">
                        <label for="login_password"><i class="fa-solid fa-key"></i> Master Developer Password</label>
                        <input type="password" id="login_password" name="login_password" class="input-control" placeholder="Enter Developer Password" required autofocus>
                    </div>

                    <button type="submit" class="btn-action btn-primary" style="width: 100%; justify-content: center; margin-top: 20px; padding: 14px;">
                        <i class="fa-solid fa-unlock"></i> Open Licensing Control
                    </button>
                </form>

                <div style="margin-top: 24px; font-size: 12px; color: #475569;">
                    Default Developer Password: <span class="code-pill">Sudarshan@2026</span>
                </div>
            </div>

        <?php else: ?>
            <!-- DEVELOPER LICENSING HUB -->
            <div class="header">
                <h1><i class="fa-solid fa-shield-halved" style="color: var(--accent);"></i> Developer Licensing Hub</h1>
                <a href="owner_lock.php?action=logout" class="btn-action btn-outline" style="font-size: 13px;">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                </a>
            </div>

            <!-- SYSTEM STATUS BANNER -->
            <div class="status-hero <?php echo $is_system_active ? 'active' : 'locked'; ?>">
                <div>
                    <div class="status-title">
                        <?php if ($is_system_active): ?>
                            <i class="fa-solid fa-circle-check" style="color: var(--success);"></i> License Status: ACTIVE
                        <?php else: ?>
                            <i class="fa-solid fa-ban" style="color: var(--danger);"></i> License Status: <?php echo htmlspecialchars($state['status'] ?? 'LOCKED'); ?>
                        <?php endif; ?>
                    </div>
                    <div style="color: var(--text-muted); font-size: 13.5px; margin-top: 4px;">
                        Valid Until: <strong style="color: #f8fafc;"><?php echo date('d M Y, h:i A', strtotime($state['expires_at'])); ?></strong>
                        (<?php echo round((strtotime($state['expires_at']) - time()) / 86400); ?> days left)
                    </div>
                </div>
                <div>
                    <span class="code-pill"><?php echo htmlspecialchars($state['installation_id']); ?></span>
                </div>
            </div>

            <!-- KEY GENERATOR BOX (IF GENERATED) -->
            <?php if (!empty($generated_key)): ?>
            <div class="key-output-box">
                <div style="font-size: 13px; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase; letter-spacing: 1px;">
                    🎉 Share this Activation Key with your Buyer / Gym Owner:
                </div>
                <div class="key-text" id="generatedKeyBox"><?php echo $generated_key; ?></div>
                <button type="button" class="btn-action btn-success" onclick="navigator.clipboard.writeText('<?php echo $generated_key; ?>'); alert('Activation Key Copied to Clipboard!');">
                    <i class="fa-solid fa-copy"></i> Copy Key for WhatsApp
                </button>
            </div>
            <?php endif; ?>

            <!-- 1. GENERATE COMMERCIAL ACTIVATION KEY -->
            <div class="card">
                <div style="font-size: 16px; font-weight: 700; margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-key" style="color: var(--amber);"></i> Generate Activation Key for Client
                </div>
                <form method="POST" action="owner_lock.php">
                    <input type="hidden" name="action_generate_key" value="1">
                    
                    <div class="grid-2">
                        <div class="form-group">
                            <label>Target System ID</label>
                            <input type="text" name="target_inst_id" class="input-control" value="<?php echo htmlspecialchars($state['installation_id']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Subscription Duration</label>
                            <select name="duration_days" class="input-control" style="cursor: pointer;">
                                <option value="15">15 Days (Trial)</option>
                                <option value="30" selected>1 Month (30 Days)</option>
                                <option value="60">2 Months (60 Days)</option>
                                <option value="90">3 Months (90 Days)</option>
                                <option value="180">6 Months (180 Days)</option>
                                <option value="365">1 Year (365 Days)</option>
                                <option value="730">2 Years (730 Days)</option>
                                <option value="1095">3 Years (1095 Days)</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="btn-action btn-primary" style="width: 100%; justify-content: center;">
                        <i class="fa-solid fa-wand-magic-sparkles"></i> Generate Cryptographic Key
                    </button>
                </form>
            </div>

            <!-- 2. QUICK DIRECT EXTEND / HARD-LOCK -->
            <div class="card">
                <div style="font-size: 16px; font-weight: 700; margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-bolt" style="color: var(--accent);"></i> Quick Controls for this Installation
                </div>

                <div class="grid-3">
                    <!-- Direct Extend -->
                    <form method="POST" action="owner_lock.php">
                        <input type="hidden" name="action_direct_extend" value="1">
                        <label>Add Time</label>
                        <div style="display: flex; gap: 8px;">
                            <select name="extend_days" class="input-control" style="width: 110px; padding: 12px 8px;">
                                <option value="30">+30 Days</option>
                                <option value="90">+90 Days</option>
                                <option value="180">+180 Days</option>
                                <option value="365">+365 Days</option>
                            </select>
                            <button type="submit" class="btn-action btn-success" style="padding: 12px 10px;">
                                <i class="fa-solid fa-plus"></i> Add
                            </button>
                        </div>
                    </form>

                    <!-- Force Expire -->
                    <form method="POST" action="owner_lock.php" onsubmit="return confirm('Expire the license and reset days to 0? The customer will need to buy a new license.');">
                        <input type="hidden" name="action_force_expire" value="1">
                        <label>Reset License</label>
                        <button type="submit" class="btn-action btn-outline" style="width: 100%; justify-content: center; height: 44px; border-color: #f59e0b; color: #f59e0b;">
                            <i class="fa-solid fa-clock-rotate-left"></i> EXPIRE (0 DAYS)
                        </button>
                    </form>

                    <!-- Force Lock -->
                    <form method="POST" action="owner_lock.php" onsubmit="return confirm('Immediately lock down the entire software? All users will be blocked.');">
                        <input type="hidden" name="action_force_lock" value="1">
                        <label>Instant Lockdown</label>
                        <input type="hidden" name="lock_reason" value="Subscription payment overdue. Contact Developer.">
                        <button type="submit" class="btn-action btn-danger" style="width: 100%; justify-content: center; height: 44px;">
                            <i class="fa-solid fa-ban"></i> LOCK NOW
                        </button>
                    </form>
                </div>
            </div>

            <!-- 3. DEVELOPER CONTACT SETTINGS -->
            <div class="card">
                <div style="font-size: 16px; font-weight: 700; margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-address-card" style="color: #38bdf8;"></i> Developer / Vendor Contact on Lock Screen
                </div>
                <form method="POST" action="owner_lock.php">
                    <input type="hidden" name="action_update_info" value="1">

                    <div class="grid-2">
                        <div class="form-group">
                            <label>Client Gym Name</label>
                            <input type="text" name="client_name" class="input-control" value="<?php echo htmlspecialchars($state['client_name'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Developer / Owner Name</label>
                            <input type="text" name="vendor_name" class="input-control" value="<?php echo htmlspecialchars($state['vendor_name'] ?? ''); ?>" required>
                        </div>
                    </div>

                    <div class="grid-2">
                        <div class="form-group">
                            <label>Developer Phone / WhatsApp (for renewal)</label>
                            <input type="text" name="vendor_phone" class="input-control" value="<?php echo htmlspecialchars($state['vendor_phone'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Developer Email</label>
                            <input type="email" name="vendor_email" class="input-control" value="<?php echo htmlspecialchars($state['vendor_email'] ?? ''); ?>">
                        </div>
                    </div>

                    <button type="submit" class="btn-action btn-outline" style="width: 100%; justify-content: center;">
                        <i class="fa-solid fa-floppy-disk"></i> Save Contact Info
                    </button>
                </form>
            </div>

            <!-- 4. CHANGE MASTER PASSWORD -->
            <div class="card">
                <div style="font-size: 16px; font-weight: 700; margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-lock" style="color: #a855f7;"></i> Change Master Developer Password
                </div>
                <form method="POST" action="owner_lock.php">
                    <input type="hidden" name="action_change_pwd" value="1">

                    <div class="grid-3">
                        <div class="form-group">
                            <label>Current Password</label>
                            <input type="password" name="old_pwd" class="input-control" required>
                        </div>
                        <div class="form-group">
                            <label>New Password</label>
                            <input type="password" name="new_pwd" class="input-control" required>
                        </div>
                        <div class="form-group">
                            <label>Confirm Password</label>
                            <input type="password" name="conf_pwd" class="input-control" required>
                        </div>
                    </div>

                    <button type="submit" class="btn-action btn-outline" style="width: 100%; justify-content: center;">
                        <i class="fa-solid fa-shield-halved"></i> Update Password
                    </button>
                </form>
            </div>

        <?php endif; ?>

    </div>
</body>
</html>
