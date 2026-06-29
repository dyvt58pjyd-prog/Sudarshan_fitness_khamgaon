<?php
require '../../include/db_conn.php';
page_protect();

if ($_SESSION['role'] !== 'super_admin' && $_SESSION['role'] !== 'owner') {
    echo "<head><script>alert('Access Denied');</script></head></html>";
    echo "<meta http-equiv='refresh' content='0; url=/index.php'>";
    exit();
}

$gym = get_gym_details($con);

// Fetch members with biometric mappings
$sql_mems = "SELECT userid, username, mobile, biometric_id, biometric_enabled 
             FROM users 
             ORDER BY username ASC";
$res_mems = mysqli_query($con, $sql_mems);
$members = [];
if ($res_mems) {
    while ($row = mysqli_fetch_assoc($res_mems)) {
        // Check if checked in today
        $today = date('Y-m-d');
        $uid = $row['userid'];
        $chk_q = mysqli_query($con, "SELECT exit_time FROM attendance WHERE uid = '$uid' AND date = '$today' LIMIT 1");
        $row['checked_in_today'] = false;
        $row['checked_out_today'] = false;
        if ($chk_q && mysqli_num_rows($chk_q) > 0) {
            $att_row = mysqli_fetch_assoc($chk_q);
            if (empty($att_row['exit_time']) || $att_row['exit_time'] === '00:00:00') {
                $row['checked_in_today'] = true;
            } else {
                $row['checked_out_today'] = true;
            }
        }
        $members[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo htmlspecialchars($gym['gym_name']); ?> | Biometric Gate Simulator</title>
    <link rel="stylesheet" href="../../css/style.css" id="style-resource-5">
    <script type="text/javascript" src="../../js/Script.js"></script>
    <link rel="stylesheet" href="../../css/dashMain.css">
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
    <link rel="stylesheet" href="../../css/premium.css">
    <link href="a1style.css" rel="stylesheet" type="text/css">
    <style>
        .page-container .sidebar-menu #main-menu li#biometric_simulator_link > a {
            background-color: rgba(59, 130, 246, 0.1) !important;
            color: var(--accent-primary) !important;
            font-weight: 600 !important;
            box-shadow: inset 3px 0 0 var(--accent-primary);
        }

        .simulator-grid {
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            gap: 30px;
        }

        @media (max-width: 900px) {
            .simulator-grid {
                grid-template-columns: 1fr;
            }
        }

        .portal-card {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 30px;
            box-shadow: var(--glass-shadow);
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }

        .scanner-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            background: rgba(0, 0, 0, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            margin-bottom: 25px;
            text-align: center;
            position: relative;
        }

        .fingerprint-icon {
            width: 100px;
            height: 100px;
            fill: var(--text-muted);
            transition: all 0.3s ease;
            cursor: pointer;
            filter: drop-shadow(0 0 0px var(--accent-primary));
        }

        .fingerprint-icon.active {
            fill: var(--accent-primary);
            animation: pulse-glow 1.5s infinite alternate;
            filter: drop-shadow(0 0 15px var(--accent-primary));
        }

        .fingerprint-icon.success-scan {
            fill: var(--success) !important;
            filter: drop-shadow(0 0 15px var(--success)) !important;
        }

        .fingerprint-icon.error-scan {
            fill: var(--danger) !important;
            filter: drop-shadow(0 0 15px var(--danger)) !important;
        }

        @keyframes pulse-glow {
            from {
                transform: scale(1);
                filter: drop-shadow(0 0 5px var(--accent-primary));
            }
            to {
                transform: scale(1.08);
                filter: drop-shadow(0 0 25px var(--accent-primary));
            }
        }

        .laser-line {
            width: 140px;
            height: 3px;
            background: var(--accent-primary);
            box-shadow: 0 0 10px var(--accent-primary);
            position: absolute;
            top: 25%;
            left: calc(50% - 70px);
            opacity: 0;
            pointer-events: none;
            transition: all 0.3s ease;
        }

        .laser-line.scanning {
            opacity: 1;
            animation: scan-move 1.5s infinite linear;
        }

        @keyframes scan-move {
            0% { top: 25%; }
            50% { top: 55%; }
            100% { top: 25%; }
        }

        .log-console {
            background: #090d16;
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 20px;
            font-family: 'Courier New', Courier, monospace;
            font-size: 13px;
            line-height: 1.6;
            height: 380px;
            overflow-y: auto;
            color: #4ade80;
            box-shadow: inset 0 0 10px rgba(0,0,0,0.8);
        }

        .log-entry {
            margin-bottom: 10px;
            border-bottom: 1px dashed rgba(255,255,255,0.03);
            padding-bottom: 5px;
        }

        .log-time {
            color: #60a5fa;
            font-weight: bold;
        }

        .log-tag-success {
            color: #4ade80;
            font-weight: bold;
            background: rgba(74, 222, 128, 0.1);
            padding: 2px 6px;
            border-radius: 4px;
        }

        .log-tag-error {
            color: #f87171;
            font-weight: bold;
            background: rgba(248, 113, 113, 0.1);
            padding: 2px 6px;
            border-radius: 4px;
        }

        .log-tag-info {
            color: #fb923c;
            font-weight: bold;
            background: rgba(251, 146, 60, 0.1);
            padding: 2px 6px;
            border-radius: 4px;
        }
    </style>
</head>
<body class="page-body page-fade" onload="collapseSidebar()">
    <div class="page-container sidebar-collapsed" id="navbarcollapse">
        <div class="sidebar-menu">
            <header class="logo-env">
                <div class="logo">
                    <a href="index.php">
                        <img src="<?php echo htmlspecialchars($gym['gym_logo']); ?>" alt="" style="max-height: 60px; max-width: 180px;" />
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
                        <li>Welcome <?php echo htmlspecialchars($_SESSION['full_name']); ?></li>
                        <li>
                            <a href="logout.php">
                                Log Out <i class="entypo-logout right"></i>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <h2>Biometric Gate Machine Simulator</h2>
            <hr />

            <div class="simulator-grid">
                <!-- Left Panel: Device Controller -->
                <div class="portal-card">
                    <h3 style="margin-top: 0; color: #ffffff; font-weight: 700;">Simulate Device Swipe</h3>
                    <p style="color: var(--text-muted); font-size: 13px; margin-bottom: 25px;">
                        Select a member from the database below to simulate a physical check-in or check-out fingerprint scanner trigger.
                    </p>

                    <!-- Animated Biometric Scanner -->
                    <div class="scanner-container">
                        <div class="laser-line" id="scanner-laser"></div>
                        <svg class="fingerprint-icon" id="scanner-fingerprint" viewBox="0 0 24 24" onclick="triggerSimulatedScan()">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17.93c-3.95-.49-7-3.85-7-7.93h2c0 2.76 2.24 5 5 5s5-2.24 5-5h2c0 4.08-3.05 7.44-7 7.93zM12 14c-1.1 0-2-.9-2-2V8c0-1.1.9-2 2-2s2 .9 2 2v4c0 1.1-.9 2-2 2zm4.9-2c0 2.76-2.24 5-5 5s-5-2.24-5-5H5c0 3.53 2.61 6.43 6 6.92V21h2v-2.08c3.39-.49 6-3.39 6-6.92h-2.1z"/>
                        </svg>
                        <div style="font-weight: bold; font-size: 13px; color: var(--text-muted); margin-top: 15px;" id="scanner-status-label">
                            TAP FINGERPRINT TO SWIPE
                        </div>
                    </div>

                    <form id="simulator-form" onsubmit="event.preventDefault(); triggerSimulatedScan();">
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label style="color: var(--text-main); font-weight: 600; margin-bottom: 8px;">Select Gym Member</label>
                            <select class="form-control-premium" id="member-select" style="background: rgba(15, 23, 42, 0.6) !important; border: 1px solid var(--glass-border) !important; border-radius: 10px !important; color: var(--text-main) !important; height: auto !important; padding: 10px !important;" onchange="updateSelectedMemberStatus()">
                                <option value="" disabled selected>-- Select Member (Biometric ID) --</option>
                                <?php foreach ($members as $m): 
                                    $status_text = $m['checked_in_today'] ? ' (Inside Gym)' : ($m['checked_out_today'] ? ' (Checked Out)' : ' (Not in Today)');
                                ?>
                                    <option value="<?php echo htmlspecialchars($m['biometric_id'] ? $m['biometric_id'] : $m['userid']); ?>" data-uid="<?php echo htmlspecialchars($m['userid']); ?>" data-username="<?php echo htmlspecialchars($m['username']); ?>" data-status="<?php echo $m['checked_in_today'] ? 'in' : 'out'; ?>">
                                        <?php echo htmlspecialchars($m['username']); ?> [Bio ID: <?php echo htmlspecialchars($m['biometric_id'] ? $m['biometric_id'] : 'Auto (' . $m['userid'] . ')'); ?>] <?php echo $status_text; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group" style="margin-bottom: 20px;">
                            <label style="color: var(--text-main); font-weight: 600; margin-bottom: 8px;">Simulated Timestamp</label>
                            <input type="datetime-local" class="form-control-premium" id="scan-time" style="background: rgba(15, 23, 42, 0.6) !important; border: 1px solid var(--glass-border) !important; border-radius: 10px !important; color: var(--text-main) !important; padding: 10px !important;">
                            <span class="text-muted" style="font-size: 11px; display: block; margin-top: 5px;">Leave blank to use current local time.</span>
                        </div>

                        <button type="button" class="btn btn-primary btn-block" style="background: var(--accent-primary); border-color: var(--accent-primary); padding: 12px; font-weight: bold; font-size: 14px;" id="btn-scan" onclick="triggerSimulatedScan()">
                            ⚡ Scan Simulated Fingerprint
                        </button>
                    </form>
                </div>

                <!-- Right Panel: Dev Log Console -->
                <div class="portal-card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <h3 style="margin: 0; color: #ffffff; font-weight: 700;">Live Device Event Logs</h3>
                        <button class="btn btn-xs btn-default" onclick="clearConsoleLog()" style="padding: 3px 10px; font-size: 11px;">Clear</button>
                    </div>
                    
                    <div class="log-console" id="console-output">
                        <div class="log-entry">
                            <span class="log-time">[<?php echo date('H:i:s'); ?>]</span>
                            <span class="log-tag-info">SYSTEM</span> Biometric machine simulator loaded. Port 8000 online.
                        </div>
                    </div>
                </div>
            </div>

            <?php include('footer.php'); ?>
        </div>
    </div>

    <script>
        // Set datetime-local default to current local time in local timezone offset
        document.addEventListener("DOMContentLoaded", function() {
            resetTimeField();
        });

        function resetTimeField() {
            const now = new Date();
            const offsetMs = now.getTimezoneOffset() * 60 * 1000;
            const localISOTime = (new Date(now - offsetMs)).toISOString().slice(0, 16);
            document.getElementById('scan-time').value = localISOTime;
        }

        function updateSelectedMemberStatus() {
            const select = document.getElementById('member-select');
            const option = select.options[select.selectedIndex];
            if (!option || select.value === "") return;
            
            const name = option.getAttribute('data-username');
            const status = option.getAttribute('data-status');
            
            logToConsole('INFO', `Selected member: ${name} (Bio ID: ${select.value}). Suggested action: ${status === 'in' ? 'Check-out' : 'Check-in'}.`);
        }

        function logToConsole(tag, message) {
            const consoleBox = document.getElementById('console-output');
            const now = new Date();
            const timeStr = now.toTimeString().split(' ')[0];
            
            let tagClass = 'log-tag-info';
            if (tag === 'SUCCESS') tagClass = 'log-tag-success';
            if (tag === 'ERROR') tagClass = 'log-tag-error';
            
            const entry = document.createElement('div');
            entry.className = 'log-entry';
            entry.innerHTML = `<span class="log-time">[${timeStr}]</span> <span class="${tagClass}">${tag}</span> ${message}`;
            
            consoleBox.appendChild(entry);
            consoleBox.scrollTop = consoleBox.scrollHeight;
        }

        function clearConsoleLog() {
            document.getElementById('console-output').innerHTML = `
                <div class="log-entry">
                    <span class="log-time">[${new Date().toTimeString().split(' ')[0]}]</span>
                    <span class="log-tag-info">SYSTEM</span> Event logs cleared. Simulator ready.
                </div>
            `;
        }

        function triggerSimulatedScan() {
            const select = document.getElementById('member-select');
            const bioId = select.value;
            
            if (!bioId) {
                alert('Please select a gym member to swipe first.');
                logToConsole('ERROR', 'Scan aborted: No member selected.');
                return;
            }
            
            const option = select.options[select.selectedIndex];
            const name = option.getAttribute('data-username');
            const uid = option.getAttribute('data-uid');
            const timeVal = document.getElementById('scan-time').value;
            
            // Scanner Animation
            const fingerprint = document.getElementById('scanner-fingerprint');
            const laser = document.getElementById('scanner-laser');
            const label = document.getElementById('scanner-status-label');
            const btn = document.getElementById('btn-scan');
            
            fingerprint.className = 'fingerprint-icon active';
            laser.className = 'laser-line scanning';
            label.innerText = 'READING FINGERPRINT...';
            btn.disabled = true;
            
            logToConsole('INFO', `Initializing fingerprint matching split for ${name} (Bio ID: ${bioId})...`);
            
            // Form payload
            const payload = {
                biometric_id: parseInt(bioId)
            };
            if (timeVal) {
                payload.timestamp = timeVal.replace('T', ' ');
            }
            
            setTimeout(() => {
                fetch('../../api/log_attendance.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                })
                .then(res => res.json())
                .then(data => {
                    btn.disabled = false;
                    laser.className = 'laser-line';
                    
                    if (data.success) {
                        fingerprint.className = 'fingerprint-icon success-scan';
                        label.innerHTML = `<span style="color: var(--success);">MATCH FOUND! SUCCESS</span>`;
                        logToConsole('SUCCESS', `Match confirmed! Action: <strong>${data.action.toUpperCase()}</strong> | Date: ${data.date} | Time: ${data.time} | Streak: ${data.streak || 0} days.`);
                        
                        // Update status cache on dropdown option
                        if (data.action === 'check-in') {
                            option.setAttribute('data-status', 'in');
                            option.text = `${name} [Bio ID: ${bioId}] (Inside Gym)`;
                        } else {
                            option.setAttribute('data-status', 'out');
                            option.text = `${name} [Bio ID: ${bioId}] (Checked Out)`;
                        }
                    } else {
                        fingerprint.className = 'fingerprint-icon error-scan';
                        label.innerHTML = `<span style="color: var(--danger);">MATCH FAILED!</span>`;
                        logToConsole('ERROR', `Biometric match failed: ${data.message}`);
                    }
                    
                    // Reset scanner display state after delay
                    setTimeout(() => {
                        fingerprint.className = 'fingerprint-icon';
                        label.innerText = 'TAP FINGERPRINT TO SWIPE';
                        resetTimeField();
                    }, 2000);
                })
                .catch(err => {
                    console.error('Scan error:', err);
                    btn.disabled = false;
                    laser.className = 'laser-line';
                    fingerprint.className = 'fingerprint-icon error-scan';
                    label.innerHTML = `<span style="color: var(--danger);">CONNECTION FAIL</span>`;
                    logToConsole('ERROR', `Gateway response error or network connection refused.`);
                    
                    setTimeout(() => {
                        fingerprint.className = 'fingerprint-icon';
                        label.innerText = 'TAP FINGERPRINT TO SWIPE';
                        resetTimeField();
                    }, 2000);
                });
            }, 1000); // 1 sec simulation scan latency
        }
    </script>
</body>
</html>
