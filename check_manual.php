<?php
require 'Files/include/db_conn.php';
$q = 'SELECT pr.*, u.username, u.mobile FROM payment_requests pr LEFT JOIN users u ON pr.uid = u.userid WHERE pr.status = \'pending\' AND pr.is_new_registration = 1 ORDER BY pr.id DESC';
$res = mysqli_query($con, $q);
while($row = mysqli_fetch_assoc($res)) print_r($row);
?>
