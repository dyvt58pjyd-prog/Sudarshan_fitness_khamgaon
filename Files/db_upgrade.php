<?php
require 'Files/include/db_conn.php';

$columns_to_add = [
    'smtp_user_payments' => 'VARCHAR(255) DEFAULT NULL',
    'smtp_pass_payments' => 'VARCHAR(255) DEFAULT NULL',
    'smtp_name_payments' => 'VARCHAR(255) DEFAULT NULL',
    'smtp_user_recovery' => 'VARCHAR(255) DEFAULT NULL',
    'smtp_pass_recovery' => 'VARCHAR(255) DEFAULT NULL',
    'smtp_name_recovery' => 'VARCHAR(255) DEFAULT NULL',
    'smtp_user_cyber'    => 'VARCHAR(255) DEFAULT NULL',
    'smtp_pass_cyber'    => 'VARCHAR(255) DEFAULT NULL',
    'smtp_name_cyber'    => 'VARCHAR(255) DEFAULT NULL'
];

echo "<h3>Upgrading Database...</h3>";

foreach ($columns_to_add as $col_name => $col_def) {
    // Check if column exists
    $chk = mysqli_query($con, "SHOW COLUMNS FROM smtp_settings LIKE '$col_name'");
    if ($chk && mysqli_num_rows($chk) === 0) {
        $q = "ALTER TABLE smtp_settings ADD COLUMN $col_name $col_def";
        if (mysqli_query($con, $q)) {
            echo "Added column: $col_name<br>";
        } else {
            echo "Failed to add $col_name: " . mysqli_error($con) . "<br>";
        }
    } else {
        echo "Column $col_name already exists.<br>";
    }
}

// Automatically seed the new columns with the emails the user requested, leaving passwords blank so they can fill them in the UI!
$seed_query = "UPDATE smtp_settings SET 
    smtp_user_payments = 'payments@sudarshanfitness.de',
    smtp_name_payments = 'Sudarshan Fitness Billing',
    smtp_user_recovery = 'recovery@support.sudarshanfitness.de',
    smtp_name_recovery = 'Sudarshan Fitness Security',
    smtp_user_cyber = 'cyber.officer@support.sudarshanfitness.de',
    smtp_name_cyber = 'Sudarshan Fitness Cyber Defense'
    WHERE id = 1";

if (mysqli_query($con, $seed_query)) {
    echo "Successfully seeded email configurations!<br>";
} else {
    echo "Failed to seed emails: " . mysqli_error($con) . "<br>";
}

echo "<h3>Upgrade Complete! You can now delete this script.</h3>";
?>
