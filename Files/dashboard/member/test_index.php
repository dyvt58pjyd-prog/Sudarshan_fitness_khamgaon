<?php
session_start();
$_SESSION['user_data'] = '1529336794'; // Need a user with NO active plan
$_SESSION['logged'] = 'start';
$_SESSION['role'] = 'member';
$_SESSION['full_name'] = 'Test User';
ini_set('display_errors', 1);
error_reporting(E_ALL);
require './index.php';
