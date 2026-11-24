<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../include/db_conn.php';
require_once __DIR__ . '/../include/google_wallet_helper.php';

$wallet_portal_allowed_roles = ['super_admin', 'owner', 'reception'];
$is_api_request = defined('WALLET_PORTAL_API') && WALLET_PORTAL_API === true;

if (!isset($_SESSION['user_data']) || !isset($_SESSION['logged']) || !isset($_SESSION['role'])) {
    if ($is_api_request) {
        http_response_code(401);
        header("Content-Type: application/json; charset=UTF-8");
        echo json_encode(['success' => false, 'message' => 'Authentication required. Please sign in to the Wallet Pass Portal.']);
        exit();
    }
    $request_uri = $_SERVER['REQUEST_URI'] ?? 'index.php';
    header("Location: login.php?ref=" . urlencode($request_uri));
    exit();
}

$current_role = strtolower(trim((string)$_SESSION['role']));
if (!in_array($current_role, $wallet_portal_allowed_roles, true)) {
    if ($is_api_request) {
        http_response_code(403);
        header("Content-Type: application/json; charset=UTF-8");
        echo json_encode(['success' => false, 'message' => 'Access denied: Reception, Admin, or Owner role required.']);
        exit();
    }
    http_response_code(403);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Access Restricted | Wallet Pass Portal</title>
        <style>
            body { margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center; background:#0f172a; color:#f8fafc; font-family:-apple-system,BlinkMacSystemFont,'Inter',sans-serif; padding:20px; text-align:center; }
            .card { background:#1e293b; border:1px solid #334155; border-radius:18px; padding:36px 28px; max-width:460px; box-shadow:0 20px 40px rgba(0,0,0,0.5); }
            .btn { display:inline-block; margin-top:18px; background:#ff6b00; color:#fff; padding:11px 22px; border-radius:10px; text-decoration:none; font-weight:700; font-size:13px; }
        </style>
    </head>
    <body>
        <div class="card">
            <h2 style="color:#ef4444;margin:0 0 10px;">🔒 Staff Authorization Required</h2>
            <p style="color:#94a3b8;font-size:14px;line-height:1.6;margin:0;">
                The Wallet Pass Issuance Portal is restricted strictly to Reception, Admin, and Owner accounts.
            </p>
            <a class="btn" href="logout.php">Switch Account</a>
        </div>
    </body>
    </html>
    <?php
    exit();
}

$current_staff_user = $_SESSION['user_data'] ?? 'staff';
$current_staff_name = $_SESSION['full_name'] ?? $current_staff_user;
$current_staff_role = $current_role;
