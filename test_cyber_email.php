<?php
require 'Files/include/db_conn.php';
require 'Files/include/smtp_mailer.php';

$con = mysqli_connect($host, $username, $password, $db_name);

// Call send_payment_email for a test email
send_payment_email($con, 'anuragbawaskar680@gmail.com', 'Anurag Bawaskar (Test)', 'M-TEST01', 'PRO Cyber Plan', 1500, '2027-01-01', 'UPI', 'Admin', '1234', 500, 1000);

echo "Test email dispatched to anuragbawaskar680@gmail.com\n";
?>
