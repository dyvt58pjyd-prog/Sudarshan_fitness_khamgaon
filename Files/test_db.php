<?php
include 'include/db_conn.php';
$res = mysqli_query($con, "SELECT id, uid, screenshot FROM payment_requests WHERE uid IN ('102', '104')");
if (!$res) { echo mysqli_error($con); exit; }
while($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
