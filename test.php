<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h3>Testing PHP Connection...</h3>";
try {
    include './Files/include/db_conn.php';
    echo "<p style='color:green;'>SUCCESS: db_conn.php has no syntax errors!</p>";
} catch (Throwable $e) {
    echo "<p style='color:red;'>ERROR: " . $e->getMessage() . "</p>";
}
?>
