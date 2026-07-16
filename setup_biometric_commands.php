<?php
require 'Files/include/db_conn.php';

$sql = "CREATE TABLE IF NOT EXISTS biometric_commands (
    id INT AUTO_INCREMENT PRIMARY KEY,
    command_type VARCHAR(50) NOT NULL COMMENT 'e.g., DELETE_USER, UPDATE_USERINFO, CLEAR_FINGERPRINT',
    target_uid VARCHAR(50) NOT NULL COMMENT 'The member ID (biometric_id)',
    payload JSON NULL COMMENT 'Any extra data needed for the command',
    status ENUM('pending', 'sent', 'completed', 'failed') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_status (status),
    KEY idx_target_uid (target_uid)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if (mysqli_query($con, $sql)) {
    echo "<h2 style='color:green;'>Success: `biometric_commands` table created successfully!</h2>";
} else {
    echo "<h2 style='color:red;'>Error creating table: " . mysqli_error($con) . "</h2>";
}
?>
