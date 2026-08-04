<?php
require_once __DIR__ . '/../include/db_conn.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_data']) || $_SESSION['role'] !== 'member') {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_data'];
$gym = get_gym_details($con);

// Fetch Member Details
$res_m = mysqli_query($con, "SELECT * FROM users WHERE userid = '$user_id'");
$member = mysqli_fetch_assoc($res_m);

$msg = '';

// Handle Product Order Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'order_product') {
    $product_id = intval($_POST['product_id']);
    $notes = mysqli_real_escape_string($con, trim($_POST['notes'] ?? ''));

    $q_p = mysqli_query($con, "SELECT * FROM nutrition_products WHERE id = $product_id");
    if ($q_p && mysqli_num_rows($q_p) > 0) {
        $p = mysqli_fetch_assoc($q_p);
        $order_code = 'ORD-' . strtoupper(substr(md5(uniqid()), 0, 6));
        $m_name = mysqli_real_escape_string($con, $member['username']);
        $m_mobile = mysqli_real_escape_string($con, $member['mobile']);
        $p_name = mysqli_real_escape_string($con, $p['product_name']);
        $price = $p['discount_price'] > 0 ? $p['discount_price'] : $p['price'];

        $q_ins = "INSERT INTO nutrition_orders (order_code, member_id, member_name, member_mobile, product_id, product_name, price, status, notes) 
                  VALUES ('$order_code', '$user_id', '$m_name', '$m_mobile', $product_id, '$p_name', $price, 'pending', '$notes')";

        if (mysqli_query($con, $q_ins)) {
            $gym_wa = !empty($gym['phone']) ? preg_replace('/[^0-9]/', '', $gym['phone']) : '919325205075';
            $wa_text = "Hello " . $gym['gym_name'] . "! I would like to order/inquire about the nutrition product:\n\n*Product*: " . $p['product_name'] . "\n*Price*: ₹" . number_format($price) . "\n*Order Code*: " . $order_code . "\n*Member*: " . $member['username'] . " (ID: #" . $user_id . ")\n\nPlease confirm availability and delivery!";
            $wa_url = "https://wa.me/91" . $gym_wa . "?text=" . urlencode($wa_text);

            header("Location: " . $wa_url);
            exit();
        }
    }
}

// Category filter
$cat_filter = isset($_GET['cat']) ? $_GET['cat'] : 'all';
$where_clause = "";
if ($cat_filter === 'fat_loss') $where_clause = "WHERE category = 'Fat Loss'";
elseif ($cat_filter === 'muscle_gain') $where_clause = "WHERE category = 'Muscle Gain'";
elseif ($cat_filter === 'lean_muscle') $where_clause = "WHERE category = 'Lean Muscle'";
elseif ($cat_filter === 'recovery') $where_clause = "WHERE category = 'Recovery'";

$q_catalog = mysqli_query($con, "SELECT * FROM nutrition_products $where_clause ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Nutrition &amp; Supplement Store | <?php echo htmlspecialchars($gym['gym_name']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-dark: #070a13;
            --card-bg: rgba(30, 41, 59, 0.85);
            --accent-orange: #f97316;
            --border: rgba(249, 115, 22, 0.25);
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Outfit', sans-serif; }
        body { background: var(--bg-dark); color: #f8fafc; padding-bottom: 90px; }

        .header {
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--border);
            padding: 16px 20px;
            position: sticky;
            top: 0;
            z-index: 100;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header-title { font-size: 18px; font-weight: 800; color: #fff; display: flex; align-items: center; gap: 8px; }

        .container { padding: 20px; max-width: 800px; margin: 0 auto; }

        /* Filter Pills */
        .cat-pills {
            display: flex;
            gap: 10px;
            overflow-x: auto;
            padding-bottom: 10px;
            margin-bottom: 20px;
            scrollbar-width: none;
        }
        .cat-pills::-webkit-scrollbar { display: none; }
        .pill {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #cbd5e1;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none !important;
            white-space: nowrap;
            transition: all 0.2s ease;
        }
        .pill.active, .pill:hover {
            background: rgba(249, 115, 22, 0.2);
            color: #fff;
            border-color: #f97316;
            box-shadow: 0 0 12px rgba(249, 115, 22, 0.3);
        }

        /* Products Grid */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 20px;
        }
        .p-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            position: relative;
            box-shadow: 0 8px 25px rgba(0,0,0,0.4);
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
        .p-title { font-size: 18px; font-weight: 800; color: #fff; }
        .p-benefits { color: #f97316; font-size: 12px; font-weight: 700; }
        .p-desc { color: #94a3b8; font-size: 13px; line-height: 1.5; flex-grow: 1; }

        .price-box {
            display: flex;
            align-items: baseline;
            gap: 10px;
            margin-top: 5px;
        }
        .price-current { font-size: 22px; font-weight: 900; color: #10b981; }
        .price-old { font-size: 14px; color: #64748b; text-decoration: line-through; }
        .save-tag { font-size: 11px; background: rgba(16, 185, 129, 0.15); color: #10b981; padding: 2px 8px; border-radius: 6px; font-weight: 800; }

        .btn-buy {
            background: linear-gradient(135deg, #25d366, #128c7e);
            color: #fff;
            border: none;
            padding: 12px;
            border-radius: 12px;
            font-weight: 800;
            font-size: 14px;
            cursor: pointer;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(37, 211, 102, 0.3);
            transition: transform 0.2s ease;
        }
        .btn-buy:hover { transform: translateY(-2px); }

        /* Bottom Nav */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(16px);
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: space-around;
            padding: 10px 0;
            z-index: 1000;
        }
        .nav-item {
            color: #94a3b8;
            text-decoration: none !important;
            font-size: 11px;
            font-weight: 700;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
        }
        .nav-item.active { color: #f97316; }
        .nav-item i { font-size: 20px; }
    </style>
</head>
<body>

    <header class="header">
        <div class="header-title">🍎 Nutrition Store</div>
        <a href="dashboard.php" style="color: #94a3b8; text-decoration: none; font-size: 13px; font-weight: 700;">&larr; Back to App</a>
    </header>

    <div class="container">
        <!-- Goal Filter Pills -->
        <div class="cat-pills">
            <a href="?cat=all" class="pill <?php echo $cat_filter === 'all' ? 'active' : ''; ?>">🔥 All Supplements</a>
            <a href="?cat=fat_loss" class="pill <?php echo $cat_filter === 'fat_loss' ? 'active' : ''; ?>">⚡ Fat Loss</a>
            <a href="?cat=muscle_gain" class="pill <?php echo $cat_filter === 'muscle_gain' ? 'active' : ''; ?>">💪 Muscle Gain</a>
            <a href="?cat=lean_muscle" class="pill <?php echo $cat_filter === 'lean_muscle' ? 'active' : ''; ?>">🏋️ Lean Muscle</a>
            <a href="?cat=recovery" class="pill <?php echo $cat_filter === 'recovery' ? 'active' : ''; ?>">🔋 Recovery</a>
        </div>

        <div class="products-grid">
            <?php if ($q_catalog && mysqli_num_rows($q_catalog) > 0): ?>
                <?php while ($p = mysqli_fetch_assoc($q_catalog)): ?>
                    <?php 
                    $img_url = get_member_photo_url($p, '../');
                    $curr_price = $p['discount_price'] > 0 ? $p['discount_price'] : $p['price'];
                    $has_disc = $p['discount_price'] > 0 && $p['discount_price'] < $p['price'];
                    $save_amt = $p['price'] - $p['discount_price'];
                    ?>
                    <div class="p-card">
                        <span class="p-badge"><?php echo htmlspecialchars($p['category']); ?></span>
                        
                        <?php if (!empty($p['photo_base64']) || !empty($p['photo_url'])): ?>
                            <img src="<?php echo $img_url; ?>" style="width: 100%; height: 160px; object-fit: cover; border-radius: 12px;" alt="Product">
                        <?php endif; ?>

                        <div class="p-title"><?php echo htmlspecialchars($p['product_name']); ?></div>
                        
                        <?php if (!empty($p['benefits'])): ?>
                            <div class="p-benefits">✨ <?php echo htmlspecialchars($p['benefits']); ?></div>
                        <?php endif; ?>

                        <div class="p-desc"><?php echo htmlspecialchars($p['description']); ?></div>

                        <div class="price-box">
                            <div class="price-current">₹<?php echo number_format($curr_price); ?></div>
                            <?php if ($has_disc): ?>
                                <div class="price-old">₹<?php echo number_format($p['price']); ?></div>
                                <span class="save-tag">SAVE ₹<?php echo number_format($save_amt); ?></span>
                            <?php endif; ?>
                        </div>

                        <form method="POST" action="">
                            <input type="hidden" name="action" value="order_product">
                            <input type="hidden" name="product_id" value="<?php echo $p['id']; ?>">
                            <button type="submit" class="btn-buy">
                                💬 Buy / Inquire via WhatsApp ➔
                            </button>
                        </form>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="text-align: center; color: #94a3b8; padding: 40px; grid-column: 1 / -1;">No products found in this category.</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bottom Navigation -->
    <nav class="bottom-nav">
        <a href="dashboard.php" class="nav-item">
            <span>🏠 Home</span>
        </a>
        <a href="store.php" class="nav-item active">
            <span>🍎 Nutrition Store</span>
        </a>
        <a href="routine.php" class="nav-item">
            <span>🏋️ Workout</span>
        </a>
        <a href="profile.php" class="nav-item">
            <span>👤 Profile</span>
        </a>
    </nav>

</body>
</html>
