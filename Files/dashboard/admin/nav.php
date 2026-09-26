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
$watermark_text = isset($_SESSION['user_data']) ? $_SESSION['user_data'] . " (" . $current_role . ")" : "UNAUTHORIZED";
?>
<!-- SECURITY WATERMARK OVERLAY -->
<div class="security-watermark">
    <?php for($i = 0; $i < 30; $i++): ?>
        <span><?php echo htmlspecialchars($watermark_text); ?></span>
    <?php endfor; ?>
</div>

<style>
    /* Security Watermark Styling */
    .security-watermark {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        z-index: 9999;
        pointer-events: none; /* Let clicks pass through */
        overflow: hidden;
        display: flex;
        flex-wrap: wrap;
        opacity: 0.03; /* Barely visible */
        justify-content: center;
        align-content: center;
        gap: 50px;
        transform: rotate(-15deg) scale(1.5);
    }
    .security-watermark span {
        font-size: 24px;
        font-weight: 800;
        color: #000;
        white-space: nowrap;
        font-family: 'Inter', sans-serif;
    }

    /* Modern SaaS Layout Fixes - Sidebar Removed */
    .page-container {
        padding-left: 0; /* Sidebar removed */
    }
    
    .sidebar-menu {
        display: none !important; /* Force hide old sidebar container */
    }

    /* Top Navigation Breadcrumb Button */
    .btn-dashboard-home {
        background: rgba(255, 255, 255, 0.85);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(0, 0, 0, 0.1);
        border-radius: 12px;
        padding: 8px 16px;
        color: #1d1d1f;
        font-weight: 600;
        font-family: 'Inter', sans-serif;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        transition: all 0.3s ease;
    }
    
    .btn-dashboard-home:hover {
        background: #fff;
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0,0,0,0.1);
    }
</style>
<script src="../../js/theme_engine.js"></script>
<script>

    document.addEventListener("DOMContentLoaded", function() {
        // Move Operating Year & Theme Switcher to top header links list
        const yearSelector = document.querySelector('.working-year-selector');
        const linksList = document.querySelector('.links-list');
        if (linksList) {
            // Theme Switcher Component
            if (!document.getElementById('sf-theme-switcher-wrapper')) {
                const themeLi = document.createElement('li');
                themeLi.id = 'sf-theme-switcher-wrapper';
                themeLi.style.marginRight = '15px';
                themeLi.innerHTML = `
                    <select id="sf-theme-select" onchange="SFThemeEngine.setThemeMode(this.value)" style="background: rgba(255, 255, 255, 0.95); color: #1d1d1f; border: 1.5px solid #ff5722; border-radius: 10px; padding: 6px 12px; font-size: 13px; font-weight: 700; font-family: 'Inter', sans-serif; cursor: pointer; box-shadow: 0 4px 12px rgba(255,87,34,0.25);">
                        <option value="festive">🔱 Navratri & Garba Surge</option>
                        <option value="light">☀️ Light Mode</option>
                        <option value="dark">🌙 Dark Mode</option>
                        <option value="system">💻 System Mode</option>
                    </select>
                `;
                linksList.insertBefore(themeLi, linksList.firstChild);
                
                // Set select default value
                const currentMode = SFThemeEngine.getThemeMode();
                document.getElementById('sf-theme-select').value = currentMode;
            }

            if (yearSelector && !document.getElementById('nav-working-year')) {
                const yearLi = document.createElement('li');
                yearLi.id = 'nav-working-year';
                yearLi.style.marginRight = '15px';
                yearLi.innerHTML = yearSelector.outerHTML.replace('style="', 'style="background: rgba(255, 255, 255, 0.9); color: #1d1d1f; border: 1px solid rgba(0, 0, 0, 0.1); border-radius: 8px; padding: 4px 10px; font-size: 13px; font-weight: 600; font-family: \'Inter\', sans-serif; box-shadow: 0 2px 8px rgba(0,0,0,0.04); ');
                linksList.insertBefore(yearLi, linksList.firstChild);
            }
        }
        
        // Add Back to Dashboard link to top right navigation list if not on index.php / main.php
        const currentPath = window.location.pathname;
        if (!currentPath.endsWith('index.php') && !currentPath.endsWith('main.php') && !currentPath.endsWith('/')) {
            if (linksList) {
                if (!document.getElementById('nav-dashboard-home')) {
                    const li = document.createElement('li');
                    li.id = 'nav-dashboard-home';
                    li.innerHTML = '<a href="index.php" style="color: #007aff; font-weight: 600; font-size: 13px; font-family: \'Inter\', sans-serif;"><i class="entypo-home" style="margin-right: 4px;"></i>Dashboard Home</a>';
                    linksList.insertBefore(li, linksList.firstChild);
                }
            }
        }
    });
</script>
<link rel="stylesheet" href="../../css/premium.css?v=<?php echo time(); ?>">

<!-- Working Year Selector -->
<div class="working-year-selector" style="display: none;">
    <span style="color: #86868b; font-size: 10px; text-transform: uppercase; letter-spacing: 1px; display: block; margin-bottom: 5px; font-weight: 600;">Operating Year</span>
    <div style="display: flex; justify-content: center; align-items: center; gap: 8px;">
        <a href="?set_working_year=<?php echo $working_year - 1; ?>" style="background: rgba(0, 122, 255, 0.1); color: #007aff; border: 1px solid rgba(0, 122, 255, 0.2); padding: 2px 8px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 12px; transition: all 0.2s;">&lt;</a>
        <span style="color: #1d1d1f; font-size: 15px; font-weight: 700; min-width: 45px; display: inline-block; font-family: 'Inter', sans-serif;"><?php echo $working_year; ?></span>
        <a href="?set_working_year=<?php echo $working_year + 1; ?>" style="background: rgba(0, 122, 255, 0.1); color: #007aff; border: 1px solid rgba(0, 122, 255, 0.2); padding: 2px 8px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 12px; transition: all 0.2s;">&gt;</a>
    </div>
</div>

<!-- Top Navigation Breadcrumb & Festive Header -->
<div style="padding: 15px 24px; position: sticky; top: 0; z-index: 900; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
    <a href="index.php" class="btn-dashboard-home">
        <i class="entypo-layout"></i> Dashboard Hub
    </a>
    <div id="sf-festive-nav-banner" style="background: linear-gradient(135deg, #FF5722 0%, #E91E63 50%, #FFC107 100%); color: #ffffff; padding: 6px 16px; border-radius: 20px; font-size: 13px; font-weight: 800; display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 15px rgba(255, 87, 34, 0.4); text-transform: uppercase; letter-spacing: 0.5px;">
        <span>✨ 🔱 NAVRATRI & GARBA SURGE EDITION 🔱 ✨</span>
    </div>
</div>


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


    
    // Live eTimeOffice Cloud Biometric Sync (Runs silently in background every 60 seconds)
    setInterval(function() {
        var xhr = new XMLHttpRequest();
        xhr.open("GET", "../../api/sync_etimeoffice.php", true);
        xhr.send();
    }, 60000);
});
</script>
