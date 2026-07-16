<?php
require 'Files/include/db_conn.php';
$r = mysqli_query($con, 'SELECT id, uid, status, is_new_registration FROM payment_requests ORDER BY id DESC LIMIT 5');
while($row = mysqli_fetch_assoc($r)) print_r($row);
?>
