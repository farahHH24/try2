<?php

declare(strict_types=1);

header('Content-Type: application/json');

require_once dirname(__DIR__, 2) . '/app/db/connection.php';

try {
    $pdo = get_db_connection();
    $stmt = $pdo->query('SELECT id, title FROM jobs ORDER BY title ASC');
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'jobs' => $jobs]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to load jobs: ' . $e->getMessage(),
    ]);
}
