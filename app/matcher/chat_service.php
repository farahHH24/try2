<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/db/connection.php';
require_once dirname(__DIR__) . '/gemini/client.php';

function get_job_by_id(int $jobId): ?array
{
    $pdo = get_db_connection();
    $stmt = $pdo->prepare('SELECT id, title, description, requirements FROM jobs WHERE id = :id LIMIT 1');
    $stmt->bindValue(':id', $jobId, PDO::PARAM_INT);
    $stmt->execute();
    $job = $stmt->fetch(PDO::FETCH_ASSOC);

    return $job ?: null;
}

function fetch_chat_history(string $sessionId, int $jobId): array
{
    $pdo = get_db_connection();
    $stmt = $pdo->prepare(
        'SELECT user_message, assistant_response FROM chat_logs WHERE session_id = :session_id AND job_id = :job_id ORDER BY id ASC'
    );
    $stmt->execute([
        ':session_id' => $sessionId,
        ':job_id' => $jobId,
    ]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function log_chat(string $sessionId, int $jobId, string $userMessage, string $assistantResponse): void
{
    $pdo = get_db_connection();
    $stmt = $pdo->prepare(
        'INSERT INTO chat_logs (session_id, job_id, user_message, assistant_response) VALUES (:session_id, :job_id, :user_message, :assistant_response)'
    );
    $stmt->execute([
        ':session_id' => $sessionId,
        ':job_id' => $jobId,
        ':user_message' => $userMessage,
        ':assistant_response' => $assistantResponse,
    ]);
}

function build_gemini_prompt(array $job, string $cvText, array $history, string $userMessage): array
{
    $trimmedCv = mb_substr($cvText, 0, 8000);
    $system = <<<PROMPT
You are an expert recruiter helping a candidate compare their CV to a job.

Rules:
- Base your answers only on the job posting, CV text, and the conversation so far.
- Do NOT invent skills, certifications, or experience that are not present.
- Clearly state when information is missing or unclear.
- Keep responses concise, actionable, and easy to scan.

Job:
- Title: {$job['title']}
- Description: {$job['description']}
- Requirements: {$job['requirements']}

Candidate CV text (do not store permanently):
{$trimmedCv}
PROMPT;

    $contents = [
        [
            'role' => 'user',
            'parts' => [
                ['text' => $system],
            ],
        ],
    ];

    foreach ($history as $exchange) {
        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $exchange['user_message']]],
        ];
        $contents[] = [
            'role' => 'model',
            'parts' => [['text' => $exchange['assistant_response']]],
        ];
    }

    $contents[] = [
        'role' => 'user',
        'parts' => [
            ['text' => $userMessage],
        ],
    ];

    return $contents;
}

function chat_with_gemini(int $jobId, string $sessionId, string $userMessage, string $cvText): string
{
    $job = get_job_by_id($jobId);

    if (!$job) {
        throw new RuntimeException('Job not found.');
    }

    $history = fetch_chat_history($sessionId, $jobId);
    $contents = build_gemini_prompt($job, $cvText, $history, $userMessage);
    $response = call_gemini($contents);

    $assistantText = $response['candidates'][0]['content']['parts'][0]['text'] ?? '';
    if (empty($assistantText)) {
        throw new RuntimeException('Empty response from Gemini.');
    }

    log_chat($sessionId, $jobId, $userMessage, $assistantText);

    return $assistantText;
}
