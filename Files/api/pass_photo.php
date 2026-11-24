<?php
/**
 * HMAC-signed public member photo endpoint for Google Wallet cloud image fetchers.
 * Validates ?uid=<id>&sig=<hmac> without requiring a browser login cookie.
 */
require_once __DIR__ . '/../include/db_conn.php';
require_once __DIR__ . '/../include/pass_helper.php';

$uid = trim((string)($_GET['uid'] ?? ''));
$sig = trim((string)($_GET['sig'] ?? ''));
$variant = strtolower(trim((string)($_GET['variant'] ?? 'portrait')));

if ($uid === '' || $sig === '' || !verify_pass_url_signature($con, $uid, $sig, 0)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Forbidden: Invalid or missing pass signature.';
    exit();
}

$uid_esc = mysqli_real_escape_string($con, $uid);
$q = mysqli_query($con, "SELECT photo, image_path, photo_base64 FROM users WHERE userid = '$uid_esc' LIMIT 1");
if (!$q || mysqli_num_rows($q) === 0) {
    http_response_code(404);
    exit();
}
$row = mysqli_fetch_assoc($q);

$files_root = realpath(__DIR__ . '/..');
$img_bytes = null;
$mime = 'image/png';

$try_local_file = function($rel) use ($files_root) {
    $rel = trim((string)$rel);
    if ($rel === '' || preg_match('#^https?://#i', $rel) || strpos($rel, 'data:') === 0) {
        return null;
    }
    $clean = ltrim(str_replace('\\', '/', $rel), '/');
    while (strpos($clean, '../') === 0) {
        $clean = substr($clean, 3);
    }
    if (strpos($clean, 'Files/') === 0) {
        $clean = substr($clean, 6);
    }
    $candidates = [
        $files_root . '/' . $clean,
        $files_root . '/uploads/' . basename($clean),
        $files_root . '/uploads/member_photos/' . basename($clean),
        $files_root . '/dashboard/admin/' . $clean,
    ];
    foreach ($candidates as $cand) {
        $real = @realpath($cand);
        if ($real && is_file($real) && strpos($real, $files_root) === 0) {
            return $real;
        }
    }
    return null;
};

$local_path = $try_local_file($row['photo'] ?? '');
if (!$local_path) {
    $local_path = $try_local_file($row['image_path'] ?? '');
}
if ($local_path) {
    $img_bytes = @file_get_contents($local_path);
}

if (!$img_bytes && !empty($row['photo_base64'])) {
    $b64 = trim((string)$row['photo_base64']);
    if (strpos($b64, 'base64,') !== false) {
        $parts = explode('base64,', $b64, 2);
        $b64 = $parts[1] ?? '';
    }
    $decoded = base64_decode($b64, true);
    if ($decoded !== false && strlen($decoded) > 64) {
        $img_bytes = $decoded;
    }
}

if (!$img_bytes) {
    $fallback = $files_root . '/images/default_avatar.png';
    if (is_file($fallback)) {
        $img_bytes = @file_get_contents($fallback);
    }
}

if (!$img_bytes) {
    http_response_code(404);
    exit();
}

// Detect mime safely
if (function_exists('getimagesizefromstring')) {
    $info = @getimagesizefromstring($img_bytes);
    if (!empty($info['mime'])) {
        $mime = $info['mime'];
    }
}

// For Google Wallet GenericObject heroImage (1032x336 recommended ratio), frame portrait cleanly without stretching.
if ($variant === 'hero' && function_exists('imagecreatefromstring') && function_exists('imagecreatetruecolor')) {
    $src = @imagecreatefromstring($img_bytes);
    if ($src !== false) {
        $src_w = imagesx($src);
        $src_h = imagesy($src);
        $hero_w = 1032;
        $hero_h = 336;

        $canvas = imagecreatetruecolor($hero_w, $hero_h);
        $bg_dark = imagecolorallocate($canvas, 15, 23, 42);       // #0f172a
        $panel_bg = imagecolorallocate($canvas, 30, 41, 59);     // #1e293b
        $accent = imagecolorallocate($canvas, 255, 107, 0);      // #ff6b00
        $gold_border = imagecolorallocate($canvas, 245, 158, 11);// #f59e0b

        imagefilledrectangle($canvas, 0, 0, $hero_w, $hero_h, $bg_dark);
        imagefilledrectangle($canvas, 28, 18, $hero_w - 28, $hero_h - 18, $panel_bg);
        imagefilledrectangle($canvas, 28, 18, $hero_w - 28, 24, $accent);

        // Square-crop center of source portrait and place neatly in center
        $crop_size = min($src_w, $src_h);
        $src_x = (int)(($src_w - $crop_size) / 2);
        $src_y = (int)(max(0, ($src_h - $crop_size) / 3)); // favor face near top-center

        $avatar_size = 248;
        $dst_x = (int)(($hero_w - $avatar_size) / 2);
        $dst_y = (int)(($hero_h - $avatar_size) / 2) + 4;

        imagefilledrectangle($canvas, $dst_x - 5, $dst_y - 5, $dst_x + $avatar_size + 5, $dst_y + $avatar_size + 5, $gold_border);
        imagecopyresampled($canvas, $src, $dst_x, $dst_y, $src_x, $src_y, $avatar_size, $avatar_size, $crop_size, $crop_size);

        header('Content-Type: image/png');
        header('Cache-Control: public, max-age=3600');
        imagepng($canvas, null, 6);
        if (PHP_VERSION_ID < 80000) {
            imagedestroy($src);
            imagedestroy($canvas);
        }
        exit();
    }
}

header('Content-Type: ' . $mime);
header('Cache-Control: public, max-age=3600');
echo $img_bytes;
