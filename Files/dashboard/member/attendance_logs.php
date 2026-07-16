<?php
require '../../include/db_conn.php';
page_protect();

if ($_SESSION['role'] !== 'member') {
    echo "<head><script>alert('Access Denied');</script></head></html>";
    echo "<meta http-equiv='refresh' content='0; url=/index.php'>";
    exit();
}

$gym = get_gym_details($con);
$userid = mysqli_real_escape_string($con, $_SESSION['user_data']);

// Query personal logs
$sql = "SELECT date, entry_time, exit_time 
        FROM attendance 
        WHERE uid = '$userid' 
        ORDER BY date DESC, entry_time DESC";
$result = mysqli_query($con, $sql);
$logs = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $logs[] = $row;
    }
}

// Fetch member's joining date
$joining_date = '2026-01-01';
$user_q = mysqli_query($con, "SELECT joining_date FROM users WHERE userid = '$userid'");
if ($user_q && $user_row = mysqli_fetch_assoc($user_q)) {
    $joining_date = !empty($user_row['joining_date']) ? $user_row['joining_date'] : '2026-01-01';
}

// Collect simplified attendance history for calendar JS
$calendar_data = [];
foreach ($logs as $l) {
    $date_key = $l['date'];
    $duration_str = '--';
    if (!empty($l['exit_time'])) {
        $diff = strtotime($l['exit_time']) - strtotime($l['entry_time']);
        $hours = floor($diff / 3600);
        $mins = floor(($diff % 3600) / 60);
        $duration_str = ($hours > 0 ? $hours . "h " : "") . $mins . "m";
    }
    
    if (!isset($calendar_data[$date_key])) {
        $calendar_data[$date_key] = [];
    }
    
    $calendar_data[$date_key][] = [
        'check_in' => date('h:i A', strtotime($l['entry_time'])),
        'check_out' => !empty($l['exit_time']) ? date('h:i A', strtotime($l['exit_time'])) : '--',
        'duration' => $duration_str
    ];
}
$calendar_json = json_encode($calendar_data);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo htmlspecialchars($gym['gym_name']); ?> | My Check-In Logs</title>
    <link rel="stylesheet" href="../../css/style.css" id="style-resource-5">
    <script type="text/javascript" src="../../js/Script.js"></script>
    <link rel="stylesheet" href="../../css/dashMain.css">
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
    <link rel="stylesheet" href="../../css/premium.css">
    <link href="a1style.css" rel="stylesheet" type="text/css">
    <style>
        .page-container .sidebar-menu #main-menu li#attendance_logs > a {
            background-color: rgba(59, 130, 246, 0.1) !important;
            color: var(--accent-primary) !important;
            font-weight: 600 !important;
            box-shadow: inset 3px 0 0 var(--accent-primary);
        }

        /* Heatmap styling */
        .calendar-header-cell {
            color: var(--text-muted);
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 8px 0;
            letter-spacing: 0.5px;
        }

        .calendar-cell {
            position: relative;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.04);
            border-radius: 8px;
            aspect-ratio: 1.25 / 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 8px;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-muted);
            transition: all 0.2s ease;
        }

        .calendar-cell.attended {
            background: rgba(16, 185, 129, 0.15) !important;
            border-color: var(--success) !important;
            color: #ffffff !important;
        }

        .calendar-cell.missed {
            background: rgba(239, 68, 68, 0.1) !important;
            border-color: rgba(239, 68, 68, 0.2) !important;
            color: #f87171 !important;
        }

        .calendar-cell:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            border-color: var(--accent-primary) !important;
            z-index: 10;
        }

        .calendar-cell-num {
            text-align: right;
            font-size: 12px;
        }

        .calendar-cell-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            margin: 0 auto;
        }

        .calendar-cell.attended .calendar-cell-dot {
            background: var(--success);
            box-shadow: 0 0 6px var(--success);
        }

        .calendar-cell.missed .calendar-cell-dot {
            background: #ef4444;
        }

        /* Tooltip styling */
        .calendar-cell .tooltip-content {
            visibility: hidden;
            width: 200px;
            background: #0f172a;
            border: 1px solid var(--glass-border);
            color: #ffffff;
            text-align: left;
            border-radius: 8px;
            padding: 12px;
            position: absolute;
            z-index: 100;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%);
            opacity: 0;
            transition: opacity 0.2s;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.5);
            font-family: inherit;
            font-size: 11px;
            line-height: 1.4;
            pointer-events: none;
        }

        .calendar-cell:hover .tooltip-content {
            visibility: visible;
            opacity: 1;
        }

        .calendar-cell .tooltip-content::after {
            content: "";
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: #0f172a transparent transparent transparent;
        }
        
        .logs-card {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 30px;
            box-shadow: var(--glass-shadow);
            margin-bottom: 30px;
        }

        .table-premium {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }

        .table-premium th, .table-premium td {
            padding: 15px 20px;
            text-align: left;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            vertical-align: middle;
        }

        .table-premium th {
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            font-size: 11.5px;
            letter-spacing: 0.5px;
        }

        .badge-premium {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-inside {
            background: rgba(255, 107, 0, 0.12);
            color: var(--accent-primary);
            border: 1px solid rgba(255, 107, 0, 0.25);
        }

        .badge-completed {
            background: rgba(148, 163, 184, 0.08);
            color: var(--text-muted);
            border: 1px solid rgba(148, 163, 184, 0.15);
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
                            <a href="../admin/logout.php">
                                Log Out <i class="entypo-logout right"></i>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <h2>My Check-In History</h2>
            <hr />

            <!-- Interactive Attendance Calendar Heatmap -->
            <div class="logs-card" style="margin-bottom: 25px;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 20px;">
                    <h3 style="margin: 0; color: #ffffff; font-weight: 700; display: flex; align-items: center; gap: 10px;">
                        <span style="font-size: 22px;">📅</span> Interactive Attendance Heatmap
                    </h3>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <button class="btn btn-xs btn-default" onclick="changeMonth(-1)" style="padding: 5px 12px; font-weight: bold; border-radius: 6px;">&lt; Prev</button>
                        <span id="calendar-month-title" style="font-weight: 700; color: #ffffff; min-width: 140px; text-align: center; font-size: 15px;"></span>
                        <button class="btn btn-xs btn-default" onclick="changeMonth(1)" style="padding: 5px 12px; font-weight: bold; border-radius: 6px;">Next &gt;</button>
                    </div>
                </div>
                
                <!-- Legend -->
                <div style="display: flex; gap: 15px; font-size: 11px; color: var(--text-muted); margin-bottom: 20px; flex-wrap: wrap;">
                    <div style="display: flex; align-items: center; gap: 5px;">
                        <div style="width: 14px; height: 14px; background: rgba(16, 185, 129, 0.15); border: 1px solid var(--success); border-radius: 3px;"></div>
                        <span>Attended</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 5px;">
                        <div style="width: 14px; height: 14px; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); border-radius: 3px;"></div>
                        <span>Missed (Gym Day)</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 5px;">
                        <div style="width: 14px; height: 14px; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.04); border-radius: 3px;"></div>
                        <span>No Session / Future / Before Joined</span>
                    </div>
                </div>

                <div id="heatmap-calendar-grid" style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 8px; text-align: center;">
                    <!-- Calendar cells injected by JS -->
                </div>
            </div>

            <div class="logs-card">
                <h3 style="margin-top: 0; color: #ffffff; font-weight: 700; display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                    <i class="entypo-clock" style="color: var(--accent-primary);"></i> Fingerprint Gate Entries
                </h3>
                <p style="color: var(--text-muted); font-size: 13.5px; margin-bottom: 25px; line-height: 1.5;">
                    Below is your complete log of door check-ins and check-outs recorded by the biometric entry lock system.
                </p>

                <div class="table-responsive" style="border: 1px solid var(--glass-border); border-radius: 12px; background: rgba(0,0,0,0.15); overflow: hidden;">
                    <table class="table-premium">
                        <thead>
                            <tr style="background: rgba(0,0,0,0.25);">
                                <th style="width: 25%;">Date &amp; Day</th>
                                <th style="width: 20%;">Check-In Time</th>
                                <th style="width: 20%;">Check-Out Time</th>
                                <th style="width: 20%;">Total Duration</th>
                                <th style="width: 15%; text-align: right;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($logs) > 0): ?>
                                <?php foreach ($logs as $l): 
                                    $day_str = date('l', strtotime($l['date']));
                                    $date_formatted = date('d-M-Y', strtotime($l['date']));
                                    
                                    // Calculate duration if checked out
                                    $duration_str = '--';
                                    if (!empty($l['exit_time'])) {
                                        $diff = strtotime($l['exit_time']) - strtotime($l['entry_time']);
                                        $hours = floor($diff / 3600);
                                        $mins = floor(($diff % 3600) / 60);
                                        $duration_str = ($hours > 0 ? $hours . " hr " : "") . $mins . " mins";
                                    }
                                ?>
                                    <tr>
                                        <td>
                                            <strong style="color: #ffffff; font-size: 14.5px;"><?php echo $date_formatted; ?></strong>
                                            <div style="color: var(--text-muted); font-size: 11px; margin-top: 1px;"><?php echo $day_str; ?></div>
                                        </td>
                                        <td>
                                            <span style="font-family: monospace; font-weight: bold; color: var(--success); font-size: 13.5px;">
                                                <?php echo date('h:i A', strtotime($l['entry_time'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!empty($l['exit_time'])): ?>
                                                <span style="font-family: monospace; font-weight: bold; color: var(--text-muted); font-size: 13.5px;">
                                                    <?php echo date('h:i A', strtotime($l['exit_time'])); ?>
                                                </span>
                                            <?php else: ?>
                                                <span style="color: var(--text-muted); font-size: 13px;">--:--</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span style="font-weight: 600; color: #ffffff;">
                                                <?php echo $duration_str; ?>
                                            </span>
                                        </td>
                                        <td style="text-align: right;">
                                            <?php if (!empty($l['exit_time'])): ?>
                                                <span class="badge-premium badge-completed">Completed</span>
                                            <?php else: ?>
                                                <span class="badge-premium badge-inside">Inside</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                        No door check-in logs recorded for your account yet.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php include('../admin/footer.php'); ?>
        </div>
    </div>

    <script>
        const attendanceHistory = <?php echo $calendar_json; ?>;
        const memberJoinedDate = new Date('<?php echo $joining_date; ?>');
        
        let currentYear = new Date().getFullYear();
        let currentMonth = new Date().getMonth();
        
        function renderCalendar(year, month) {
            const grid = document.getElementById('heatmap-calendar-grid');
            const monthTitle = document.getElementById('calendar-month-title');
            if (!grid || !monthTitle) return;
            
            grid.innerHTML = '';
            
            const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
            monthTitle.innerText = `${monthNames[month]} ${year}`;
            
            // Day Names Headers
            const dayNames = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
            dayNames.forEach(d => {
                const cell = document.createElement('div');
                cell.className = 'calendar-header-cell';
                cell.innerText = d;
                grid.appendChild(cell);
            });
            
            const firstDay = new Date(year, month, 1).getDay();
            const totalDays = new Date(year, month + 1, 0).getDate();
            
            // Shift empty slots
            for (let i = 0; i < firstDay; i++) {
                const cell = document.createElement('div');
                grid.appendChild(cell);
            }
            
            const today = new Date();
            today.setHours(0,0,0,0);
            
            for (let d = 1; d <= totalDays; d++) {
                const cellDate = new Date(year, month, d);
                cellDate.setHours(0,0,0,0);
                
                const yyyy = cellDate.getFullYear();
                const mm = String(cellDate.getMonth() + 1).padStart(2, '0');
                const dd = String(cellDate.getDate()).padStart(2, '0');
                const dateStr = `${yyyy}-${mm}-${dd}`;
                
                const cell = document.createElement('div');
                cell.className = 'calendar-cell';
                
                const numSpan = document.createElement('span');
                numSpan.className = 'calendar-cell-num';
                numSpan.innerText = d;
                cell.appendChild(numSpan);
                
                const dotDiv = document.createElement('div');
                dotDiv.className = 'calendar-cell-dot';
                cell.appendChild(dotDiv);
                
                const hasAttended = attendanceHistory[dateStr] !== undefined;
                
                if (hasAttended) {
                    cell.classList.add('attended');
                    
                    const tooltip = document.createElement('div');
                    tooltip.className = 'tooltip-content';
                    let tooltipHTML = `<strong>📅 ${d} ${monthNames[month]}</strong><br>`;
                    attendanceHistory[dateStr].forEach((log) => {
                        tooltipHTML += `<hr style="margin: 6px 0; border-color: rgba(255,255,255,0.1);">` +
                                       `📥 In: <strong>${log.check_in}</strong><br>` +
                                       `📤 Out: <strong>${log.check_out}</strong><br>` +
                                       `⏱️ Duration: <strong>${log.duration}</strong>`;
                    });
                    tooltip.innerHTML = tooltipHTML;
                    cell.appendChild(tooltip);
                } else {
                    const isPast = cellDate < today;
                    const isAfterJoined = cellDate >= memberJoinedDate;
                    
                    if (isPast && isAfterJoined) {
                        cell.classList.add('missed');
                        
                        const tooltip = document.createElement('div');
                        tooltip.className = 'tooltip-content';
                        tooltip.innerHTML = `<strong>📅 ${d} ${monthNames[month]}</strong><br><span style="color:#f87171;">⚠️ Missed workout session</span>`;
                        cell.appendChild(tooltip);
                    }
                }
                
                grid.appendChild(cell);
            }
        }
        
        function changeMonth(dir) {
            currentMonth += dir;
            if (currentMonth < 0) {
                currentMonth = 11;
                currentYear--;
            } else if (currentMonth > 11) {
                currentMonth = 0;
                currentYear++;
            }
            renderCalendar(currentYear, currentMonth);
        }
        
        renderCalendar(currentYear, currentMonth);
    </script>
</body>
</html>
