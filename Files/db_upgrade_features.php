<?php
require 'include/db_conn.php';

echo "<h3>Upgrading Database for Automated Reminders and Routines...</h3>";

// 1. reminders_log
$q1 = "CREATE TABLE IF NOT EXISTS reminders_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    uid VARCHAR(50) NOT NULL,
    reminder_type VARCHAR(20) NOT NULL,
    sent_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_reminder (uid, reminder_type, sent_date)
)";
if (mysqli_query($con, $q1)) {
    echo "Checked/Created table: reminders_log<br>";
} else {
    echo "Error creating reminders_log: " . mysqli_error($con) . "<br>";
}

// 2. member_routines
$q2 = "CREATE TABLE IF NOT EXISTS member_routines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    uid VARCHAR(50) NOT NULL UNIQUE,
    trainer_id VARCHAR(50) NOT NULL,
    workout_plan TEXT,
    diet_plan TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
if (mysqli_query($con, $q2)) {
    echo "Checked/Created table: member_routines<br>";
} else {
    echo "Error creating member_routines: " . mysqli_error($con) . "<br>";
}

echo "<h3>Database Upgrade Complete!</h3>";
?>
