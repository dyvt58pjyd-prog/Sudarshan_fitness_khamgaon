<?php
define('WALLET_PORTAL_API', true);
require_once __DIR__ . '/../auth_check.php';

$uid_raw = trim((string)($_GET['uid'] ?? ''));
$files_root = realpath(__DIR__ . '/../..');
$default_avatar = $files_root ? ($files_root . '/images/default_avatar.png') : '';

$serve_file = function($path) {
    if (!$path || !file_exists($path) || filesize($path) <= 0) return false;
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $mime = ($ext === 'png') ? 'image/png' : (($ext === 'webp') ? 'image/webp' : 'image/jpeg');
    header('Content-Type: ' . $mime);
    header('Cache-Control: private, max-age=86400');
    readfile($path);
    exit();
};

if ($uid_raw === '' || !$con) {
    if (!$serve_file($default_avatar)) {
        http_response_code(404);
    }
    exit();
}

$safe_uid = preg_replace('/[^a-zA-Z0-9_\-]/', '', $uid_raw);
if ($files_root && $safe_uid !== '') {
    $cached_file = $files_root . '/uploads/member_photos/mem_' . $safe_uid . '.jpg';
    $serve_file($cached_file);
}

$uid_esc = mysqli_real_escape_string($con, $uid_raw);
$q = @mysqli_query($con, "SELECT photo, member_photo, photo_base64 FROM users WHERE userid = '$uid_esc' LIMIT 1");
if ($q && $row = mysqli_fetch_assoc($q)) {
    foreach (['member_photo', 'photo'] as $col) {
        if (!empty($row[$col]) && $files_root) {
            $rel = ltrim(str_replace(['../', './'], '', $row[$col]), '/');
            $serve_file($files_root . '/' . $rel);
        }
    }
    $b64 = $row['photo_base64'] ?? '';
    if (!empty($b64) && preg_match('/^data:image\/(\w+);base64,/', $b64, $m)) {
        $raw_b64 = substr($b64, strpos($b64, ',') + 1);
        $decoded = base64_decode($raw_b64);
        if ($decoded !== false && strlen($decoded) > 0) {
            if ($files_root && $safe_uid !== '') {
                $dir = $files_root . '/uploads/member_photos';
                if (!is_dir($dir)) @mkdir($dir, 0755, true);
                @file_put_contents($dir . '/mem_' . $safe_uid . '.jpg', $decoded);
            }
            $fmt = strtolower($m[1]);
            header('Content-Type: image/' . ($fmt === 'png' ? 'png' : 'jpeg'));
            header('Cache-Control: private, max-age=86400');
            echo $decoded;
            exit();
        }
    }
}

$serve_file($default_avatar);
http_response_code(404);
