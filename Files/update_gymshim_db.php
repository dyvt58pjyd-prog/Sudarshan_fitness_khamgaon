<?php
require 'include/db_conn.php';

$queries = [
    // 1. Add pool_group_id to users for Pool Memberships
    "ALTER TABLE users ADD COLUMN pool_group_id VARCHAR(50) DEFAULT NULL",
    
    // 2. Create pt_attendance table for tracking personal training sessions
    "CREATE TABLE IF NOT EXISTS pt_attendance (
        id INT AUTO_INCREMENT PRIMARY KEY,
        member_id VARCHAR(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
        trainer_id VARCHAR(50),
        session_date DATE,
        time_logged TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (member_id) REFERENCES users(userid) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=latin1;",
    
    // 3. Create visitors table if not exists (for SalesBot / Inquiries)
    "CREATE TABLE IF NOT EXISTS visitors (
        visitor_id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        mobile VARCHAR(20) NOT NULL,
        email VARCHAR(100),
        inquiry_date DATE,
        status VARCHAR(20) DEFAULT 'Pending',
        source VARCHAR(50) DEFAULT 'Manual'
    )"
];

foreach ($queries as $query) {
    if (mysqli_query($con, $query)) {
        echo "Success: " . substr($query, 0, 50) . "...\n";
    } else {
        echo "Error or already exists: " . mysqli_error($con) . "\n";
    }
}
?>
