<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../include/db_conn.php';

if (isset($_SESSION['user_data'])) {
    if (function_exists('log_security_event') && isset($con)) {
        log_security_event($con, 'ATTENDANCE_LOGOUT', "Staff {$_SESSION['user_data']} logged out from Attendance Terminal", 'info', $_SESSION['user_data'], $_SESSION['user_data']);
    }
}

// Unset all attendance session tokens
$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

header("Location: login.php?msg=logged_out");
exit();
