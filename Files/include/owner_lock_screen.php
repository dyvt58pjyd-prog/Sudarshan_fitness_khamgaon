<?php
// Master Owner Lock Screen
$lock_title = !empty($lock_data['lock_title']) ? htmlspecialchars($lock_data['lock_title']) : 'Software Access Suspended';
$lock_message = !empty($lock_data['lock_message']) ? nl2br(htmlspecialchars($lock_data['lock_message'])) : 'This software application has been locked by the System Owner.';
$contact_person = !empty($lock_data['contact_person']) ? htmlspecialchars($lock_data['contact_person']) : '';
$contact_phone = !empty($lock_data['contact_phone']) ? htmlspecialchars($lock_data['contact_phone']) : '';
$updated_at = !empty($lock_data['updated_at']) ? htmlspecialchars($lock_data['updated_at']) : date('Y-m-d H:i:s');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Locked | Sudarshan Fitness</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-color: #07090e;
            --card-bg: rgba(18, 22, 34, 0.85);
            --card-border: rgba(239, 68, 68, 0.3);
            --danger-glow: rgba(239, 68, 68, 0.25);
            --accent-red: #ef4444;
            --accent-amber: #f59e0b;
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
                radial-gradient(circle at 50% 20%, rgba(239, 68, 68, 0.12) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(245, 158, 11, 0.05) 0%, transparent 40%),
                linear-gradient(rgba(255, 255, 255, 0.02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.02) 1px, transparent 1px);
            background-size: 100% 100%, 100% 100%, 32px 32px, 32px 32px;
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .lock-container {
            width: 100%;
            max-width: 560px;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 24px;
            padding: 40px 32px;
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.7), 0 0 40px var(--danger-glow);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            text-align: center;
            position: relative;
            overflow: hidden;
            animation: fadeIn 0.6s ease-out;
        }

        .lock-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #ef4444, #f59e0b, #ef4444);
            animation: scanline 3s infinite linear;
        }

        @keyframes scanline {
            0% { background-position: 0% 50%; }
            100% { background-position: 200% 50%; }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px) scale(0.97); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .shield-icon {
            width: 88px;
            height: 88px;
            background: rgba(239, 68, 68, 0.12);
            border: 2px solid rgba(239, 68, 68, 0.35);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 38px;
            color: var(--accent-red);
            margin-bottom: 24px;
            box-shadow: 0 0 30px rgba(239, 68, 68, 0.3);
            animation: pulse 2.5s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); box-shadow: 0 0 25px rgba(239, 68, 68, 0.3); }
            50% { transform: scale(1.05); box-shadow: 0 0 40px rgba(239, 68, 68, 0.5); }
        }

        .badge-locked {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.4);
            color: #fca5a5;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            padding: 6px 14px;
            border-radius: 999px;
            margin-bottom: 18px;
        }

        .badge-locked i {
            font-size: 8px;
            animation: blink 1s infinite alternate;
        }

        @keyframes blink {
            from { opacity: 0.2; }
            to { opacity: 1; }
        }

        h1 {
            font-size: 26px;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 14px;
            letter-spacing: -0.5px;
        }

        .message-box {
            background: rgba(0, 0, 0, 0.35);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            padding: 20px 18px;
            margin: 20px 0;
            color: var(--text-muted);
            font-size: 14.5px;
            line-height: 1.6;
        }

        .contact-card {
            display: flex;
            flex-direction: column;
            gap: 8px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 14px;
            padding: 14px 18px;
            margin-top: 10px;
            font-size: 13.5px;
            text-align: left;
        }

        .contact-row {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #cbd5e1;
        }

        .contact-row i {
            color: var(--accent-amber);
            width: 16px;
            text-align: center;
        }

        .contact-row strong {
            color: #f1f5f9;
        }

        .timestamp-info {
            font-family: 'JetBrains Mono', monospace;
            font-size: 11px;
            color: #64748b;
            margin-top: 24px;
        }

        .owner-unlock-link {
            display: inline-block;
            margin-top: 20px;
            font-size: 12px;
            color: #475569;
            text-decoration: none;
            transition: all 0.2s ease;
            padding: 6px 12px;
            border-radius: 8px;
        }

        .owner-unlock-link:hover {
            color: #94a3b8;
            background: rgba(255, 255, 255, 0.05);
        }
    </style>
</head>
<body>
    <div class="lock-container">
        <div class="shield-icon">
            <i class="fa-solid fa-lock"></i>
        </div>

        <div>
            <span class="badge-locked">
                <i class="fa-solid fa-circle"></i> Authority Lock Active
            </span>
        </div>

        <h1><?php echo $lock_title; ?></h1>

        <div class="message-box">
            <?php echo $lock_message; ?>
        </div>

        <?php if (!empty($contact_person) || !empty($contact_phone)): ?>
        <div class="contact-card">
            <?php if (!empty($contact_person)): ?>
            <div class="contact-row">
                <i class="fa-solid fa-user-shield"></i>
                <div>Authority: <strong><?php echo $contact_person; ?></strong></div>
            </div>
            <?php endif; ?>

            <?php if (!empty($contact_phone)): ?>
            <div class="contact-row">
                <i class="fa-solid fa-phone"></i>
                <div>Contact: <strong><?php echo $contact_phone; ?></strong></div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="timestamp-info">
            SEC-LOCK-ID: #SF-<?php echo substr(md5($updated_at), 0, 8); ?> &bull; <?php echo $updated_at; ?>
        </div>

        <div>
            <a href="/owner_lock.php" class="owner-unlock-link">
                <i class="fa-solid fa-key"></i> Owner Authority Login
            </a>
        </div>
    </div>
</body>
</html>
