<?php
require_once __DIR__ . '/../../include/db_conn.php';
page_protect();

if (!isset($_SESSION['user_data']) || $_SESSION['role'] !== 'member') {
    header("Location: ../../index.php");
    exit();
}

$user_id = $_SESSION['user_data'];
$gym = get_gym_details($con);

// Fetch Member Details
$res_m = mysqli_query($con, "SELECT * FROM users WHERE userid = '$user_id'");
$member = mysqli_fetch_assoc($res_m);

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
    <title><?php echo htmlspecialchars($gym['gym_name']); ?> | Nutrition Store</title>
    <link rel="stylesheet" href="../../css/style.css" id="style-resource-5">
    <script type="text/javascript" src="../../js/Script.js"></script>
    <link rel="stylesheet" href="../../css/dashMain.css">
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
    <link rel="stylesheet" href="../../css/premium.css">
    <style>
        .store-box {
            background: var(--glass-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 25px;
        }
        .cat-pills { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 20px; }
        .pill {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #cbd5e1;
            padding: 8px 18px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none !important;
        }
        .pill.active { background: rgba(249, 115, 22, 0.2); border-color: #f97316; color: #fff; }
        .products-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px; }
        .p-card {
            background: rgba(0, 0, 0, 0.25);
            border: 1px solid rgba(249, 115, 22, 0.2);
            border-radius: 16px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            position: relative;
        }
        .p-badge { position: absolute; top: 15px; right: 15px; background: rgba(249, 115, 22, 0.2); border: 1px solid #f97316; color: #f97316; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; }
        .btn-wa { background: #25d366; color: #fff; border: none; padding: 12px; border-radius: 10px; font-weight: bold; width: 100%; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; }
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

            <h2>🍎 Sudarshan Nutrition &amp; Supplement Store</h2>
            <p style="color: var(--text-muted); font-size: 13px;">Special fat loss, muscle gain, and lean muscle supplements tailored for your fitness goals.</p>
            <hr />

            <div class="store-box">
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
                            $img_url = get_member_photo_url($p, '../../');
                            $curr_price = $p['discount_price'] > 0 ? $p['discount_price'] : $p['price'];
                            ?>
                            <div class="p-card">
                                <span class="p-badge"><?php echo htmlspecialchars($p['category']); ?></span>
                                
                                <?php if (!empty($p['photo_base64']) || !empty($p['photo_url'])): ?>
                                    <img src="<?php echo $img_url; ?>" style="width: 100%; height: 150px; object-fit: cover; border-radius: 10px;" alt="Product">
                                <?php endif; ?>

                                <h4 style="color: #fff; margin: 5px 0 0 0; font-weight: bold;"><?php echo htmlspecialchars($p['product_name']); ?></h4>
                                <div style="color: #94a3b8; font-size: 12px; line-height: 1.4;"><?php echo htmlspecialchars($p['description']); ?></div>

                                <div style="font-size: 20px; font-weight: bold; color: #10b981; margin-top: 5px;">₹<?php echo number_format($curr_price); ?></div>

                                <form method="POST" action="">
                                    <input type="hidden" name="action" value="order_product">
                                    <input type="hidden" name="product_id" value="<?php echo $p['id']; ?>">
                                    <button type="submit" class="btn-wa">💬 Order via WhatsApp ➔</button>
                                </form>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div style="color: var(--text-muted); text-align: center; padding: 20px; grid-column: 1 / -1;">No products found in this category.</div>
                    <?php endif; ?>
                </div>
            </div>

            <?php include('footer.php'); ?>
        </div>
    </div>

</body>
</html>
