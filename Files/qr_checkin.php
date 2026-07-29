<?php
require_once __DIR__ . '/include/db_conn.php';
$gym = get_gym_details($con);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>QR Gate Terminal | <?php echo htmlspecialchars($gym['gym_name']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <style>
        :root {
            --bg-color: #0b0f19;
            --accent-primary: #ff6b00;
            --accent-green: #10b981;
            --accent-blue: #38bdf8;
            --accent-red: #ef4444;
            --card-bg: rgba(30, 41, 59, 0.7);
            --glass-border: rgba(255, 255, 255, 0.1);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }

        body {
            background: var(--bg-color);
            color: #ffffff;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        .header-bar {
            background: rgba(15, 23, 42, 0.95);
            border-bottom: 1px solid var(--glass-border);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            backdrop-filter: blur(10px);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .brand img {
            max-height: 50px;
            max-width: 140px;
            object-fit: contain;
        }

        .brand-text h1 {
            font-size: 22px;
            font-weight: 900;
            color: #fff;
            letter-spacing: -0.5px;
        }

        .brand-text span {
            font-size: 11px;
            font-weight: 800;
            color: var(--accent-primary);
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }

        .terminal-status {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.3);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 800;
            color: var(--accent-green);
        }

        .status-dot {
            width: 8px;
            height: 8px;
            background: var(--accent-green);
            border-radius: 50%;
            box-shadow: 0 0 10px var(--accent-green);
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(1.2); }
            100% { opacity: 1; transform: scale(1); }
        }

        .clock-display {
            text-align: right;
        }

        .clock-time {
            font-size: 20px;
            font-weight: 800;
            color: #fff;
        }

        .clock-date {
            font-size: 11px;
            color: #94a3b8;
            font-weight: 600;
        }

        .main-container {
            flex: 1;
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 30px;
            padding: 30px;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
        }

        @media (max-width: 900px) {
            .main-container { grid-template-columns: 1fr; }
        }

        .scanner-card {
            background: var(--card-bg);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 25px;
            display: flex;
            flex-direction: column;
            align-items: center;
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
            position: relative;
        }

        .card-title {
            font-size: 18px;
            font-weight: 800;
            color: #fff;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-subtitle {
            font-size: 12px;
            color: #94a3b8;
            margin-bottom: 20px;
            text-align: center;
        }

        #qr-reader {
            width: 100%;
            max-width: 450px;
            border-radius: 16px;
            overflow: hidden;
            border: 2px solid var(--accent-primary);
            box-shadow: 0 10px 25px rgba(255, 107, 0, 0.2);
            background: #000;
        }

        #qr-reader video {
            object-fit: cover !important;
            border-radius: 14px;
        }

        .scanner-hint {
            margin-top: 15px;
            font-size: 12px;
            color: #94a3b8;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .manual-card {
            background: var(--card-bg);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 25px;
            display: flex;
            flex-direction: column;
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
        }

        .input-display-box {
            background: rgba(0,0,0,0.4);
            border: 2px solid rgba(255, 107, 0, 0.4);
            border-radius: 16px;
            padding: 15px 20px;
            font-size: 24px;
            font-weight: 800;
            color: #fff;
            text-align: center;
            letter-spacing: 2px;
            width: 100%;
            outline: none;
            margin-bottom: 20px;
        }

        .input-display-box:focus {
            border-color: var(--accent-primary);
            box-shadow: 0 0 15px rgba(255,107,0,0.3);
        }

        .keypad {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            flex: 1;
        }

        .key-btn {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 14px;
            color: #fff;
            font-size: 22px;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.2s ease;
            padding: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .key-btn:hover {
            background: rgba(255, 107, 0, 0.2);
            border-color: var(--accent-primary);
            transform: scale(1.02);
        }

        .key-btn.action-btn {
            background: linear-gradient(135deg, var(--accent-primary), #ff8800);
            border: none;
            box-shadow: 0 5px 15px rgba(255,107,0,0.4);
        }

        /* FULLSCREEN OVERLAY MODAL */
        .overlay-modal {
            position: fixed;
            top: 0; left: 0; width: 100vw; height: 100vh;
            background: rgba(11, 15, 25, 0.95);
            backdrop-filter: blur(20px);
            z-index: 9999;
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 30px;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }

        .modal-content {
            background: rgba(30, 41, 59, 0.9);
            border: 2px solid var(--glass-border);
            border-radius: 28px;
            padding: 40px;
            max-width: 550px;
            width: 100%;
            text-align: center;
            box-shadow: 0 25px 50px rgba(0,0,0,0.6);
        }

        .modal-status-icon {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 45px;
            margin: 0 auto 20px auto;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
        }

        .modal-status-icon.success { background: var(--accent-green); color: #fff; }
        .modal-status-icon.checkout { background: var(--accent-blue); color: #fff; }
        .modal-status-icon.expired { background: var(--accent-red); color: #fff; }

        .modal-member-name {
            font-size: 26px;
            font-weight: 900;
            color: #fff;
            margin-bottom: 5px;
        }

        .modal-member-id {
            font-size: 13px;
            color: #94a3b8;
            font-weight: 700;
            margin-bottom: 20px;
        }

        /* PROMINENT EXPIRY BANNER */
        .expiry-banner {
            background: rgba(0,0,0,0.4);
            border: 2px solid var(--accent-green);
            border-radius: 16px;
            padding: 16px 20px;
            margin: 20px 0;
            text-align: center;
        }

        .expiry-banner.expired-style {
            border-color: var(--accent-red);
            background: rgba(239, 68, 68, 0.15);
        }

        .expiry-banner.warning-style {
            border-color: #f59e0b;
            background: rgba(245, 158, 11, 0.15);
        }

        .expiry-title {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-weight: 800;
            color: #94a3b8;
            margin-bottom: 6px;
        }

        .expiry-date-text {
            font-size: 22px;
            font-weight: 900;
            color: #fff;
        }

        .days-badge {
            display: inline-block;
            margin-top: 6px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
        }

        .days-badge.active-badge { background: rgba(16,185,129,0.2); color: var(--accent-green); }
        .days-badge.warning-badge { background: rgba(245,158,11,0.2); color: #f59e0b; }
        .days-badge.expired-badge { background: rgba(239,68,68,0.2); color: var(--accent-red); }

        .close-countdown {
            font-size: 11px;
            color: #94a3b8;
            font-weight: 600;
            margin-top: 20px;
        }
    </style>
</head>
<body>

    <!-- Header Area -->
    <div class="header-bar">
        <div class="brand">
            <img src="<?php echo htmlspecialchars($gym['gym_logo']); ?>" alt="Gym Logo" />
            <div class="brand-text">
                <h1><?php echo htmlspecialchars($gym['gym_name']); ?></h1>
                <span>📷 Member QR Entrance Terminal</span>
            </div>
        </div>

        <div class="terminal-status">
            <div class="status-dot"></div>
            <span>GATE SCANNER ONLINE</span>
        </div>

        <div class="clock-display">
            <div class="clock-time" id="clock-time">00:00:00</div>
            <div class="clock-date" id="clock-date">Loading date...</div>
        </div>
    </div>

    <!-- Main Body Area -->
    <div class="main-container">
        <!-- Scanner Card -->
        <div class="scanner-card">
            <div class="card-title">📷 Scan Member QR Code</div>
            <div class="card-subtitle">Show your Member App QR code to the camera for instant check-in</div>
            
            <div id="qr-reader"></div>

            <div class="scanner-hint">
                <span>💡 Auto-detects QR codes in real-time</span>
            </div>
        </div>

        <!-- Manual Input Card -->
        <div class="manual-card">
            <div class="card-title">🔢 Manual Entrance Keypad</div>
            <div class="card-subtitle">Enter Member ID or Phone Number if QR scan is unavailable</div>

            <input type="text" id="member-input" class="input-display-box" placeholder="ID or Mobile" autofocus autocomplete="off">

            <div class="keypad">
                <button class="key-btn" onclick="pressNum('1')">1</button>
                <button class="key-btn" onclick="pressNum('2')">2</button>
                <button class="key-btn" onclick="pressNum('3')">3</button>
                <button class="key-btn" onclick="pressNum('4')">4</button>
                <button class="key-btn" onclick="pressNum('5')">5</button>
                <button class="key-btn" onclick="pressNum('6')">6</button>
                <button class="key-btn" onclick="pressNum('7')">7</button>
                <button class="key-btn" onclick="pressNum('8')">8</button>
                <button class="key-btn" onclick="pressNum('9')">9</button>
                <button class="key-btn" onclick="clearNum()">C</button>
                <button class="key-btn" onclick="pressNum('0')">0</button>
                <button class="key-btn action-btn" onclick="submitCheckin()">GO ➔</button>
            </div>
        </div>
    </div>

    <!-- Result Overlay Modal -->
    <div class="overlay-modal" id="overlay-modal">
        <div class="modal-content">
            <div class="modal-status-icon success" id="status-icon">✓</div>
            <div class="modal-member-name" id="member-name">Anurag Bawaskar</div>
            <div class="modal-member-id" id="member-id">Member ID: 101 | Plan: 1 Year Fitness</div>

            <!-- Expiry Banner -->
            <div class="expiry-banner" id="expiry-banner-box">
                <div class="expiry-title">Membership Expiration Date</div>
                <div class="expiry-date-text" id="expiry-date-val">15-AUG-2026</div>
                <div class="days-badge active-badge" id="days-badge-val">18 Days Remaining</div>
            </div>

            <div style="font-size: 14px; font-weight: 700; color: #cbd5e1;" id="modal-action-msg">Check-In Logged Successfully</div>
            <div class="close-countdown" id="close-timer">Auto closing in 4s...</div>
        </div>
    </div>

    <!-- Audio Elements -->
    <audio id="sound-success" src="https://actions.google.com/sounds/v1/cartoon/clack.ogg" preload="auto"></audio>
    <audio id="sound-error" src="https://actions.google.com/sounds/v1/alarms/beep_short.ogg" preload="auto"></audio>

    <script>
        // Realtime Clock
        function updateClock() {
            const now = new Date();
            const timeOptions = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true };
            const dateOptions = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' };
            document.getElementById('clock-time').textContent = now.toLocaleTimeString('en-US', timeOptions);
            document.getElementById('clock-date').textContent = now.toLocaleDateString('en-US', dateOptions);
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Keypad Inputs
        const inputElem = document.getElementById('member-input');
        function pressNum(val) {
            inputElem.value += val;
        }
        function clearNum() {
            inputElem.value = '';
        }

        inputElem.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                submitCheckin();
            }
        });

        // Speech Voice Generator
        function speakMessage(text) {
            if ('speechSynthesis' in window) {
                const utterance = new SpeechSynthesisUtterance(text);
                utterance.rate = 1.0;
                utterance.pitch = 1.0;
                window.speechSynthesis.speak(utterance);
            }
        }

        let isProcessing = false;

        function submitCheckin(customId) {
            if (isProcessing) return;
            const identifier = customId || inputElem.value.trim();
            if (!identifier) return;

            isProcessing = true;

            fetch('./api/kiosk_checkin.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ identifier: identifier })
            })
            .then(res => res.json().then(data => ({ status: res.status, body: data })))
            .then(resObj => {
                const data = resObj.body;
                displayResult(data);
                inputElem.value = '';
                setTimeout(() => { isProcessing = false; }, 2000);
            })
            .catch(err => {
                console.error(err);
                displayResult({
                    success: false,
                    message: 'Network / Connection error. Please try again.'
                });
                inputElem.value = '';
                setTimeout(() => { isProcessing = false; }, 2000);
            });
        }

        function displayResult(data) {
            const modal = document.getElementById('overlay-modal');
            const iconBox = document.getElementById('status-icon');
            const nameElem = document.getElementById('member-name');
            const idElem = document.getElementById('member-id');
            const expBox = document.getElementById('expiry-banner-box');
            const expDateElem = document.getElementById('expiry-date-val');
            const daysBadgeElem = document.getElementById('days-badge-val');
            const msgElem = document.getElementById('modal-action-msg');

            modal.style.display = 'flex';

            if (data.success) {
                const isCheckOut = (data.action === 'check-out');
                iconBox.className = 'modal-status-icon ' + (isCheckOut ? 'checkout' : 'success');
                iconBox.textContent = isCheckOut ? '👋' : '✓';

                nameElem.textContent = data.name || 'Member';
                idElem.textContent = `Member ID: ${data.uid} | Plan: ${data.plan}`;
                msgElem.textContent = isCheckOut ? `Checked Out at ${data.time}` : `Checked In at ${data.time} (Streak: ${data.streak} Days)`;

                // Format Expiry Banner
                expDateElem.textContent = data.expiry || 'Active';
                if (data.expiry_status === 'EXPIRING_SOON') {
                    expBox.className = 'expiry-banner warning-style';
                    daysBadgeElem.className = 'days-badge warning-badge';
                    daysBadgeElem.textContent = `⚠️ Expiring Soon (${data.days_left} Days Left)`;
                } else {
                    expBox.className = 'expiry-banner';
                    daysBadgeElem.className = 'days-badge active-badge';
                    daysBadgeElem.textContent = `✓ Active (${data.days_left} Days Remaining)`;
                }

                document.getElementById('sound-success').play().catch(() => {});
                speakMessage(`Welcome ${data.name}. Valid until ${data.expiry}`);

            } else {
                iconBox.className = 'modal-status-icon expired';
                iconBox.textContent = '✕';

                nameElem.textContent = data.name || 'Access Denied';
                idElem.textContent = data.uid ? `Member ID: ${data.uid} | ${data.plan}` : 'Gym Entrance Terminal';
                msgElem.textContent = data.message || 'Membership inactive or expired.';

                expBox.className = 'expiry-banner expired-style';
                expDateElem.textContent = data.expiry || 'EXPIRED';
                daysBadgeElem.className = 'days-badge expired-badge';
                daysBadgeElem.textContent = data.days_expired ? `🚨 Expired ${data.days_expired} Days Ago` : '🚨 Membership Expired';

                document.getElementById('sound-error').play().catch(() => {});
                speakMessage(`Access denied. Membership expired. Please renew.`);
            }

            // Auto Hide after 4s
            let countdown = 4;
            const timerElem = document.getElementById('close-timer');
            const timerInterval = setInterval(() => {
                countdown--;
                timerElem.textContent = `Auto closing in ${countdown}s...`;
                if (countdown <= 0) {
                    clearInterval(timerInterval);
                    modal.style.display = 'none';
                }
            }, 1000);
        }

        // Initialize HTML5 QR Code Camera Scanner
        document.addEventListener("DOMContentLoaded", function() {
            const html5QrCode = new Html5Qrcode("qr-reader");
            const config = { fps: 10, qrbox: { width: 260, height: 260 } };

            Html5Qrcode.getCameras().then(devices => {
                if (devices && devices.length) {
                    const cameraId = devices[0].id;
                    html5QrCode.start(cameraId, config, (decodedText, decodedResult) => {
                        submitCheckin(decodedText);
                    });
                }
            }).catch(err => {
                console.warn("Camera scan init fallback:", err);
            });
        });
    </script>
</body>
</html>
