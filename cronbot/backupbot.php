<?php
/**
 * MirzaBot Compatible Backup Cron Job
 * Pure PHP 8.1+ / MySQL / SQLite
 * Path: cronbot/backupbot.php
 */

chdir(__DIR__ . '/..');

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} else {
    require_once __DIR__ . '/../src/autoload.php';
}

use App\Core\Database;
use App\Models\Setting;
use App\Services\BackupService;

// Initialize Database schema
Database::initSchema();

// Verify key if accessed via HTTP
if (php_sapi_name() !== 'cli') {
    $cronSecret = Setting::get('cron_secret_key');
    if (!empty($cronSecret)) {
        $reqKey = $_GET['key'] ?? '';
        if ($reqKey !== $cronSecret) {
            http_response_code(403);
            die(json_encode(['ok' => false, 'error' => 'Forbidden: Invalid cron secret key']));
        }
    }
}

// Execute Backup Job (creates database dump & sends to backupfile topic thread / admin)
$res = BackupService::runBackupJob();

if (php_sapi_name() === 'cli') {
    echo "[" . date('Y-m-d H:i:s') . "] Backup result: " . json_encode($res, JSON_UNESCAPED_UNICODE) . PHP_EOL;
} else {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
