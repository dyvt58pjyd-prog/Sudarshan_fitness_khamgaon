<?php
require 'include/db_conn.php';

echo "<h3>Upgrading Database for Mass Marketing Campaign Manager...</h3>";

$q1 = "CREATE TABLE IF NOT EXISTS festival_campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_name VARCHAR(100) NOT NULL,
    scheduled_date DATE NOT NULL,
    message_text TEXT NOT NULL,
    status VARCHAR(20) DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if (mysqli_query($con, $q1)) {
    echo "Checked/Created table: festival_campaigns<br>";
} else {
    echo "Error creating festival_campaigns: " . mysqli_error($con) . "<br>";
}

echo "<h3>Database Upgrade Complete!</h3>";
?>
