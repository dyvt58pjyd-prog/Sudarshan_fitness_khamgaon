<?php
// Sudarshan Fitness Direct APK Downloader with multi-location fallback
$possiblePaths = [
    __DIR__ . '/sudarshan_fitness.apk',
    __DIR__ . '/../sudarshan_fitness.apk',
    $_SERVER['DOCUMENT_ROOT'] . '/sudarshan_fitness.apk',
    $_SERVER['DOCUMENT_ROOT'] . '/Files/sudarshan_fitness.apk',
    dirname(__DIR__) . '/Files/sudarshan_fitness.apk',
    dirname(__DIR__) . '/sudarshan_fitness.apk'
];

$apkPath = null;
foreach ($possiblePaths as $path) {
    if (file_exists($path) && filesize($path) > 1000) {
        $apkPath = $path;
        break;
    }
}

if (!$apkPath) {
    http_response_code(404);
    echo "APK file is preparing on server. Checked paths: " . implode(', ', $possiblePaths);
    exit;
}

$fileSize = filesize($apkPath);

header('Content-Type: application/vnd.android.package-archive');
header('Content-Disposition: attachment; filename="sudarshan_fitness.apk"');
header('Content-Length: ' . $fileSize);
header('Cache-Control: public, max-age=3600');
header('Pragma: public');

readfile($apkPath);
exit;
