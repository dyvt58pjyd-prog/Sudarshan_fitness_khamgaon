<?php
require 'include/db_conn.php';

echo "<h3>Upgrading Database for Member Photo Capture...</h3>";

$chk = mysqli_query($con, "SHOW COLUMNS FROM users LIKE 'photo'");
if ($chk && mysqli_num_rows($chk) === 0) {
    $q = "ALTER TABLE users ADD COLUMN photo VARCHAR(255) DEFAULT NULL";
    if (mysqli_query($con, $q)) {
        echo "Added column: photo to users table<br>";
    } else {
        echo "Failed to add photo column: " . mysqli_error($con) . "<br>";
    }
} else {
    echo "Column 'photo' already exists in the users table.<br>";
}

echo "<h3>Upgrade Complete! The system can now store member profile pictures!</h3>";
?>
