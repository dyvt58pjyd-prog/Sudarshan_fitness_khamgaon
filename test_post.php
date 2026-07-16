<?php
$_POST['submit_registration'] = 'submit';
$_POST['u_name'] = 'Test White Screen';
$_POST['gender'] = 'Male';
$_POST['mobile'] = '9999999999';
$_POST['email'] = 'test@example.com';
$_POST['dob'] = '2000-01-01';
$_POST['street_name'] = 'St';
$_POST['city'] = 'City';
$_POST['state'] = 'State';
$_POST['zipcode'] = '123';
$_POST['plan_id'] = 'POQKJC';

$_FILES['screenshot'] = [
    'name' => 'test.jpg',
    'type' => 'image/jpeg',
    'tmp_name' => __DIR__ . '/test.jpg',
    'error' => UPLOAD_ERR_OK,
    'size' => 100
];
file_put_contents(__DIR__ . '/test.jpg', 'dummy');

chdir('Files');
ob_start();
include 'prebook.php';
$out = ob_get_clean();

if (trim($out) === '') {
    echo "WHITE SCREEN DETECTED!";
} else {
    echo "Output length: " . strlen($out);
}
unlink('../test.jpg');
?>
