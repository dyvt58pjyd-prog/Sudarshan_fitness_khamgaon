<?php
session_start();
$uid = $_GET['uid'] ?? $_SESSION['member_uid'] ?? $_SESSION['user_data'] ?? '';
header("Location: ../member_app/wallet_pass.php" . (!empty($uid) ? "?uid=" . urlencode($uid) : ""));
exit;
