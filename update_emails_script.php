<?php
require 'Files/include/db_conn.php';

// 1. Update Gym Settings Email (Support Email)
$support_email = 'cyber.officer@support.sudarshanfitness.de';
$q1 = "UPDATE gym_settings SET gym_email = '$support_email'";
if (mysqli_query($con, $q1)) {
    echo "Gym Contact Email updated successfully to $support_email.\n";
} else {
    echo "Failed to update Gym Email: " . mysqli_error($con) . "\n";
}

// 2. Update SMTP Settings (System Email)
$smtp_host = 'smtp.hostinger.com';
$smtp_port = 465;
$smtp_secure = 'ssl';
$smtp_username = 'admin@sudarshanfitness.de';
$smtp_from_name = 'Sudarshan Fitness System';
$smtp_from_email = 'admin@sudarshanfitness.de';

$q2 = "UPDATE smtp_settings SET 
    smtp_host = '$smtp_host',
    smtp_port = $smtp_port,
    smtp_secure = '$smtp_secure',
    smtp_username = '$smtp_username',
    smtp_from_name = '$smtp_from_name',
    smtp_from_email = '$smtp_from_email'
    WHERE id = 1";

if (mysqli_query($con, $q2)) {
    echo "SMTP Settings updated successfully for $smtp_username.\n";
} else {
    echo "Failed to update SMTP Settings: " . mysqli_error($con) . "\n";
}
?>
