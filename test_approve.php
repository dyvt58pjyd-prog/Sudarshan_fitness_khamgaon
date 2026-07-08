<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'Files/include/db_conn.php';
$_GET['id'] = 13; // PENDING request
include 'Files/dashboard/admin/approve_payment.php';
?>
