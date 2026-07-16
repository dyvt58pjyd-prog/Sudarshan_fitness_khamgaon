<?php
include 'include/db_conn.php';

$query = "
CREATE TABLE IF NOT EXISTS `visitors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `mobile` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `photo_path` varchar(255) DEFAULT NULL,
  `visit_date` datetime NOT NULL,
  `status` varchar(50) DEFAULT 'visited',
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";

if (mysqli_query($con, $query)) {
    echo "<h2>Success! The 'visitors' table has been created successfully.</h2>";
    echo "<a href='dashboard/admin/index.php'>Return to Dashboard</a>";
} else {
    echo "<h2>Error creating table: " . mysqli_error($con) . "</h2>";
}
?>
