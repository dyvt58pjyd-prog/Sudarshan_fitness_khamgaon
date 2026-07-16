<?php
include 'include/db_conn.php';

$q = "INSERT INTO admin (username, pass_key, securekey, Full_name, mobile, role) VALUES ('auditor', 'auditor123', 'auditor123', 'Financial Auditor', '9999999999', 'auditor') ON DUPLICATE KEY UPDATE role='auditor'";

if(mysqli_query($con, $q)) {
    echo "<h1>Auditor Account Created Successfully!</h1>";
    echo "<p>You can now go back to the login page and log in.</p>";
    echo "<a href='index.php'>Go to Login</a>";
} else {
    echo "<h1>Error creating account:</h1>";
    echo mysqli_error($con);
}
?>
