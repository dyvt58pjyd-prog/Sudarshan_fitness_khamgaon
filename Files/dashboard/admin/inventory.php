<?php
require '../../include/db_conn.php';
page_protect();
$gym = get_gym_details($con);

// Handle adding new product
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_product') {
    $product_name = mysqli_real_escape_string($con, $_POST['product_name']);
    $category = mysqli_real_escape_string($con, $_POST['category']);
    $price = intval($_POST['price']);
    $stock = intval($_POST['stock_quantity']);
    
    $q = "INSERT INTO inventory_items (product_name, category, price, stock_quantity) VALUES ('$product_name', '$category', '$price', '$stock')";
    if (mysqli_query($con, $q)) {
        echo "<head><script>alert('Product Added Successfully!');</script></head></html>";
        echo "<meta http-equiv='refresh' content='0; url=inventory.php'>";
    } else {
        echo "<head><script>alert('Error: " . mysqli_error($con) . "');</script></head></html>";
    }
}

// Handle updating stock/price
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_product') {
    $id = intval($_POST['id']);
    $price = intval($_POST['price']);
    $stock = intval($_POST['stock_quantity']);
    
    $q = "UPDATE inventory_items SET price = '$price', stock_quantity = stock_quantity + '$stock' WHERE id = '$id'";
    if (mysqli_query($con, $q)) {
        echo "<head><script>alert('Product Updated Successfully!');</script></head></html>";
        echo "<meta http-equiv='refresh' content='0; url=inventory.php'>";
    } else {
        echo "<head><script>alert('Error: " . mysqli_error($con) . "');</script></head></html>";
    }
}

// Handle deletion
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    mysqli_query($con, "DELETE FROM inventory_items WHERE id = '$id'");
    echo "<head><script>alert('Product Deleted!');</script></head></html>";
    echo "<meta http-equiv='refresh' content='0; url=inventory.php'>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>SUDARSHAN FITNESS | Inventory Store</title>
    <link rel="stylesheet" href="../../css/style.css"  id="style-resource-5">
    <script type="text/javascript" src="../../js/Script.js"></script>
    <link rel="stylesheet" href="../../css/dashMain.css">
    <link rel="stylesheet" type="text/css" href="../../css/entypo.css">
    <link href="a1style.css" rel="stylesheet" type="text/css">
    <style>
        .page-container .sidebar-menu #main-menu li#inventory > a {
            background-color: #2b303a;
            color: #ffffff;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 450px;
            border-radius: 8px;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .stock-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 12px;
        }
        .stock-high { background-color: #d1fae5; color: #065f46; }
        .stock-low { background-color: #fef3c7; color: #92400e; }
        .stock-out { background-color: #fee2e2; color: #991b1b; }
    </style>
</head>
<body class="page-body page-fade" onload="collapseSidebar()">
    <div class="page-container sidebar-collapsed" id="navbarcollapse">	
        <div class="sidebar-menu">
            <header class="logo-env">
                <div class="logo">
                    <a href="main.php">
                        <img src="<?php echo htmlspecialchars($gym['gym_logo']); ?>" alt="" style="max-height: 80px; max-width: 192px;" />
                    </a>
                </div>
                <div class="sidebar-collapse" onclick="collapseSidebar()">
                    <a href="#" class="sidebar-collapse-icon with-animation">
                        <i class="entypo-menu"></i>
                    </a>
                </div>
            </header>
            <?php include('nav.php'); ?>
        </div>

        <div class="main-content">
            <div class="row">
                <div class="col-md-6 col-sm-8 clearfix"></div>
                <div class="col-md-6 col-sm-4 clearfix hidden-xs">
                    <ul class="list-inline links-list pull-right">
                        <li>Welcome <?php echo $_SESSION['full_name']; ?> </li>						
                        <li><a href="logout.php">Log Out <i class="entypo-logout right"></i></a></li>
                    </ul>
                </div>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h2>Inventory Stock & Store</h2>
                <div>
                    <button class="a1-btn a1-green" onclick="document.getElementById('sellModal').style.display='block'">+ Sell Item</button>
                    <button class="a1-btn a1-blue" onclick="document.getElementById('addModal').style.display='block'">+ Add New Product</button>
                </div>
            </div>
            <hr />

            <div style="overflow-x:auto;">
                <table class="table table-bordered datatable" id="table-1" border=1>
                    <thead>
                        <tr>
                            <th>S.No</th>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>Selling Price</th>
                            <th>Stock Left</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            $query = "SELECT * FROM inventory_items ORDER BY category ASC, product_name ASC";
                            $result = mysqli_query($con, $query);
                            $sno = 1;

                            if (mysqli_num_rows($result) > 0) {
                                while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
                                    $stock = intval($row['stock_quantity']);
                                    $stock_class = 'stock-high';
                                    if ($stock == 0) $stock_class = 'stock-out';
                                    elseif ($stock < 5) $stock_class = 'stock-low';
                                    
                                    echo "<tr>";
                                    echo "<td>" . $sno . "</td>";
                                    echo "<td><strong>" . htmlspecialchars($row['product_name']) . "</strong></td>";
                                    echo "<td>" . htmlspecialchars($row['category']) . "</td>";
                                    echo "<td>₹" . htmlspecialchars($row['price']) . "</td>";
                                    echo "<td><span class='stock-badge $stock_class'>" . $stock . "</span></td>";
                                    echo "<td>
                                            <button class='a1-btn a1-blue' style='padding:4px 8px;' onclick=\"openUpdateModal('".$row['id']."', '".addslashes($row['product_name'])."', '".$row['price']."')\">Update</button>
                                            <a href='?delete=".$row['id']."' class='a1-btn a1-red' style='padding:4px 8px;' onclick=\"return confirm('Delete this product?')\">Delete</a>
                                          </td>";
                                    echo "</tr>";
                                    $sno++;
                                }
                            } else {
                                echo "<tr><td colspan='6' class='text-center'>No products in inventory!</td></tr>";
                            }
                        ?>
                    </tbody>
                </table>
            </div>

            <!-- Add Product Modal -->
            <div id="addModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="document.getElementById('addModal').style.display='none'">&times;</span>
                    <h4>Add New Product</h4>
                    <hr>
                    <form action="inventory.php" method="POST">
                        <input type="hidden" name="action" value="add_product">
                        <label>Product Name</label>
                        <input type="text" name="product_name" class="form-control" required style="margin-bottom:15px;">
                        
                        <label>Category</label>
                        <select name="category" class="form-control" required style="margin-bottom:15px;">
                            <option value="Supplement (Whey/Mass)">Supplement (Whey/Mass)</option>
                            <option value="Pre-Workout/BCAA">Pre-Workout/BCAA</option>
                            <option value="Accessories (Shaker/Belt)">Accessories (Shaker/Belt)</option>
                            <option value="Apparel (T-Shirt/Tracks)">Apparel (T-Shirt/Tracks)</option>
                            <option value="Snacks/Energy Bars">Snacks/Energy Bars</option>
                            <option value="Other">Other</option>
                        </select>
                        
                        <label>Selling Price (₹)</label>
                        <input type="number" name="price" class="form-control" required style="margin-bottom:15px;">
                        
                        <label>Initial Stock Quantity</label>
                        <input type="number" name="stock_quantity" class="form-control" value="0" required style="margin-bottom:15px;">
                        
                        <button type="submit" class="a1-btn a1-blue" style="width:100%;">Save Product</button>
                    </form>
                </div>
            </div>

            <!-- Update Stock Modal -->
            <div id="updateModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="document.getElementById('updateModal').style.display='none'">&times;</span>
                    <h4>Update Product & Stock</h4>
                    <hr>
                    <form action="inventory.php" method="POST">
                        <input type="hidden" name="action" value="update_product">
                        <input type="hidden" name="id" id="upd_id">
                        
                        <p><strong>Product:</strong> <span id="upd_name"></span></p>
                        
                        <label>Update Selling Price (₹)</label>
                        <input type="number" name="price" id="upd_price" class="form-control" required style="margin-bottom:15px;">
                        
                        <label>Add Stock Quantity (Type 0 to just update price)</label>
                        <input type="number" name="stock_quantity" value="0" class="form-control" required style="margin-bottom:15px;">
                        
                        <button type="submit" class="a1-btn a1-blue" style="width:100%;">Update Product</button>
                    </form>
                </div>
            </div>

            <!-- Sell Modal -->
            <div id="sellModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="document.getElementById('sellModal').style.display='none'">&times;</span>
                    <h4>Sell Product (POS)</h4>
                    <hr>
                    <form action="inventory_sell.php" method="POST">
                        <label>Select Product</label>
                        <select name="product_id" id="sell_product" class="form-control" required style="margin-bottom:15px;" onchange="updateSellPrice()">
                            <option value="">-- Choose Product --</option>
                            <?php
                                $q = mysqli_query($con, "SELECT * FROM inventory_items WHERE stock_quantity > 0");
                                while($r = mysqli_fetch_assoc($q)){
                                    echo "<option value='".$r['id']."' data-price='".$r['price']."'>".$r['product_name']." (₹".$r['price']." - Stock: ".$r['stock_quantity'].")</option>";
                                }
                            ?>
                        </select>
                        
                        <label>Quantity</label>
                        <input type="number" name="quantity" id="sell_qty" class="form-control" value="1" min="1" required style="margin-bottom:15px;" oninput="updateSellPrice()">
                        
                        <label>Total Price (₹)</label>
                        <input type="number" name="total_price" id="sell_total" class="form-control" readonly style="margin-bottom:15px;">
                        
                        <label>Member ID (Optional)</label>
                        <input type="text" name="member_id" class="form-control" placeholder="Enter Member ID if applicable" style="margin-bottom:15px;">
                        
                        <label>Payment Mode</label>
                        <select name="payment_mode" class="form-control" required style="margin-bottom:15px;">
                            <option value="Cash">Cash</option>
                            <option value="UPI">UPI</option>
                        </select>
                        
                        <button type="submit" class="a1-btn a1-green" style="width:100%;">Complete Sale</button>
                    </form>
                </div>
            </div>

            <script>
                function openUpdateModal(id, name, price) {
                    document.getElementById('upd_id').value = id;
                    document.getElementById('upd_name').innerText = name;
                    document.getElementById('upd_price').value = price;
                    document.getElementById('updateModal').style.display = 'block';
                }
                
                function updateSellPrice() {
                    let select = document.getElementById('sell_product');
                    if (select.selectedIndex <= 0) return;
                    let price = select.options[select.selectedIndex].getAttribute('data-price');
                    let qty = document.getElementById('sell_qty').value;
                    document.getElementById('sell_total').value = price * qty;
                }
            </script>

            <?php include('footer.php'); ?>
        </div>
    </body>
</html>
