<?php
session_start();
require '../../include/db_conn.php';

if (isset($_POST['switch_uid'])) {
    $switch_uid = mysqli_real_escape_string($con, $_POST['switch_uid']);
    $switch_name = mysqli_real_escape_string($con, $_POST['switch_name']);
    
    $_SESSION['user_data'] = $switch_uid;
    $_SESSION['full_name'] = $switch_name;
    $_SESSION['username'] = $switch_uid;
}

header("Location: index.php");
exit();
?>
