<?php
// Centralized AI Integration for Sudarshan Fitness v2.0 (Deploy.com + Gemini Fallback)

if (file_exists(__DIR__ . '/ai_config.php')) {
    require_once __DIR__ . '/ai_config.php';
}

if (!defined('DEPLOY_API_KEY')) {
    define('DEPLOY_API_KEY', getenv('DEPLOY_API_KEY') ?: '');
}
if (!defined('DEPLOY_API_BASE')) {
    define('DEPLOY_API_BASE', getenv('DEPLOY_API_BASE') ?: 'https://api.deploy.com/api/v1');
}
if (!defined('DEPLOY_MODEL')) {
    define('DEPLOY_MODEL', getenv('DEPLOY_MODEL') ?: 'deepseek-ai/deepseek-v4.1-flash-1017');
}
if (!defined('DEPLOY_FALLBACK_MODEL')) {
    define('DEPLOY_FALLBACK_MODEL', getenv('DEPLOY_FALLBACK_MODEL') ?: 'zai-org/glm-5.3-flash');
}
if (!defined('GEMINI_API_KEY')) {
    define('GEMINI_API_KEY', getenv('GEMINI_API_KEY') ?: '');
}

if (!function_exists('call_deploy_chat_api')) {
    function call_deploy_chat_api($messages, $options = []) {
        $apiKey = defined('DEPLOY_API_KEY') ? trim(DEPLOY_API_KEY) : '';
        if (empty($apiKey) || $apiKey === 'YOUR_DEPLOY_COM_API_KEY') {
            return false;
        }

        $baseUrl = rtrim(defined('DEPLOY_API_BASE') ? DEPLOY_API_BASE : 'https://api.deploy.com/api/v1', '/');
        $url = $baseUrl . '/chat/completions';

        $models = [];
        if (!empty($options['model'])) {
            $models[] = $options['model'];
        } else {
            $models[] = DEPLOY_MODEL;
            if (DEPLOY_FALLBACK_MODEL !== DEPLOY_MODEL) {
                $models[] = DEPLOY_FALLBACK_MODEL;
            }
        }

        $temperature = isset($options['temperature']) ? floatval($options['temperature']) : 0.6;
        // Reasoning models consume reasoning tokens before generating final content; keep generous budget.
        $maxTokens = isset($options['max_tokens']) ? intval($options['max_tokens']) : 2048;
        $timeout = isset($options['timeout']) ? intval($options['timeout']) : 25;

        foreach ($models as $model) {
            $payload = [
                'model' => $model,
                'messages' => $messages,
                'temperature' => $temperature,
                'max_tokens' => $maxTokens
            ];

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode >= 200 && $httpCode < 300 && !empty($response)) {
                $result = json_decode($response, true);
                $content = $result['choices'][0]['message']['content'] ?? '';
                if (is_string($content) && trim($content) !== '') {
                    return trim($content);
                }
            }
        }

        return false;
    }
}

if (!function_exists('query_deploy_ai')) {
    function query_deploy_ai($prompt, $system_instruction = '', $options = []) {
        $messages = [];
        if (!empty($system_instruction)) {
            $messages[] = ['role' => 'system', 'content' => $system_instruction];
        }
        $messages[] = ['role' => 'user', 'content' => $prompt];
        return call_deploy_chat_api($messages, $options);
    }
}

if (!function_exists('query_deploy_vision')) {
    function query_deploy_vision($prompt, $image_path_or_data_url, $system_instruction = '', $options = []) {
        $imageUrl = $image_path_or_data_url;
        if (strpos($imageUrl, 'data:image/') !== 0 && strpos($imageUrl, 'http') !== 0) {
            if (!file_exists($imageUrl)) {
                return false;
            }
            $ext = strtolower(pathinfo($imageUrl, PATHINFO_EXTENSION));
            $mime = ($ext === 'png') ? 'image/png' : (($ext === 'webp') ? 'image/webp' : 'image/jpeg');
            $raw = @file_get_contents($imageUrl);
            if ($raw === false) {
                return false;
            }
            $imageUrl = 'data:' . $mime . ';base64,' . base64_encode($raw);
        }

        $messages = [];
        if (!empty($system_instruction)) {
            $messages[] = ['role' => 'system', 'content' => $system_instruction];
        }
        $messages[] = [
            'role' => 'user',
            'content' => [
                ['type' => 'text', 'text' => $prompt],
                ['type' => 'image_url', 'image_url' => ['url' => $imageUrl]]
            ]
        ];
        return call_deploy_chat_api($messages, $options);
    }
}

if (!function_exists('query_gemini_ai')) {
    function query_gemini_ai($prompt, $system_instruction = '') {
        // 1. Prefer Deploy.com OpenAI-compatible AI endpoint when configured
        $deployReply = query_deploy_ai($prompt, $system_instruction);
        if ($deployReply !== false && $deployReply !== '') {
            return $deployReply;
        }

        // 2. Fallback to Google Gemini API if GEMINI_API_KEY env var is configured
        $apiKey = defined('GEMINI_API_KEY') ? trim(GEMINI_API_KEY) : '';
        if (empty($apiKey)) {
            return false;
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . urlencode($apiKey);

        $contents = [];
        if (!empty($system_instruction)) {
            $contents[] = [
                "role" => "user",
                "parts" => [["text" => "System Instruction: " . $system_instruction]]
            ];
            $contents[] = [
                "role" => "model",
                "parts" => [["text" => "Understood. I will act as Sudarshan Fitness AI Assistant according to these instructions."]]
            ];
        }

        $contents[] = [
            "role" => "user",
            "parts" => [["text" => $prompt]]
        ];

        $payload = [
            "contents" => $contents,
            "generationConfig" => [
                "temperature" => 0.7,
                "maxOutputTokens" => 1200
            ]
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && !empty($response)) {
            $result = json_decode($response, true);
            if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
                return trim($result['candidates'][0]['content']['parts'][0]['text']);
            }
        }

        return false;
    }
}
?>
