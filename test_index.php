<?php
session_start();
$_SESSION['user_data'] = '1000'; // Assuming a user id
$_SESSION['logged'] = 'start';
$_SESSION['full_name'] = 'Test User';
require './Files/dashboard/member/index.php';
