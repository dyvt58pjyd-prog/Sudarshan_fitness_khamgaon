<?php
session_start();
if (!isset($_SESSION['user_data']) || $_SESSION['role'] !== 'auditor') {
    header("Location: ../../index.php");
    exit();
}
?>
