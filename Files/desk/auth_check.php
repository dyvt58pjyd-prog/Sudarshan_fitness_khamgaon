<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../include/db_conn.php';

// Valid staff roles permitted to use the Front Desk Registration & Renewal Portal
$allowed_roles = ['super_admin', 'owner', 'reception', 'trainer', 'auditor'];

if (!isset($_SESSION['user_data']) || !isset($_SESSION['logged']) || !isset($_SESSION['role'])) {
    // Save intended destination for seamless redirect after login
    $request_uri = $_SERVER['REQUEST_URI'] ?? 'index.php';
    header("Location: login.php?ref=" . urlencode($request_uri));
    exit();
}

$current_role = strtolower(trim($_SESSION['role']));

// Block regular gym members from accessing the desk portal
if (!in_array($current_role, $allowed_roles) || $current_role === 'member') {
    http_response_code(403);
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Access Restricted - Sudarshan Fitness Desk</title>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #0f172a; color: #f8fafc; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 20px; text-align: center; }
            .card { background: #1e293b; border: 1px solid #334155; border-radius: 16px; padding: 40px 30px; max-width: 480px; box-shadow: 0 20px 40px rgba(0,0,0,0.5); }
            h2 { color: #ef4444; margin-top: 0; }
            p { color: #94a3b8; font-size: 14px; line-height: 1.6; }
            a { display: inline-block; margin-top: 20px; background: #ff6b00; color: #fff; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 600; }
        </style>
    </head>
    <body>
        <div class='card'>
            <h2>🔒 Staff Access Required</h2>
            <p>The Registration & Renewal Portal is reserved for Front Desk, Reception, and Gym Administration staff. Member accounts cannot view or modify registrations.</p>
            <a href='../index.php'>Go to Member Login</a>
        </div>
    </body>
    </html>";
    exit();
}

$current_staff_user = $_SESSION['user_data'] ?? 'staff';
$current_staff_name = $_SESSION['full_name'] ?? 'Front Desk Staff';
$current_staff_role = $_SESSION['role'] ?? 'reception';
