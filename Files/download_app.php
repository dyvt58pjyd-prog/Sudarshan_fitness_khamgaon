<?php
// Sudarshan Fitness Direct APK Downloader
$apkPath = __DIR__ . '/sudarshan_fitness.apk';

if (!file_exists($apkPath)) {
    // Check if stored in root or parent
    if (file_exists(__DIR__ . '/../sudarshan_fitness.apk')) {
        $apkPath = __DIR__ . '/../sudarshan_fitness.apk';
    } else {
        http_response_code(404);
        echo "APK File Not Found. Please rebuild.";
        exit;
    }
}

$fileSize = filesize($apkPath);

header('Content-Type: application/vnd.android.package-archive');
header('Content-Disposition: attachment; filename="sudarshan_fitness.apk"');
header('Content-Length: ' . $fileSize);
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

readfile($apkPath);
exit;
