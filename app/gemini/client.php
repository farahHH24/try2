<?php

declare(strict_types=1);

function call_gemini(array $contents): array
{
    $config = require dirname(__DIR__) . '/config/config.php';
    $apiKey = $config['gemini']['api_key'];
    $model = $config['gemini']['model'];
    $endpoint = rtrim($config['gemini']['endpoint'], '/');

    if (empty($apiKey)) {
        throw new RuntimeException('GEMINI_API_KEY is missing. Add it to your environment or config.');
    }

    $url = sprintf('%s/%s:generateContent?key=%s', $endpoint, $model, urlencode($apiKey));

    $payload = json_encode([
        'contents' => $contents,
        'generationConfig' => [
            'temperature' => 0.2,
            'topK' => 40,
            'topP' => 0.9,
            'maxOutputTokens' => 512,
        ],
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new RuntimeException('Gemini request failed: ' . $error);
    }

    curl_close($ch);
    $data = json_decode($response, true);

    if ($httpCode >= 400) {
        $message = $data['error']['message'] ?? 'Unknown Gemini API error';
        throw new RuntimeException('Gemini API returned HTTP ' . $httpCode . ': ' . $message);
    }

    if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
        throw new RuntimeException('Unexpected Gemini response format.');
    }

    return $data;
}
