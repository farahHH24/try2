<?php

declare(strict_types=1);

header('Content-Type: application/json');
session_start();

$config = require dirname(__DIR__, 2) . '/app/config/config.php';
require_once dirname(__DIR__, 2) . '/app/matcher/pdf_parser.php';
$uploadDir = rtrim($config['uploads']['path'], '/');

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0775, true);
}

try {
    if (!isset($_FILES['cv'])) {
        throw new RuntimeException('No file uploaded.');
    }

    $file = $_FILES['cv'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('File upload failed with error code ' . $file['error']);
    }

    $maxBytes = $config['uploads']['max_size_mb'] * 1024 * 1024;
    if ($file['size'] > $maxBytes) {
        throw new RuntimeException('File exceeds the maximum size of ' . $config['uploads']['max_size_mb'] . 'MB.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if ($mime !== 'application/pdf') {
        throw new RuntimeException('Only PDF CVs are allowed.');
    }

    $sessionId = session_id();
    $tempName = $sessionId . '_' . time() . '.pdf';
    $destination = $uploadDir . '/' . $tempName;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('Failed to store uploaded file.');
    }

    $cvText = extract_text_from_pdf($destination);
    unlink($destination);

    if (empty($cvText)) {
        throw new RuntimeException('No text could be extracted from the PDF.');
    }

    $_SESSION['cv_text'] = $cvText;

    echo json_encode([
        'success' => true,
        'session_id' => $sessionId,
        'message' => 'CV uploaded and processed successfully.',
    ]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ]);
}
