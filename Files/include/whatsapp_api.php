<?php
// whatsapp_api.php

function sendWhatsAppMessage($phone, $message) {
    // ------------------------------------------------------------------
    // TODO: Update these with your UltraMsg (or other provider) credentials
    // ------------------------------------------------------------------
    $instance_id = 'INSTANCE_ID_HERE'; // e.g., instance12345
    $api_token = 'API_TOKEN_HERE';     // e.g., 12ab34cd56ef78
    
    // Clean phone number (must include country code, e.g. +91)
    $clean_phone = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($clean_phone) == 10) {
        $clean_phone = '91' . $clean_phone; // Default to India if no code
    }
    
    // Check if API is configured
    if ($instance_id === 'INSTANCE_ID_HERE' || empty($instance_id)) {
        error_log("WhatsApp API not configured. Message to $clean_phone skipped.");
        return false;
    }
    
    $url = "https://api.ultramsg.com/$instance_id/messages/chat";
    $data = [
        "token" => $api_token,
        "to" => '+' . $clean_phone,
        "body" => $message
    ];
    
    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL => $url,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_SSL_VERIFYHOST => 0,
      CURLOPT_SSL_VERIFYPEER => 0,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => http_build_query($data),
      CURLOPT_HTTPHEADER => array(
        "content-type: application/x-www-form-urlencoded"
      ),
    ));
    
    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);
    
    if ($err) {
        error_log("WhatsApp API Error: " . $err);
        return false;
    }
    
    return json_decode($response, true);
}
?>
