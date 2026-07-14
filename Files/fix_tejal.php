<?php
require 'include/db_conn.php';
$query = "UPDATE enrolls_to SET paid_date = '2026-07-13' WHERE uid = '165' AND paid_date = '2026-07-14'";
if(mysqli_query($con, $query)) {
    echo "Successfully updated TEJAL RATHOD (ID: 165) transaction date to yesterday (July 13).";
} else {
    echo "Error updating: " . mysqli_error($con);
}
?>
