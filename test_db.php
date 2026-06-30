<?php
require './Files/include/db_conn.php';
$res = mysqli_query($con, "DESCRIBE workout_logs");
if ($res) {
    echo "exists";
} else {
    echo mysqli_error($con);
}
