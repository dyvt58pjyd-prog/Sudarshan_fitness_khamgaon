<?php
require '../../include/db_conn.php';
page_protect();

if ($_SESSION['role'] !== 'super_admin' && $_SESSION['role'] !== 'owner') {
    die("Access Denied");
}

if (!isset($_GET['id'])) {
    header("Location: payment_requests.php");
    exit();
}

$req_id = intval($_GET['id']);
mysqli_query($con, "UPDATE payment_requests SET status = 'rejected' WHERE id = $req_id AND status = 'pending'");

echo "<script>alert('Payment request has been rejected.'); window.location.href='payment_requests.php';</script>";
?>
