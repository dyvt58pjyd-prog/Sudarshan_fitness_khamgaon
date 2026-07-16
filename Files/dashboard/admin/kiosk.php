<?php
require '../../include/db_conn.php';
// We do page_protect but allow super_admin, owner, and reception to open the kiosk
page_protect();

if ($_SESSION['role'] !== 'super_admin' && $_SESSION['role'] !== 'owner' && $_SESSION['role'] !== 'reception') {
    echo "<head><script>alert('Access Denied');</script></head></html>";
    echo "<meta http-equiv='refresh' content='0; url=index.php'>";
    exit();
}

$gym = get_gym_details($con);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($gym['gym_name']); ?> | Front Desk Kiosk</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <!-- Load modern typography -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
    
    <style>
        :root {
            --primary: #0f172a;
            --primary-light: #1e293b;
            --accent: #ff6b00;
            --accent-hover: #ea580c;
            --bg-gradient: linear-gradient(135deg, #0f172a 0%, #020617 100%);
            --glass-bg: rgba(30, 41, 59, 0.45);
            --glass-border: rgba(255, 255, 255, 0.08);
            --glass-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --success: #10b981;
            --danger: #ef4444;
        }

        * {
            box-sizing: border-box;
            font-family: 'Outfit', sans-serif;
        }

        body {
            margin: 0;
            padding: 0;
            background: var(--bg-gradient);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        /* Top Header Area */
        .kiosk-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 40px;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--glass-border);
            z-index: 10;
        }

        .gym-logo-brand {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .gym-logo-brand img {
            max-height: 55px;
            max-width: 140px;
            object-fit: contain;
        }

        .gym-logo-brand h1 {
            margin: 0;
            font-size: 20px;
            font-weight: 800;
            letter-spacing: -0.5px;
            background: linear-gradient(90deg, #ffffff, var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .kiosk-clock {
            text-align: right;
        }

        .kiosk-clock .time {
            font-size: 24px;
            font-weight: 800;
            color: var(--accent);
            font-family: monospace;
            letter-spacing: 0.5px;
        }

        .kiosk-clock .date {
            font-size: 12px;
            color: var(--text-muted);
            font-weight: 500;
            margin-top: 2px;
        }

        /* Main Workspace Container */
        .kiosk-body {
            flex: 1;
            display: grid;
            grid-template-columns: 1.4fr 1fr;
            gap: 40px;
            padding: 40px;
            max-width: 1600px;
            width: 100%;
            margin: 0 auto;
            align-items: center;
        }

        @media (max-width: 1024px) {
            .kiosk-body {
                grid-template-columns: 1fr;
                padding: 20px;
                gap: 25px;
            }
        }

        /* Left: Check-in Box */
        .checkin-panel {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 40px;
            box-shadow: var(--glass-shadow);
            text-align: center;
            position: relative;
            min-height: 580px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow: hidden;
        }

        .checkin-panel::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, var(--primary-light), var(--accent), var(--accent-hover));
        }

        .panel-title {
            margin-top: 0;
            margin-bottom: 25px;
        }

        .panel-title h2 {
            margin: 0;
            font-size: 26px;
            font-weight: 700;
            color: #ffffff;
        }

        .panel-title p {
            margin: 8px 0 0 0;
            font-size: 14px;
            color: var(--text-muted);
        }

        /* Large input display */
        .input-display-container {
            position: relative;
            max-width: 440px;
            margin: 0 auto 25px auto;
            width: 100%;
        }

        .input-display {
            width: 100%;
            background: rgba(15, 23, 42, 0.8) !important;
            border: 2px solid var(--glass-border);
            border-radius: 16px;
            color: #ffffff;
            font-size: 26px;
            font-weight: 700;
            letter-spacing: 1px;
            padding: 18px 50px 18px 20px;
            text-align: center;
            outline: none;
            transition: all 0.3s ease;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.5);
        }

        .input-display:focus {
            border-color: var(--accent);
            box-shadow: 0 0 15px rgba(255, 107, 0, 0.25), inset 0 2px 4px rgba(0,0,0,0.5);
        }

        .clear-icon-btn {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 20px;
            cursor: pointer;
            transition: color 0.2s;
            padding: 5px;
        }

        .clear-icon-btn:hover {
            color: var(--danger);
        }

        /* Virtual Keypad styling */
        .virtual-keypad {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            max-width: 320px;
            margin: 0 auto;
            width: 100%;
        }

        .key-btn {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            color: #ffffff;
            font-size: 22px;
            font-weight: 600;
            height: 60px;
            cursor: pointer;
            transition: all 0.15s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .key-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: var(--accent);
            transform: scale(1.02);
        }

        .key-btn:active {
            background: var(--accent);
            color: #ffffff;
            transform: scale(0.98);
        }

        .key-btn.action-submit {
            background: linear-gradient(135deg, var(--accent), var(--accent-hover));
            font-size: 16px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 10px rgba(255, 107, 0, 0.2);
            border: none;
        }

        .key-btn.action-submit:hover {
            box-shadow: 0 6px 15px rgba(255, 107, 0, 0.35);
        }

        /* Right: Recent logs feed */
        .logs-panel {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 30px;
            box-shadow: var(--glass-shadow);
            height: 580px;
            display: flex;
            flex-direction: column;
        }

        .logs-panel-header {
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            padding-bottom: 15px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logs-panel-header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logs-panel-header h3 i {
            color: var(--accent);
        }

        .logs-feed-container {
            flex: 1;
            overflow-y: auto;
            padding-right: 5px;
        }

        .logs-feed-container::-webkit-scrollbar {
            width: 6px;
        }

        .logs-feed-container::-webkit-scrollbar-track {
            background: transparent;
        }

        .logs-feed-container::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 3px;
        }

        .log-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.04);
            border-radius: 12px;
            padding: 12px 18px;
            margin-bottom: 10px;
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .log-item-details strong {
            font-weight: 600;
            color: #ffffff;
            display: block;
            font-size: 13.5px;
        }

        .log-item-details span {
            font-size: 11px;
            color: var(--text-muted);
        }

        .log-item-meta {
            text-align: right;
        }

        .log-item-meta .time {
            font-size: 12px;
            font-weight: 700;
            color: var(--accent);
            font-family: monospace;
            display: block;
        }

        .log-item-meta .type-badge {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 2px 6px;
            border-radius: 4px;
            margin-top: 4px;
            display: inline-block;
        }

        .type-check-in {
            background: rgba(16, 185, 129, 0.15);
            color: var(--success);
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .type-check-out {
            background: rgba(239, 68, 68, 0.15);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        /* Result Popup Overlay */
        .result-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: var(--primary);
            border-radius: 24px;
            padding: 40px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 20;
            opacity: 0;
            pointer-events: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            transform: scale(0.95);
        }

        .result-overlay.active {
            opacity: 1;
            pointer-events: auto;
            transform: scale(1);
        }

        .result-icon {
            width: 85px;
            height: 85px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            margin-bottom: 25px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.2);
            animation: bounceIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        @keyframes bounceIn {
            0% { transform: scale(0.3); opacity: 0; }
            50% { transform: scale(1.05); }
            70% { transform: scale(0.9); }
            100% { transform: scale(1); opacity: 1; }
        }

        .result-icon.success {
            background: rgba(16, 185, 129, 0.15);
            border: 2px solid var(--success);
            color: var(--success);
        }

        .result-icon.danger {
            background: rgba(239, 68, 68, 0.15);
            border: 2px solid var(--danger);
            color: var(--danger);
        }

        .result-msg-big {
            font-size: 26px;
            font-weight: 800;
            margin-bottom: 5px;
            text-align: center;
        }

        .result-msg-sub {
            font-size: 14px;
            color: var(--text-muted);
            margin-bottom: 25px;
            text-align: center;
            max-width: 420px;
        }

        /* Frosted Detail Cards inside Success Overlay */
        .result-details-box {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 20px;
            width: 100%;
            max-width: 440px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 25px;
            text-align: left;
        }

        .detail-cell strong {
            display: block;
            font-size: 11px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .detail-cell span {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #ffffff;
            margin-top: 4px;
        }

        /* Streak container */
        .result-streak-box {
            text-align: center;
            background: linear-gradient(135deg, rgba(255, 107, 0, 0.15) 0%, rgba(255, 107, 0, 0.02) 100%);
            border: 1px solid rgba(255, 107, 0, 0.25);
            padding: 12px 20px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 25px;
            max-width: 440px;
            width: 100%;
        }

        .result-streak-box span {
            font-size: 15px;
            font-weight: 700;
            color: var(--accent);
        }

        .result-timer-countdown {
            font-size: 11px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: auto;
        }
    </style>
</head>
<body>

    <!-- Header Area -->
    <div class="kiosk-header">
        <div class="gym-logo-brand">
            <img src="<?php echo htmlspecialchars($gym['gym_logo']); ?>" alt="Gym Logo" />
            <div>
                <h1><?php echo htmlspecialchars($gym['gym_name']); ?></h1>
                <span style="font-size: 10px; font-weight: 700; color: var(--accent); text-transform: uppercase; letter-spacing: 1.5px; display: block; margin-top: 2px;">Front Desk Check-In Kiosk</span>
            </div>
        </div>

        <div style="display: flex; align-items: center; gap: 20px;">
            <a href="index.php" style="background: rgba(255,255,255,0.05); border: 1px solid var(--glass-border); border-radius: 8px; padding: 8px 15px; text-decoration: none; color: #ffffff; font-size: 12px; font-weight: 600; transition: background 0.2s;">
                ← Back to Dashboard
            </a>
            <div class="kiosk-clock">
                <div class="time" id="kiosk-clock-time">00:00:00</div>
                <div class="date" id="kiosk-clock-date">Loading date...</div>
            </div>
        </div>
    </div>

    <!-- Body Area -->
    <div class="kiosk-body">
        
        <!-- Left: Input & Keypad check-in panel -->
        <div class="checkin-panel">
            <div class="panel-title">
                <h2>Member Check-In Portal</h2>
                <p>Type your Gym Member ID or registered Mobile Number below to log attendance</p>
            </div>

            <!-- Display -->
            <div class="input-display-container">
                <input type="text" id="member-identifier" class="input-display" placeholder="ID or Mobile" autofocus autocomplete="off">
                <button class="clear-icon-btn" onclick="clearInput()" id="clear-btn" style="display: none;">✕</button>
            </div>

            <!-- Virtual Keypad -->
            <div class="virtual-keypad">
                <button class="key-btn" onclick="pressKey('1')">1</button>
                <button class="key-btn" onclick="pressKey('2')">2</button>
                <button class="key-btn" onclick="pressKey('3')">3</button>
                <button class="key-btn" onclick="pressKey('4')">4</button>
                <button class="key-btn" onclick="pressKey('5')">5</button>
                <button class="key-btn" onclick="pressKey('6')">6</button>
                <button class="key-btn" onclick="pressKey('7')">7</button>
                <button class="key-btn" onclick="pressKey('8')">8</button>
                <button class="key-btn" onclick="pressKey('9')">9</button>
                <button class="key-btn" onclick="clearInput()">C</button>
                <button class="key-btn" onclick="pressKey('0')">0</button>
                <button class="key-btn action-submit" onclick="submitCheckin()">Go</button>
            </div>

            <!-- Overlay for checkin results -->
            <div class="result-overlay" id="result-overlay">
                <div class="result-icon" id="result-icon-box">✓</div>
                <div class="result-msg-big" id="result-msg-big">Check-in Success!</div>
                <div class="result-msg-sub" id="result-msg-sub">Welcome back, Anurag Bawaskar. Enjoy your workout session.</div>
                
                <!-- Detail cards for success -->
                <div class="result-details-box" id="result-details-box">
                    <div class="detail-cell">
                        <strong>Member ID</strong>
                        <span id="res-uid">109</span>
                    </div>
                    <div class="detail-cell">
                        <strong>Active Plan</strong>
                        <span id="res-plan">6 Months Plan</span>
                    </div>
                    <div class="detail-cell">
                        <strong>Checked At</strong>
                        <span id="res-time">04:22 PM</span>
                    </div>
                    <div class="detail-cell">
                        <strong>Expiry Date</strong>
                        <span id="res-expiry" style="color: #ef4444;">12-Dec-2026</span>
                    </div>
                </div>

                <!-- Streak milestone -->
                <div class="result-streak-box" id="result-streak-box">
                    🔥 <span id="res-streak-text">Current Streak: 12 Days!</span>
                </div>

                <div class="result-timer-countdown" id="result-timer-countdown">
                    Closing in 5s...
                </div>
            </div>
        </div>

        <!-- Right: Recent logs feed -->
        <div class="logs-panel">
            <div class="logs-panel-header">
                <h3><i class="entypo-list"></i> Entrance Activity Feed</h3>
                <span style="font-size: 11px; background: rgba(255, 107, 0, 0.12); padding: 3px 8px; border-radius: 6px; color: var(--accent); font-weight: 600;">Real-Time</span>
            </div>
            
            <div class="logs-feed-container" id="kiosk-logs-feed">
                <!-- Log items populated dynamically -->
                <div style="text-align: center; color: var(--text-muted); padding: 40px;">Loading logs...</div>
            </div>
        </div>

    </div>

    <!-- Audio & Interaction Scripts -->
    <script>
        // Setup clock
        function updateClock() {
            const now = new Date();
            const hrs = now.getHours();
            const mins = now.getMinutes();
            const secs = now.getSeconds();
            
            const ampm = hrs >= 12 ? 'PM' : 'AM';
            const displayHrs = hrs % 12 === 0 ? 12 : hrs % 12;
            
            const timeStr = `${displayHrs.toString().padStart(2, '0')}:${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')} ${ampm}`;
            const dateStr = now.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            
            document.getElementById('kiosk-clock-time').textContent = timeStr;
            document.getElementById('kiosk-clock-date').textContent = dateStr;
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Virtual Keypad handling
        const display = document.getElementById('member-identifier');
        const clearBtn = document.getElementById('clear-btn');

        display.addEventListener('input', () => {
            clearBtn.style.display = display.value.length > 0 ? 'block' : 'none';
        });

        // Focus display immediately and capture enter key
        display.focus();
        display.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                submitCheckin();
            }
        });

        function pressKey(key) {
            display.value += key;
            clearBtn.style.display = 'block';
            display.focus();
        }

        function clearInput() {
            display.value = '';
            clearBtn.style.display = 'none';
            display.focus();
        }

        // Web Audio API Synthesized Sounds for Premium Touch
        let audioCtx = null;
        function getAudioContext() {
            if (!audioCtx) {
                audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            }
            return audioCtx;
        }

        function playSuccessSound() {
            try {
                const ctx = getAudioContext();
                const osc1 = ctx.createOscillator();
                const osc2 = ctx.createOscillator();
                const gainNode = ctx.createGain();

                osc1.type = 'sine';
                osc1.frequency.setValueAtTime(523.25, ctx.currentTime); // C5
                osc1.frequency.setValueAtTime(659.25, ctx.currentTime + 0.12); // E5

                gainNode.gain.setValueAtTime(0.12, ctx.currentTime);
                gainNode.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.35);

                osc1.connect(gainNode);
                gainNode.connect(ctx.destination);
                
                osc1.start();
                osc1.stop(ctx.currentTime + 0.35);
            } catch (e) {
                console.error("Audio Context playback error:", e);
            }
        }

        function playFailureSound() {
            try {
                const ctx = getAudioContext();
                const osc = ctx.createOscillator();
                const gainNode = ctx.createGain();

                osc.type = 'sawtooth';
                osc.frequency.setValueAtTime(140, ctx.currentTime); // low buzz
                osc.frequency.setValueAtTime(110, ctx.currentTime + 0.15); 

                gainNode.gain.setValueAtTime(0.18, ctx.currentTime);
                gainNode.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.45);

                osc.connect(gainNode);
                gainNode.connect(ctx.destination);
                
                osc.start();
                osc.stop(ctx.currentTime + 0.45);
            } catch (e) {
                console.error("Audio Context playback error:", e);
            }
        }

        // Fetch recent logs feed
        function pollAttendanceLogs() {
            fetch('../../api/get_latest_attendance.php')
            .then(r => r.json())
            .then(res => {
                const feed = document.getElementById('kiosk-logs-feed');
                if (!res.success || !res.logs || res.logs.length === 0) {
                    feed.innerHTML = `<div style="text-align: center; color: var(--text-muted); padding: 40px;">No entrance activity today.</div>`;
                    return;
                }
                
                let html = '';
                res.logs.slice(0, 10).forEach(log => {
                    const typeClass = log.type === 'Check-In' ? 'type-check-in' : 'type-check-out';
                    html += `
                        <div class="log-item">
                            <div class="log-item-details">
                                <strong>${log.name}</strong>
                                <span>ID: ${log.biometric_id ? log.biometric_id : 'Kiosk Lookup'}</span>
                            </div>
                            <div class="log-item-meta">
                                <span class="time">${log.time}</span>
                                <span class="type-badge ${typeClass}">${log.type}</span>
                            </div>
                        </div>
                    `;
                });
                feed.innerHTML = html;
            })
            .catch(err => {
                console.error("Logs poll error:", err);
            });
        }
        
        pollAttendanceLogs();
        setInterval(pollAttendanceLogs, 7000);

        // Submit Check-in logic
        let checkinInProgress = false;
        function submitCheckin() {
            const val = display.value.trim();
            if (val.length === 0 || checkinInProgress) return;

            checkinInProgress = true;
            
            fetch('../../api/kiosk_checkin.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ identifier: val })
            })
            .then(r => r.json())
            .then(data => {
                checkinInProgress = false;
                if (data.success) {
                    playSuccessSound();
                    showResultPopup(true, data);
                    clearInput();
                    pollAttendanceLogs();
                } else {
                    playFailureSound();
                    showResultPopup(false, data);
                }
            })
            .catch(err => {
                checkinInProgress = false;
                playFailureSound();
                showResultPopup(false, { message: 'Connection failed. Please check backend server.' });
            });
        }

        // Overlay result controller
        let overlayTimeout = null;
        function showResultPopup(success, data) {
            const overlay = document.getElementById('result-overlay');
            const icon = document.getElementById('result-icon-box');
            const msgBig = document.getElementById('result-msg-big');
            const msgSub = document.getElementById('result-msg-sub');
            const detailsBox = document.getElementById('result-details-box');
            const streakBox = document.getElementById('result-streak-box');
            const counter = document.getElementById('result-timer-countdown');

            // Reset timers
            if (overlayTimeout) clearTimeout(overlayTimeout);

            if (success) {
                // Success view
                icon.className = 'result-icon success';
                icon.textContent = '✓';
                
                if (data.action === 'check-out') {
                    msgBig.textContent = 'Goodbye, ' + data.name + '!';
                    msgSub.textContent = 'Check-out registered. Rest up and recover well!';
                } else if (data.action === 'already-logged') {
                    msgBig.textContent = 'Already Logged!';
                    msgSub.textContent = data.name + ' has already checked in and out today.';
                } else {
                    msgBig.textContent = 'Welcome, ' + data.name + '!';
                    msgSub.textContent = 'Check-in registered. Let\'s crush today\'s gym session!';
                }

                // Show details
                detailsBox.style.display = 'grid';
                document.getElementById('res-uid').textContent = data.uid;
                document.getElementById('res-plan').textContent = data.plan;
                document.getElementById('res-time').textContent = data.time;
                document.getElementById('res-expiry').textContent = data.expiry;

                // Color expiry if close
                const expiryParts = data.expiry.split('-');
                const today = new Date();
                const expiryDate = new Date(data.expiry);
                const diffTime = expiryDate - today;
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                if (diffDays <= 5) {
                    document.getElementById('res-expiry').style.color = '#ef4444';
                } else {
                    document.getElementById('res-expiry').style.color = '#10b981';
                }

                // Streak details
                if (data.streak > 0) {
                    streakBox.style.display = 'flex';
                    document.getElementById('res-streak-text').textContent = 'Attendance Streak: ' + data.streak + ' Days!';
                } else {
                    streakBox.style.display = 'none';
                }
            } else {
                // Failure view
                icon.className = 'result-icon danger';
                icon.textContent = '✕';
                msgBig.textContent = 'Access Denied';
                msgSub.textContent = data.message;
                
                detailsBox.style.display = 'none';
                streakBox.style.display = 'none';
            }

            overlay.classList.add('active');

            // Countdown timer (5 seconds auto close)
            let secondsLeft = 5;
            counter.textContent = `Closing in ${secondsLeft}s...`;
            
            const timerInterval = setInterval(() => {
                secondsLeft--;
                if (secondsLeft > 0) {
                    counter.textContent = `Closing in ${secondsLeft}s...`;
                } else {
                    clearInterval(timerInterval);
                }
            }, 1000);

            overlayTimeout = setTimeout(() => {
                overlay.classList.remove('active');
                clearInterval(timerInterval);
                display.focus();
            }, 5000);
        }
    </script>
</body>
</html>
