<?php
require '../../include/db_conn.php';
page_protect();

if ($_SESSION['role'] !== 'super_admin') {
    echo "Access Denied. Only App Developers can export emails.";
    exit();
}

$filename = "Exported_Emails_" . date('Y-m-d') . ".txt";

// Output headers to trigger a file download
header('Content-Type: text/plain; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

// Fetch the emails from the database
$query = "SELECT email FROM users WHERE email IS NOT NULL AND email != '' ORDER BY joining_date DESC";
$result = mysqli_query($con, $query);

$emails = [];
while ($row = mysqli_fetch_assoc($result)) {
    // Sanitize and trim the email just in case
    $email = trim($row['email']);
    if (!empty($email) && !in_array($email, $emails)) {
        $emails[] = $email;
    }
}

// Output as a single comma-separated string
echo implode(', ', $emails);

exit();
?>
