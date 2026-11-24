<?php
/**
 * Automatically verifies a UPI payment screenshot using Deploy.com Multimodal Vision AI.
 * Fails closed (returns false -> routes to manual admin approval) unless the image is
 * clearly a completed payment matching the expected amount.
 */
require_once __DIR__ . '/gemini_config.php';

function verify_payment_screenshot_ai($physical_path, $expected_amount) {
    if (!file_exists($physical_path) || filesize($physical_path) <= 0) {
        return false;
    }

    $expected_int = intval(round(floatval($expected_amount)));
    if ($expected_int <= 0) {
        return false;
    }

    $system = "You are a strict financial payment screenshot auditor for Sudarshan Fitness (Khamgaon). Analyze payment screenshots conservatively. Never guess or assume success if text is blurry, cropped, pending, failed, or not a real payment confirmation.";
    $prompt = "Inspect this image carefully and return ONLY raw JSON (no markdown fences) with keys:\n"
            . "{\n"
            . "  \"is_payment_receipt\": boolean,\n"
            . "  \"status\": \"success\" | \"pending\" | \"failed\" | \"unknown\",\n"
            . "  \"amount_inr\": number or null,\n"
            . "  \"utr\": string or null,\n"
            . "  \"recipient_text\": string or null,\n"
            . "  \"confidence\": number between 0 and 1\n"
            . "}\n"
            . "Rules: status must be \"success\" ONLY if the screenshot explicitly shows payment succeeded/completed/paid (not pending/processing/failed). Extract exact numeric INR amount paid into amount_inr.";

    $raw = query_deploy_vision($prompt, $physical_path, $system, [
        'temperature' => 0.1,
        'max_tokens' => 800,
        'timeout' => 20
    ]);

    if (empty($raw)) {
        return false;
    }

    // Strip optional markdown code block wrappers if present
    $clean = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', trim($raw));
    if (preg_match('/\{.*\}/s', $clean, $m)) {
        $clean = $m[0];
    }

    $parsed = json_decode($clean, true);
    if (!is_array($parsed)) {
        return false;
    }

    $is_receipt = !empty($parsed['is_payment_receipt']);
    $status = strtolower(trim($parsed['status'] ?? ''));
    $amount_inr = isset($parsed['amount_inr']) && is_numeric($parsed['amount_inr']) ? intval(round(floatval($parsed['amount_inr']))) : -1;
    $confidence = isset($parsed['confidence']) ? floatval($parsed['confidence']) : 0.0;

    if (!$is_receipt || $status !== 'success' || $confidence < 0.8) {
        return false;
    }

    if ($amount_inr !== $expected_int) {
        return false;
    }

    // If a recipient string is visible, reject obvious mismatches against unrelated personal transfers
    $recipient = strtolower(trim($parsed['recipient_text'] ?? ''));
    if (!empty($recipient)) {
        $allowed_markers = ['sudarshan', 'anurag', 'bawaskar', 'fitness', 'gym', '4326'];
        $matched = false;
        foreach ($allowed_markers as $marker) {
            if (strpos($recipient, $marker) !== false) {
                $matched = true;
                break;
            }
        }
        if (!$matched) {
            return false;
        }
    }

    return true;
}
?>
