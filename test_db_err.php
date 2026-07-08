<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'Files/include/db_conn.php';
$json_payload = '{}';
$ins_req = "INSERT INTO payment_requests (uid, pid, amount, screenshot, status, utr, registration_payload, is_new_registration) VALUES ('PENDING', 'POQKJC', 1000, 'test.jpg', 'pending', '123', '$json_payload', 1)";
if(!mysqli_query($con, $ins_req)) {
    echo "ERROR: " . mysqli_error($con);
} else {
    echo "SUCCESS";
}
?>
