<?php
$gym_settings_data = get_gym_details($con);
?>
<ul id="main-menu" class="">
    <li id="dash"><a href="index.php"><i class="entypo-gauge"></i><span>My Dashboard</span></a></li>
    <li id="myplan"><a href="my_plan.php"><i class="entypo-star" style="color: #ff6b00;"></i><span style="color: #ff6b00; font-weight: bold;">My Smart Plan</span></a></li>
    <li id="my_routine"><a href="my_routine.php"><i class="entypo-alert"></i><span>My Routine</span></a></li>
    <li id="profile"><a href="profile.php"><i class="entypo-folder"></i><span>My Profile</span></a></li>
    <li id="receipts"><a href="receipts.php"><i class="entypo-doc-text"></i><span>My Receipts</span></a></li>
    <li id="renew"><a href="payment.php"><i class="entypo-credit-card"></i><span>Membership Renewal</span></a></li>
    <li id="attendance_logs"><a href="attendance_logs.php"><i class="entypo-clock"></i><span>Check-In Logs</span></a></li>
    <li id="pt_booking"><a href="pt_booking.php"><i class="entypo-calendar"></i><span>PT Slot Booking</span></a></li>
    <li><a href="../admin/logout.php"><i class="entypo-logout"></i><span>Logout</span></a></li>
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
    
    // Header title injection
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
    favicon.type = 'image/x-icon';
    favicon.rel = 'shortcut icon';
    // Use fixed favicon as requested by the user
    favicon.href = "../../images/favicon_fixed.jpg?v=2";
    document.getElementsByTagName('head')[0].appendChild(favicon);
});
</script>
