<?php

/**
 * Front Controller for cPanel / DirectAdmin Shared Hosting (root public_html)
 */

// Check if system is installed
$lockFile = __DIR__ . '/database/installed.lock';
$uri = $_SERVER['REQUEST_URI'] ?? '';
if (!file_exists($lockFile) && !str_contains($uri, 'install.php')) {
    header("Location: /install.php");
    exit;
}

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    require_once __DIR__ . '/src/autoload.php';
}

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Controllers\AdminController;
use App\Controllers\MiniAppController;
use App\Controllers\WebhookController;

Database::initSchema();

$router = new Router();
$request = new Request();

// Root redirect
$router->get('/', function() {
    Response::redirect('/admin/dashboard');
});

// Admin Authentication
$router->get('/admin/login', [AdminController::class, 'loginView']);
$router->post('/admin/login', [AdminController::class, 'handleLogin']);
$router->get('/admin/logout', [AdminController::class, 'logout']);

// Admin Dashboard & Management
$router->get('/admin/dashboard', [AdminController::class, 'dashboard']);

// Admin Management (RBAC)
$router->get('/admin/admins', [AdminController::class, 'admins']);
$router->post('/admin/admins/save', [AdminController::class, 'saveAdmin']);
$router->get('/admin/admins/delete/{id}', [AdminController::class, 'deleteAdmin']);

// Buttons Position & Colors Customization & Removal
$router->get('/admin/buttons', [AdminController::class, 'buttons']);
$router->post('/admin/buttons/save-layout', [AdminController::class, 'saveButtonsLayout']);
$router->post('/admin/buttons/save', [AdminController::class, 'saveButton']);
$router->get('/admin/buttons/delete/{id}', [AdminController::class, 'deleteButton']);
$router->get('/admin/buttons/restore', [AdminController::class, 'restoreButtons']);

// Webhook for cPanel Shared Hosting (No SSH needed)
$router->post('/admin/webhook/set', [AdminController::class, 'setWebhook']);
$router->get('/admin/webhook/delete', [AdminController::class, 'deleteWebhook']);
$router->get('/admin/webhook/info', [AdminController::class, 'webhookInfo']);

// Panels
$router->get('/admin/panels', [AdminController::class, 'panels']);
$router->post('/admin/panels/save', [AdminController::class, 'savePanel']);
$router->get('/admin/panels/delete/{id}', [AdminController::class, 'deletePanel']);
$router->get('/admin/panels/test/{id}', [AdminController::class, 'testPanel']);
$router->post('/admin/panels/set-inbounds', [AdminController::class, 'setPanelInboundsFromClient']);

// Plans
$router->get('/admin/plans', [AdminController::class, 'plans']);
$router->post('/admin/plans/save', [AdminController::class, 'savePlan']);
$router->get('/admin/plans/delete/{id}', [AdminController::class, 'deletePlan']);

// Bank Cards
$router->get('/admin/cards', [AdminController::class, 'cards']);
$router->post('/admin/cards/save', [AdminController::class, 'saveCard']);
$router->get('/admin/cards/delete/{id}', [AdminController::class, 'deleteCard']);

// Orders & Verification
$router->get('/admin/orders', [AdminController::class, 'orders']);
$router->get('/admin/orders/approve/{id}', [AdminController::class, 'approveOrder']);
$router->post('/admin/orders/reject/{id}', [AdminController::class, 'rejectOrder']);

// Customization (Button Texts, Colors & Settings)
$router->get('/admin/customization', [AdminController::class, 'customization']);
$router->post('/admin/customization/save', [AdminController::class, 'saveCustomization']);

// Forum Report Topics & Database Backup
$router->post('/admin/reports/sync-topics', [AdminController::class, 'syncReportTopics']);
$router->get('/admin/backup/download', [AdminController::class, 'downloadBackup']);
$router->post('/admin/backup/send-telegram', [AdminController::class, 'sendBackupToTelegram']);
$router->get('/cron/backup', [AdminController::class, 'cronBackup']);

// Database Migration from MirzaBot
$router->get('/admin/migration', [AdminController::class, 'migrationView']);
$router->post('/admin/migration/upload', [AdminController::class, 'handleMigration']);

// Users & Broadcast
$router->get('/admin/users', [AdminController::class, 'users']);
$router->get('/admin/users/toggle-ban/{id}', [AdminController::class, 'toggleBanUser']);
$router->get('/admin/broadcast', [AdminController::class, 'broadcast']);
$router->post('/admin/broadcast', [AdminController::class, 'broadcast']);

// Telegram Mini App
$router->get('/miniapp', [MiniAppController::class, 'index']);
$router->get('/api/miniapp/data', [MiniAppController::class, 'getData']);
$router->post('/api/miniapp/order', [MiniAppController::class, 'submitOrder']);
$router->get('/api/miniapp/sync-service', [MiniAppController::class, 'syncService']);
$router->post('/api/miniapp/topup', [MiniAppController::class, 'topupWallet']);

// Telegram Webhook
$router->post('/webhook', [WebhookController::class, 'handle']);

// Dispatch request
$router->dispatch($request);
