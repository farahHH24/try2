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
    if ($jobId <= 0) {
        throw new RuntimeException('Job ID is required.');
    }

    $sessionId = session_id();
    $history = fetch_chat_history($sessionId, $jobId);

    echo json_encode([
        'success' => true,
        'session_id' => $sessionId,
        'history' => $history,
    ]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ]);
}
