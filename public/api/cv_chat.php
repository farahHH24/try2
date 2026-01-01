<?php

declare(strict_types=1);

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true) ?: [];

if (!empty($input['session_id'])) {
    session_id($input['session_id']);
}

session_start();

require_once dirname(__DIR__, 2) . '/app/matcher/chat_service.php';

try {
    $jobId = isset($input['job_id']) ? (int) $input['job_id'] : 0;
    $userMessage = trim($input['message'] ?? '');

    if ($jobId <= 0) {
        throw new RuntimeException('Invalid job selection.');
    }

    if ($userMessage === '') {
        throw new RuntimeException('Please enter a message.');
    }

    if (empty($_SESSION['cv_text'])) {
        throw new RuntimeException('Upload a CV before starting the chat.');
    }

    $sessionId = session_id();
    $assistantResponse = chat_with_gemini($jobId, $sessionId, $userMessage, $_SESSION['cv_text']);

    echo json_encode([
        'success' => true,
        'session_id' => $sessionId,
        'response' => $assistantResponse,
    ]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ]);
}
