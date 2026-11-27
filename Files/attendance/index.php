<?php
require_once __DIR__ . '/auth_check.php';

$gym = get_gym_details($con);
$logo_path = $gym['gym_logo'] ?? '../images/logo.png';
if (substr($logo_path, 0, 6) === '../../') {
    $logo_path = '../' . substr($logo_path, 6);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Attendance Terminal | <?php echo htmlspecialchars($gym['gym_name']); ?></title>
    <link rel="shortcut icon" href="<?php echo htmlspecialchars($logo_path); ?>" type="image/jpeg">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="attendance.css?v=<?php echo time(); ?>">
</head>
<body>

    <!-- Top Navigation & Brand Header -->
    <header class="portal-header">
        <div class="brand-section">
            <?php if (!empty($logo_path)): ?>
                <img src="<?php echo htmlspecialchars($logo_path); ?>" alt="Gym Logo" class="brand-logo-img" onerror="this.style.display='none'">
            <?php endif; ?>
            <div class="brand-info">
                <h1><?php echo htmlspecialchars($gym['gym_name']); ?></h1>
                <div class="brand-badge">
                    <span class="live-indicator"></span>
                    <span>Gate Attendance Terminal</span>
                </div>
            </div>
        </div>

        <div class="header-center">
            <div class="digital-clock-box">
                <div class="clock-time" id="liveClockTime">00:00:00</div>
                <div class="clock-date" id="liveClockDate">Loading...</div>
            </div>
        </div>

        <div class="header-actions">
            <div class="staff-tag">
                <span>👤 <?php echo htmlspecialchars($current_staff_name); ?></span>
                <span class="role-pill"><?php echo htmlspecialchars($current_staff_role); ?></span>
            </div>

            <button type="button" class="btn-control active-toggle" id="btnToggleSound" onclick="toggleSound()" title="Toggle Sound Chimes">
                🔊 Sound On
            </button>

            <button type="button" class="btn-control active-toggle" id="btnToggleVoice" onclick="toggleVoice()" title="Toggle Voice Announcements">
                🗣️ Voice On
            </button>

            <button type="button" class="btn-control" id="btnToggleFullscreen" onclick="toggleFullscreenKiosk()" title="Fullscreen Kiosk Mode">
                ⛶ Kiosk
            </button>

            <a href="../desk/" class="btn-control" target="_blank" title="Open Front Desk Portal">
                ⚡ Front Desk
            </a>

            <?php if ($current_staff_role === 'super_admin' || $current_staff_role === 'owner'): ?>
                <a href="../dashboard/admin/" class="btn-control" title="Return to Admin Suite">
                    ⚙️ Admin
                </a>
            <?php endif; ?>

            <a href="logout.php" class="btn-control" style="color: #ef4444;" title="Sign out of Attendance Terminal">
                🚪 Exit
            </a>
        </div>
    </header>

    <!-- Main Viewport Layout -->
    <main class="main-viewport">
        <!-- Center Attendance Hero Station -->
        <section class="attendance-station">
            <div class="hero-title-box">
                <h2 class="hero-title">⚡ Express Member Attendance</h2>
                <p class="hero-subtitle">Enter Member Name, Mobile Number, or Email to instantly verify &amp; log attendance</p>
            </div>

            <!-- Instant Search Input Bar -->
            <div class="search-container">
                <div class="search-input-box">
                    <span class="search-icon-left">🔍</span>
                    <input type="text" id="attendanceSearchInput" class="search-input" 
                           placeholder="Type Member Name, Mobile, or Email..." 
                           autofocus autocomplete="off" spellcheck="false">

                    <div class="search-actions-right">
                        <button type="button" class="btn-clear-search" id="btnClearSearch" title="Clear input (Esc)">✕</button>
                        <span class="kbd-shortcut">↵ Enter</span>
                    </div>
                </div>

                <!-- Instant Autocomplete Dropdown -->
                <div class="search-results-dropdown" id="searchResultsDropdown"></div>
            </div>

            <!-- Active / Expired Big Outcome Display Card -->
            <div class="outcome-display-card" id="outcomeDisplayCard">
                <!-- Status Icon: Big Animated Green Tick (✓) or Red Denied Cross (✕) -->
                <div class="status-icon-wrapper" id="outcomeStatusIcon">✓</div>

                <!-- Status Header Text -->
                <h2 class="outcome-title" id="outcomeTitleText">Attendance Logged Successfully</h2>

                <!-- Member Info Banner -->
                <div class="outcome-member-box">
                    <img src="../images/default_avatar.png" alt="Avatar" class="outcome-member-avatar" id="outcomeMemberAvatar">
                    <div class="outcome-member-details">
                        <h2 id="outcomeMemberName">Member Name</h2>
                        <p id="outcomeMemberMeta">
                            <span>ID: #101</span>
                            <span>📞 9876543210</span>
                            <span>🏋️ 1 Year Fitness Plan</span>
                        </p>
                    </div>
                </div>

                <!-- Expiration Date Display Banner -->
                <div class="expiry-display-box" id="outcomeExpiryBox">
                    <span class="expiry-box-label" id="outcomeExpiryLabel">Membership Expiration Date</span>
                    <span class="expiry-box-date" id="outcomeExpiryDate">24-DEC-2026</span>
                    <span class="expiry-days-pill" id="outcomeExpiryPill">✓ Active (208 Days Remaining)</span>
                </div>

                <!-- Meta Footer (Time, Streak, Action Buttons) -->
                <div class="outcome-meta-footer" id="outcomeMetaFooter">
                    <!-- Populated dynamically via JavaScript -->
                </div>

                <!-- Auto-Dismiss Countdown Fill Bar -->
                <div class="countdown-bar-wrapper">
                    <div class="countdown-bar-fill" id="countdownProgressFill"></div>
                </div>
            </div>

            <!-- Keyboard Shortcuts Hint Bar -->
            <div class="shortcuts-hint-bar">
                <span><kbd>Enter</kbd> Log top match</span>
                <span><kbd>↑</kbd> <kbd>↓</kbd> Select candidate</span>
                <span><kbd>Esc</kbd> Clear / Dismiss</span>
                <span><kbd>/</kbd> Focus search</span>
                <span><kbd>F</kbd> Fullscreen Kiosk</span>
                <span><kbd>M</kbd> Mute / Unmute</span>
            </div>
        </section>

        <!-- Right Side: Today's Stats & Recent Activity Feed -->
        <aside class="sidebar-panel">
            <!-- 4-Stat Quick Tiles -->
            <div class="stats-grid">
                <div class="stat-card">
                    <span class="stat-label">Present Today</span>
                    <span class="stat-val color-cyan" id="statTotalToday">0</span>
                </div>
                <div class="stat-card">
                    <span class="stat-label">Inside Now</span>
                    <span class="stat-val color-green" id="statInsideNow">0</span>
                </div>
                <div class="stat-card">
                    <span class="stat-label">Checked Out</span>
                    <span class="stat-val" id="statCheckedOut">0</span>
                </div>
                <div class="stat-card">
                    <span class="stat-label">Denied Attempts</span>
                    <span class="stat-val color-red" id="statDeniedCount">0</span>
                </div>
            </div>

            <!-- Feed Header -->
            <div class="feed-header">
                <h3>📋 Live Today's Log</h3>
                <span style="font-size: 11px; color: var(--accent-cyan); font-weight: 700;">Real-Time</span>
            </div>

            <!-- Activity List -->
            <div class="feed-list" id="recentAttendanceFeed">
                <div class="empty-feed">Loading today's attendance...</div>
            </div>
        </aside>
    </main>

    <script src="attendance.js?v=<?php echo time(); ?>"></script>
</body>
</html>
