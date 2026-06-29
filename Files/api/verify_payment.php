<?php
header('Content-Type: application/json');
require '../include/db_conn.php';

// Allow only admins to run this
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['super_admin', 'owner', 'reception'])) {
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

if (!isset($_POST['image_path'])) {
    echo json_encode(['error' => 'No image provided']);
    exit();
}

$image_val = $_POST['image_path']; // e.g. "../../Sudarshan Data Folder/img.jpg"
$clean_path = ltrim($image_val, './');
$physical_path = __DIR__ . '/../' . $clean_path;

if (!file_exists($physical_path)) {
    echo json_encode(['error' => 'Image file not found on server']);
    exit();
}

$response = [
    'exif' => [],
    'ocr' => []
];

// 1. EXIF EXTRACTION
$exif_data = @exif_read_data($physical_path);
if ($exif_data !== false) {
    if (isset($exif_data['DateTimeOriginal'])) {
        $response['exif']['date_taken'] = date('F j, Y g:i A', strtotime($exif_data['DateTimeOriginal']));
    } elseif (isset($exif_data['DateTime'])) {
        $response['exif']['date_taken'] = date('F j, Y g:i A', strtotime($exif_data['DateTime']));
    } else {
        $response['exif']['date_taken'] = 'Not Available (Stripped by App)';
    }

    $device = [];
    if (isset($exif_data['Make'])) $device[] = $exif_data['Make'];
    if (isset($exif_data['Model'])) $device[] = $exif_data['Model'];
    
    $response['exif']['device'] = !empty($device) ? implode(' ', $device) : 'Not Available';
} else {
    $response['exif']['date_taken'] = 'No EXIF Data (Likely a forwarded image)';
    $response['exif']['device'] = 'Not Available';
}

// 2. OCR API (OCR.Space)
// We will base64 encode the image to send to the OCR API
$image_data = file_get_contents($physical_path);
$base64_image = 'data:image/' . pathinfo($physical_path, PATHINFO_EXTENSION) . ';base64,' . base64_encode($image_data);

$ocr_url = 'https://api.ocr.space/parse/image';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $ocr_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
$post = array(
    'apikey' => 'helloworld', // Free API Key
    'base64Image' => $base64_image,
    'language' => 'eng',
    'isTable' => 'true'
);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
$ocr_result = curl_exec($ch);
curl_close($ch);

if ($ocr_result) {
    $ocr_json = json_decode($ocr_result, true);
    if (isset($ocr_json['ParsedResults'][0]['ParsedText'])) {
        $text = $ocr_json['ParsedResults'][0]['ParsedText'];
        $response['ocr']['raw_text'] = trim($text);
        
        // Try to find the amount (12000)
        if (strpos($text, '12000') !== false || strpos($text, '12,000') !== false) {
            $response['ocr']['amount_found'] = true;
        } else {
            $response['ocr']['amount_found'] = false;
        }
    } else {
        $response['ocr']['error'] = 'Could not parse text from image';
    }
} else {
    $response['ocr']['error'] = 'OCR API Request Failed';
}

echo json_encode($response);
?>
