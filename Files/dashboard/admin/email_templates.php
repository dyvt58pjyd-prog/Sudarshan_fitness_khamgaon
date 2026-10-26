<?php
/**
 * Sudarshan Fitness - Executive Email Template Customizer & Live Updater Hub
 * 
 * Allows Administrators to customize subjects, badges, branding colors,
 * animated gym stickers, promotional banners, and CTA buttons with
 * live split-screen preview and instant 1-click test email dispatch.
 */

require_once __DIR__ . '/../../include/db_conn.php';
require_once __DIR__ . '/../../include/smtp_mailer.php';
page_protect();

$current_role = $_SESSION['role'] ?? 'super_admin';
if (!in_array($current_role, ['super_admin', 'owner', 'reception'])) {
    echo "<head><script>alert('Access Denied: Only Gym Administrators can access Email Template Customizer.');</script></head></html>";
    echo "<meta http-equiv='refresh' content='0; url=index.php'>";
    exit();
}

$gym = get_gym_details($con);
$msg = '';
$msg_type = '';

// Current active template key
$selected_key = isset($_GET['tpl']) ? trim($_GET['tpl']) : (isset($_GET['template']) ? trim($_GET['template']) : 'member_qr_pass');
$valid_keys = ['member_qr_pass', 'expiry_reminder', 'payment_receipt', 'birthday_wish'];
if (!in_array($selected_key, $valid_keys)) {
    $selected_key = 'member_qr_pass';
}

// ── Handle Save Action ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_template'])) {
    $tpl_key      = mysqli_real_escape_string($con, trim($_POST['template_key']));
    $subject      = mysqli_real_escape_string($con, trim($_POST['subject']));
    $badge_title  = mysqli_real_escape_string($con, trim($_POST['badge_title']));
    $badge_icon   = mysqli_real_escape_string($con, trim($_POST['badge_icon']));
    $accent_color = mysqli_real_escape_string($con, trim($_POST['accent_color']));
    $greeting     = mysqli_real_escape_string($con, trim($_POST['greeting']));
    $intro_text   = mysqli_real_escape_string($con, trim($_POST['intro_text']));
    $body_content = mysqli_real_escape_string($con, trim($_POST['body_content']));
    $promo_banner = mysqli_real_escape_string($con, trim($_POST['promo_banner']));
    $cta_text     = mysqli_real_escape_string($con, trim($_POST['cta_text']));
    $cta_url      = mysqli_real_escape_string($con, trim($_POST['cta_url']));
    $footer_text  = mysqli_real_escape_string($con, trim($_POST['footer_text']));
    $sticker      = mysqli_real_escape_string($con, trim($_POST['animated_sticker']));

    $update_sql = "UPDATE email_templates SET
        subject = '$subject',
        badge_title = '$badge_title',
        badge_icon = '$badge_icon',
        accent_color = '$accent_color',
        greeting = '$greeting',
        intro_text = '$intro_text',
        body_content = '$body_content',
        promo_banner = '$promo_banner',
        cta_text = '$cta_text',
        cta_url = '$cta_url',
        footer_text = '$footer_text',
        animated_sticker = '$sticker'
        WHERE template_key = '$tpl_key'";

    if (mysqli_query($con, $update_sql)) {
        $msg = "✅ Email template <strong>" . htmlspecialchars($tpl_key) . "</strong> updated successfully! All future dispatches will use these new styles.";
        $msg_type = "success";
    } else {
        $msg = "❌ Error saving template: " . mysqli_error($con);
        $msg_type = "danger";
    }
}

// ── Handle Test Email Dispatch ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_test_email'])) {
    $test_email = trim($_POST['test_email_address'] ?? '');
    $tpl_key    = trim($_POST['template_key'] ?? $selected_key);

    if (empty($test_email) || !filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
        $msg = "❌ Please enter a valid test recipient email address.";
        $msg_type = "danger";
    } else {
        $sample_data = [
            '{member_name}' => 'Anurag Bawaskar (Test Recipient)',
            '{userid}'      => 'MEM-777',
            '{plan_name}'   => '12 Month Elite Muscle Building',
            '{expire_date}' => date('d-M-Y', strtotime('+45 days')),
            '{days_left}'   => '5 Days',
            '{amount}'      => '12,500',
            '{receipt_no}'  => 'TEST-REC-2026',
            '{qr_image_url}'=> 'https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=' . urlencode('TEST_MEM_777')
        ];

        $test_result = send_dynamic_template_email($con, $test_email, 'Gym Admin Tester', $tpl_key, $sample_data);
        if ($test_result) {
            $msg = "🚀 <strong>Live Test Email Dispatched!</strong> Sent to <strong>" . htmlspecialchars($test_email) . "</strong>. Please check your inbox / spam folder in 15 seconds.";
            $msg_type = "success";
        } else {
            $msg = "⚠️ Failed to send test email. Please check your SMTP configuration in <a href='smtp_settings.php' style='color:#fff; text-decoration:underline;'>SMTP Settings</a>.";
            $msg_type = "danger";
        }
    }
}

// Fetch current template data
$current_tpl = get_email_template($con, $selected_key);
$stickers = get_animated_stickers_catalog();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($gym['gym_name']); ?> | Email Template Customizer Hub</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --bg-body: #0a0f1d;
            --bg-card: rgba(15, 23, 42, 0.75);
            --border-glass: rgba(255, 255, 255, 0.08);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --accent-primary: #ff6b00;
        }

        body {
            background-color: var(--bg-body);
            color: var(--text-main);
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 25px;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(255, 107, 0, 0.12) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(56, 189, 248, 0.08) 0%, transparent 40%);
            min-height: 100vh;
        }

        .header-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 25px;
            border-bottom: 1px solid var(--border-glass);
            padding-bottom: 20px;
        }

        .title-group h2 {
            margin: 0;
            font-size: 24px;
            font-weight: 900;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #ffffff;
            letter-spacing: -0.5px;
        }

        .tabs-nav {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 25px;
        }

        .tab-btn {
            background: rgba(30, 41, 59, 0.7);
            color: var(--text-muted);
            border: 1px solid var(--border-glass);
            padding: 12px 20px;
            border-radius: 14px;
            font-size: 13.5px;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease-in-out;
        }

        .tab-btn:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.08);
            transform: translateY(-2px);
        }

        .tab-btn.active {
            background: linear-gradient(135deg, rgba(255, 107, 0, 0.25), rgba(255, 107, 0, 0.1));
            border-color: #ff6b00;
            color: #ff6b00;
            box-shadow: 0 4px 20px rgba(255, 107, 0, 0.25);
        }

        .split-grid {
            display: grid;
            grid-template-columns: 1.15fr 1fr;
            gap: 25px;
            align-items: start;
        }

        @media (max-width: 1024px) {
            .split-grid { grid-template-columns: 1fr; }
        }

        .panel-card {
            background: var(--bg-card);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-glass);
            border-radius: 20px;
            padding: 26px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.4);
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-label {
            display: block;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #cbd5e1;
            margin-bottom: 8px;
        }

        .form-control-custom {
            width: 100%;
            box-sizing: border-box;
            background: rgba(15, 23, 42, 0.9);
            border: 1.5px solid var(--border-glass);
            border-radius: 12px;
            color: #ffffff;
            padding: 12px 16px;
            font-size: 13.5px;
            font-family: inherit;
            outline: none;
            transition: border-color 0.2s;
        }

        .form-control-custom:focus {
            border-color: var(--accent-primary);
            box-shadow: 0 0 15px rgba(255, 107, 0, 0.25);
        }

        .color-presets {
            display: flex;
            gap: 10px;
            margin-top: 8px;
            flex-wrap: wrap;
        }

        .color-pill {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            cursor: pointer;
            border: 2px solid transparent;
            transition: transform 0.2s;
        }

        .color-pill:hover, .color-pill.active {
            transform: scale(1.15);
            border-color: #ffffff;
        }

        .tag-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 6px;
        }

        .tag-chip {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #38bdf8;
            font-size: 11px;
            font-weight: 700;
            padding: 4px 8px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.15s;
        }

        .tag-chip:hover {
            background: #38bdf8;
            color: #0f172a;
        }

        /* Email Mockup Container */
        .preview-wrapper {
            position: sticky;
            top: 20px;
        }

        .preview-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .client-mockup {
            background: #0f172a;
            border-radius: 20px;
            border: 1px solid rgba(255,255,255,0.1);
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0,0,0,0.7);
        }

        .client-bar {
            background: #1e293b;
            padding: 12px 18px;
            display: flex;
            align-items: center;
            gap: 8px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
        }

        .circle {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }

        .email-render-box {
            padding: 25px 20px;
            max-height: 720px;
            overflow-y: auto;
            background: #0b0f19;
        }

        .btn-submit {
            background: linear-gradient(135deg, #ff6b00, #ff8800);
            color: #ffffff;
            border: none;
            padding: 14px 28px;
            border-radius: 14px;
            font-size: 14px;
            font-weight: 800;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 6px 20px rgba(255, 107, 0, 0.4);
            transition: all 0.2s;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 107, 0, 0.6);
        }

        .alert-box {
            padding: 14px 20px;
            border-radius: 14px;
            margin-bottom: 22px;
            font-size: 13.5px;
            font-weight: 600;
        }

        .alert-success { background: rgba(16, 185, 129, 0.15); border: 1px solid #10b981; color: #34d399; }
        .alert-danger { background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #f87171; }
    </style>
</head>
<body>

    <!-- Header Section -->
    <div class="header-bar">
        <div class="title-group">
            <h2><span>📧🎨</span> Email Template Customizer &amp; Live Updater</h2>
            <div style="font-size: 13px; color: var(--text-muted); margin-top: 4px;">Customize outgoing member emails, animated gym stickers, and trigger live test dispatches</div>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="smtp_settings.php" class="tab-btn" style="background: rgba(14, 165, 233, 0.15); color: #38bdf8; border-color: rgba(14, 165, 233, 0.3);">
                <i class="fa-solid fa-server"></i> SMTP Server Settings
            </a>
            <a href="index.php" class="tab-btn">
                <i class="fa-solid fa-arrow-left"></i> Dashboard
            </a>
        </div>
    </div>

    <?php if (!empty($msg)): ?>
        <div class="alert-box alert-<?php echo $msg_type; ?>">
            <?php echo $msg; ?>
        </div>
    <?php endif; ?>

    <!-- Template Switcher Tabs -->
    <div class="tabs-nav">
        <a href="?tpl=member_qr_pass" class="tab-btn <?php echo $selected_key === 'member_qr_pass' ? 'active' : ''; ?>">
            <span>🎫</span> Digital Entrance Pass &amp; Welcome
        </a>
        <a href="?tpl=expiry_reminder" class="tab-btn <?php echo $selected_key === 'expiry_reminder' ? 'active' : ''; ?>">
            <span>⚠️</span> Membership Expiry Alert
        </a>
        <a href="?tpl=payment_receipt" class="tab-btn <?php echo $selected_key === 'payment_receipt' ? 'active' : ''; ?>">
            <span>🧾</span> Payment Receipt &amp; Billing
        </a>
        <a href="?tpl=birthday_wish" class="tab-btn <?php echo $selected_key === 'birthday_wish' ? 'active' : ''; ?>">
            <span>🎉</span> Birthday Celebration &amp; VIP Gift
        </a>
    </div>

    <!-- Main Split-Screen Customizer Grid -->
    <div class="split-grid">
        
        <!-- LEFT: Template Configuration Controls -->
        <div class="panel-card">
            <h3 style="margin: 0 0 20px 0; font-size: 17px; font-weight: 800; color: #ffffff; display: flex; align-items: center; justify-content: space-between;">
                <span>🛠️ Edit Template: <?php echo htmlspecialchars($current_tpl['template_name']); ?></span>
                <span style="font-size: 11px; background: rgba(255, 107, 0, 0.15); color: #ff6b00; padding: 4px 10px; border-radius: 20px; border: 1px solid #ff6b00;">Active in System</span>
            </h3>

            <form method="POST" action="?tpl=<?php echo urlencode($selected_key); ?>" id="templateForm">
                <input type="hidden" name="template_key" value="<?php echo htmlspecialchars($selected_key); ?>">

                <!-- Email Subject Line -->
                <div class="form-group">
                    <label class="form-label">Email Subject Line</label>
                    <input type="text" name="subject" id="inp_subject" class="form-control-custom" value="<?php echo htmlspecialchars($current_tpl['subject']); ?>" required oninput="updateLivePreview()">
                    <div class="tag-chips">
                        <span class="tag-chip" onclick="insertTag('inp_subject', '{gym_name}')">{gym_name}</span>
                        <span class="tag-chip" onclick="insertTag('inp_subject', '{member_name}')">{member_name}</span>
                        <span class="tag-chip" onclick="insertTag('inp_subject', '{userid}')">{userid}</span>
                        <span class="tag-chip" onclick="insertTag('inp_subject', '{expire_date}')">{expire_date}</span>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 15px;">
                    <!-- Badge Title -->
                    <div class="form-group">
                        <label class="form-label">Header Badge Title</label>
                        <input type="text" name="badge_title" id="inp_badge_title" class="form-control-custom" value="<?php echo htmlspecialchars($current_tpl['badge_title']); ?>" required oninput="updateLivePreview()">
                    </div>

                    <!-- Badge Icon / Emoji -->
                    <div class="form-group">
                        <label class="form-label">Badge Icon</label>
                        <input type="text" name="badge_icon" id="inp_badge_icon" class="form-control-custom" value="<?php echo htmlspecialchars($current_tpl['badge_icon']); ?>" required oninput="updateLivePreview()">
                    </div>
                </div>

                <!-- Theme Accent Color Palette -->
                <div class="form-group">
                    <label class="form-label">Accent Highlight Color</label>
                    <div style="display: flex; gap: 12px; align-items: center;">
                        <input type="color" name="accent_color" id="inp_accent_color" value="<?php echo htmlspecialchars($current_tpl['accent_color']); ?>" style="width: 48px; height: 42px; border: none; border-radius: 10px; cursor: pointer; background: transparent;" oninput="updateLivePreview()">
                        <input type="text" id="inp_accent_color_hex" class="form-control-custom" style="width: 140px;" value="<?php echo htmlspecialchars($current_tpl['accent_color']); ?>" oninput="syncColorInput(this.value)">
                        
                        <div class="color-presets">
                            <span class="color-pill" style="background: #ff6b00;" onclick="setColor('#ff6b00')" title="Flame Orange"></span>
                            <span class="color-pill" style="background: #f59e0b;" onclick="setColor('#f59e0b')" title="Amber Gold"></span>
                            <span class="color-pill" style="background: #10b981;" onclick="setColor('#10b981')" title="Emerald Green"></span>
                            <span class="color-pill" style="background: #0ea5e9;" onclick="setColor('#0ea5e9')" title="Cyber Sky"></span>
                            <span class="color-pill" style="background: #8b5cf6;" onclick="setColor('#8b5cf6')" title="Royal Purple"></span>
                            <span class="color-pill" style="background: #ef4444;" onclick="setColor('#ef4444')" title="Crimson Red"></span>
                        </div>
                    </div>
                </div>

                <!-- Animated Live Gym Sticker Selector -->
                <div class="form-group">
                    <label class="form-label">Animated Live Gym Sticker</label>
                    <select name="animated_sticker" id="inp_sticker" class="form-control-custom" onchange="updateLivePreview()">
                        <?php foreach ($stickers as $skey => $sval): ?>
                            <option value="<?php echo $skey; ?>" <?php echo ($current_tpl['animated_sticker'] === $skey) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($sval['name']); ?> (<?php echo $sval['badge']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Greeting Line -->
                <div class="form-group">
                    <label class="form-label">Greeting Line</label>
                    <input type="text" name="greeting" id="inp_greeting" class="form-control-custom" value="<?php echo htmlspecialchars($current_tpl['greeting']); ?>" required oninput="updateLivePreview()">
                    <div class="tag-chips">
                        <span class="tag-chip" onclick="insertTag('inp_greeting', '{member_name}')">{member_name}</span>
                        <span class="tag-chip" onclick="insertTag('inp_greeting', '{gym_name}')">{gym_name}</span>
                    </div>
                </div>

                <!-- Intro Summary Text -->
                <div class="form-group">
                    <label class="form-label">Intro Paragraph</label>
                    <textarea name="intro_text" id="inp_intro_text" class="form-control-custom" rows="3" oninput="updateLivePreview()"><?php echo htmlspecialchars($current_tpl['intro_text']); ?></textarea>
                    <div class="tag-chips">
                        <span class="tag-chip" onclick="insertTag('inp_intro_text', '{plan_name}')">{plan_name}</span>
                        <span class="tag-chip" onclick="insertTag('inp_intro_text', '{days_left}')">{days_left}</span>
                        <span class="tag-chip" onclick="insertTag('inp_intro_text', '{amount}')">{amount}</span>
                        <span class="tag-chip" onclick="insertTag('inp_intro_text', '{receipt_no}')">{receipt_no}</span>
                        <span class="tag-chip" onclick="insertTag('inp_intro_text', '{payment_mode}')">{payment_mode}</span>
                    </div>
                </div>

                <!-- Main Body Content -->
                <div class="form-group">
                    <label class="form-label">Main Body Content</label>
                    <textarea name="body_content" id="inp_body_content" class="form-control-custom" rows="3" oninput="updateLivePreview()"><?php echo htmlspecialchars($current_tpl['body_content']); ?></textarea>
                    <div class="tag-chips">
                        <span class="tag-chip" onclick="insertTag('inp_body_content', '{expire_date}')">{expire_date}</span>
                        <span class="tag-chip" onclick="insertTag('inp_body_content', '{gym_name}')">{gym_name}</span>
                        <span class="tag-chip" onclick="insertTag('inp_body_content', '{whatsapp_button}')">💬 {whatsapp_button}</span>
                        <span class="tag-chip" onclick="insertTag('inp_body_content', '{whatsapp_group_link}')">🔗 {whatsapp_group_link}</span>
                    </div>
                </div>

                <!-- Promotional / Offer Announcement Banner -->
                <div class="form-group">
                    <label class="form-label">Promotional Announcement Banner (Optional)</label>
                    <textarea name="promo_banner" id="inp_promo_banner" class="form-control-custom" rows="2" placeholder="e.g. ⭐ Renew early & get 1 extra month free!" oninput="updateLivePreview()"><?php echo htmlspecialchars($current_tpl['promo_banner']); ?></textarea>
                </div>

                <!-- Call to Action Button -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label class="form-label">CTA Button Label</label>
                        <input type="text" name="cta_text" id="inp_cta_text" class="form-control-custom" value="<?php echo htmlspecialchars($current_tpl['cta_text']); ?>" required oninput="updateLivePreview()">
                    </div>
                    <div class="form-group">
                        <label class="form-label">CTA Button URL</label>
                        <input type="url" name="cta_url" id="inp_cta_url" class="form-control-custom" value="<?php echo htmlspecialchars($current_tpl['cta_url']); ?>" required oninput="updateLivePreview()">
                    </div>
                </div>

                <!-- Footer Disclaimer -->
                <div class="form-group">
                    <label class="form-label">Footer Disclaimer &amp; Notice</label>
                    <textarea name="footer_text" id="inp_footer_text" class="form-control-custom" rows="2" oninput="updateLivePreview()"><?php echo htmlspecialchars($current_tpl['footer_text']); ?></textarea>
                </div>

                <!-- Save Action Buttons -->
                <div style="display: flex; gap: 15px; margin-top: 25px; align-items: center;">
                    <button type="submit" name="save_template" class="btn-submit">
                        <i class="fa-solid fa-floppy-disk"></i> Save Email Template
                    </button>
                    <a href="?tpl=<?php echo urlencode($selected_key); ?>" style="color: var(--text-muted); text-decoration: none; font-size: 13px; font-weight: 600;">Discard Changes</a>
                </div>
            </form>

            <hr style="border: none; border-top: 1px solid var(--border-glass); margin: 30px 0;">

            <!-- Instant Test Email Dispatch Tool -->
            <div style="background: rgba(14, 165, 233, 0.08); border: 1px solid rgba(14, 165, 233, 0.25); border-radius: 16px; padding: 20px;">
                <h4 style="margin: 0 0 8px 0; color: #38bdf8; font-size: 14.5px; font-weight: 800; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-paper-plane"></i> 1-Click Live Inbox Test Send
                </h4>
                <p style="color: var(--text-muted); font-size: 12px; margin: 0 0 14px 0;">Send this configured template with dummy member data directly to your inbox to review SMTP formatting and mobile responsiveness.</p>
                
                <form method="POST" action="?tpl=<?php echo urlencode($selected_key); ?>" style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <input type="hidden" name="template_key" value="<?php echo htmlspecialchars($selected_key); ?>">
                    <input type="email" name="test_email_address" class="form-control-custom" style="flex: 1; min-width: 240px;" placeholder="Enter test email address..." value="anuragbawaskar680@gmail.com" required>
                    <button type="submit" name="send_test_email" class="btn-submit" style="background: linear-gradient(135deg, #0ea5e9, #0284c7); box-shadow: 0 4px 15px rgba(14, 165, 233, 0.4);">
                        <i class="fa-solid fa-bolt"></i> Send Test Email
                    </button>
                </form>
            </div>
        </div>

        <!-- RIGHT: Live Interactive Email Client Mockup Preview -->
        <div class="preview-wrapper">
            <div class="preview-header">
                <span style="font-size: 13px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted);">
                    👁️ Real-Time Email Preview
                </span>
                <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                    <div style="display: inline-flex; background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; padding: 2px;">
                        <button type="button" class="tab-btn active" id="btnGenderMale" style="padding: 4px 8px; font-size: 11px; border: none; border-radius: 6px;" onclick="setGenderPreview('male')">
                            👨 Male Member
                        </button>
                        <button type="button" class="tab-btn" id="btnGenderFemale" style="padding: 4px 8px; font-size: 11px; border: none; border-radius: 6px;" onclick="setGenderPreview('female')">
                            👩 Female Member
                        </button>
                    </div>
                    <button type="button" class="tab-btn" id="btnModeDesktop" style="padding: 6px 12px; font-size: 12px;" onclick="setPreviewMode('desktop')">
                        <i class="fa-solid fa-desktop"></i> Desktop
                    </button>
                    <button type="button" class="tab-btn" id="btnModeMobile" style="padding: 6px 12px; font-size: 12px;" onclick="setPreviewMode('mobile')">
                        <i class="fa-solid fa-mobile-screen"></i> Mobile
                    </button>
                </div>
            </div>

            <div class="client-mockup" id="clientMockupBox">
                <!-- Email Client Top Chrome Bar -->
                <div class="client-bar">
                    <span class="circle" style="background: #ef4444;"></span>
                    <span class="circle" style="background: #f59e0b;"></span>
                    <span class="circle" style="background: #10b981;"></span>
                    <span style="font-size: 11px; color: var(--text-muted); margin-left: 10px; font-weight: 600;" id="prevClientSubject">
                        Subject: <?php echo htmlspecialchars($current_tpl['subject']); ?>
                    </span>
                </div>

                <!-- Sender / Metadata Simulation -->
                <div style="background: #131d33; padding: 12px 18px; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 11.5px; color: #94a3b8; display: flex; justify-content: space-between;">
                    <div>From: <strong style="color: #fff;"><?php echo htmlspecialchars($gym['gym_name']); ?></strong> &lt;notifications@sudarshanfitness.de&gt;</div>
                    <div>To: <span style="color: #38bdf8;" id="prevRecipientHeader">Rahul Sharma &lt;member@domain.com&gt;</span></div>
                </div>

                <!-- Inner HTML Email Container -->
                <div class="email-render-box" id="emailRenderFrame">
                    <!-- Dynamic Live HTML Injected by JS -->
                </div>
            </div>
        </div>

    </div>

    <!-- Live Preview Script -->
    <script>
        const stickerCatalog = <?php echo json_encode($stickers); ?>;
        const currentTemplateKey = "<?php echo $selected_key; ?>";
        const gymName = "<?php echo addslashes($gym['gym_name']); ?>";
        const gymAddress = "<?php echo addslashes($gym['gym_address'] ?? 'Station Road, Khamgaon'); ?>";
        const gymPhone = "<?php echo addslashes($gym['gym_contact'] ?? '9325205075'); ?>";
        const maleWaLink = "<?php echo addslashes(!empty($gym['whatsapp_group_male']) ? $gym['whatsapp_group_male'] : 'https://chat.whatsapp.com/LMkWJql6kT91P5X59caDI0?s=sw&p=i&ilr=0'); ?>";
        const femaleWaLink = "<?php echo addslashes(!empty($gym['whatsapp_group_female']) ? $gym['whatsapp_group_female'] : 'https://chat.whatsapp.com/ISk4F5HqcJhBK477gJ55Ee?s=sw&p=i&ilr=0'); ?>";

        let previewGender = 'male';

        function setGenderPreview(gender) {
            previewGender = gender;
            const btnM = document.getElementById('btnGenderMale');
            const btnF = document.getElementById('btnGenderFemale');
            const recHeader = document.getElementById('prevRecipientHeader');
            if (gender === 'female') {
                btnF.classList.add('active');
                btnM.classList.remove('active');
                if (recHeader) recHeader.innerHTML = 'Priya Sharma &lt;priya@domain.com&gt;';
            } else {
                btnM.classList.add('active');
                btnF.classList.remove('active');
                if (recHeader) recHeader.innerHTML = 'Rahul Sharma &lt;rahul@domain.com&gt;';
            }
            updateLivePreview();
        }

        function insertTag(fieldId, tag) {
            const el = document.getElementById(fieldId);
            if (!el) return;
            const start = el.selectionStart || el.value.length;
            const end = el.selectionEnd || el.value.length;
            const val = el.value;
            el.value = val.substring(0, start) + tag + val.substring(end);
            el.focus();
            el.selectionStart = el.selectionEnd = start + tag.length;
            updateLivePreview();
        }

        function setColor(hex) {
            document.getElementById('inp_accent_color').value = hex;
            document.getElementById('inp_accent_color_hex').value = hex;
            updateLivePreview();
        }

        function syncColorInput(hex) {
            if (/^#[0-9A-F]{6}$/i.test(hex)) {
                document.getElementById('inp_accent_color').value = hex;
                updateLivePreview();
            }
        }

        function setPreviewMode(mode) {
            const box = document.getElementById('clientMockupBox');
            const btnD = document.getElementById('btnModeDesktop');
            const btnM = document.getElementById('btnModeMobile');
            if (mode === 'mobile') {
                box.style.maxWidth = '400px';
                box.style.margin = '0 auto';
                btnM.classList.add('active');
                btnD.classList.remove('active');
            } else {
                box.style.maxWidth = '100%';
                btnD.classList.add('active');
                btnM.classList.remove('active');
            }
        }

        function replacePlaceholders(text) {
            if (!text) return '';
            const isFemale = (previewGender === 'female');
            const memberName = isFemale ? "Priya Sharma" : "Rahul Sharma";
            const memberId = isFemale ? "MEM-F204" : "MEM-M108";
            const waLink = isFemale ? femaleWaLink : maleWaLink;

            return text
                .replace(/{gym_name}/g, gymName)
                .replace(/{member_name}/g, memberName)
                .replace(/{userid}/g, memberId)
                .replace(/{plan_name}/g, "12 Month Elite Muscle Building")
                .replace(/{expire_date}/g, "15-Nov-2026")
                .replace(/{days_left}/g, "5 Days")
                .replace(/{amount}/g, "12,500")
                .replace(/{receipt_no}/g, "REC-2026-8941")
                .replace(/{payment_mode}/g, "UPI / Google Pay")
                .replace(/{whatsapp_group_link}/g, waLink);
        }

        function updateLivePreview() {
            const subject = document.getElementById('inp_subject').value;
            const badgeTitle = document.getElementById('inp_badge_title').value;
            const badgeIcon = document.getElementById('inp_badge_icon').value;
            const accentColor = document.getElementById('inp_accent_color').value;
            const stickerKey = document.getElementById('inp_sticker').value;
            const greeting = document.getElementById('inp_greeting').value;
            const introText = document.getElementById('inp_intro_text').value;
            const bodyContent = document.getElementById('inp_body_content').value;
            const promoBanner = document.getElementById('inp_promo_banner').value;
            const ctaText = document.getElementById('inp_cta_text').value;
            const ctaUrl = document.getElementById('inp_cta_url').value;
            const footerText = document.getElementById('inp_footer_text').value;

            // Update top bar subject
            document.getElementById('prevClientSubject').innerText = "Subject: " + replacePlaceholders(subject);

            const sticker = stickerCatalog[stickerKey] || stickerCatalog['fitness_fire'];
            const stickerHtml = sticker ? sticker.icon_html : '🔥';

            const isFemale = (previewGender === 'female');
            const memberName = isFemale ? "Priya Sharma" : "Rahul Sharma";
            const memberId = isFemale ? "MEM-F204" : "MEM-M108";
            const waTitle = isFemale ? "Women's Fitness Community" : "Men's Fitness Community";
            const waLabel = isFemale ? "🌸 Join Women's WhatsApp Group" : "💪 Join Men's WhatsApp Group";
            const waDesc = isFemale 
                ? "Connect with female trainers, schedule nutrition checkups, and get daily motivation in our exclusive women's group."
                : "Connect with coaches, workout routines, heavy-lifting tips, and gym announcements in our exclusive men's group.";
            const waLink = isFemale ? femaleWaLink : maleWaLink;

            const waCardHtml = `
            <div style="background: linear-gradient(135deg, rgba(37, 211, 102, 0.12) 0%, rgba(37, 211, 102, 0.04) 100%); border: 1.5px solid #25d366; border-radius: 14px; padding: 18px; margin: 20px 0; text-align: center; box-shadow: 0 8px 20px rgba(37, 211, 102, 0.15);">
                <div style="font-size: 24px; margin-bottom: 5px;">💬</div>
                <div style="color: #25d366; font-size: 14px; font-weight: 800; letter-spacing: 0.5px; text-transform: uppercase; margin-bottom: 6px;">${waTitle}</div>
                <p style="color: #cbd5e1; font-size: 12px; line-height: 1.6; margin: 0 0 14px 0;">${waDesc}</p>
                <a href="${waLink}" target="_blank" style="display: inline-block; background: #25d366; color: #ffffff; text-decoration: none; padding: 10px 22px; border-radius: 10px; font-size: 13px; font-weight: 800; letter-spacing: 0.5px; box-shadow: 0 4px 14px rgba(37, 211, 102, 0.35); text-transform: uppercase;">
                    ${waLabel} &rarr;
                </a>
            </div>`;

            let promoHtml = '';
            if (promoBanner.trim() !== '') {
                promoHtml = `
                <div style="background: linear-gradient(135deg, rgba(255,255,255,0.05), rgba(255,255,255,0.02)); border: 1.5px dashed ${accentColor}; border-radius: 14px; padding: 14px 18px; margin: 18px 0; font-size: 13px; color: #f8fafc; line-height: 1.6; text-align: left;">
                    ${replacePlaceholders(promoBanner)}
                </div>`;
            }

            let receiptRowsHtml = '';
            if (currentTemplateKey === 'payment_receipt') {
                receiptRowsHtml = `
                <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid rgba(255,255,255,0.06); font-size: 12.5px;">
                    <span style="color: #94a3b8; font-weight: 600;">Receipt Number</span>
                    <span style="color: #10b981; font-weight: 800;">REC-2026-8941</span>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid rgba(255,255,255,0.06); font-size: 12.5px;">
                    <span style="color: #94a3b8; font-weight: 600;">Amount Received</span>
                    <span style="color: #ffffff; font-weight: 800;">₹12,500 (Paid in Full)</span>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid rgba(255,255,255,0.06); font-size: 12.5px;">
                    <span style="color: #94a3b8; font-weight: 600;">Payment Mode</span>
                    <span style="color: #38bdf8; font-weight: 700;">UPI / Google Pay</span>
                </div>`;
            }

            const liveEmailHtml = `
            <div style="max-width: 540px; margin: 0 auto; background: #1e293b; border-radius: 20px; border: 2px solid ${accentColor}; overflow: hidden; box-shadow: 0 15px 35px rgba(0,0,0,0.5);">
                <!-- Header -->
                <div style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); padding: 25px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.08);">
                    <div style="margin-bottom: 8px;">${stickerHtml}</div>
                    <div style="color:${accentColor}; font-size:11px; font-weight:900; letter-spacing:1.5px; text-transform:uppercase; background:rgba(255,255,255,0.06); border:1px solid ${accentColor}; display:inline-block; padding:4px 12px; border-radius:30px; margin-bottom:10px;">
                        ${badgeIcon} ${replacePlaceholders(badgeTitle)}
                    </div>
                    <h2 style="color:#ffffff; font-size:20px; font-weight:800; margin:0 0 4px 0;">${gymName}</h2>
                    <div style="color:#94a3b8; font-size:11px;">${gymAddress} | Phone: ${gymPhone}</div>
                </div>

                <!-- Body -->
                <div style="padding: 26px 22px; text-align: center;">
                    <h3 style="font-size:18px; font-weight:700; color:#ffffff; margin:0 0 12px 0;">${replacePlaceholders(greeting)}</h3>
                    
                    <p style="font-size:13.5px; color:#cbd5e1; line-height:1.6; margin:0 0 14px 0; text-align:left;">
                        ${replacePlaceholders(introText)}
                    </p>

                    <div style="background: rgba(0,0,0,0.35); border-radius: 12px; padding: 15px; margin: 16px 0; border: 1px solid rgba(255,255,255,0.08); text-align: left;">
                        <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid rgba(255,255,255,0.06); font-size: 12.5px;">
                            <span style="color: #94a3b8; font-weight: 600;">Member Name</span>
                            <span style="color: #ffffff; font-weight: 700;">${memberName}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid rgba(255,255,255,0.06); font-size: 12.5px;">
                            <span style="color: #94a3b8; font-weight: 600;">Membership ID</span>
                            <span style="color: #38bdf8; font-weight: 800;">${memberId}</span>
                        </div>
                        ${receiptRowsHtml}
                        <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid rgba(255,255,255,0.06); font-size: 12.5px;">
                            <span style="color: #94a3b8; font-weight: 600;">Subscription Plan</span>
                            <span style="color: #ffffff; font-weight: 700;">12 Month Elite Muscle Building</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding: 6px 0; font-size: 12.5px;">
                            <span style="color: #94a3b8; font-weight: 600;">Expiration Date</span>
                            <span style="color: #10b981; font-weight: 800;">15-Nov-2026</span>
                        </div>
                    </div>

                    <p style="font-size:13px; color:#cbd5e1; line-height:1.6; margin:0 0 16px 0; text-align:left;">
                        ${replacePlaceholders(bodyContent)}
                    </p>

                    ${waCardHtml}

                    ${promoHtml}

                    <div style="margin-top:22px;">
                        <a href="${replacePlaceholders(ctaUrl)}" style="display:block; box-sizing:border-box; background:linear-gradient(135deg, ${accentColor}, #ff8800); color:#ffffff; text-decoration:none; padding:14px 20px; border-radius:12px; font-weight:800; font-size:13.5px; text-align:center; box-shadow:0 6px 20px rgba(255,107,0,0.35); text-transform:uppercase; letter-spacing:0.5px;">
                            ${replacePlaceholders(ctaText)}
                        </a>
                    </div>
                </div>

                <!-- Footer -->
                <div style="background:#0f172a; padding:18px 20px; text-align:center; border-top:1px solid rgba(255,255,255,0.06); font-size:11px; color:#64748b; line-height:1.6;">
                    <div style="color:#94a3b8; margin-bottom:5px;">${replacePlaceholders(footerText)}</div>
                    <div>&copy; 2026 ${gymName}. All rights reserved. | <a href="https://sudarshanfitness.de/privacy" style="color:#94a3b8; text-decoration:underline;">Privacy Policy</a></div>
                </div>
            </div>`;

            document.getElementById('emailRenderFrame').innerHTML = liveEmailHtml;
        }

        // Initialize preview on page load
        document.addEventListener('DOMContentLoaded', () => {
            updateLivePreview();
            setPreviewMode('desktop');
        });
    </script>
</body>
</html>
