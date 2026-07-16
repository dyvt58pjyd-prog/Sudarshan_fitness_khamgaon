<?php
require './Files/include/db_conn.php';

// 1. Create workout_logs table
$sql_logs = "CREATE TABLE IF NOT EXISTS workout_logs (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    uid VARCHAR(255) NOT NULL,
    muscle_group VARCHAR(100) NOT NULL,
    intensity INT(11) DEFAULT 5,
    log_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY(uid)
)";
if (mysqli_query($con, $sql_logs)) {
    echo "Table 'workout_logs' created successfully.\n";
} else {
    echo "Error creating table: " . mysqli_error($con) . "\n";
}

// 2. Add gamification columns to users table
$sql_xp = "ALTER TABLE users ADD COLUMN xp_points INT(11) DEFAULT 0";
if (mysqli_query($con, $sql_xp)) {
    echo "Column 'xp_points' added.\n";
} else {
    echo "Column 'xp_points' might already exist: " . mysqli_error($con) . "\n";
}

$sql_rank = "ALTER TABLE users ADD COLUMN gym_rank VARCHAR(100) DEFAULT 'Beginner'";
if (mysqli_query($con, $sql_rank)) {
    echo "Column 'gym_rank' added.\n";
} else {
    echo "Column 'gym_rank' might already exist: " . mysqli_error($con) . "\n";
}
?>
