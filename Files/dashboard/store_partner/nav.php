<?php
// Store Partner Navigation Component
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$partner_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Nutrition Partner';
?>
<style>
.store-nav {
    background: rgba(15, 23, 42, 0.95);
    backdrop-filter: blur(16px);
    border-bottom: 1px solid rgba(249, 115, 22, 0.3);
    padding: 15px 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: sticky;
    top: 0;
    z-index: 1000;
    box-shadow: 0 4px 20px rgba(0,0,0,0.4);
}
.store-logo {
    display: flex;
    align-items: center;
    gap: 12px;
    color: #fff;
    font-family: 'Orbitron', sans-serif;
    font-weight: 800;
    font-size: 16px;
    text-decoration: none !important;
}
.store-logo span {
    color: #f97316;
}
.store-menu {
    display: flex;
    align-items: center;
    gap: 15px;
}
.store-menu-item {
    color: #cbd5e1;
    text-decoration: none !important;
    font-size: 13px;
    font-weight: 600;
    padding: 8px 16px;
    border-radius: 10px;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 6px;
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.08);
}
.store-menu-item:hover, .store-menu-item.active {
    background: rgba(249, 115, 22, 0.2);
    color: #ffffff;
    border-color: #f97316;
    box-shadow: 0 0 15px rgba(249, 115, 22, 0.3);
}
.store-user-badge {
    background: rgba(249, 115, 22, 0.15);
    border: 1px solid rgba(249, 115, 22, 0.4);
    color: #f97316;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
}
.btn-logout {
    background: rgba(239, 68, 68, 0.2);
    border: 1px solid #ef4444;
    color: #ef4444;
    padding: 8px 14px;
    border-radius: 10px;
    text-decoration: none !important;
    font-size: 12px;
    font-weight: 700;
    transition: all 0.2s ease;
}
.btn-logout:hover {
    background: #ef4444;
    color: #ffffff;
}
</style>

<nav class="store-nav">
    <a href="index.php" class="store-logo">
        <span style="font-size: 22px;">🍎</span> SUDARSHAN <span>NUTRITION PARTNER</span>
    </a>
    <div class="store-menu">
        <a href="index.php" class="store-menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>">
            📊 Dashboard
        </a>
        <a href="manage_products.php" class="store-menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'manage_products.php' ? 'active' : ''; ?>">
            📦 Product Catalog
        </a>
        <a href="orders.php" class="store-menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'orders.php' ? 'active' : ''; ?>">
            🛒 Member Orders &amp; Inquiries
        </a>
        <span class="store-user-badge">👤 <?php echo htmlspecialchars($partner_name); ?></span>
        <a href="../../logout.php" class="btn-logout">🚪 Logout</a>
    </div>
</nav>
