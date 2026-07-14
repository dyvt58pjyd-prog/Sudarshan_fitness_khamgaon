<?php
require 'include/db_conn.php';
$query = "UPDATE enrolls_to SET paid_date = '2026-07-13' WHERE uid = '165' AND paid_date >= '2026-07-14'";
if(mysqli_query($con, $query)) {
    $rows = mysqli_affected_rows($con);
    echo "SUCCESS: Updated $rows record(s) for TEJAL RATHOD (ID: 165) back to July 13th.";
} else {
    echo "Error updating: " . mysqli_error($con);
}
?>
