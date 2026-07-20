<?php
require 'include/db_conn.php';

$sql1 = "ALTER TABLE visitors ADD COLUMN address VARCHAR(255) DEFAULT NULL";
$sql2 = "ALTER TABLE visitors ADD COLUMN photo_url VARCHAR(255) DEFAULT NULL";

mysqli_query($con, $sql1);
mysqli_query($con, $sql2);

echo "Columns added successfully";
?>
