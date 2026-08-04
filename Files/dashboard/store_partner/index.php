<?php
require '../../include/db_conn.php';
page_protect();

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['nutrition_partner', 'super_admin', 'owner'])) {
    header("Location: ../../index.php");
    exit();
}

$gym = get_gym_details($con);

// Fetch Stats
$res_p_cnt = mysqli_query($con, "SELECT COUNT(*) as total FROM nutrition_products");
$p_cnt_row = mysqli_fetch_assoc($res_p_cnt);
$total_products = $p_cnt_row['total'] ?? 0;

$res_o_cnt = mysqli_query($con, "SELECT COUNT(*) as total FROM nutrition_orders");
$o_cnt_row = mysqli_fetch_assoc($res_o_cnt);
$total_orders = $o_cnt_row['total'] ?? 0;

$res_p_orders = mysqli_query($con, "SELECT COUNT(*) as total FROM nutrition_orders WHERE status='pending'");
$p_orders_row = mysqli_fetch_assoc($res_p_orders);
$pending_orders = $p_orders_row['total'] ?? 0;

// Fetch Recent Products
$q_products = mysqli_query($con, "SELECT * FROM nutrition_products ORDER BY id DESC LIMIT 6");

// Fetch Recent Orders
$q_recent_orders = mysqli_query($con, "SELECT * FROM nutrition_orders ORDER BY id DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nutrition Partner Portal | <?php echo htmlspecialchars($gym['gym_name']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@600;800;900&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-dark: #0b0f19;
            --card-bg: rgba(30, 41, 59, 0.85);
            --accent-orange: #f97316;
            --border-color: rgba(249, 115, 22, 0.25);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Outfit', sans-serif; }
        body { background: var(--bg-dark); color: #f8fafc; min-height: 100vh; }
        
        .container { max-width: 1200px; margin: 0 auto; padding: 30px 20px; }
        
        .hero-banner {
            background: linear-gradient(135deg, rgba(249, 115, 22, 0.2), rgba(15, 23, 42, 0.9));
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        .hero-title { font-family: 'Orbitron', sans-serif; font-size: 24px; font-weight: 800; color: #fff; }
        .hero-sub { color: #94a3b8; font-size: 14px; margin-top: 6px; }

        .btn-add {
            background: linear-gradient(135deg, #f97316, #ea580c);
            color: #fff;
            padding: 12px 24px;
            border-radius: 12px;
            text-decoration: none !important;
            font-weight: 700;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(249, 115, 22, 0.4);
            transition: transform 0.2s ease;
        }
        .btn-add:hover { transform: translateY(-2px); }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 35px;
        }
        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 22px;
            display: flex;
            align-items: center;
            gap: 18px;
        }
        .stat-icon {
            width: 54px;
            height: 54px;
            border-radius: 14px;
            background: rgba(249, 115, 22, 0.15);
            border: 1px solid rgba(249, 115, 22, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: var(--accent-orange);
        }
        .stat-num { font-size: 28px; font-weight: 800; font-family: 'Orbitron', sans-serif; color: #fff; }
        .stat-lbl { color: #94a3b8; font-size: 12px; font-weight: 600; text-transform: uppercase; margin-top: 2px; }

        .section-box {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 35px;
        }
        .sec-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            padding-bottom: 12px;
        }
        .sec-title { font-size: 16px; font-weight: 800; color: #fff; text-transform: uppercase; letter-spacing: 0.5px; }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }
        .p-card {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            padding: 18px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            position: relative;
        }
        .p-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(249, 115, 22, 0.2);
            border: 1px solid #f97316;
            color: #f97316;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 800;
        }
        .p-name { font-size: 16px; font-weight: 700; color: #fff; }
        .p-desc { font-size: 12px; color: #94a3b8; line-height: 1.4; flex-grow: 1; }
        .p-price { font-size: 18px; font-weight: 800; color: #10b981; }

        .table-custom {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        .table-custom th { text-align: left; padding: 12px; color: #94a3b8; font-weight: 700; text-transform: uppercase; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .table-custom td { padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.05); color: #cbd5e1; }
        
        .badge-status {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .badge-pending { background: rgba(245, 158, 11, 0.2); border: 1px solid #f59e0b; color: #f59e0b; }
        .badge-delivered { background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #10b981; }
    </style>
</head>
<body>

    <?php include 'nav.php'; ?>

    <div class="container">
        <div class="hero-banner">
            <div>
                <div class="hero-title">🍎 Nutrition &amp; Supplement Partner Portal</div>
                <div class="hero-sub">Manage your products (Fat Loss, Muscle Gain, Lean Muscle) and member orders for <?php echo htmlspecialchars($gym['gym_name']); ?>.</div>
            </div>
            <a href="manage_products.php?action=new" class="btn-add">➕ Add New Supplement</a>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📦</div>
                <div>
                    <div class="stat-num"><?php echo $total_products; ?></div>
                    <div class="stat-lbl">Active Products</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🛒</div>
                <div>
                    <div class="stat-num"><?php echo $total_orders; ?></div>
                    <div class="stat-lbl">Total Orders Received</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⚡</div>
                <div>
                    <div class="stat-num" style="color: #f59e0b;"><?php echo $pending_orders; ?></div>
                    <div class="stat-lbl">Pending Member Inquiries</div>
                </div>
            </div>
        </div>

        <!-- Featured Products Section -->
        <div class="section-box">
            <div class="sec-header">
                <div class="sec-title">📦 Active Product Catalog</div>
                <a href="manage_products.php" style="color: #f97316; font-size: 13px; font-weight: 700; text-decoration: none;">View All Products &rarr;</a>
            </div>
            <div class="products-grid">
                <?php while ($p = mysqli_fetch_assoc($q_products)): ?>
                    <?php $p_img = get_member_photo_url($p, '../../'); ?>
                    <div class="p-card">
                        <span class="p-badge"><?php echo htmlspecialchars($p['category']); ?></span>
                        <?php if (!empty($p['photo_base64']) || !empty($p['photo_url'])): ?>
                            <img src="<?php echo $p_img; ?>" style="width: 100%; height: 140px; object-fit: cover; border-radius: 10px; margin-bottom: 5px;" alt="Product">
                        <?php endif; ?>
                        <div class="p-name"><?php echo htmlspecialchars($p['product_name']); ?></div>
                        <div class="p-desc"><?php echo htmlspecialchars(substr($p['description'], 0, 90)) . '...'; ?></div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px;">
                            <div class="p-price">₹<?php echo number_format($p['discount_price'] > 0 ? $p['discount_price'] : $p['price']); ?></div>
                            <span style="font-size: 11px; color: #94a3b8; text-transform: uppercase; font-weight: 700;"><?php echo str_replace('_', ' ', $p['stock_status']); ?></span>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- Recent Member Orders Section -->
        <div class="section-box">
            <div class="sec-header">
                <div class="sec-title">🛒 Recent Member Orders &amp; Inquiries</div>
                <a href="orders.php" style="color: #f97316; font-size: 13px; font-weight: 700; text-decoration: none;">Manage Orders &rarr;</a>
            </div>
            <?php if ($q_recent_orders && mysqli_num_rows($q_recent_orders) > 0): ?>
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
                        <?php while ($ord = mysqli_fetch_assoc($q_recent_orders)): ?>
                            <tr>
                                <td><code style="color: #f97316; font-weight: bold;"><?php echo htmlspecialchars($ord['order_code']); ?></code></td>
                                <td><strong><?php echo htmlspecialchars($ord['member_name']); ?></strong> <span style="font-size:11px; color:#94a3b8;">(#<?php echo htmlspecialchars($ord['member_id']); ?>)</span></td>
                                <td><?php echo htmlspecialchars($ord['member_mobile']); ?></td>
                                <td><?php echo htmlspecialchars($ord['product_name']); ?></td>
                                <td style="font-weight: bold; color: #10b981;">₹<?php echo number_format($ord['price']); ?></td>
                                <td><span class="badge-status <?php echo $ord['status'] === 'pending' ? 'badge-pending' : 'badge-delivered'; ?>"><?php echo htmlspecialchars($ord['status']); ?></span></td>
                                <td style="color: #94a3b8; font-size: 12px;"><?php echo date('d-M-Y', strtotime($ord['created_at'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="text-align: center; color: #94a3b8; padding: 20px; font-size: 13px;">No member orders received yet.</div>
            <?php endif; ?>
        </div>

    </div>

</body>
</html>
