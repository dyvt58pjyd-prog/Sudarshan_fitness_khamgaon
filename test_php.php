<?php
session_start();
$_SESSION['user_data'] = '1529336794';
$_SESSION['logged'] = 'start';
$_SESSION['role'] = 'member';
$_SESSION['full_name'] = 'Test User';
ini_set('display_errors', 1);
error_reporting(E_ALL);
$_SERVER['DOCUMENT_ROOT'] = __DIR__;
chdir('Files/dashboard/admin');

ob_start();
include 'new_entry.php';
$html = ob_get_clean();

if (strpos($html, 'function generateStaffQR()') !== false) {
    echo "generateStaffQR is present in the final HTML.\n";
} else {
    echo "generateStaffQR is MISSING!\n";
}
?>
