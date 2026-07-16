<?php
require '../../include/db_conn.php';
page_protect();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>SUDARSHAN FITNESS | Attendance Portal</title>
    <link rel="stylesheet" href="../../css/style.css" id="style-resource-5">
    <script type="text/javascript" src="../../js/Script.js"></script>
    <link rel="stylesheet" href="../../css/dashMain.css">
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
    <link rel="stylesheet" href="../../css/premium.css">
    
    <!-- Load face-api.js from vladmandic CDN -->
    <script src="https://cdn.jsdelivr.net/npm/@vladmandic/face-api/dist/face-api.js"></script>

    <style>
        :root {
            --scan-color: #3b82f6;
            --scan-glow: rgba(59, 130, 246, 0.3);
        }
        .page-container .sidebar-menu #main-menu li#attendance_portal > a {
            background-color: rgba(59, 130, 246, 0.1) !important;
            color: var(--accent-primary) !important;
            font-weight: 600 !important;
            box-shadow: inset 3px 0 0 var(--accent-primary);
        }
        .attendance-container {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
            margin-top: 20px;
        }
        .camera-card {
            flex: 1 1 500px;
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 25px;
            box-shadow: var(--glass-shadow);
            position: relative;
            overflow: hidden;
        }
        .controls-card {
            flex: 0 0 380px;
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 25px;
            box-shadow: var(--glass-shadow);
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        /* Cyberpunk Webcam container & HUD styling */
        .webcam-wrapper {
            position: relative;
            width: 100%;
            max-width: 480px;
            aspect-ratio: 4/3;
            border-radius: 16px;
            overflow: hidden;
            background: #050505;
            margin: 0 auto;
            border: 2px solid var(--scan-color);
            box-shadow: 0 0 30px var(--scan-glow);
            transition: all 0.3s ease;
        }
        /* CRT scanlines effect */
        .webcam-wrapper::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: 
                linear-gradient(rgba(18, 16, 16, 0) 50%, rgba(0, 0, 0, 0.25) 50%), 
                linear-gradient(90deg, rgba(255, 0, 0, 0.04), rgba(0, 255, 0, 0.01), rgba(0, 0, 255, 0.04));
            background-size: 100% 4px, 6px 100%;
            z-index: 4;
            pointer-events: none;
            opacity: 0.35;
        }
        /* Scanning laser sweep line */
        .webcam-wrapper::after {
            content: '';
            position: absolute;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(95deg, transparent, var(--scan-color), transparent);
            box-shadow: 0 0 15px var(--scan-color), 0 0 5px var(--scan-color);
            animation: scanning 3.5s linear infinite;
            z-index: 5;
            pointer-events: none;
        }
        @keyframes scanning {
            0% { top: 0%; }
            50% { top: 100%; }
            100% { top: 0%; }
        }
        #webcam {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        #overlay_canvas {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 10;
            pointer-events: none;
        }

        /* HUD Scanner Labels Overlay */
        .hud-scanner-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 8;
            box-sizing: border-box;
            padding: 15px;
        }
        .hud-corner {
            position: absolute;
            width: 16px;
            height: 16px;
            border: 2px solid var(--scan-color);
            transition: border-color 0.3s ease;
        }
        .hud-corner.top-left {
            top: 12px;
            left: 12px;
            border-right: none;
            border-bottom: none;
        }
        .hud-corner.top-right {
            top: 12px;
            right: 12px;
            border-left: none;
            border-bottom: none;
        }
        .hud-corner.bottom-left {
            bottom: 12px;
            left: 12px;
            border-right: none;
            border-top: none;
        }
        .hud-corner.bottom-right {
            bottom: 12px;
            right: 12px;
            border-left: none;
            border-top: none;
        }
        .hud-telemetry-left {
            position: absolute;
            bottom: 18px;
            left: 18px;
            font-family: monospace;
            font-size: 8px;
            color: var(--scan-color);
            text-shadow: 0 0 5px var(--scan-glow);
            line-height: 1.4;
            opacity: 0.8;
            letter-spacing: 0.5px;
            transition: color 0.3s ease;
        }
        .hud-telemetry-right {
            position: absolute;
            bottom: 18px;
            right: 18px;
            font-family: monospace;
            font-size: 8px;
            color: var(--scan-color);
            text-shadow: 0 0 5px var(--scan-glow);
            line-height: 1.4;
            text-align: right;
            opacity: 0.8;
            letter-spacing: 0.5px;
            transition: color 0.3s ease;
        }
        .hud-title-badge {
            position: absolute;
            top: 15px;
            left: 50%;
            transform: translateX(-50%);
            font-family: monospace;
            font-size: 9px;
            font-weight: bold;
            color: var(--scan-color);
            background: rgba(0, 0, 0, 0.75);
            border: 1px solid var(--scan-color);
            padding: 2px 10px;
            border-radius: 4px;
            letter-spacing: 1.5px;
            text-shadow: 0 0 5px var(--scan-glow);
            box-shadow: 0 0 10px var(--scan-glow);
            opacity: 0.9;
            transition: all 0.3s ease;
        }

        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
        }
        .status-loading { background: var(--warning); animation: pulse 1.5s infinite; }
        .status-ready { background: var(--success); }
        .status-idle { background: var(--text-muted); }
        @keyframes pulse {
            0% { opacity: 0.4; }
            50% { opacity: 1; }
            100% { opacity: 0.4; }
        }
        
        /* Directory list styling */
        #enrolled_members_list {
            max-height: 280px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 8px;
            padding-right: 5px;
        }
        #enrolled_members_list::-webkit-scrollbar {
            width: 6px;
        }
        #enrolled_members_list::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.02);
            border-radius: 10px;
        }
        #enrolled_members_list::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }
        #enrolled_members_list::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .member-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(255,255,255,0.02);
            border: 1px solid rgba(255,255,255,0.04);
            padding: 8px 12px;
            border-radius: 12px;
            transition: all 0.25s ease;
        }
        .member-row:hover {
            background: rgba(255,255,255,0.05);
            border-color: rgba(255,255,255,0.1);
        }

        /* Holographic success/expired overlays */
        .success-overlay, .expired-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(8, 10, 8, 0.96);
            z-index: 100;
            border-radius: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 25px 30px;
            text-align: center;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            border: 2px solid rgba(16, 185, 129, 0.25);
            box-shadow: inset 0 0 50px rgba(16, 185, 129, 0.1);
            backdrop-filter: blur(15px);
        }
        .expired-overlay {
            background: rgba(12, 8, 8, 0.97);
            border-color: rgba(239, 68, 68, 0.3);
            box-shadow: inset 0 0 50px rgba(239, 68, 68, 0.15);
        }
        .success-overlay.active, .expired-overlay.active {
            opacity: 1;
            pointer-events: auto;
        }
        
        /* Rotating technology frames for avatar */
        .success-avatar-container, .expired-avatar-container {
            position: relative;
            width: 145px;
            height: 145px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .success-avatar-container::before, .expired-avatar-container::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 2px dashed var(--success);
            animation: rotatingRing 15s linear infinite;
            box-shadow: 0 0 20px rgba(16, 185, 129, 0.2);
        }
        .expired-avatar-container::before {
            border-color: #ef4444;
            animation: rotatingRing 8s linear infinite reverse;
            box-shadow: 0 0 20px rgba(239, 68, 68, 0.2);
        }
        @keyframes rotatingRing {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .success-avatar, .expired-avatar {
            width: 115px;
            height: 115px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--success);
            box-shadow: 0 0 25px rgba(16, 185, 129, 0.35);
            z-index: 2;
        }
        .expired-avatar {
            border-color: #ef4444;
            box-shadow: 0 0 25px rgba(239, 68, 68, 0.35);
        }

        /* Cyber Diagnostic Panel Readouts */
        .cyber-diag-panel {
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            padding: 12px 18px;
            margin: 15px 0;
            width: 100%;
            max-width: 320px;
            display: flex;
            flex-direction: column;
            gap: 6px;
            font-family: monospace;
        }
        .cyber-diag-row {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            letter-spacing: 0.5px;
        }
        .cyber-diag-label {
            color: var(--text-muted);
        }
        .cyber-diag-value {
            font-weight: bold;
            color: #fff;
        }

        .expired-icon {
            animation: alertBounce 1s infinite alternate;
        }
        @keyframes alertBounce {
            0% { transform: translateY(0); }
            100% { transform: translateY(-5px); }
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Temporary Passcode Modal styling */
        .temp-code-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.85);
            z-index: 10000;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(12px);
            transition: all 0.3s ease;
        }
        .temp-code-card {
            background: rgba(18, 18, 18, 0.95);
            border: 2px solid var(--accent-primary);
            border-radius: 20px;
            padding: 40px;
            width: 420px;
            max-width: 90%;
            text-align: center;
            box-shadow: 0 10px 30px rgba(255, 107, 0, 0.25);
            position: relative;
        }
        .close-modal {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 24px;
            color: var(--text-muted);
            cursor: pointer;
            transition: color 0.2s;
        }
        .close-modal:hover {
            color: #fff;
        }
    </style>
</head>
<body class="page-body page-fade" onload="collapseSidebar()">
    <div class="page-container sidebar-collapsed" id="navbarcollapse">
        <div class="sidebar-menu">
            <header class="logo-env">
                <div class="logo">
                    <a href="main.php">
                        <?php 
                        $sidebar_logo = $gym_settings_data["gym_logo"] ?? "../../images/logo.png";
                        ?>
                        <img src="<?php echo htmlspecialchars($sidebar_logo); ?>" alt="Gym Logo" style="max-height: 80px; max-width: 192px;" />
                    </a>
                </div>
                <div class="sidebar-collapse" onclick="collapseSidebar()">
                    <a href="#" class="sidebar-collapse-icon with-animation">
                        <i class="entypo-menu"></i>
                    </a>
                </div>
            </header>
            <?php include('nav.php'); ?>
        </div>

        <div class="main-content">
            <div class="row">
                <div class="col-md-6 col-sm-8 clearfix"></div>
                <div class="col-md-6 col-sm-4 clearfix hidden-xs">
                    <ul class="list-inline links-list pull-right">
                        <li>Welcome <?php echo $_SESSION['full_name']; ?></li>
                        <li>
                            <a href="logout.php">
                                Log Out <i class="entypo-logout right"></i>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <h2>Attendance &amp; Check-In Portal</h2>
            <hr />

            <div class="attendance-container">
                <!-- Camera view -->
                <div class="camera-card">
                    <!-- Success Overlay -->
                    <div id="success_overlay" class="success-overlay">
                        <div class="success-avatar-container">
                            <img id="success_photo" src="" class="success-avatar">
                        </div>
                        <h2 id="success_name" style="margin: 0; font-weight: 700; color: #fff; text-shadow: 0 0 10px rgba(16, 185, 129, 0.4);">--</h2>
                        <span id="success_type_badge" style="display: inline-block; padding: 4px 15px; border-radius: 20px; font-weight: bold; font-size: 13px; margin-top: 10px; border: 1px solid var(--success); background: rgba(16, 185, 129, 0.15); color: var(--success); letter-spacing: 1px;">CHECK-IN SUCCESSFUL</span>
                        
                        <div class="cyber-diag-panel">
                            <div class="cyber-diag-row">
                                <span class="cyber-diag-label">[SYSTEM CHECK]</span>
                                <span class="cyber-diag-value" style="color: var(--success);">OK</span>
                            </div>
                            <div class="cyber-diag-row">
                                <span class="cyber-diag-label">[BIOMETRIC ID]</span>
                                <span class="cyber-diag-value" style="color: var(--success);">VERIFIED</span>
                            </div>
                            <div class="cyber-diag-row">
                                <span class="cyber-diag-label">[GATE STATUS]</span>
                                <span class="cyber-diag-value" style="color: var(--success);">UNLOCKED</span>
                            </div>
                        </div>
                        
                        <div style="margin-top: 20px; display: flex; gap: 40px; text-align: center; background: rgba(0,0,0,0.3); padding: 10px 25px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.03);">
                            <div>
                                <span style="font-size: 10px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Entry Time</span>
                                <h3 id="success_entry" style="margin: 5px 0 0 0; font-weight: 700; color: #fff; font-family: monospace;">--:--</h3>
                            </div>
                            <div>
                                <span style="font-size: 10px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Exit Time</span>
                                <h3 id="success_exit" style="margin: 5px 0 0 0; font-weight: 700; color: #fff; font-family: monospace;">--:--</h3>
                            </div>
                        </div>
                        
                        <div style="margin-top: 20px; font-size: 13px; color: var(--text-muted); font-family: monospace;">
                            <span id="success_date">--</span>
                        </div>
                    </div>

                    <!-- Expired Overlay -->
                    <div id="expired_overlay" class="expired-overlay">
                        <div class="expired-icon" style="margin-bottom: 15px;">
                            <div style="width: 45px; height: 45px; border-radius: 50%; background: rgba(239, 68, 68, 0.15); border: 2px solid #ef4444; display: flex; align-items: center; justify-content: center; margin: 0 auto; box-shadow: 0 0 15px rgba(239, 68, 68, 0.3);">
                                <i class="entypo-attention" style="font-size: 20px; color: #ef4444;"></i>
                            </div>
                        </div>
                        <div class="expired-avatar-container">
                            <img id="expired_photo" src="" class="expired-avatar">
                        </div>
                        <h2 id="expired_name" style="margin: 0; font-weight: 700; color: #fff; text-shadow: 0 0 10px rgba(239, 68, 68, 0.4);">--</h2>
                        <span style="display: inline-block; padding: 4px 15px; border-radius: 20px; font-weight: bold; font-size: 13px; margin-top: 10px; background: rgba(239, 68, 68, 0.2); color: #f87171; border: 1px solid #ef4444; letter-spacing: 1px;">
                            MEMBERSHIP EXPIRED
                        </span>
                        
                        <div class="cyber-diag-panel" style="border-color: rgba(239, 68, 68, 0.2);">
                            <div class="cyber-diag-row">
                                <span class="cyber-diag-label">[SYSTEM CHECK]</span>
                                <span class="cyber-diag-value" style="color: #ef4444;">ALERT</span>
                            </div>
                            <div class="cyber-diag-row">
                                <span class="cyber-diag-label">[AUTH KEY]</span>
                                <span class="cyber-diag-value" style="color: #ef4444;">EXPIRED</span>
                            </div>
                            <div class="cyber-diag-row">
                                <span class="cyber-diag-label">[GATE STATUS]</span>
                                <span class="cyber-diag-value" style="color: #ef4444;">LOCKED</span>
                            </div>
                        </div>
                        
                        <div style="margin-top: 20px; text-align: center; background: rgba(239, 68, 68, 0.05); padding: 10px 25px; border-radius: 12px; border: 1px solid rgba(239, 68, 68, 0.15); width: 100%; max-width: 320px;">
                            <span style="font-size: 10px; color: var(--text-muted); text-transform: uppercase; display: block; margin-bottom: 5px; letter-spacing: 0.5px;">Details</span>
                            <h4 id="expired_plan_info" style="margin: 0; font-weight: 600; color: #f87171; font-size: 13px; font-family: monospace;">Expired On: --</h4>
                        </div>
                    </div>

                    <h3 style="margin-top: 0; margin-bottom: 20px; font-weight: 600;">Real-time Camera Stream</h3>
                    <div class="webcam-wrapper" id="webcam_wrapper">
                        <video id="webcam" autoplay playsinline width="480" height="360"></video>
                        <canvas id="overlay_canvas" width="480" height="360"></canvas>
                        
                        <!-- Futuristic Cyber HUD overlays -->
                        <div class="hud-scanner-overlay">
                            <div class="hud-corner top-left"></div>
                            <div class="hud-corner top-right"></div>
                            <div class="hud-corner bottom-left"></div>
                            <div class="hud-corner bottom-right"></div>
                            <div class="hud-telemetry-left" id="telemetry_l">SYS_LOC: FRONT_GATE_01<br>STATUS: ACTIVE<br>CAM: ONLINE</div>
                            <div class="hud-telemetry-right" id="telemetry_r">RESOL: 480x360<br>FPS: 30<br>SYS_SEC: SECURE</div>
                            <div class="hud-title-badge">TITAN BIO-SCAN v3.5</div>
                        </div>
                    </div>

                    <div style="margin-top: 20px;">
                        <button id="btn_temp_code" class="a1-btn a1-blue" style="width: 100%; height: 45px; font-weight: bold; font-size: 14px; border-radius: 10px; display: flex; align-items: center; justify-content: center; gap: 8px;">
                            <i class="entypo-keyboard" style="font-size: 16px;"></i> Enter via Temporary Code
                        </button>
                    </div>
                </div>

                <!-- Temporary Code Modal Overlay -->
                <div id="temp_code_modal" class="temp-code-modal">
                    <div class="temp-code-card">
                        <span class="close-modal" id="close_temp_modal">&times;</span>
                        
                        <div style="width: 60px; height: 60px; border-radius: 50%; background: rgba(255, 107, 0, 0.1); border: 1px solid var(--accent-primary); display: flex; align-items: center; justify-content: center; margin: 0 auto 20px auto; color: var(--accent-primary);">
                            <i class="entypo-lock" style="font-size: 28px;"></i>
                        </div>
                        
                        <h3 style="margin-top: 0; font-weight: 700; color: #fff; font-size: 20px;">Manual Gate Entry</h3>
                        <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 25px; line-height: 1.5;">Enter your membership entry code below to authenticate and unlock the gate.</p>
                        
                        <form id="tempCodeForm" onsubmit="submitTempCode(event)">
                            <input type="text" id="entry_code_input" class="form-control-premium" required maxlength="10" placeholder="Type Code Here" style="text-align: center; font-size: 24px; letter-spacing: 4px; font-family: monospace; font-weight: bold; height: 50px; border-radius: 12px; margin-bottom: 20px; border: 1px solid rgba(255,107,0,0.3) !important;">
                            
                            <div id="modal_error_msg" style="color: #ef4444; font-size: 13px; font-weight: bold; margin-bottom: 20px; display: none;">Invalid Entry Code</div>
                            
                            <button type="submit" class="a1-btn a1-blue" style="width: 100%; height: 45px; font-weight: bold; font-size: 15px; border-radius: 10px;">Verify &amp; Unlock Gate</button>
                        </form>
                    </div>
                </div>

                <!-- Controls & Enrolled Directory -->
                <div class="controls-card">
                    <div>
                        <h4 style="margin-top: 0; margin-bottom: 15px; font-weight: 600;">Face ID Engine Status</h4>
                        
                        <div style="background: rgba(0,0,0,0.2); padding: 12px 15px; border-radius: 8px; font-size: 13px; display: flex; align-items: center; margin-bottom: 20px;">
                            <span id="status_dot" class="status-dot status-loading"></span>
                            <span id="status_text">Loading Face ID engines...</span>
                        </div>

                        <h4 style="margin-top: 20px; margin-bottom: 10px; font-weight: 600; font-size: 13px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Enrolled Members Directory</h4>
                        <div id="enrolled_members_list">
                            <?php
                            $members = [];
                            $q_mems = "SELECT userid, username, photo FROM users WHERE photo IS NOT NULL AND photo != '' ORDER BY username";
                            $res_mems = mysqli_query($con, $q_mems);
                            if ($res_mems && mysqli_num_rows($res_mems) > 0) {
                                while ($row_mem = mysqli_fetch_assoc($res_mems)) {
                                    $m_photo_raw = $row_mem['photo'];
                                    if (strpos($m_photo_raw, '../../Sudarshan Data Folder/') === 0) {
                                        $m_photo_raw = '/Sudarshan Data Folder/' . substr($m_photo_raw, 26);
                                    } elseif (strpos($m_photo_raw, '../../') === 0) {
                                        $m_photo_raw = '/' . substr($m_photo_raw, 6);
                                    }
                                    $m_photo = htmlspecialchars($m_photo_raw);
                                    $m_name = htmlspecialchars($row_mem['username']);
                                    $m_id = htmlspecialchars($row_mem['userid']);
                                    
                                    // Save in PHP array to json encode later for JS
                                    $members[] = [
                                        'userid' => $m_id,
                                        'username' => $m_name,
                                        'photo' => $m_photo
                                    ];
                                    ?>
                                    <div class="member-row" id="member_row_<?php echo $m_id; ?>">
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <img src="<?php echo $m_photo; ?>" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover; border: 1px solid rgba(255,255,255,0.1);">
                                            <div>
                                                <div style="font-size: 12px; font-weight: 600; color: #fff; max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?php echo $m_name; ?></div>
                                                <div style="font-size: 10px; color: var(--text-muted); font-family: monospace;">ID: <?php echo $m_id; ?></div>
                                            </div>
                                        </div>
                                        <span class="biometric-indicator" id="bio_status_<?php echo $m_id; ?>" style="font-size: 10px; color: var(--warning); display: flex; align-items: center; gap: 4px;">
                                            <i class="entypo-spin entypo-cw" style="animation: spin 2s linear infinite; display: inline-block;"></i> Loading
                                        </span>
                                    </div>
                                    <?php
                                }
                            } else {
                                echo "<p style='color: var(--text-muted); font-size: 13px;'>No members with face profiles registered.</p>";
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Face Entry Details Section -->
            <div class="row" style="margin-top: 40px; clear: both;">
                <div class="col-md-12">
                    <div class="portal-card" style="background: var(--glass-bg); border: 1px solid var(--glass-border); border-radius: 20px; padding: 25px; box-shadow: var(--glass-shadow); margin-bottom: 30px;">
                        <h3 style="margin-top: 0; margin-bottom: 20px; color: #ffffff; font-weight: 700; display: flex; align-items: center; gap: 10px;">
                            <i class="entypo-list" style="color: var(--accent-primary);"></i> Face Entry Details
                        </h3>
                        
                        <div class="table-responsive">
                            <table class="table" style="width: 100%; border-collapse: collapse; color: var(--text-main);">
                                <thead>
                                    <tr style="border-bottom: 2px solid rgba(255,255,255,0.08); text-align: left;">
                                        <th style="padding: 12px 15px; color: var(--text-muted); font-weight: 600;">Photo</th>
                                        <th style="padding: 12px 15px; color: var(--text-muted); font-weight: 600;">Member ID</th>
                                        <th style="padding: 12px 15px; color: var(--text-muted); font-weight: 600;">Name</th>
                                        <th style="padding: 12px 15px; color: var(--text-muted); font-weight: 600;">Date &amp; Day</th>
                                        <th style="padding: 12px 15px; color: var(--text-muted); font-weight: 600;">Time of Entry</th>
                                        <th style="padding: 12px 15px; color: var(--text-muted); font-weight: 600;">Time of Exit</th>
                                        <th style="padding: 12px 15px; color: var(--text-muted); font-weight: 600; text-align: right;">Status</th>
                                    </tr>
                                </thead>
                                <tbody id="attendance_logs_body">
                                    <?php
                                    date_default_timezone_set("Asia/Calcutta");
                                    $today_date = date('Y-m-d');
                                    $q_logs = "SELECT a.*, u.username, u.photo 
                                               FROM attendance a 
                                               INNER JOIN users u ON a.uid = u.userid 
                                               WHERE a.date = '$today_date' 
                                               ORDER BY a.entry_time DESC";
                                    $res_logs = mysqli_query($con, $q_logs);
                                    if ($res_logs && mysqli_num_rows($res_logs) > 0) {
                                        while ($row_log = mysqli_fetch_assoc($res_logs)) {
                                            $avatar = $row_log['photo'] ? $row_log['photo'] : '../../images/logo.png';
                                            $entry = date('h:i A', strtotime($row_log['entry_time']));
                                            $exit = $row_log['exit_time'] ? date('h:i A', strtotime($row_log['exit_time'])) : '--:--';
                                            $status = $row_log['exit_time'] ? 'Checked Out' : 'Active In Gym';
                                            $status_badge_color = $row_log['exit_time'] ? 'var(--info)' : 'var(--success)';
                                            $status_badge_bg = $row_log['exit_time'] ? 'rgba(59, 130, 246, 0.15)' : 'rgba(16, 185, 129, 0.15)';
                                            ?>
                                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                                <td style="padding: 10px 15px;">
                                                    <img src="<?php echo htmlspecialchars($avatar); ?>" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 1px solid rgba(255,255,255,0.1);">
                                                </td>
                                                <td style="padding: 12px 15px; font-family: monospace;"><?php echo htmlspecialchars($row_log['uid']); ?></td>
                                                <td style="padding: 12px 15px; font-weight: 600; color: #fff;"><?php echo htmlspecialchars($row_log['username']); ?></td>
                                                <td style="padding: 12px 15px;"><?php echo date('d-M-Y', strtotime($row_log['date'])) . ' (' . date('l', strtotime($row_log['date'])) . ')'; ?></td>
                                                <td style="padding: 12px 15px; color: var(--success); font-weight: 600;"><?php echo $entry; ?></td>
                                                <td style="padding: 12px 15px; color: var(--warning); font-weight: 600;"><?php echo $exit; ?></td>
                                                <td style="padding: 12px 15px; text-align: right;">
                                                    <span style="display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: bold; border: 1px solid <?php echo $status_badge_color; ?>; background: <?php echo $status_badge_bg; ?>; color: <?php echo $status_badge_color; ?>;">
                                                        <?php echo $status; ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php
                                        }
                                    } else {
                                        ?>
                                        <tr>
                                            <td colspan="7" style="padding: 25px; text-align: center; color: var(--text-muted);">
                                                No attendance records logged for today yet.
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <?php include('footer.php'); ?>
        </div>
    </div>

    <script>
        const enrolledMembers = <?php echo json_encode($members); ?>;
        let isEnginesReady = false;
        let webcamStream = null;
        let faceMatcher = null;
        let isProcessing = false;
        let detectionInterval = null;
        
        // Futuristic animation arrays
        let latestDetections = [];
        let matchParticles = [];
        let matchShockwaves = [];
        
        // FPS telemetry tracker
        let lastFrameTime = performance.now();
        let frameCount = 0;
        let currentFps = 30;

        // Vlad Mandic's CDN models
        const MODEL_URL = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model/';

        async function initEngines() {
            try {
                updateStatus('Loading neural networks...', 'loading');
                // Load neural engines
                await faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL);
                await faceapi.nets.ssdMobilenetv1.loadFromUri(MODEL_URL);
                await faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL);
                await faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL);
                
                isEnginesReady = true;
                updateStatus('Neural engines online. Loading member profiles...', 'loading');
                
                // Load visual face profiles in parallel / sequence
                await loadAllMemberDescriptors();
                
                // Start webcam stream
                await startVideo();
                
                // Launch 60fps futuristic animation scan loop
                requestAnimationFrame(animateScannerEffects);
            } catch (err) {
                console.error(err);
                updateStatus('Failed to load neural models.', 'loading');
                alert("Face ID Initialization Error: " + err);
            }
        }

        function updateStatus(text, type) {
            const textEl = document.getElementById('status_text');
            const dotEl = document.getElementById('status_dot');
            if (textEl) textEl.innerText = text;
            if (dotEl) {
                dotEl.className = 'status-dot';
                if (type === 'loading') dotEl.classList.add('status-loading');
                else if (type === 'ready') dotEl.classList.add('status-ready');
                else dotEl.classList.add('status-idle');
            }
        }

        function setScannerColor(color, glow) {
            document.documentElement.style.setProperty('--scan-color', color);
            document.documentElement.style.setProperty('--scan-glow', glow);
        }

        function resetScannerState() {
            setScannerColor('#3b82f6', 'rgba(59, 130, 246, 0.3)');
            isProcessing = false;
            latestDetections = [];
        }

        function loadImage(src) {
            return new Promise((resolve, reject) => {
                const img = new Image();
                img.src = src;
                img.onload = () => resolve(img);
                img.onerror = (e) => reject(e);
            });
        }

        async function loadAllMemberDescriptors() {
            if (enrolledMembers.length === 0) {
                updateStatus('No registered members found.', 'ready');
                return;
            }

            const labeledDescriptors = [];
            let loadedCount = 0;
            const totalCount = enrolledMembers.length;

            for (const member of enrolledMembers) {
                try {
                    const img = await loadImage(member.photo);
                    
                    // Detect using TinyFaceDetector first for speed
                    let result = await faceapi.detectSingleFace(img, new faceapi.TinyFaceDetectorOptions({ inputSize: 160 }))
                        .withFaceLandmarks()
                        .withFaceDescriptor();

                    // Fallback to high-accuracy SsdMobilenetv1 if undetected
                    if (!result) {
                        result = await faceapi.detectSingleFace(img)
                            .withFaceLandmarks()
                            .withFaceDescriptor();
                    }

                    const indicator = document.getElementById(`bio_status_${member.userid}`);
                    const row = document.getElementById(`member_row_${member.userid}`);

                    if (result) {
                        labeledDescriptors.push(new faceapi.LabeledFaceDescriptors(member.userid, [result.descriptor]));
                        loadedCount++;
                        
                        if (indicator) {
                            indicator.innerHTML = '<i class="entypo-check" style="color: var(--success); font-size: 12px;"></i> Loaded';
                            indicator.style.color = 'var(--success)';
                        }
                        if (row) {
                            row.style.background = 'rgba(16, 185, 129, 0.04)';
                            row.style.borderColor = 'rgba(16, 185, 129, 0.15)';
                        }
                    } else {
                        console.warn(`No face detected in reference photo for: ${member.username}`);
                        if (indicator) {
                            indicator.innerHTML = '<i class="entypo-cancel" style="color: #ef4444; font-size: 12px;"></i> No Face';
                            indicator.style.color = '#ef4444';
                        }
                        if (row) {
                            row.style.background = 'rgba(239, 68, 68, 0.04)';
                            row.style.borderColor = 'rgba(239, 68, 68, 0.15)';
                        }
                    }
                } catch (err) {
                    console.error(`Error loading image for member: ${member.username}`, err);
                    const indicator = document.getElementById(`bio_status_${member.userid}`);
                    const row = document.getElementById(`member_row_${member.userid}`);
                    if (indicator) {
                        const errMsg = (err && err.message) ? err.message : 'Not Found/Format';
                        indicator.innerHTML = `<i class="entypo-cancel" style="color: #ef4444; font-size: 12px;"></i> Err: ${errMsg}`;
                        indicator.style.color = '#ef4444';
                    }
                    if (row) {
                        row.style.background = 'rgba(239, 68, 68, 0.04)';
                        row.style.borderColor = 'rgba(239, 68, 68, 0.15)';
                    }
                }
            }

            if (labeledDescriptors.length > 0) {
                faceMatcher = new faceapi.FaceMatcher(labeledDescriptors, 0.6);
                updateStatus(`Active Face ID ready (${loadedCount}/${totalCount} members loaded).`, 'ready');
            } else {
                updateStatus('Face ID database empty: no valid face profiles found.', 'idle');
            }
        }

        async function startVideo() {
            const video = document.getElementById('webcam');
            try {
                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    throw new Error("Camera Access Blocked by Browser Security!\n\nModern browsers require a secure connection (HTTPS) or a 'localhost' hostname to access cameras on mobile/tablet.\n\nPlease connect to the app via a secure tunnel (e.g. Localtunnel or Ngrok with HTTPS) to use Face ID.");
                }
                webcamStream = await navigator.mediaDevices.getUserMedia({ 
                    video: { width: 480, height: 360, facingMode: 'user' } 
                });
                video.srcObject = webcamStream;
                startDetectionLoop();
            } catch (err) {
                console.error("Camera access failed", err);
                alert(err.message || err);
                updateStatus('Camera blocked or unavailable. Make sure HTTPS is enabled.', 'idle');
            }
        }

        function startDetectionLoop() {
            const video = document.getElementById('webcam');
            const canvas = document.getElementById('overlay_canvas');
            const displaySize = { width: 480, height: 360 };
            faceapi.matchDimensions(canvas, displaySize);

            if (detectionInterval) clearInterval(detectionInterval);
            
            detectionInterval = setInterval(async () => {
                if (!isEnginesReady || isProcessing || video.paused || video.ended || !faceMatcher) {
                    latestDetections = [];
                    return;
                }

                const detections = await faceapi.detectAllFaces(video, new faceapi.TinyFaceDetectorOptions({ inputSize: 160 }))
                    .withFaceLandmarks()
                    .withFaceDescriptors();

                const resizedDetections = faceapi.resizeResults(detections, displaySize);
                
                latestDetections = resizedDetections.map(det => {
                    const bestMatch = faceMatcher.findBestMatch(det.descriptor);
                    const userid = bestMatch.label;
                    const distance = bestMatch.distance;
                    
                    const isMatch = userid !== 'unknown';
                    const score = Math.round((1 - distance) * 100);
                    const box = det.detection.box;
                    
                    let label = 'Unknown Face';
                    let boxColor = '#ef4444';
                    
                    if (isMatch) {
                        const matchedMember = enrolledMembers.find(m => m.userid === userid);
                        if (matchedMember) {
                            label = `${matchedMember.username} (${score}%)`;
                        } else {
                            label = `Member ID: ${userid} (${score}%)`;
                        }
                        boxColor = '#10b981';
                    }
                    
                    return {
                        box: box,
                        label: label,
                        boxColor: boxColor,
                        isMatch: isMatch,
                        userid: userid
                    };
                });

                if (latestDetections.length > 0) {
                    const matchedFace = latestDetections.find(d => d.isMatch);
                    if (matchedFace) {
                        triggerCheckIn(matchedFace.userid, matchedFace.box);
                    }
                }
            }, 300);
        }

        function hexToRgba(hex, alpha) {
            hex = hex.replace('#', '');
            let r = parseInt(hex.substring(0, 2), 16);
            let g = parseInt(hex.substring(2, 4), 16);
            let b = parseInt(hex.substring(4, 6), 16);
            return `rgba(${r}, ${g}, ${b}, ${alpha})`;
        }

        function drawFuturisticReticle(canvas, box, label, color, isMatch) {
            const ctx = canvas.getContext('2d');
            const { x, y, width, height } = box;
            const padding = 10;
            
            ctx.save();
            ctx.shadowColor = color;
            ctx.shadowBlur = 8;
            ctx.strokeStyle = color;
            ctx.fillStyle = color;
            
            // 1. Draw thin outer bounding square
            ctx.lineWidth = 1;
            ctx.strokeStyle = hexToRgba(color, 0.25);
            ctx.strokeRect(x - padding, y - padding, width + padding * 2, height + padding * 2);
            
            // 2. Draw cyberpunk corner brackets
            ctx.strokeStyle = color;
            ctx.lineWidth = 3;
            const cornerLen = Math.min(22, width * 0.22);
            
            // Top-Left corner
            ctx.beginPath();
            ctx.moveTo(x - padding, y - padding + cornerLen);
            ctx.lineTo(x - padding, y - padding);
            ctx.lineTo(x - padding + cornerLen, y - padding);
            ctx.stroke();
            
            // Top-Right corner
            ctx.beginPath();
            ctx.moveTo(x + width + padding - cornerLen, y - padding);
            ctx.lineTo(x + width + padding, y - padding);
            ctx.lineTo(x + width + padding, y - padding + cornerLen);
            ctx.stroke();
            
            // Bottom-Left corner
            ctx.beginPath();
            ctx.moveTo(x - padding, y - padding + height + padding * 2 - cornerLen);
            ctx.lineTo(x - padding, y - padding + height + padding * 2);
            ctx.lineTo(x - padding + cornerLen, y - padding + height + padding * 2);
            ctx.stroke();
            
            // Bottom-Right corner
            ctx.beginPath();
            ctx.moveTo(x + width + padding - cornerLen, y - padding + height + padding * 2);
            ctx.lineTo(x + width + padding, y - padding + height + padding * 2);
            ctx.lineTo(x + width + padding, y - padding + height + padding * 2 - cornerLen);
            ctx.stroke();
            
            // 3. Center Crosshairs tick marks
            ctx.shadowBlur = 0;
            ctx.lineWidth = 1;
            ctx.strokeStyle = hexToRgba(color, 0.5);
            
            // Left tick
            ctx.beginPath();
            ctx.moveTo(x - padding - 6, y + height/2);
            ctx.lineTo(x - padding + 2, y + height/2);
            ctx.stroke();
            
            // Right tick
            ctx.beginPath();
            ctx.moveTo(x + width + padding - 2, y + height/2);
            ctx.lineTo(x + width + padding + 6, y + height/2);
            ctx.stroke();
            
            // 4. Draw rotating circular HUD overlay
            const centerX = x + width / 2;
            const centerY = y + height / 2;
            const radius = Math.max(width, height) / 2 + padding * 1.5;
            const time = Date.now() * 0.0025; // rotation angle
            
            ctx.strokeStyle = hexToRgba(color, 0.3);
            ctx.lineWidth = 1.2;
            
            ctx.beginPath();
            ctx.arc(centerX, centerY, radius, time, time + Math.PI * 0.45);
            ctx.stroke();
            
            ctx.beginPath();
            ctx.arc(centerX, centerY, radius, time + Math.PI, time + Math.PI * 1.45);
            ctx.stroke();
            
            // Inner counter-rotating dashboard arc
            ctx.strokeStyle = hexToRgba(color, 0.18);
            ctx.beginPath();
            ctx.arc(centerX, centerY, radius - 5, -time, -time + Math.PI * 0.3);
            ctx.stroke();
            ctx.beginPath();
            ctx.arc(centerX, centerY, radius - 5, -time + Math.PI, -time + Math.PI * 1.3);
            ctx.stroke();
            
            // 5. Digital text readouts next to frame
            ctx.fillStyle = color;
            ctx.shadowColor = color;
            ctx.shadowBlur = 4;
            
            // Label tag (e.g. member name) at bottom
            ctx.font = 'bold 10px monospace';
            const labelText = label.toUpperCase();
            const textWidth = ctx.measureText(labelText).width;
            const textY = y + height + padding + 15;
            
            ctx.fillStyle = 'rgba(0,0,0,0.8)';
            ctx.strokeStyle = color;
            ctx.lineWidth = 1;
            ctx.beginPath();
            if (typeof ctx.roundRect === 'function') {
                ctx.roundRect(x - padding, textY - 11, textWidth + 12, 16, 3);
            } else {
                ctx.rect(x - padding, textY - 11, textWidth + 12, 16);
            }
            ctx.fill();
            ctx.stroke();
            
            ctx.fillStyle = color;
            ctx.fillText(labelText, x - padding + 6, textY);
            
            // Status marker tag at top
            ctx.font = '8px monospace';
            const statusTag = isMatch ? '[LOCK STATE: IDENTIFIED]' : '[SYS STATE: SEARCHING...]';
            const statusWidth = ctx.measureText(statusTag).width;
            
            ctx.fillStyle = 'rgba(0,0,0,0.8)';
            ctx.beginPath();
            if (typeof ctx.roundRect === 'function') {
                ctx.roundRect(x - padding, y - padding - 17, statusWidth + 10, 12, 2);
            } else {
                ctx.rect(x - padding, y - padding - 17, statusWidth + 10, 12);
            }
            ctx.fill();
            ctx.stroke();
            
            ctx.fillStyle = color;
            ctx.fillText(statusTag, x - padding + 5, y - padding - 8);
            
            ctx.restore();
        }

        function spawnMatchEffects(box, color) {
            const centerX = box.x + box.width / 2;
            const centerY = box.y + box.height / 2;
            
            // 1. Spawning primary shockwave ring
            matchShockwaves.push({
                x: centerX,
                y: centerY,
                radius: Math.max(box.width, box.height) / 2,
                maxRadius: 250,
                alpha: 1.0,
                color: color,
                speed: 9
            });
            
            // 2. Spawning tech dashed outer telemetry ring
            matchShockwaves.push({
                x: centerX,
                y: centerY,
                radius: Math.max(box.width, box.height) / 2,
                maxRadius: 210,
                alpha: 0.9,
                color: color,
                speed: 5.5,
                drawDash: true
            });

            // 3. Spawning binary matrix floaters
            for (let i = 0; i < 40; i++) {
                matchParticles.push({
                    x: box.x + Math.random() * box.width,
                    y: box.y + box.height - Math.random() * 15,
                    vx: (Math.random() - 0.5) * 3.5,
                    vy: -Math.random() * 5.5 - 2, // Floating upwards
                    size: Math.random() * 5 + 3,
                    alpha: 1.0,
                    life: 1.0,
                    decay: Math.random() * 0.025 + 0.012,
                    color: color,
                    char: Math.random() > 0.55 ? '1' : '0'
                });
            }
        }

        function animateScannerEffects() {
            requestAnimationFrame(animateScannerEffects);
            
            const canvas = document.getElementById('overlay_canvas');
            if (!canvas) return;
            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            // 1. Telemetry FPS monitoring check
            const now = performance.now();
            frameCount++;
            if (now - lastFrameTime >= 1000) {
                currentFps = frameCount;
                frameCount = 0;
                lastFrameTime = now;
                const telemetryEl = document.getElementById('telemetry_r');
                if (telemetryEl) {
                    telemetryEl.innerHTML = `RESOL: 480x360<br>FPS: ${currentFps}<br>SYS_SEC: SECURE`;
                }
            }
            
            // 2. Render reticle boxes for all detected faces
            if (isEnginesReady && !isProcessing && faceMatcher) {
                latestDetections.forEach(det => {
                    drawFuturisticReticle(canvas, det.box, det.label, det.boxColor, det.isMatch);
                });
            }
            
            // 3. Render Shockwaves
            for (let i = matchShockwaves.length - 1; i >= 0; i--) {
                const s = matchShockwaves[i];
                s.radius += s.speed;
                s.alpha = 1 - (s.radius / s.maxRadius);
                
                if (s.alpha <= 0 || s.radius >= s.maxRadius) {
                    matchShockwaves.splice(i, 1);
                    continue;
                }
                
                ctx.save();
                ctx.shadowColor = s.color;
                ctx.shadowBlur = 12;
                ctx.strokeStyle = hexToRgba(s.color, s.alpha);
                ctx.lineWidth = s.drawDash ? 2 : 4;
                
                if (s.drawDash) {
                    ctx.setLineDash([6, 12]);
                }
                
                ctx.beginPath();
                ctx.arc(s.x, s.y, s.radius, 0, Math.PI * 2);
                ctx.stroke();
                ctx.restore();
            }
            
            // 4. Render binary digital matrix particles
            for (let i = matchParticles.length - 1; i >= 0; i--) {
                const p = matchParticles[i];
                p.x += p.vx;
                p.y += p.vy;
                p.life -= p.decay;
                p.alpha = p.life;
                
                if (p.life <= 0) {
                    matchParticles.splice(i, 1);
                    continue;
                }
                
                ctx.save();
                ctx.shadowColor = p.color;
                ctx.shadowBlur = 6;
                ctx.fillStyle = hexToRgba(p.color, p.alpha);
                ctx.font = `bold ${p.size + 4}px monospace`;
                ctx.fillText(p.char, p.x, p.y);
                ctx.restore();
            }
        }

        function triggerCheckIn(uid, box) {
            if (isProcessing) return;
            isProcessing = true;
            
            // Spawn neon matching effects immediately on the target box area
            if (box) {
                spawnMatchEffects(box, '#10b981');
            }
            
            // Change HUD scan color to success green
            setScannerColor('#10b981', 'rgba(16, 185, 129, 0.3)');

            // Log attendance via AJAX
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'log_attendance.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        try {
                            const res = JSON.parse(xhr.responseText);
                            if (res.status === 'success') {
                                playSuccessBeep();
                                showSuccessOverlay(res);
                            } else if (res.status === 'expired') {
                                playErrorBuzzer();
                                // Spawn Red warning telemetry rings centered on camera
                                const camBox = { x: 120, y: 90, width: 240, height: 180 };
                                spawnMatchEffects(camBox, '#ef4444');
                                showExpiredOverlay(res);
                            } else {
                                playErrorBuzzer();
                                alert("Attendance Error: " + res.message);
                                resetScannerState();
                            }
                        } catch (e) {
                            console.error(e, xhr.responseText);
                            playErrorBuzzer();
                            alert("Server parsing error. Check connection.");
                            resetScannerState();
                        }
                    } else {
                        playErrorBuzzer();
                        alert("Communication failed. Server returned status: " + xhr.status);
                        resetScannerState();
                    }
                }
            };
            xhr.send('uid=' + encodeURIComponent(uid));
        }

        function playSuccessBeep() {
            try {
                const context = new (window.AudioContext || window.webkitAudioContext)();
                const osc = context.createOscillator();
                const gain = context.createGain();
                
                osc.connect(gain);
                gain.connect(context.destination);
                
                osc.type = 'sine';
                osc.frequency.setValueAtTime(880, context.currentTime); // High pitch check chime
                gain.gain.setValueAtTime(0.08, context.currentTime);
                
                osc.start();
                osc.stop(context.currentTime + 0.15);
            } catch (e) {
                console.error("Audio Context blocked", e);
            }
        }

        function playErrorBuzzer() {
            try {
                const context = new (window.AudioContext || window.webkitAudioContext)();
                const osc = context.createOscillator();
                const gain = context.createGain();
                
                osc.connect(gain);
                gain.connect(context.destination);
                
                osc.type = 'sawtooth';
                osc.frequency.setValueAtTime(120, context.currentTime); // Low warning alarm
                gain.gain.setValueAtTime(0.12, context.currentTime);
                
                osc.start();
                osc.stop(context.currentTime + 0.6);
            } catch (e) {
                console.error("Audio Context blocked", e);
            }
        }

        function showSuccessOverlay(data) {
            const overlay = document.getElementById('success_overlay');
            document.getElementById('success_photo').src = data.photo;
            document.getElementById('success_name').innerText = data.username;
            document.getElementById('success_entry').innerText = data.entry_time;
            document.getElementById('success_exit').innerText = data.exit_time;
            document.getElementById('success_date').innerText = data.day + ', ' + data.date;
            
            const badge = document.getElementById('success_type_badge');
            if (data.type === 'entry') {
                badge.innerText = 'CHECK-IN SUCCESSFUL';
                badge.style.background = 'rgba(16, 185, 129, 0.2)';
                badge.style.color = 'var(--success)';
                badge.style.border = '1px solid var(--success)';
            } else {
                badge.innerText = 'CHECK-OUT SUCCESSFUL';
                badge.style.background = 'rgba(59, 130, 246, 0.2)';
                badge.style.color = 'var(--info)';
                badge.style.border = '1px solid var(--info)';
            }

            overlay.classList.add('active');

            // Refresh logs
            refreshAttendanceLogs();

            setTimeout(() => {
                overlay.classList.remove('active');
                resetScannerState();
            }, 5000);
        }

        function showExpiredOverlay(data) {
            const overlay = document.getElementById('expired_overlay');
            
            // Set dynamic scanline color to red during failure display
            setScannerColor('#ef4444', 'rgba(239, 68, 68, 0.3)');
            
            document.getElementById('expired_photo').src = data.photo;
            document.getElementById('expired_name').innerText = data.username;
            document.getElementById('expired_plan_info').innerText = 'Expired On: ' + data.expire_date;

            overlay.classList.add('active');

            setTimeout(() => {
                overlay.classList.remove('active');
                resetScannerState();
            }, 5000);
        }

        function refreshAttendanceLogs() {
            const xhr = new XMLHttpRequest();
            xhr.open('GET', 'fetch_today_logs.php', true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    document.getElementById('attendance_logs_body').innerHTML = xhr.responseText;
                }
            };
            xhr.send();
        }

        // Modal Event Listeners & Functions for Temporary Code Manual Entry
        document.getElementById('btn_temp_code').addEventListener('click', function() {
            isProcessing = true; // Pause Face ID matching loop
            document.getElementById('temp_code_modal').style.display = 'flex';
            document.getElementById('entry_code_input').focus();
        });

        document.getElementById('close_temp_modal').addEventListener('click', function() {
            document.getElementById('temp_code_modal').style.display = 'none';
            document.getElementById('entry_code_input').value = '';
            document.getElementById('modal_error_msg').style.display = 'none';
            resetScannerState(); // Resume Face ID matching loop
        });

        // Close modal when clicking outside card
        document.getElementById('temp_code_modal').addEventListener('click', function(e) {
            if (e.target === this) {
                document.getElementById('temp_code_modal').style.display = 'none';
                document.getElementById('entry_code_input').value = '';
                document.getElementById('modal_error_msg').style.display = 'none';
                resetScannerState();
            }
        });

        function submitTempCode(e) {
            e.preventDefault();
            const code = document.getElementById('entry_code_input').value.trim();
            if (!code) return;

            // Set scanner color to orange during passcode check
            setScannerColor('#ff6b00', 'rgba(255, 107, 0, 0.3)');

            // Submit passcode via AJAX
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'log_attendance.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        try {
                            const res = JSON.parse(xhr.responseText);
                            if (res.status === 'success') {
                                playSuccessBeep();
                                showSuccessOverlay(res);
                                
                                // Reset and close modal
                                document.getElementById('temp_code_modal').style.display = 'none';
                                document.getElementById('entry_code_input').value = '';
                                document.getElementById('modal_error_msg').style.display = 'none';
                            } else if (res.status === 'expired') {
                                playErrorBuzzer();
                                showExpiredOverlay(res);
                                
                                // Close modal on expiration
                                document.getElementById('temp_code_modal').style.display = 'none';
                                document.getElementById('entry_code_input').value = '';
                                document.getElementById('modal_error_msg').style.display = 'none';
                            } else {
                                playErrorBuzzer();
                                setScannerColor('#ef4444', 'rgba(239, 68, 68, 0.3)');
                                const errorEl = document.getElementById('modal_error_msg');
                                errorEl.innerText = res.message;
                                errorEl.style.display = 'block';
                            }
                        } catch (err) {
                            console.error(err, xhr.responseText);
                            playErrorBuzzer();
                            setScannerColor('#ef4444', 'rgba(239, 68, 68, 0.3)');
                            document.getElementById('modal_error_msg').innerText = "Server parsing error.";
                            document.getElementById('modal_error_msg').style.display = 'block';
                        }
                    } else {
                        playErrorBuzzer();
                        setScannerColor('#ef4444', 'rgba(239, 68, 68, 0.3)');
                        document.getElementById('modal_error_msg').innerText = "Communication failed.";
                        document.getElementById('modal_error_msg').style.display = 'block';
                    }
                }
            };
            xhr.send('code=' + encodeURIComponent(code));
        }

        window.addEventListener('DOMContentLoaded', initEngines);
    </script>
</body>
</html>
