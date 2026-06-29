<?php
require 'include/db_conn.php';

echo "<h1>Setup Deferred Registration</h1>";

// 1. Add registration_payload to payment_requests
$query1 = "ALTER TABLE payment_requests ADD COLUMN registration_payload TEXT NULL";
if (mysqli_query($con, $query1)) {
    echo "<p style='color:green;'>Successfully added 'registration_payload' to payment_requests table.</p>";
} else {
    echo "<p style='color:red;'>Failed to add 'registration_payload': " . mysqli_error($con) . "</p>";
}

// 2. Add is_new_registration to payment_requests
$query2 = "ALTER TABLE payment_requests ADD COLUMN is_new_registration TINYINT(1) DEFAULT 0";
if (mysqli_query($con, $query2)) {
    echo "<p style='color:green;'>Successfully added 'is_new_registration' to payment_requests table.</p>";
} else {
    echo "<p style='color:red;'>Failed to add 'is_new_registration': " . mysqli_error($con) . "</p>";
}

echo "<h3>Setup Complete! You can delete this file.</h3>";
?>
