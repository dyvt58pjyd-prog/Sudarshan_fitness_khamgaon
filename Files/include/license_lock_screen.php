<?php
// License Lock & Activation Screen
require_once __DIR__ . '/license_engine.php';

$state = LicenseEngine::get_state();
$inst_id = $state['installation_id'] ?? 'SF-GYM-DEFAULT';
$vendor_name = !empty($state['vendor_name']) ? htmlspecialchars($state['vendor_name']) : 'Software Developer';
$vendor_phone = !empty($state['vendor_phone']) ? htmlspecialchars($state['vendor_phone']) : '';
$vendor_email = !empty($state['vendor_email']) ? htmlspecialchars($state['vendor_email']) : '';
$lock_reason = !empty($license_check['reason']) ? htmlspecialchars($license_check['reason']) : ($state['lock_reason'] ?? 'Subscription Renewal Required.');
$expires_at = !empty($state['expires_at']) ? date('d M Y, h:i A', strtotime($state['expires_at'])) : 'Expired';

$activation_error = '';
$activation_success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['activation_key'])) {
    $res = LicenseEngine::activate_with_key($_POST['activation_key']);
    if (!empty($res['success'])) {
        $activation_success = "License successfully activated! Valid until " . date('d M Y', strtotime($res['expires_at']));
        // Redirect to homepage after 2 seconds
        header("Refresh: 2; url=/");
    } else {
        $activation_error = $res['error'] ?? 'Invalid Activation Key.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Software License Expired | Activation Required</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-color: #07090e;
            --card-bg: rgba(15, 20, 32, 0.92);
            --card-border: rgba(239, 68, 68, 0.35);
            --accent-red: #ef4444;
            --accent-amber: #f59e0b;
            --accent-green: #10b981;
            --accent-indigo: #6366f1;
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
                radial-gradient(circle at 50% 15%, rgba(239, 68, 68, 0.15) 0%, transparent 55%),
                radial-gradient(circle at 85% 85%, rgba(99, 102, 241, 0.08) 0%, transparent 45%),
                linear-gradient(rgba(255, 255, 255, 0.02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.02) 1px, transparent 1px);
            background-size: 100% 100%, 100% 100%, 30px 30px, 30px 30px;
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            width: 100%;
            max-width: 580px;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 24px;
            padding: 40px 32px;
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.8), 0 0 40px rgba(239, 68, 68, 0.2);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            text-align: center;
            position: relative;
            overflow: hidden;
            animation: fadeIn 0.5s ease-out;
        }

        .container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #ef4444, #f59e0b, #ef4444);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .lock-icon {
            width: 84px;
            height: 84px;
            background: rgba(239, 68, 68, 0.12);
            border: 2px solid rgba(239, 68, 68, 0.4);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            color: var(--accent-red);
            margin-bottom: 20px;
            box-shadow: 0 0 30px rgba(239, 68, 68, 0.35);
            animation: pulse 2.5s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .badge-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.4);
            color: #fca5a5;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            padding: 6px 14px;
            border-radius: 999px;
            margin-bottom: 16px;
        }

        h1 {
            font-size: 24px;
            font-weight: 800;
            margin-bottom: 10px;
            color: #ffffff;
            letter-spacing: -0.5px;
        }

        .reason-box {
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 14px;
            padding: 16px 18px;
            margin: 18px 0;
            color: #cbd5e1;
            font-size: 14px;
            line-height: 1.5;
        }

        .hardware-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 8px 16px;
            border-radius: 10px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 13px;
            font-weight: 700;
            color: #e2e8f0;
            margin-bottom: 22px;
        }

        .hardware-pill span {
            color: var(--accent-amber);
        }

        .activation-form {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 18px;
            padding: 24px 20px;
            margin: 20px 0;
            text-align: left;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            color: #f1f5f9;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
        }

        .input-key {
            width: 100%;
            padding: 14px 16px;
            background: rgba(0, 0, 0, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 12px;
            color: #38bdf8;
            font-family: 'JetBrains Mono', monospace;
            font-size: 15px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            outline: none;
            transition: all 0.2s;
            text-align: center;
        }

        .input-key:focus {
            border-color: var(--accent-indigo);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.3);
        }

        .btn-activate {
            width: 100%;
            padding: 14px 20px;
            margin-top: 14px;
            background: linear-gradient(135deg, #10b981, #059669);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s;
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
        }

        .btn-activate:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(16, 185, 129, 0.45);
        }

        .contact-box {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 14px;
            padding: 14px 18px;
            font-size: 13.5px;
            text-align: left;
            margin-top: 20px;
        }

        .contact-box a {
            color: #38bdf8;
            text-decoration: none;
            font-weight: 600;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
            padding: 12px 14px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #6ee7b7;
            padding: 12px 14px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .owner-link {
            display: inline-block;
            margin-top: 20px;
            font-size: 11.5px;
            color: #475569;
            text-decoration: none;
            transition: all 0.2s;
        }

        .owner-link:hover {
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="lock-icon">
            <i class="fa-solid fa-lock"></i>
        </div>

        <div>
            <span class="badge-status">
                <i class="fa-solid fa-circle-exclamation"></i> System Locked
            </span>
        </div>

        <h1>Software License Expired</h1>

        <div class="reason-box">
            <?php echo $lock_reason; ?>
        </div>

        <div class="hardware-pill">
            <i class="fa-solid fa-microchip"></i> System ID: <span><?php echo htmlspecialchars($inst_id); ?></span>
        </div>

        <!-- ACTIVATION INPUT -->
        <div class="activation-form">
            <form method="POST" action="">
                <?php if (!empty($activation_error)): ?>
                    <div class="alert-error">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <?php echo htmlspecialchars($activation_error); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($activation_success)): ?>
                    <div class="alert-success">
                        <i class="fa-solid fa-circle-check"></i>
                        <?php echo htmlspecialchars($activation_success); ?>
                    </div>
                <?php endif; ?>

                <label class="form-label" for="activation_key">
                    <span><i class="fa-solid fa-key"></i> Enter License Activation Key</span>
                    <span style="font-size: 11px; color: var(--text-muted);">From Developer</span>
                </label>

                <input type="text" id="activation_key" name="activation_key" class="input-key" placeholder="SF-30D-XXXX-XXXX" required autofocus autocomplete="off">

                <button type="submit" class="btn-activate">
                    <i class="fa-solid fa-unlock"></i> Activate Software Now
                </button>
            </form>
        </div>

        <!-- DEVELOPER CONTACT -->
        <div class="contact-box">
            <div style="font-weight: 700; color: #f1f5f9; margin-bottom: 6px;">
                <i class="fa-solid fa-headset" style="color: var(--accent-amber);"></i> Need Renewal Key? Contact Developer:
            </div>
            <div style="color: var(--text-muted); font-size: 13px;">
                Owner: <strong><?php echo $vendor_name; ?></strong><br>
                <?php if (!empty($vendor_phone)): ?>
                    Phone/WhatsApp: <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $vendor_phone); ?>"><?php echo $vendor_phone; ?></a><br>
                <?php endif; ?>
                Provide your <strong>System ID (<?php echo htmlspecialchars($inst_id); ?>)</strong> to receive your unlock key.
            </div>
        </div>

        <div>
            <a href="/owner_lock.php" class="owner-link">
                <i class="fa-solid fa-shield"></i> Developer Authority Portal
            </a>
        </div>
    </div>
</body>
</html>
