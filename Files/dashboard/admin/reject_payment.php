<?php
require '../../include/db_conn.php';
page_protect();

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['super_admin', 'owner', 'reception'])) {
    die("Access Denied");
}

if (!isset($_GET['id'])) {
    header("Location: payment_requests.php");
    exit();
}

$req_id = intval($_GET['id']);
mysqli_query($con, "UPDATE payment_requests SET status = 'rejected' WHERE id = $req_id AND status = 'pending'");

$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'payment_requests.php';
echo "<script>alert('Payment request has been rejected.'); window.location.href='" . htmlspecialchars($referer, ENT_QUOTES) . "';</script>";
?>
