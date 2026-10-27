<?php
// Permanently decommissioned APK downloader - redirect to secure member portal
http_response_code(301);
header('Location: /Files/index.php');
exit;
