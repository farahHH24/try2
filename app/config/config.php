<?php

declare(strict_types=1);

return [
    'db' => [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'name' => getenv('DB_NAME') ?: 'cv_matcher',
        'user' => getenv('DB_USER') ?: 'root',
        'pass' => getenv('DB_PASS') ?: '',
        'charset' => 'utf8mb4',
    ],
    'gemini' => [
        'api_key' => getenv('GEMINI_API_KEY') ?: '',
        'model' => getenv('GEMINI_MODEL') ?: 'gemini-2.5-flash',
        'endpoint' => 'https://generativelanguage.googleapis.com/v1beta/models',
    ],
    'uploads' => [
        'path' => dirname(__DIR__, 2) . '/uploads/cvs',
        'max_size_mb' => 10,
    ],
];
