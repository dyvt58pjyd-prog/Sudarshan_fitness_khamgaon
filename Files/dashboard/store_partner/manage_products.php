<?php
require '../../include/db_conn.php';
page_protect();

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['nutrition_partner', 'super_admin', 'owner'])) {
    header("Location: ../../index.php");
    exit();
}

$gym = get_gym_details($con);
$msg = '';

// Handle Add Product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_product') {
    $name = mysqli_real_escape_string($con, trim($_POST['product_name']));
    $category = mysqli_real_escape_string($con, trim($_POST['category']));
    $description = mysqli_real_escape_string($con, trim($_POST['description']));
    $benefits = mysqli_real_escape_string($con, trim($_POST['benefits']));
    $price = intval($_POST['price']);
    $discount_price = intval($_POST['discount_price']);
    $stock_status = mysqli_real_escape_string($con, $_POST['stock_status']);

    $photo_path = '';
    $photo_b64 = '';

    if (isset($_FILES['product_photo']) && $_FILES['product_photo']['error'] === UPLOAD_ERR_OK) {
        $tmp = $_FILES['product_photo']['tmp_name'];
        $raw = @file_get_contents($tmp);
        if ($raw !== false) {
            $photo_b64 = 'data:image/jpeg;base64,' . base64_encode($raw);
        }
        $upload_dir = __DIR__ . '/../../uploads/member_photos/';
        if (!is_dir($upload_dir)) @mkdir($upload_dir, 0755, true);

        $ext = strtolower(pathinfo($_FILES['product_photo']['name'], PATHINFO_EXTENSION));
        $filename = 'product_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
        if (move_uploaded_file($tmp, $upload_dir . $filename)) {
            $photo_path = 'uploads/member_photos/' . $filename;
        }
    }

    $photo_b64_esc = mysqli_real_escape_string($con, $photo_b64);
    $q_add = "INSERT INTO nutrition_products (product_name, category, description, benefits, price, discount_price, photo_url, photo_base64, stock_status) 
              VALUES ('$name', '$category', '$description', '$benefits', $price, $discount_price, '$photo_path', '$photo_b64_esc', '$stock_status')";

    if (mysqli_query($con, $q_add)) {
        $msg = "<div style='background: rgba(16, 185, 129, 0.2); border: 1px solid #10b981; color: #10b981; padding: 14px; border-radius: 12px; margin-bottom: 20px; font-weight: bold;'>✅ Product '$name' added successfully!</div>";
    } else {
        $msg = "<div style='background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #ef4444; padding: 14px; border-radius: 12px; margin-bottom: 20px; font-weight: bold;'>Error adding product: " . mysqli_error($con) . "</div>";
    }
}

// Handle Delete Product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_product') {
    $pid = intval($_POST['product_id']);
    mysqli_query($con, "DELETE FROM nutrition_products WHERE id = $pid");
    $msg = "<div style='background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #ef4444; padding: 14px; border-radius: 12px; margin-bottom: 20px; font-weight: bold;'>🗑️ Product deleted successfully!</div>";
}

// Fetch All Products
$q_all_products = mysqli_query($con, "SELECT * FROM nutrition_products ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Catalog Manager | Sudarshan Nutrition</title>
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
        
        .header-box {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }
        .page-title { font-family: 'Orbitron', sans-serif; font-size: 22px; font-weight: 800; color: #fff; }

        .form-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 35px;
        }
        .form-row { margin-bottom: 18px; }
        .form-row label { display: block; font-size: 13px; font-weight: 700; color: #cbd5e1; margin-bottom: 6px; text-transform: uppercase; }
        .form-input {
            width: 100%;
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid var(--border-color);
            color: #fff;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 14px;
        }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; }

        .btn-submit {
            background: linear-gradient(135deg, #f97316, #ea580c);
            color: #fff;
            border: none;
            padding: 14px 28px;
            border-radius: 12px;
            font-weight: 800;
            font-size: 14px;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(249, 115, 22, 0.4);
        }

        .products-table-box {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 25px;
        }
        .table-custom { width: 100%; border-collapse: collapse; font-size: 13px; }
        .table-custom th { text-align: left; padding: 12px; color: #94a3b8; font-weight: 700; text-transform: uppercase; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .table-custom td { padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.05); color: #cbd5e1; }
    </style>
</head>
<body>

    <?php include 'nav.php'; ?>

    <div class="container">
        <div class="header-box">
            <div class="page-title">📦 Manage Product Catalog</div>
        </div>

        <?php echo $msg; ?>

        <!-- Add New Product Form -->
        <div class="form-card">
            <h3 style="color: #fff; margin-bottom: 20px; font-size: 16px; font-weight: 800; text-transform: uppercase;">➕ Add New Nutrition Product</h3>
            
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_product">

                <div class="form-grid">
                    <div class="form-row">
                        <label>Product Name *</label>
                        <input type="text" name="product_name" class="form-input" placeholder="e.g. Ultra Thermo Fat Burner X" required>
                    </div>

                    <div class="form-row">
                        <label>Category *</label>
                        <select name="category" class="form-input" required>
                            <option value="Fat Loss">⚡ Fat Loss</option>
                            <option value="Muscle Gain">💪 Muscle Gain</option>
                            <option value="Lean Muscle">🏋️ Lean Muscle</option>
                            <option value="Recovery">🔋 Recovery &amp; Wellness</option>
                        </select>
                    </div>

                    <div class="form-row">
                        <label>Standard Price (₹) *</label>
                        <input type="number" name="price" class="form-input" placeholder="e.g. 2499" required>
                    </div>

                    <div class="form-row">
                        <label>Member Special Offer Price (₹)</label>
                        <input type="number" name="discount_price" class="form-input" placeholder="e.g. 1999">
                    </div>
                </div>

                <div class="form-row">
                    <label>Description &amp; Purpose *</label>
                    <textarea name="description" class="form-input" rows="3" placeholder="Explain how this product helps members achieve fat loss, muscle gain, or lean fitness..." required></textarea>
                </div>

                <div class="form-row">
                    <label>Key Benefits Highlights</label>
                    <input type="text" name="benefits" class="form-input" placeholder="e.g. 27g Protein | Zero Sugar | Fast Fat Burning">
                </div>

                <div class="form-grid">
                    <div class="form-row">
                        <label>Product Photo</label>
                        <input type="file" name="product_photo" class="form-input" accept="image/*">
                    </div>

                    <div class="form-row">
                        <label>Stock Status</label>
                        <select name="stock_status" class="form-input">
                            <option value="in_stock" selected>🟢 In Stock</option>
                            <option value="out_of_stock">🔴 Out of Stock</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn-submit" style="margin-top: 10px;">💾 Save &amp; Publish Product</button>
            </form>
        </div>

        <!-- Existing Products Table -->
        <div class="products-table-box">
            <h3 style="color: #fff; margin-bottom: 20px; font-size: 16px; font-weight: 800; text-transform: uppercase;">📋 All Catalog Products</h3>

            <?php if ($q_all_products && mysqli_num_rows($q_all_products) > 0): ?>
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Photo</th>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Special Offer</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($q_all_products)): ?>
                            <?php $img_url = get_member_photo_url($row, '../../'); ?>
                            <tr>
                                <td>
                                    <img src="<?php echo $img_url; ?>" style="width: 48px; height: 48px; border-radius: 8px; object-fit: cover; border: 1px solid var(--accent-orange);" alt="Photo">
                                </td>
                                <td><strong><?php echo htmlspecialchars($row['product_name']); ?></strong></td>
                                <td><span style="background: rgba(249, 115, 22, 0.2); border: 1px solid #f97316; color: #f97316; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: bold;"><?php echo htmlspecialchars($row['category']); ?></span></td>
                                <td>₹<?php echo number_format($row['price']); ?></td>
                                <td style="color: #10b981; font-weight: bold;"><?php echo $row['discount_price'] > 0 ? '₹' . number_format($row['discount_price']) : '-'; ?></td>
                                <td><span style="font-weight: bold; font-size: 11px;"><?php echo str_replace('_', ' ', strtoupper($row['stock_status'])); ?></span></td>
                                <td>
                                    <form method="POST" action="" onsubmit="return confirm('Delete this product?');" style="display:inline;">
                                        <input type="hidden" name="action" value="delete_product">
                                        <input type="hidden" name="product_id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" style="background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #ef4444; padding: 6px 12px; border-radius: 8px; font-weight: bold; cursor: pointer;">🗑️ Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="text-align: center; color: #94a3b8; padding: 20px; font-size: 13px;">No products added yet.</div>
            <?php endif; ?>
        </div>

    </div>

</body>
</html>
