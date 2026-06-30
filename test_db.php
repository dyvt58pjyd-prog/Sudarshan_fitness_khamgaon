<?php
require 'Files/include/db_conn.php';
$r = mysqli_query($con, "SHOW TABLES LIKE 'gym_settings'");
var_dump(mysqli_num_rows($r));

$r2 = mysqli_query($con, "SHOW TABLES");
while ($row = mysqli_fetch_row($r2)) {
    echo $row[0] . "\n";
}
?>
