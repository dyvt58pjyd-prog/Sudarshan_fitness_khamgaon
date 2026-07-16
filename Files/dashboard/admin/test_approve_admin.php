<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
$_SESSION['user_data'] = 'test';
$_SESSION['logged'] = 'yes';
$_SESSION['role'] = 'super_admin';

$_GET['id'] = 12; // PENDING PRE-BOOKING
include 'approve_payment.php';
?>
