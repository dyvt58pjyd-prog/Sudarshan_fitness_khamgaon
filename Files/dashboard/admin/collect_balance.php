<?php
require '../../include/db_conn.php';
page_protect();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['et_id']) && isset($_POST['collect_amount'])) {
    $et_id = mysqli_real_escape_string($con, $_POST['et_id']);
    $collect_amount = floatval($_POST['collect_amount']);
    $payment_mode = isset($_POST['payment_mode']) ? mysqli_real_escape_string($con, $_POST['payment_mode']) : 'Cash';
    
    // Fetch the current enrollment
    $query = "SELECT e.*, u.username, u.mobile, p.planName 
              FROM enrolls_to e 
              INNER JOIN users u ON u.userid = e.uid
              INNER JOIN plan p ON p.pid = e.pid
              WHERE e.et_id = '$et_id'";
    $result = mysqli_query($con, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $current_balance = floatval($row['balance']);
        $current_paid = floatval($row['paid_amount']);
        $uid = $row['uid'];
        
        // Calculate new values
        $new_balance = $current_balance - $collect_amount;
        if ($new_balance < 0) $new_balance = 0;
        $new_paid = $current_paid + $collect_amount;
        
        $due_date_update = $new_balance == 0 ? ", balance_due_date = NULL" : "";
        
        // Update database
        $update_query = "UPDATE enrolls_to 
                         SET paid_amount = $new_paid, balance = $new_balance $due_date_update 
                         WHERE et_id = '$et_id'";
                         
        if (mysqli_query($con, $update_query)) {
            // Queue WhatsApp Message for Balance Payment Receipt
            $gym = get_gym_details($con);
            $gym_name = $gym['gym_name'];
            $member_name = trim(explode(' ', $row['username'])[0]);
            
            $msg = "✅ *Balance Payment Received*\n\n"
                 . "Hi $member_name, we have received your balance payment for the *{$row['planName']}* at $gym_name.\n\n"
                 . "Amount Paid: *₹" . number_format($collect_amount) . "*\n"
                 . "Payment Mode: *$payment_mode*\n";
                 
            if ($new_balance > 0) {
                $msg .= "Remaining Balance: *₹" . number_format($new_balance) . "*\n";
            } else {
                $msg .= "Remaining Balance: *₹0 (Fully Paid)*\n";
            }
            
            $msg .= "\nThank you!\n-$gym_name";
            
            queue_whatsapp_message($con, $row['mobile'], $msg, null);
            
            echo "<head><script>alert('Balance collected successfully!');</script></head></html>";
            echo "<meta http-equiv='refresh' content='0; url=pending_dues.php'>";
        } else {
            echo "<head><script>alert('Error collecting balance: " . mysqli_error($con) . "');</script></head></html>";
            echo "<meta http-equiv='refresh' content='0; url=pending_dues.php'>";
        }
    } else {
        echo "<head><script>alert('Record not found.');</script></head></html>";
        echo "<meta http-equiv='refresh' content='0; url=pending_dues.php'>";
    }
} else {
    header("Location: pending_dues.php");
}
?>
