<?php
require '../../include/db_conn.php';
page_protect();

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['nutrition_partner', 'super_admin', 'owner'])) {
    header("Location: ../../index.php");
    exit();
}

$gym = get_gym_details($con);
$msg = '';

// Handle Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $oid = intval($_POST['order_id']);
    $new_status = mysqli_real_escape_string($con, $_POST['new_status']);
    
    mysqli_query($con, "UPDATE nutrition_orders SET status = '$new_status' WHERE id = $oid");
    $msg = "<div style='background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #10b981; padding: 14px; border-radius: 12px; margin-bottom: 20px; font-weight: bold;'>✅ Order status updated to '$new_status'!</div>";
}

// Fetch All Orders
$q_orders = mysqli_query($con, "SELECT * FROM nutrition_orders ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Orders &amp; Inquiries | Sudarshan Nutrition</title>
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
        
        .page-title { font-family: 'Orbitron', sans-serif; font-size: 22px; font-weight: 800; color: #fff; margin-bottom: 25px; }

        .orders-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 25px;
        }
        .table-custom { width: 100%; border-collapse: collapse; font-size: 13px; }
        .table-custom th { text-align: left; padding: 12px; color: #94a3b8; font-weight: 700; text-transform: uppercase; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .table-custom td { padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.05); color: #cbd5e1; }

        .btn-wa {
            background: rgba(37, 211, 102, 0.2);
            border: 1px solid #25d366;
            color: #25d366;
            padding: 6px 12px;
            border-radius: 8px;
            text-decoration: none !important;
            font-weight: 700;
            font-size: 12px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .btn-wa:hover { background: #25d366; color: #fff; }
    </style>
</head>
<body>

    <?php include 'nav.php'; ?>

    <div class="container">
        <div class="page-title">🛒 Member Product Orders &amp; Inquiries</div>

        <?php echo $msg; ?>

        <div class="orders-card">
            <?php if ($q_orders && mysqli_num_rows($q_orders) > 0): ?>
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Order Code</th>
                            <th>Member Name</th>
                            <th>Mobile</th>
                            <th>Product Name</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action &amp; Contact</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($ord = mysqli_fetch_assoc($q_orders)): ?>
                            <?php
                            $wa_msg = "Hello " . $ord['member_name'] . "! Regarding your order " . $ord['order_code'] . " for " . $ord['product_name'] . " (₹" . number_format($ord['price']) . ") at " . $gym['gym_name'] . ". How can we assist you with delivery?";
                            $wa_url = "https://wa.me/91" . preg_replace('/[^0-9]/', '', $ord['member_mobile']) . "?text=" . urlencode($wa_msg);
                            ?>
                            <tr>
                                <td><code style="color: #f97316; font-weight: bold;"><?php echo htmlspecialchars($ord['order_code']); ?></code></td>
                                <td><strong><?php echo htmlspecialchars($ord['member_name']); ?></strong> <span style="font-size:11px; color:#94a3b8;">(#<?php echo htmlspecialchars($ord['member_id']); ?>)</span></td>
                                <td><?php echo htmlspecialchars($ord['member_mobile']); ?></td>
                                <td><?php echo htmlspecialchars($ord['product_name']); ?></td>
                                <td style="font-weight: bold; color: #10b981;">₹<?php echo number_format($ord['price']); ?></td>
                                <td>
                                    <form method="POST" action="" style="display:inline-flex; gap: 5px;">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="order_id" value="<?php echo $ord['id']; ?>">
                                        <select name="new_status" onchange="this.form.submit()" style="background: rgba(15,23,42,0.9); border: 1px solid var(--border-color); color: #fff; padding: 4px 8px; border-radius: 8px; font-size: 11px; font-weight: bold;">
                                            <option value="pending" <?php echo $ord['status'] === 'pending' ? 'selected' : ''; ?>>⏳ Pending</option>
                                            <option value="confirmed" <?php echo $ord['status'] === 'confirmed' ? 'selected' : ''; ?>>✅ Confirmed</option>
                                            <option value="delivered" <?php echo $ord['status'] === 'delivered' ? 'selected' : ''; ?>>🚚 Delivered</option>
                                        </select>
                                    </form>
                                </td>
                                <td style="color: #94a3b8; font-size: 12px;"><?php echo date('d-M-Y', strtotime($ord['created_at'])); ?></td>
                                <td>
                                    <a href="<?php echo $wa_url; ?>" target="_blank" class="btn-wa">💬 WhatsApp Client</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="text-align: center; color: #94a3b8; padding: 20px; font-size: 13px;">No member orders logged yet.</div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>
