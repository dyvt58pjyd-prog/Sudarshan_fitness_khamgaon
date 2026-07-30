<?php
require_once __DIR__ . '/include/db_conn.php';

// Clear partner_uid for members with dummy mobile numbers or non-couple plans
$q1 = mysqli_query($con, "UPDATE users SET partner_uid = NULL WHERE mobile IN ('0000000000', '1234567890', '9876543210', '0') OR mobile IS NULL OR mobile = ''");

// Clear partner_uid for users whose enrolled plan is NOT a couple plan
$q2 = mysqli_query($con, "
    UPDATE users u
    JOIN enrolls_to e ON u.userid = e.uid
    JOIN plan p ON e.pid = p.pid
    SET u.partner_uid = NULL
    WHERE LOWER(p.planName) NOT LIKE '%couple%' 
      AND LOWER(p.pid) NOT LIKE '%couple%'
      AND u.partner_uid IS NOT NULL
");

echo "Cleaned dummy couple links successfully.";
?>
