<?php
require_once __DIR__ . '/../include/db_conn.php';
require_once __DIR__ . '/../include/pass_helper.php';
require_once __DIR__ . '/../include/google_wallet_helper.php';

$requested_uid = trim((string)($_GET['uid'] ?? ''));
$sig = trim((string)($_GET['sig'] ?? ''));
$exp = isset($_GET['exp']) ? intval($_GET['exp']) : 0;
$mode = strtolower(trim((string)($_GET['mode'] ?? 'redirect')));

$auth = authorize_wallet_pass_request($con, $requested_uid, $sig, $exp);
if (!empty($auth['ok']) && is_google_wallet_configured()) {
    $sync_now = !empty($_GET['sync']);
    $save_url = build_google_wallet_save_url($con, $auth['uid'], [], $sync_now);
    if (!empty($save_url)) {
        if ($mode === 'json') {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['success' => true, 'configured' => true, 'uid' => $auth['uid'], 'save_url' => $save_url]);
            exit();
        }
        header('Location: ' . $save_url);
        exit();
    }
}

if ($mode === 'json') {
    header('Content-Type: application/json; charset=UTF-8');
    http_response_code(empty($auth['ok']) ? 403 : 422);
    echo json_encode([
        'success'    => false,
        'configured' => is_google_wallet_configured(),
        'message'    => empty($auth['ok']) ? ($auth['reason'] ?? 'Unauthorized pass request.') : 'Google Wallet credentials not configured yet.'
    ]);
    exit();
}

handle_wallet_pass_redirect($con, 'android');
