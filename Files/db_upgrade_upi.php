<?php
require 'include/db_conn.php';

echo "<h3>Upgrading Database for Dynamic UPI Gateway...</h3>";

// 1. Add gym_upi to gym_settings
$chk = mysqli_query($con, "SHOW COLUMNS FROM gym_settings LIKE 'gym_upi'");
if ($chk && mysqli_num_rows($chk) === 0) {
    $q = "ALTER TABLE gym_settings ADD COLUMN gym_upi VARCHAR(255) DEFAULT NULL";
    if (mysqli_query($con, $q)) {
        echo "Added column: gym_upi to gym_settings<br>";
    } else {
        echo "Failed to add gym_upi: " . mysqli_error($con) . "<br>";
    }
} else {
    echo "Column gym_upi already exists.<br>";
}

// Seed the default UPI so it's not totally empty (they should change it)
mysqli_query($con, "UPDATE gym_settings SET gym_upi = 'sudarshanfitness@ybl' WHERE id = 1 AND (gym_upi IS NULL OR gym_upi = '')");

// 2. Add 'status' to payment_requests if it doesn't exist. Wait, earlier I saw it inserts 'approved', so it probably already exists.
$chk2 = mysqli_query($con, "SHOW COLUMNS FROM payment_requests LIKE 'status'");
if ($chk2 && mysqli_num_rows($chk2) === 0) {
    $q2 = "ALTER TABLE payment_requests ADD COLUMN status VARCHAR(50) DEFAULT 'pending'";
    if (mysqli_query($con, $q2)) {
        echo "Added column: status to payment_requests<br>";
    } else {
        echo "Failed to add status to payment_requests: " . mysqli_error($con) . "<br>";
    }
} else {
    echo "Column status in payment_requests already exists.<br>";
}

echo "<h3>Upgrade Complete! You can now use the Dynamic UPI Gateway.</h3>";
?>
