<?php
require '../../include/db_conn.php';
page_protect();

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['super_admin', 'owner'])) {
    header("Location: index.php");
    exit();
}

$gym = get_gym_details($con);
$msg = '';

// Handle Reset Partner Password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_partner_pass') {
    $new_pass = mysqli_real_escape_string($con, trim($_POST['new_pass']));
    mysqli_query($con, "UPDATE admin SET pass_key = '$new_pass' WHERE role = 'nutrition_partner' OR username = 'nutrition_partner'");
    $msg = "<div style='background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #10b981; padding: 14px; border-radius: 12px; margin-bottom: 20px; font-weight: bold;'>✅ Nutrition Partner Login Password updated to '$new_pass'!</div>";
}

// Fetch Partner Details
$q_partner = mysqli_query($con, "SELECT * FROM admin WHERE role = 'nutrition_partner' OR username = 'nutrition_partner' LIMIT 1");
$partner_data = mysqli_fetch_assoc($q_partner);

// Fetch Products Count & Orders Count
$res_p = mysqli_query($con, "SELECT COUNT(*) as total FROM nutrition_products");
$p_total = mysqli_fetch_assoc($res_p)['total'] ?? 0;

$res_o = mysqli_query($con, "SELECT COUNT(*) as total FROM nutrition_orders");
$o_total = mysqli_fetch_assoc($res_o)['total'] ?? 0;

$q_products = mysqli_query($con, "SELECT * FROM nutrition_products ORDER BY id DESC");
$q_orders = mysqli_query($con, "SELECT * FROM nutrition_orders ORDER BY id DESC LIMIT 15");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($gym['gym_name']); ?> | Nutrition &amp; Supplement Store Center</title>
    <link rel="stylesheet" href="../../css/style.css" id="style-resource-5">
    <script type="text/javascript" src="../../js/Script.js"></script>
    <link rel="stylesheet" href="../../css/dashMain.css">
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
    <link rel="stylesheet" href="../../css/premium.css">
    <style>
        .sec-card {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 25px;
        }
        .sec-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255, 107, 0, 0.2);
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .sec-title { font-size: 16px; font-weight: 800; color: var(--accent-primary); text-transform: uppercase; }
        .table-custom { width: 100%; border-collapse: collapse; font-size: 13px; }
        .table-custom th { text-align: left; padding: 10px; color: var(--text-muted); font-weight: 700; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .table-custom td { padding: 10px; border-bottom: 1px solid rgba(255,255,255,0.05); color: #cbd5e1; }
    </style>
</head>
<body class="page-body page-fade" onload="collapseSidebar()">

    <div class="page-container sidebar-collapsed" id="navbarcollapse">	
        <div class="sidebar-menu">
            <header class="logo-env">
                <div class="logo">
                    <a href="index.php">
                        <img src="../../images/logo.png" alt="" style="max-height: 60px; max-width: 180px;" />
                    </a>
                </div>
                <div class="sidebar-collapse" onclick="collapseSidebar()">
                    <a href="#" class="sidebar-collapse-icon with-animation"><i class="entypo-menu"></i></a>
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
                        <li><a href="logout.php">Log Out <i class="entypo-logout right"></i></a></li>
                    </ul>
                </div>
            </div>

            <h2>🍎 Nutrition &amp; Supplement Store Command Center</h2>
            <p style="color: var(--text-muted); font-size: 13px;">Manage nutrition vendor partner login, product catalog, and member supplement orders.</p>
            <hr />

            <?php echo $msg; ?>

            <!-- Partner Credentials Management Card -->
            <div class="sec-card">
                <div class="sec-header">
                    <div class="sec-title">👤 Nutrition Partner Vendor Access Credentials</div>
                    <a href="../store_partner/index.php" target="_blank" class="a1-btn" style="background: rgba(249,115,22,0.2) !important; color: #f97316 !important; border: 1px solid #f97316;">🔗 Open Partner Portal &rarr;</a>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; align-items: center;">
                    <div>
                        <div style="font-size: 13px; color: var(--text-muted); margin-bottom: 4px;">Partner Login Username:</div>
                        <code style="font-size: 16px; color: #38bdf8; font-weight: bold; background: rgba(0,0,0,0.3); padding: 4px 10px; border-radius: 6px;">nutrition_partner</code>
                        <div style="font-size: 11px; color: var(--text-muted); margin-top: 8px;">Login Role: <strong>Store Partner (nutrition_partner)</strong></div>
                    </div>

                    <form method="POST" action="" style="display: flex; gap: 10px; align-items: flex-end;">
                        <input type="hidden" name="action" value="reset_partner_pass">
                        <div style="flex: 1;">
                            <label style="font-size: 12px; color: var(--text-muted); font-weight: bold;">Set New Partner Login Password:</label>
                            <input type="text" name="new_pass" class="form-control" value="<?php echo htmlspecialchars($partner_data['pass_key'] ?? '268724'); ?>" required style="margin-top: 4px;">
                        </div>
                        <button type="submit" class="a1-btn a1-blue">Update Password</button>
                    </form>
                </div>
            </div>

            <!-- Product Catalog Table -->
            <div class="sec-card">
                <div class="sec-header">
                    <div class="sec-title">📦 Active Supplement Catalog (<?php echo $p_total; ?> Products)</div>
                    <a href="../store_partner/manage_products.php" class="a1-btn a1-green">+ Add Product</a>
                </div>

                <?php if ($q_products && mysqli_num_rows($q_products) > 0): ?>
                    <table class="table-custom">
                        <thead>
                            <tr>
                                <th>Photo</th>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Special Offer</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($p = mysqli_fetch_assoc($q_products)): ?>
                                <?php $img_url = get_member_photo_url($p, '../../'); ?>
                                <tr>
                                    <td><img src="<?php echo $img_url; ?>" style="width: 40px; height: 40px; border-radius: 8px; object-fit: cover;" alt="Photo"></td>
                                    <td><strong><?php echo htmlspecialchars($p['product_name']); ?></strong></td>
                                    <td><span style="background: rgba(249,115,22,0.15); color: #f97316; padding: 2px 8px; border-radius: 8px; font-weight: bold; font-size: 11px;"><?php echo htmlspecialchars($p['category']); ?></span></td>
                                    <td>₹<?php echo number_format($p['price']); ?></td>
                                    <td style="color: #10b981; font-weight: bold;"><?php echo $p['discount_price'] > 0 ? '₹' . number_format($p['discount_price']) : '-'; ?></td>
                                    <td style="font-weight: bold; font-size: 11px;"><?php echo str_replace('_', ' ', strtoupper($p['stock_status'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div style="color: var(--text-muted); text-align: center; padding: 20px;">No products in catalog.</div>
                <?php endif; ?>
            </div>

            <!-- Member Orders Table -->
            <div class="sec-card">
                <div class="sec-header">
                    <div class="sec-title">🛒 Recent Member Orders (<?php echo $o_total; ?> Orders)</div>
                    <a href="../store_partner/orders.php" class="a1-btn a1-blue">Manage Orders</a>
                </div>

                <?php if ($q_orders && mysqli_num_rows($q_orders) > 0): ?>
                    <table class="table-custom">
                        <thead>
                            <tr>
                                <th>Order Code</th>
                                <th>Member Name</th>
                                <th>Mobile</th>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($ord = mysqli_fetch_assoc($q_orders)): ?>
                                <tr>
                                    <td><code style="color: #f97316; font-weight: bold;"><?php echo htmlspecialchars($ord['order_code']); ?></code></td>
                                    <td><strong><?php echo htmlspecialchars($ord['member_name']); ?></strong> <span style="font-size:11px; color:var(--text-muted);">(#<?php echo htmlspecialchars($ord['member_id']); ?>)</span></td>
                                    <td><?php echo htmlspecialchars($ord['member_mobile']); ?></td>
                                    <td><?php echo htmlspecialchars($ord['product_name']); ?></td>
                                    <td style="font-weight: bold; color: #10b981;">₹<?php echo number_format($ord['price']); ?></td>
                                    <td style="font-weight: bold; font-size: 11px; text-transform: uppercase;"><?php echo htmlspecialchars($ord['status']); ?></td>
                                    <td style="color: var(--text-muted); font-size: 12px;"><?php echo date('d-M-Y', strtotime($ord['created_at'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div style="color: var(--text-muted); text-align: center; padding: 20px;">No member orders recorded yet.</div>
                <?php endif; ?>
            </div>

            <?php include('footer.php'); ?>
        </div>
    </div>

</body>
</html>
