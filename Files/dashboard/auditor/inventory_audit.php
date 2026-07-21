<?php
require 'auth.php';
require '../../include/db_conn.php';
$gym = get_gym_details($con);

// Date filter
$filter_date = isset($_GET['date']) ? mysqli_real_escape_string($con, $_GET['date']) : date('Y-m-d');

// Fetch today's sales
$q_sales = "SELECT s.*, i.product_name, i.category, i.price as unit_price, u.username
            FROM inventory_sales s
            INNER JOIN inventory_items i ON s.product_id = i.id
            LEFT JOIN users u ON s.member_id = u.userid
            WHERE s.sale_date = '$filter_date'
            ORDER BY s.id DESC";
$res_sales = mysqli_query($con, $q_sales);

// Fetch current stock
$q_stock = "SELECT * FROM inventory_items ORDER BY category ASC, product_name ASC";
$res_stock = mysqli_query($con, $q_stock);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Inventory Audit | <?php echo htmlspecialchars($gym['gym_name']); ?></title>
    <link rel="stylesheet" href="../../css/style.css"/>
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
    <style>
        body { background: #0b0f19; color: #fff; font-family: 'Inter', sans-serif; }
        .page-container { display: flex; }
        .sidebar-menu { width: 250px; background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(20px); border-right: 1px solid rgba(255, 255, 255, 0.05); padding: 20px; min-height: 100vh; }
        .main-content { flex: 1; padding: 40px; }
        .sidebar-menu ul { list-style: none; padding: 0; }
        .sidebar-menu ul li { margin-bottom: 10px; }
        .sidebar-menu ul li a { color: #9ca3af; text-decoration: none; display: block; padding: 12px 15px; border-radius: 8px; font-weight: 600; transition: all 0.2s; }
        .sidebar-menu ul li a:hover, .sidebar-menu ul li.active a { background: linear-gradient(135deg, #10b981, #059669); color: #fff; box-shadow: 0 4px 15px rgba(16,185,129,0.3); }
        .audit-card { background: rgba(255, 255, 255, 0.02); backdrop-filter: blur(15px); border: 1px solid rgba(255,255,255,0.05); border-radius: 20px; padding: 30px; margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 13.5px; }
        th { color: #9ca3af; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; }
        .badge { padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 11px; }
        .badge-success { background: rgba(16, 185, 129, 0.2); color: #10b981; }
        .badge-warning { background: rgba(245, 158, 11, 0.2); color: #f59e0b; }
        .badge-danger { background: rgba(239, 68, 68, 0.2); color: #ef4444; }
    </style>
</head>
<body>
    <div class="page-container">
        <div class="sidebar-menu">
            <h2 style="color:#10b981; text-align:center; margin-bottom: 30px;">Titan Gym<br><small style="color:#fff;">Auditor</small></h2>
            <?php include('nav.php'); ?>
        </div>

        <div class="main-content">
            <h1 style="margin-bottom: 10px; font-weight: 800; font-size: 32px; display: flex; align-items: center; gap: 10px;">
                <i class="entypo-basket" style="color: #10b981;"></i> Inventory &amp; Store Stock Audit
            </h1>
            <p style="color: rgba(255,255,255,0.6); margin-bottom: 30px;">Audit all supplement sales, product stock levels, and store revenues.</p>

            <!-- Date Filter & Sales Audit -->
            <div class="audit-card">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                    <h3 style="margin: 0; font-weight: 800; color: #10b981;"><i class="entypo-list"></i> Store Sales Ledger</h3>
                    <form method="GET" style="display: flex; gap: 10px; align-items: center;">
                        <label style="color: #9ca3af; font-size: 13px;">Date:</label>
                        <input type="date" name="date" value="<?php echo htmlspecialchars($filter_date); ?>" style="padding: 8px 12px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.2); background: rgba(0,0,0,0.3); color: white; color-scheme: dark;" onchange="this.form.submit()">
                    </form>
                </div>

                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Qty</th>
                                <th>Total Revenue</th>
                                <th>Payment Mode</th>
                                <th>Sold To</th>
                                <th>Billed By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $total_sales_sum = 0;
                            $count = 1;
                            if ($res_sales && mysqli_num_rows($res_sales) > 0) {
                                while ($row = mysqli_fetch_assoc($res_sales)) {
                                    $total_sales_sum += intval($row['total_price']);
                                    $buyer = !empty($row['username']) ? htmlspecialchars($row['username']) : 'Guest / Direct';
                                    echo "<tr>";
                                    echo "<td>{$count}</td>";
                                    echo "<td><strong>" . htmlspecialchars($row['product_name']) . "</strong></td>";
                                    echo "<td>" . htmlspecialchars($row['category']) . "</td>";
                                    echo "<td>" . intval($row['quantity']) . "</td>";
                                    echo "<td style='color:#10b981; font-weight:bold;'>₹" . number_format($row['total_price']) . "</td>";
                                    echo "<td><span class='badge badge-success'>" . htmlspecialchars($row['payment_mode']) . "</span></td>";
                                    echo "<td>" . $buyer . "</td>";
                                    echo "<td>" . htmlspecialchars($row['received_by']) . "</td>";
                                    echo "</tr>";
                                    $count++;
                                }
                                echo "<tr style='background: rgba(16,185,129,0.08); font-weight: bold;'>";
                                echo "<td colspan='4' style='text-align: right; color: #10b981;'>Total Store Revenue ($filter_date):</td>";
                                echo "<td style='color:#10b981; font-size: 16px;'>₹" . number_format($total_sales_sum) . "</td>";
                                echo "<td colspan='3'></td>";
                                echo "</tr>";
                            } else {
                                echo "<tr><td colspan='8' style='text-align:center; color:#9ca3af;'>No inventory sales recorded on {$filter_date}</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Current Stock Levels -->
            <div class="audit-card">
                <h3 style="margin-top: 0; font-weight: 800; color: #3b82f6;"><i class="entypo-box"></i> Live Product Stock Audit</h3>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th>Unit Price</th>
                                <th>Stock Remaining</th>
                                <th>Stock Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $s_count = 1;
                            if ($res_stock && mysqli_num_rows($res_stock) > 0) {
                                while ($s_row = mysqli_fetch_assoc($res_stock)) {
                                    $st = intval($s_row['stock_quantity']);
                                    $badge = "<span class='badge badge-success'>In Stock ($st)</span>";
                                    if ($st == 0) {
                                        $badge = "<span class='badge badge-danger'>OUT OF STOCK (0)</span>";
                                    } elseif ($st < 5) {
                                        $badge = "<span class='badge badge-warning'>LOW STOCK ($st)</span>";
                                    }
                                    echo "<tr>";
                                    echo "<td>{$s_count}</td>";
                                    echo "<td><strong>" . htmlspecialchars($s_row['product_name']) . "</strong></td>";
                                    echo "<td>" . htmlspecialchars($s_row['category']) . "</td>";
                                    echo "<td>₹" . number_format($s_row['price']) . "</td>";
                                    echo "<td>" . $st . "</td>";
                                    echo "<td>" . $badge . "</td>";
                                    echo "</tr>";
                                    $s_count++;
                                }
                            } else {
                                echo "<tr><td colspan='6' style='text-align:center;'>No products in inventory</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</body>
</html>
