<?php

/**
 * Direct Telegram Webhook Endpoint for cPanel Shared Hosting (No SSH, No Rewriting needed)
 * Set Telegram webhook to: https://yourdomain.com/webhook.php
 */

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    require_once __DIR__ . '/src/autoload.php';
}

use App\Core\Database;
use App\Telegram\Handler;

header('Content-Type: application/json; charset=utf-8');

try {
    Database::initSchema();

    $input = file_get_contents('php://input');
    $update = json_decode($input, true);

    if (!empty($update)) {
        $handler = new Handler();
        $handler->handleUpdate($update);
    }

    echo json_encode(['ok' => true]);
} catch (\Throwable $e) {
    http_response_code(200); // Always return 200 to Telegram so it doesn't repeatedly retry on error
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
