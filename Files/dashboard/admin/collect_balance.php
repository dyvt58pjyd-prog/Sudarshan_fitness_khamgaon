<?php
require '../../include/db_conn.php';
page_protect();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['et_id']) && isset($_POST['collect_amount'])) {
    $et_id = mysqli_real_escape_string($con, $_POST['et_id']);
    $collect_amount = floatval($_POST['collect_amount']);
    $payment_mode = isset($_POST['payment_mode']) ? mysqli_real_escape_string($con, $_POST['payment_mode']) : 'Cash';
    
    // Fetch the current enrollment
    $query = "SELECT e.*, u.username, u.mobile, u.email, p.planName 
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
            
            // Send Email Receipt
            require_once '../../include/smtp_mailer.php';
            $gym_email = $gym['gym_email'];
            $subject = "Payment Receipt: Pending Dues - $gym_name";
            
            $html_msg = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px; }
                    .receipt-box { background-color: #ffffff; padding: 30px; border-radius: 8px; max-width: 500px; margin: 0 auto; border-top: 5px solid #ff6b00; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
                    .header { text-align: center; margin-bottom: 20px; }
                    .header h2 { color: #333; margin: 0; }
                    .details { line-height: 1.6; color: #555; margin-bottom: 20px; }
                    .amount { font-size: 24px; color: #10b981; font-weight: bold; text-align: center; margin: 20px 0; padding: 15px; background: #ecfdf5; border-radius: 8px; }
                    .footer { text-align: center; font-size: 12px; color: #aaa; margin-top: 30px; }
                </style>
            </head>
            <body>
                <div class='receipt-box'>
                    <div class='header'>
                        <h2>$gym_name</h2>
                        <p>Payment Receipt</p>
                    </div>
                    <div class='details'>
                        Dear <strong>$member_name</strong>,<br><br>
                        We have successfully received your pending balance payment for your <strong>{$row['planName']}</strong> subscription.
                    </div>
                    <div class='amount'>
                        Amount Paid: ₹" . number_format($collect_amount) . "
                    </div>
                    <div class='details'>
                        <strong>Payment Mode:</strong> $payment_mode<br>";
            
            if ($new_balance > 0) {
                $html_msg .= "<strong>Remaining Balance:</strong> <span style='color: #ef4444;'>₹" . number_format($new_balance) . "</span>";
            } else {
                $html_msg .= "<strong>Remaining Balance:</strong> <span style='color: #10b981;'>₹0 (Fully Paid)</span>";
            }
            
            $html_msg .= "
                    </div>
                    <div class='footer'>
                        Thank you for your payment!<br>
                        For any queries, please contact us at $gym_email
                    </div>
                </div>
            </body>
            </html>";
            
            send_smtp_email($row['email'], $row['username'], $subject, $html_msg);
            
            
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
