<?php
require './Files/include/db_conn.php';
$res = mysqli_query($con, "DESCRIBE users");
while($row = mysqli_fetch_array($res)) {
    echo $row[0] . "\n";
}
