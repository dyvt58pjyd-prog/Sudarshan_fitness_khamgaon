<?php
require 'Files/include/db_conn.php';
$res = mysqli_query($con, "SHOW CREATE TABLE payment_requests");
$row = mysqli_fetch_assoc($res);
echo $row['Create Table'];
?>
