<?php
/**
 * Automatically verifies a payment screenshot using OCR.
 * Returns true if the expected amount is found, false otherwise.
 */
function verify_payment_screenshot_ai($physical_path, $expected_amount) {
    if (!file_exists($physical_path)) {
        return false;
    }

    $image_data = file_get_contents($physical_path);
    $base64_image = 'data:image/' . pathinfo($physical_path, PATHINFO_EXTENSION) . ';base64,' . base64_encode($image_data);

    $ocr_url = 'https://api.ocr.space/parse/image';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $ocr_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    // Timeout set to 15 seconds so we don't hang the registration forever
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
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
            $text = strtolower($ocr_json['ParsedResults'][0]['ParsedText']);
            
            // Format expected amount with and without comma
            $amount_no_comma = strval($expected_amount);
            $amount_comma = number_format($expected_amount);
            
            if (strpos($text, $amount_no_comma) !== false || strpos($text, $amount_comma) !== false) {
                return true;
            }
        }
    }
    
    return false;
}
?>
