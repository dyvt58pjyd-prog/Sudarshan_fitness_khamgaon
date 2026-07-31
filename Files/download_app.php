<?php
// Sudarshan Fitness Direct APK Downloader
$apkPath = __DIR__ . '/sudarshan_fitness.apk';

if (!file_exists($apkPath) || filesize($apkPath) < 1000) {
    http_response_code(404);
    echo "APK file is preparing on server.";
    exit;
}

$fileSize = filesize($apkPath);

header('Content-Type: application/vnd.android.package-archive');
header('Content-Disposition: attachment; filename="sudarshan_fitness.apk"');
header('Content-Length: ' . $fileSize);
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

readfile($apkPath);
exit;
