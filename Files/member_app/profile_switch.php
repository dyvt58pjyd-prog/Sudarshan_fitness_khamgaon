<?php
session_start();
require '../include/db_conn.php';

if (isset($_POST['switch_uid'])) {
    $switch_uid = mysqli_real_escape_string($con, $_POST['switch_uid']);
    $switch_name = mysqli_real_escape_string($con, $_POST['switch_name']);
    
    $_SESSION['member_uid'] = $switch_uid;
    $_SESSION['member_name'] = $switch_name;
}

header("Location: dashboard.php");
exit();
?>
