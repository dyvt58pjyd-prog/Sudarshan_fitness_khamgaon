<?php
// This is a temporary script to "catch" what the Bio Park B100 machine sends.
// Once we see the format it uses, we will build the actual attendance integration API.

$log_file = 'bio_log.txt';

$timestamp = date("Y-m-d H:i:s");
$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];
$get_data = json_encode($_GET);
$post_data = json_encode($_POST);
$raw_data = file_get_contents('php://input');

$log_entry = "[$timestamp] METHOD: $method | URI: $uri\n";
$log_entry .= "GET: $get_data\n";
$log_entry .= "POST: $post_data\n";
$log_entry .= "RAW: $raw_data\n";
$log_entry .= "--------------------------------------------------\n\n";

file_put_contents($log_file, $log_entry, FILE_APPEND);

// Return standard success to the machine so it stops retrying
echo "OK";
?>
