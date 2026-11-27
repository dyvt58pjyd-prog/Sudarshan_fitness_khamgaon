<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../include/db_conn.php';

// Authorized roles permitted to operate the Standalone Attendance Terminal
$allowed_roles = ['super_admin', 'owner', 'reception', 'trainer', 'staff', 'admin', 'auditor'];

if (!isset($_SESSION['user_data']) || !isset($_SESSION['logged']) || !isset($_SESSION['role'])) {
    $request_uri = $_SERVER['REQUEST_URI'] ?? 'index.php';
    header("Location: login.php?ref=" . urlencode($request_uri));
    exit();
}

$current_role = strtolower(trim($_SESSION['role']));

// Block regular gym members from accessing the staff attendance portal
if (!in_array($current_role, $allowed_roles) || $current_role === 'member') {
    http_response_code(403);
    echo "<!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Access Restricted - Attendance Terminal</title>
        <link href='https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap' rel='stylesheet'>
        <style>
            * { box-sizing: border-box; font-family: 'Inter', sans-serif; }
            body { background: #0b0f19; color: #f8fafc; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 20px; text-align: center; }
            .card { background: #1e293b; border: 1px solid rgba(255,255,255,0.1); border-radius: 20px; padding: 40px 30px; max-width: 460px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); }
            h2 { color: #ef4444; font-size: 24px; margin-top: 0; margin-bottom: 12px; }
            p { color: #94a3b8; font-size: 14px; line-height: 1.6; margin-bottom: 24px; }
            .btn { display: inline-block; background: #ff6b00; color: #fff; padding: 12px 28px; border-radius: 10px; text-decoration: none; font-weight: 700; font-size: 14px; transition: transform 0.2s; }
            .btn:hover { transform: translateY(-2px); }
        </style>
    </head>
    <body>
        <div class='card'>
            <h2>🔒 Staff Authorization Required</h2>
            <p>This terminal is dedicated exclusively to Attendance Logging and is restricted to Reception, Staff, Admin, and Gym Owner accounts.</p>
            <a href='../index.php' class='btn'>Back to Portal Home</a>
        </div>
    </body>
    </html>";
    exit();
}

$current_staff_user = $_SESSION['user_data'] ?? 'staff';
$current_staff_name = $_SESSION['full_name'] ?? 'Front Desk Staff';
$current_staff_role = $_SESSION['role'] ?? 'reception';
