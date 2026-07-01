<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Handle setting the working year
if (isset($_GET['set_working_year'])) {
    $_SESSION['working_year'] = intval($_GET['set_working_year']);
    $redirect = strtok($_SERVER["REQUEST_URI"], '?');
    // Maintain query params if any, except set_working_year
    $params = $_GET;
    unset($params['set_working_year']);
    if (!empty($params)) {
        $redirect .= '?' . http_build_query($params);
    }
    header("Location: " . $redirect);
    exit();
}
if (!isset($_SESSION['working_year'])) {
    $gym_settings_data = get_gym_details($con);
    $_SESSION['working_year'] = (isset($gym_settings_data['current_year']) && $gym_settings_data['current_year']) ? intval($gym_settings_data['current_year']) : intval(date('Y'));
}
$working_year = $_SESSION['working_year'];
$gym_settings_data = get_gym_details($con);
$current_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'super_admin';
?>
<script>
    if (localStorage.getItem('theme') === 'light') {
        document.documentElement.classList.add('light-theme');
    }
</script>
<link rel="stylesheet" href="../../css/premium.css">

<!-- Working Year Selector -->
<div class="working-year-selector" style="padding: 15px; border-bottom: 1px solid rgba(255,107,0,0.15); text-align: center; background: rgba(0,0,0,0.3); border-radius: 8px; margin: 10px 15px;">
    <span style="color: #a3a3a3; font-size: 10px; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 5px;">Operating Year</span>
    <div style="display: flex; justify-content: center; align-items: center; gap: 10px;">
        <a href="?set_working_year=<?php echo $working_year - 1; ?>" style="background: rgba(255, 107, 0, 0.15); color: #ff6b00; border: 1px solid rgba(255,107,0,0.3); padding: 2px 8px; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 12px; transition: all 0.2s;">&lt;</a>
        <span style="color: #ffffff; font-size: 16px; font-weight: 700; min-width: 50px; display: inline-block; text-shadow: 0 0 8px rgba(255,107,0,0.6);"><?php echo $working_year; ?></span>
        <a href="?set_working_year=<?php echo $working_year + 1; ?>" style="background: rgba(255, 107, 0, 0.15); color: #ff6b00; border: 1px solid rgba(255,107,0,0.3); padding: 2px 8px; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 12px; transition: all 0.2s;">&gt;</a>
    </div>
</div>

<ul id="main-menu" class="" >
    <!-- 1. DASHBOARD -->
    <li id="dash"><a href="index.php"><i class="entypo-gauge"></i><span>Dashboard</span></a></li>
    
    <!-- 2. FRONT DESK / VISITORS -->
    <?php if ($current_role === 'super_admin' || $current_role === 'owner' || $current_role === 'reception'): ?>
        <li id="visitor_entry"><a href="visitor_entry.php" style="color: #3b82f6;"><i class="entypo-vcard"></i><span>New Visitor Entry</span></a></li>
        <li id="visitors_list"><a href="visitors_list.php"><i class="entypo-folder"></i><span>Visitor Logs</span></a></li>
    <?php endif; ?>

    <!-- 3. MEMBERS & REGISTRATION -->
    <?php if ($current_role === 'super_admin' || $current_role === 'owner' || $current_role === 'reception'): ?>
        <li id="regis"><a href="new_entry.php"><i class="entypo-user-add"></i><span>New Registration</span></a></li>
        <li id="manual_approve"><a href="manual_approve.php" style="color: #f59e0b;"><i class="entypo-check"></i><span>Manual Approve (Bookings)</span></a></li>
    <?php endif; ?>
    
    <li class="" id="hassubopen"><a href="#" onclick="memberExpand(1)"><i class="entypo-users"></i><span>Members</span></a>
        <ul id="memExpand">
            <?php if ($current_role === 'super_admin' || $current_role === 'owner' || $current_role === 'reception'): ?>
                <li class="active"><a href="view_mem.php"><span>Edit Members</span></a></li>
            <?php endif; ?>
            <li><a href="table_view.php"><span>View Members</span></a></li>
            <li id="assign_routine"><a href="assign_routine.php"><span>Assign Routines</span></a></li>
        </ul>
    </li>
    <li id="searchmem"><a href="search_member.php"><i class="entypo-search"></i><span>Search Member</span></a></li>

    <!-- 4. ATTENDANCE & ACCESS -->
    <li id="attendance_portal"><a href="attendance.php"><i class="entypo-camera"></i><span>Attendance Portal</span></a></li>
    <li id="kiosk_link"><a href="kiosk.php" target="_blank"><i class="entypo-monitor"></i><span>Front Desk Kiosk</span></a></li>
    <li id="biometric_manage"><a href="biometric_management.php"><i class="entypo-key"></i><span>Biometric Management</span></a></li>
    <?php if ($current_role === 'super_admin' || $current_role === 'owner'): ?>
        <li id="biometric_logs_link"><a href="biometric_logs.php"><i class="entypo-list"></i><span>Biometric Logs</span></a></li>
        <li id="biometric_simulator_link"><a href="biometric_simulator.php"><i class="entypo-switch"></i><span>Biometric Simulator</span></a></li>
    <?php endif; ?>

    <!-- 5. BILLING & PAYMENTS -->
    <?php if ($current_role === 'super_admin' || $current_role === 'owner' || $current_role === 'reception'): ?>
        <li id="payment_requests"><a href="payment_requests.php" style="color: #10b981;"><i class="entypo-check"></i><span>Payment Approvals (UPI)</span></a></li>
        <li id="paymnt"><a href="payments.php"><i class="entypo-star"></i><span>Make Payment</span></a></li>
        <li id="online_paymnt_records"><a href="online_payments_records.php"><i class="entypo-folder"></i><span>Online Payments Records</span></a></li>
        <li id="invoices_link"><a href="invoices.php"><i class="entypo-doc-text"></i><span>Invoices</span></a></li>
    <?php endif; ?>

    <!-- 6. ANALYTICS & SUBSCRIPTIONS -->
    <?php if ($current_role === 'super_admin' || $current_role === 'owner'): ?>
        <li class="" id="planhassubopen"><a href="#" onclick="memberExpand(2)"><i class="entypo-quote"></i><span>Plan Details</span></a>
            <ul id="planExpand">
                <li class="active"><a href="new_plan.php"><span>New Plan</span></a></li>
                <li><a href="view_plan.php"><span>Edit Subscription Details</span></a></li>
            </ul>
        </li>
        <li class="" id="overviewhassubopen"><a href="#" onclick="memberExpand(3)"><i class="entypo-box"></i><span>Overview</span></a>
            <ul id="overviewExpand">
                <li class="active"><a href="over_members_month.php"><span>Members per Month</span></a></li>
                <li><a href="over_members_year.php"><span>Members per Year</span></a></li>
                <li><a href="revenue_month.php"><span>Income per Month</span></a></li>
            </ul>
        </li>
        <li id="churn_analytics"><a href="churn_analytics.php"><i class="entypo-chart-line"></i><span>Churn Risk Analytics</span></a></li>
        <li id="renewal_pipeline"><a href="renewal_pipeline.php"><i class="entypo-chart-bar"></i><span>Renewal Pipeline</span></a></li>
    <?php endif; ?>

    <!-- 7. PERSONAL TRAINING & ROUTINES -->
    <?php if ($current_role === 'super_admin' || $current_role === 'owner' || $current_role === 'trainer' || $current_role === 'reception'): ?>
        <li class="" id="pthassubopen"><a href="#" onclick="memberExpand(5)"><i class="entypo-heart"></i><span>Personal Training</span></a>
            <ul id="ptExpand">
                <?php if ($current_role === 'super_admin' || $current_role === 'owner' || $current_role === 'reception'): ?>
                    <li><a href="enroll_pt.php"><span>Enroll PT Client</span></a></li>
                <?php endif; ?>
                <li><a href="view_pt_clients.php"><span>PT Client Assignments</span></a></li>
                <li><a href="add_pt.php"><span>Record PT Workout/Diet</span></a></li>
                <li><a href="view_pt.php"><span>View PT Session Logs</span></a></li>
            </ul>
        </li>
    <?php endif; ?>
    
    <li class="" id="routinehassubopen"><a href="#" onclick="memberExpand(4)"><i class="entypo-alert"></i><span>Exercise Routine</span></a>
        <ul id="routineExpand">
            <?php if ($current_role === 'super_admin' || $current_role === 'owner' || $current_role === 'trainer'): ?>
                <li class="active"><a href="addroutine.php"><span>Add Routine</span></a></li>
                <li><a href="editroutine.php"><span>Edit Routine</span></a></li>
            <?php endif; ?>
            <li><a href="viewroutine.php"><span>View Routine</span></a></li>
        </ul>
    </li>

    <?php if ($current_role === 'super_admin' || $current_role === 'owner' || $current_role === 'trainer' || $current_role === 'reception'): ?>
        <li id="bmicalc"><a href="bmi_calc.php"><i class="entypo-chart-bar"></i><span>BMI Calculator</span></a></li>
    <?php endif; ?>

    <!-- 8. SETTINGS & ADMIN -->
    <?php if ($current_role === 'super_admin' || $current_role === 'owner'): ?>
        <li id="campaign_manager_link"><a href="campaign_manager.php"><i class="entypo-calendar"></i><span>Automated Campaigns</span></a></li>
        <li id="broadcastsettings"><a href="broadcast_campaign.php"><i class="entypo-megaphone"></i><span>WhatsApp Broadcast</span></a></li>
        <li id="expenses_ledger"><a href="expenses.php"><i class="entypo-book-open"></i><span>Expenses Ledger</span></a></li>
        <li id="staffmanage"><a href="manage_staff.php"><i class="entypo-users"></i><span>Manage Staff</span></a></li>
        <li id="gymsettings"><a href="gym_settings.php"><i class="entypo-cog"></i><span>Gym Settings</span></a></li>
        <li id="whatsappsettings"><a href="whatsapp_setup.php"><i class="entypo-phone"></i><span>WhatsApp Settings</span></a></li>
        <?php if ($current_role === 'super_admin'): ?>
            <li id="smtpsettings"><a href="smtp_settings.php"><i class="entypo-mail"></i><span>SMTP Configuration</span></a></li>
            <li id="discountlock"><a href="discount_lock.php"><i class="entypo-lock"></i><span>Discount Lock</span></a></li>
        <?php endif; ?>
        <li id="databackup"><a href="backup_data.php"><i class="entypo-drive"></i><span>Data Import/Export</span></a></li>
    <?php endif; ?>

    <!-- 9. PROFILE & LOGOUT -->
    <li id="adminprofile"><a href="more-userprofile.php"><i class="entypo-folder"></i><span>Profile</span></a></li>
    <li><a href="logout.php"><i class="entypo-logout"></i><span>Logout</span></a></li>
</ul>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Dynamic viewport injection for mobile and tablet responsiveness
    var metaViewport = document.querySelector('meta[name="viewport"]');
    if (!metaViewport) {
        metaViewport = document.createElement('meta');
        metaViewport.name = 'viewport';
        metaViewport.content = 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no';
        document.head.appendChild(metaViewport);
    }

    // Global Mobile Hamburger Menu Injection
    var headerEnv = document.querySelector(".logo-env");
    if (headerEnv && !document.querySelector(".sidebar-mobile-menu")) {
        var mobileMenuDiv = document.createElement("div");
        mobileMenuDiv.className = "sidebar-mobile-menu visible-xs";
        mobileMenuDiv.innerHTML = '<a href="#" class="with-animation"><i class="entypo-menu"></i></a>';
        headerEnv.appendChild(mobileMenuDiv);
    }

    // Dynamic logo injection
    var logoImg = document.querySelector(".logo img");
    if (logoImg) {
        logoImg.src = "<?php echo htmlspecialchars($gym_settings_data['gym_logo']); ?>";
        logoImg.style.maxHeight = "95px";
        logoImg.style.maxWidth = "210px";
        logoImg.style.width = "auto";
        logoImg.style.height = "auto";
    }
    
    // Header text injection
    var headerTitle = document.querySelector("h2");
    if (headerTitle && (headerTitle.innerHTML.trim() === "SUDARSHAN FITNESS" || headerTitle.innerHTML.trim() === "SUDARSHAN FITNESS KHAMGAON")) {
        headerTitle.innerHTML = "<?php echo htmlspecialchars($gym_settings_data['gym_name']); ?>";
    }
    
    // Dynamic title rebranding fallback
    if (document.title.includes("SUDARSHAN FITNESS") && !document.title.includes("KHAMGAON")) {
        document.title = document.title.replace("SUDARSHAN FITNESS", "SUDARSHAN FITNESS KHAMGAON");
    }
    
    // Dynamic favicon injection
    var favicon = document.querySelector('link[rel="shortcut icon"]') || document.createElement('link');
    favicon.type = 'image/jpeg';
    favicon.rel = 'shortcut icon';
    // Use dynamic gym logo as favicon
    favicon.href = "<?php echo htmlspecialchars($gym_settings_data['gym_logo']); ?>";
    document.getElementsByTagName('head')[0].appendChild(favicon);

    // Dynamic Premium Theme Toggle Button Injection
    if (!document.getElementById("theme-toggle")) {
        var themeToggle = document.createElement("button");
        themeToggle.id = "theme-toggle";
        themeToggle.className = "theme-toggle-btn";
        themeToggle.title = "Toggle Light/Dark Theme";
        
        var isLight = document.documentElement.classList.contains("light-theme");
        
        themeToggle.innerHTML = `
            <svg class="sun-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#ff6b00" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="${isLight ? 'display:none;' : 'display:block;'}"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>
            <svg class="moon-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="#ff6b00" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="${isLight ? 'display:block;' : 'display:none;'}"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
        `;
        
        document.body.appendChild(themeToggle);
        
        themeToggle.addEventListener("click", function() {
            var activeLight = document.documentElement.classList.toggle("light-theme");
            localStorage.setItem("theme", activeLight ? "light" : "dark");
            
            var sun = themeToggle.querySelector(".sun-icon");
            var moon = themeToggle.querySelector(".moon-icon");
            
            if (activeLight) {
                sun.style.display = "none";
                moon.style.display = "block";
            } else {
                sun.style.display = "block";
                moon.style.display = "none";
            }
        });
    }
    
    // Live eTimeOffice Cloud Biometric Sync (Runs silently in background every 15 seconds)
    setInterval(function() {
        var xhr = new XMLHttpRequest();
        xhr.open("GET", "../../api/sync_etimeoffice.php", true);
        xhr.send();
    }, 15000);
});
</script>
