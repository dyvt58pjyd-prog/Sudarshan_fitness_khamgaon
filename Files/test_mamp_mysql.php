<?php
$host = '127.0.0.1';
$user = 'root';
$pass = 'root';
$port = 8889;
$db = 'titangym';

echo "Testing port 8889...\n";
$con = @mysqli_connect($host, $user, $pass, $db, $port);
if (!$con) {
    echo "Port 8889 error: " . mysqli_connect_error() . "\n";
} else {
    echo "Connected successfully to port 8889!\n";
}

echo "Testing port 8889 without DB name...\n";
$con2 = @mysqli_connect($host, $user, $pass, "", $port);
if (!$con2) {
    echo "Port 8889 no-DB error: " . mysqli_connect_error() . "\n";
} else {
    echo "Connected successfully without DB!\n";
}
?>
