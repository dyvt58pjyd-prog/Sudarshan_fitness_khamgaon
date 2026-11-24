<?php
require_once __DIR__ . '/include/db_conn.php';
if (php_sapi_name() !== 'cli') {
    page_protect();
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['super_admin', 'owner'])) {
        http_response_code(403);
        die("Access Denied.");
    }
}
$res = mysqli_query($con, "SHOW TABLES");
if ($res) {
    while ($row = mysqli_fetch_row($res)) {
        echo $row[0] . "\n";
    }
}
