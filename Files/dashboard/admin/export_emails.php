<?php
require '../../include/db_conn.php';
page_protect();

if ($_SESSION['role'] !== 'super_admin') {
    echo "Access Denied. Only App Developers can export emails.";
    exit();
}

$filename = "Exported_Emails_" . date('Y-m-d') . ".csv";

// Output headers to trigger a file download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

// Create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

// Output the column headings
fputcsv($output, array('User ID', 'Full Name', 'Email', 'Mobile', 'Joining Date'));

// Fetch the emails from the database
$query = "SELECT userid, username, email, mobile, joining_date FROM users ORDER BY joining_date DESC";
$result = mysqli_query($con, $query);

while ($row = mysqli_fetch_assoc($result)) {
    // We can also skip empty emails if requested, but let's just output everything
    // or specifically those who have emails
    if (!empty($row['email'])) {
        fputcsv($output, $row);
    }
}

fclose($output);
exit();
?>
