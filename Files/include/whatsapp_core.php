<?php
// Core library for sending official Meta WhatsApp Cloud API messages

function get_whatsapp_config($con) {
    $res = mysqli_query($con, "SELECT * FROM whatsapp_config ORDER BY id DESC LIMIT 1");
    if ($res && mysqli_num_rows($res) > 0) {
        return mysqli_fetch_assoc($res);
    }
    return null;
}

function send_meta_whatsapp_message($con, $to_mobile, $message) {
    $config = get_whatsapp_config($con);
    if (!$config) {
        return ['success' => false, 'message' => 'WhatsApp Meta API credentials not configured.'];
    }

    $phone_number_id = trim($config['phone_number_id']);
    $access_token = trim($config['access_token']);

    if (empty($phone_number_id) || empty($access_token)) {
        return ['success' => false, 'message' => 'Missing Phone Number ID or Access Token.'];
    }

    // Clean phone number (Meta requires exactly the country code + number without +, spaces, or dashes)
    $clean_mobile = preg_replace('/\D/', '', $to_mobile);
    if (strlen($clean_mobile) == 10) {
        $clean_mobile = '91' . $clean_mobile; // Default to India if only 10 digits
    }

    // Meta Graph API URL (v17.0+)
    $url = "https://graph.facebook.com/v17.0/{$phone_number_id}/messages";

    // Payload for sending a simple text message
    $payload = [
        'messaging_product' => 'whatsapp',
        'recipient_type' => 'individual',
        'to' => $clean_mobile,
        'type' => 'text',
        'text' => [
            'preview_url' => false,
            'body' => $message
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $access_token,
        'Content-Type: application/json'
    ]);
    
    // Timeout
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);

    $res = curl_exec($ch);
    $err = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($res === false) {
        return ['success' => false, 'message' => 'cURL Error: ' . $err];
    }

    $data = json_decode($res, true);

    if ($code >= 200 && $code < 300) {
        return ['success' => true, 'message' => 'Message dispatched successfully.', 'meta_response' => $data];
    } else {
        $err_msg = isset($data['error']['message']) ? $data['error']['message'] : 'Unknown Meta API error';
        return ['success' => false, 'message' => $err_msg, 'meta_response' => $data];
    }
}

function send_meta_whatsapp_image($con, $to_mobile, $message, $image_url) {
    $config = get_whatsapp_config($con);
    if (!$config) return ['success' => false];

    $phone_number_id = trim($config['phone_number_id']);
    $access_token = trim($config['access_token']);
    $clean_mobile = preg_replace('/\D/', '', $to_mobile);
    if (strlen($clean_mobile) == 10) $clean_mobile = '91' . $clean_mobile;

    $url = "https://graph.facebook.com/v17.0/{$phone_number_id}/messages";
    
    $payload = [
        'messaging_product' => 'whatsapp',
        'recipient_type' => 'individual',
        'to' => $clean_mobile,
        'type' => 'image',
        'image' => [
            'link' => $image_url,
            'caption' => $message
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $access_token,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($code >= 200 && $code < 300) {
        return ['success' => true];
    }
    return ['success' => false, 'error' => $res];
}
?>
