<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$_POST['submit_registration'] = 'submit';
$_POST['u_name'] = 'Test User';
$_POST['gender'] = 'Male';
$_POST['mobile'] = '9999999999';
$_POST['email'] = 'test@example.com';
$_POST['dob'] = '2000-01-01';
$_POST['street_name'] = 'Test St';
$_POST['city'] = 'Test City';
$_POST['state'] = 'Test State';
$_POST['zipcode'] = '123456';
$_POST['plan_id'] = 'POQKJC';
$_POST['utr'] = '123456789012';
$_POST['captured_photo'] = 'data:image/jpeg;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

$_FILES['screenshot'] = [
    'name' => 'test_screenshot.jpg',
    'type' => 'image/jpeg',
    'tmp_name' => __DIR__ . '/Files/test_screenshot.jpg',
    'error' => UPLOAD_ERR_OK,
    'size' => 100
];

file_put_contents(__DIR__ . '/Files/test_screenshot.jpg', 'dummy');
chdir('Files');

$content = file_get_contents('register.php');
$content = str_replace('move_uploaded_file', 'rename', $content);
file_put_contents('register_test.php', $content);

ob_start();
include 'register_test.php';
$output = ob_get_clean();

if (preg_match('/<div class="alert alert-(danger|success)"[^>]*>.*?(?:⚠️|🎉)?\s*(.*?)<\/div>/s', $output, $matches)) {
    echo "MESSAGE: " . trim(strip_tags($matches[2])) . "\n";
} else {
    echo "NO MESSAGE FOUND\n";
}
unlink('register_test.php');
?>
