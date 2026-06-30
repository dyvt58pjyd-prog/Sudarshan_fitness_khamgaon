<?php
session_start();
$_SESSION['user_data'] = '1529336794';
$_SESSION['logged'] = 'start';
$_SESSION['role'] = 'member';
$_SESSION['full_name'] = 'Test User';
ini_set('display_errors', 1);
error_reporting(E_ALL);

// mock POST data
$input = [
    'goal' => 'Weight Loss',
    'diet' => 'Vegetarian',
    'weight' => '80',
    'height' => '175',
    'medical' => 'None'
];
file_put_contents('php://input', json_encode($input));

$_SERVER['REQUEST_METHOD'] = 'POST';

require './Files/api/get_ai_plan.php';
