<?php
require 'Files/include/db_conn.php';
$res = mysqli_query($con, "SELECT id, screenshot FROM payment_requests ORDER BY id DESC LIMIT 5");
while ($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
?>
