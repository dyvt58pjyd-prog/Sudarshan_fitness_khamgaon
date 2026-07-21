<?php
require '../../include/db_conn.php';
page_protect();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['product_id'])) {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);
    $total_price = intval($_POST['total_price']);
    $member_id = mysqli_real_escape_string($con, $_POST['member_id']);
    $payment_mode = mysqli_real_escape_string($con, $_POST['payment_mode']);
    $received_by = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'System';
    $sale_date = date('Y-m-d');
    
    // Check stock
    $q_stock = mysqli_query($con, "SELECT stock_quantity, product_name FROM inventory_items WHERE id = '$product_id'");
    if ($q_stock && mysqli_num_rows($q_stock) > 0) {
        $row = mysqli_fetch_assoc($q_stock);
        if (intval($row['stock_quantity']) >= $quantity) {
            // Insert sale
            $q_insert = "INSERT INTO inventory_sales (product_id, member_id, quantity, total_price, payment_mode, sale_date, received_by) 
                         VALUES ('$product_id', '$member_id', '$quantity', '$total_price', '$payment_mode', '$sale_date', '$received_by')";
            if (mysqli_query($con, $q_insert)) {
                // Deduct stock
                mysqli_query($con, "UPDATE inventory_items SET stock_quantity = stock_quantity - $quantity WHERE id = '$product_id'");
                
                echo "<head><script>alert('Successfully Sold: " . $quantity . "x " . addslashes($row['product_name']) . "');</script></head></html>";
                echo "<meta http-equiv='refresh' content='0; url=inventory.php'>";
            } else {
                echo "<head><script>alert('Database Error: " . mysqli_error($con) . "');</script></head></html>";
                echo "<meta http-equiv='refresh' content='0; url=inventory.php'>";
            }
        } else {
            echo "<head><script>alert('Not enough stock available!');</script></head></html>";
            echo "<meta http-equiv='refresh' content='0; url=inventory.php'>";
        }
    } else {
        echo "<head><script>alert('Product not found!');</script></head></html>";
        echo "<meta http-equiv='refresh' content='0; url=inventory.php'>";
    }
} else {
    header("Location: inventory.php");
}
?>
