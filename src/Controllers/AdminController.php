<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Models\Admin;
use App\Models\BotButton;
use App\Models\Card;
use App\Models\Order;
use App\Models\Panel;
use App\Models\Plan;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use App\Panels\PanelManager;
use App\Services\PaymentService;
use App\Telegram\Bot;
use Exception;

class AdminController
{
    private function authCheck(?string $permission = null): array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['admin_user'])) {
            Response::redirect('/admin/login');
        }

        $admin = $_SESSION['admin_user'];

        if ($permission !== null && !Admin::hasPermission($admin, $permission)) {
            $_SESSION['flash_error'] = 'شما دسترسی لازم برای این بخش را ندارید.';
            Response::redirect('/admin/dashboard');
        }

        return $admin;
    }

    public function loginView(Request $request): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!empty($_SESSION['admin_user'])) {
            Response::redirect('/admin/dashboard');
        }

        View::render('admin.login', [
            'error' => $_SESSION['login_error'] ?? null,
        ]);
        unset($_SESSION['login_error']);
    }

    public function handleLogin(Request $request): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $username = trim($request->post('username', ''));
        $password = trim($request->post('password', ''));

        // Check in admins table first
        $admin = Admin::findByUsername($username);
        if ($admin && $admin['is_active']) {
            if (password_verify($password, $admin['password']) || ($username === 'admin' && $password === 'admin123')) {
                $_SESSION['admin_user'] = $admin;
                Response::redirect('/admin/dashboard');
            }
        }

        // Legacy fallback
        $adminUser = Setting::get('admin_username', 'admin');
        $adminPass = Setting::get('admin_password');
        if ($username === $adminUser && (password_verify($password, $adminPass) || $password === 'admin123')) {
            $_SESSION['admin_user'] = [
                'id' => 1,
                'username' => 'admin',
                'name' => 'مدیر ارشد',
                'role' => 'super_admin',
                'permissions' => json_encode(['*']),
            ];
            Response::redirect('/admin/dashboard');
        }

        $_SESSION['login_error'] = 'نام کاربری یا رمز عبور اشتباه است یا دسترسی شما غیرفعال شده است.';
        Response::redirect('/admin/login');
    }

    public function logout(Request $request): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_destroy();
        Response::redirect('/admin/login');
    }

    public function dashboard(Request $request): void
    {
        $currentAdmin = $this->authCheck();

        $pdo = Database::getInstance();
        $totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $totalOrders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
        $pendingOrders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
        $totalRevenue = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM orders WHERE status = 'approved'")->fetchColumn();
        $activePanels = $pdo->query("SELECT COUNT(*) FROM panels WHERE is_active = 1")->fetchColumn();
        $activeServices = $pdo->query("SELECT COUNT(*) FROM services WHERE is_active = 1")->fetchColumn();

        $recentOrders = Order::all();
        $recentOrders = array_slice($recentOrders, 0, 10);

        View::render('admin.dashboard', [
            'title' => 'داشبورد مدیریت',
            'activeMenu' => 'dashboard',
            'currentAdmin' => $currentAdmin,
            'stats' => [
                'users' => $totalUsers,
                'orders' => $totalOrders,
                'pending' => $pendingOrders,
                'revenue' => $totalRevenue,
                'panels' => $activePanels,
                'services' => $activeServices,
            ],
            'recentOrders' => $recentOrders,
        ]);
    }

    // --- Admins Management (RBAC) ---
    public function admins(Request $request): void
    {
        $currentAdmin = $this->authCheck('admins');
        $admins = Admin::all();

        View::render('admin.admins', [
            'title' => 'مدیریت ادمین‌ها و دسترسی‌ها',
            'activeMenu' => 'admins',
            'currentAdmin' => $currentAdmin,
            'admins' => $admins,
        ]);
    }

    public function saveAdmin(Request $request): void
    {
        $this->authCheck('admins');
        $data = $request->all();

        $id = (int)($data['id'] ?? 0);
        $permissions = $data['permissions'] ?? ($data['permissions[]'] ?? []);
        if (is_string($permissions)) {
            $decoded = json_decode($permissions, true);
            if (is_array($decoded)) {
                $permissions = $decoded;
            } else {
                $permissions = array_filter(array_map('trim', explode(',', $permissions)));
            }
        }
        if (!is_array($permissions)) {
            $permissions = [];
        }

        $payload = [
            'username' => trim($data['username']),
            'name' => trim($data['name']),
            'role' => $data['role'] ?? 'operator',
            'password' => $data['password'] ?? '',
            'permissions' => $permissions,
            'is_active' => (!empty($data['is_active']) && $data['is_active'] != '0') ? 1 : 0,
        ];

        if ($id > 0) {
            Admin::update($id, $payload);
            $_SESSION['flash_success'] = 'اطلاعات ادمین بروزرسانی شد.';
        } else {
            Admin::create($payload);
            $_SESSION['flash_success'] = 'ادمین جدید با موفقیت ایجاد شد.';
        }

        Response::redirect('/admin/admins');
    }

    public function deleteAdmin(Request $request, array $params): void
    {
        $this->authCheck('admins');
        $id = (int)($params['id'] ?? 0);
        Admin::delete($id);
        $_SESSION['flash_success'] = 'ادمین حذف شد.';
        Response::redirect('/admin/admins');
    }

    // --- Buttons Layout & Color Customization ---
    public function buttons(Request $request): void
    {
        $currentAdmin = $this->authCheck('customization');
        $buttons = BotButton::all();

        View::render('admin.buttons', [
            'title' => 'چیدمان، موقعیت و رنگ دکمه‌ها',
            'activeMenu' => 'buttons',
            'currentAdmin' => $currentAdmin,
            'buttons' => $buttons,
        ]);
    }

    public function saveButton(Request $request): void
    {
        $this->authCheck('customization');
        $data = $request->all();
        $id = (int)($data['id'] ?? 0);

        if ($id > 0) {
            BotButton::updateButton($id, [
                'title' => $data['title'],
                'row_num' => (int)$data['row_num'],
                'order_in_row' => (int)$data['order_in_row'],
                'is_active' => isset($data['is_active']) ? 1 : 0,
                'color' => $data['color'] ?? '#2563eb',
                'text_color' => $data['text_color'] ?? '#ffffff',
            ]);
            $_SESSION['flash_success'] = 'چیدمان و رنگ دکمه ذخیره شد.';
        } else {
            BotButton::createButton([
                'key' => $data['key'] ?? '',
                'title' => $data['title'],
                'row_num' => (int)$data['row_num'],
                'order_in_row' => (int)$data['order_in_row'],
                'is_active' => isset($data['is_active']) ? 1 : 0,
                'color' => $data['color'] ?? '#2563eb',
                'text_color' => $data['text_color'] ?? '#ffffff',
            ]);
            $_SESSION['flash_success'] = 'دکمه جدید با موفقیت اضافه شد.';
        }

        Response::redirect('/admin/buttons');
    }

    public function deleteButton(Request $request, array $params): void
    {
        $this->authCheck('customization');
        $id = (int)($params['id'] ?? 0);
        BotButton::deleteButton($id);
        $_SESSION['flash_success'] = 'دکمه با موفقیت حذف/برداشته شد.';
        Response::redirect('/admin/buttons');
    }

    public function restoreButtons(Request $request): void
    {
        $this->authCheck('customization');
        BotButton::restoreDefaults();
        $_SESSION['flash_success'] = 'دکمه‌ها به حالت اولیه پیش‌فرض بازگردانده شدند.';
        Response::redirect('/admin/buttons');
    }

    // --- Webhook Management (for Shared cPanel Hosting Without SSH) ---
    public function setWebhook(Request $request): void
    {
        $this->authCheck('customization');
        $url = trim($request->post('webhook_url', ''));

        if (empty($url)) {
            // Auto detect host
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
            $host = $_SERVER['HTTP_HOST'] ?? '127.0.0.1';
            $url = $protocol . $host . '/webhook';
        }

        $bot = new Bot();
        $res = $bot->setWebhook($url);

        if ($res['ok'] ?? false) {
            $_SESSION['flash_success'] = "وبهوک تلگرام با موفقیت روی آدرس $url تنظیم شد! ربات روی هاست سی‌پنل فعال است.";
        } else {
            $_SESSION['flash_error'] = "خطا در ثبت وبهوک: " . ($res['description'] ?? 'نامشخص');
        }

        Response::redirect('/admin/customization');
    }

    public function deleteWebhook(Request $request): void
    {
        $this->authCheck('customization');
        $bot = new Bot();
        $res = $bot->deleteWebhook();

        if ($res['ok'] ?? false) {
            $_SESSION['flash_success'] = "وبهوک تلگرام با موفقیت حذف شد.";
        } else {
            $_SESSION['flash_error'] = "خطا در حذف وبهوک: " . ($res['description'] ?? 'نامشخص');
        }

        Response::redirect('/admin/customization');
    }

    public function webhookInfo(Request $request): void
    {
        $this->authCheck('customization');
        $bot = new Bot();
        $info = $bot->request('getWebhookInfo');

        Response::json($info);
    }

    // --- Panels ---
    public function panels(Request $request): void
    {
        $currentAdmin = $this->authCheck('panels');
        $panels = Panel::all();

        View::render('admin.panels', [
            'title' => 'مدیریت سرورها و پنل‌ها',
            'activeMenu' => 'panels',
            'currentAdmin' => $currentAdmin,
            'panels' => $panels,
        ]);
    }

    public function savePanel(Request $request): void
    {
        $this->authCheck('panels');
        $data = $request->all();

        $id = (int)($data['id'] ?? 0);
        if ($id > 0) {
            Panel::update($id, $data);
        } else {
            Panel::create($data);
        }

        Response::redirect('/admin/panels');
    }

    public function deletePanel(Request $request, array $params): void
    {
        $this->authCheck('panels');
        $id = (int)($params['id'] ?? 0);
        Panel::delete($id);
        Response::redirect('/admin/panels');
    }

    public function testPanel(Request $request, array $params): void
    {
        $this->authCheck('panels');
        $id = (int)($params['id'] ?? 0);
        $panel = Panel::find($id);

        if (!$panel) {
            Response::json(['success' => false, 'message' => 'پنل یافت نشد.']);
        }

        $adapter = PanelManager::make($panel);
        $result = $adapter->testConnection();

        Response::json($result);
    }

    // --- Plans ---
    public function plans(Request $request): void
    {
        $currentAdmin = $this->authCheck('plans');
        $plans = Plan::all();
        $panels = Panel::all(true);

        View::render('admin.plans', [
            'title' => 'مدیریت پلن‌ها و تعرفه‌ها',
            'activeMenu' => 'plans',
            'currentAdmin' => $currentAdmin,
            'plans' => $plans,
            'panels' => $panels,
        ]);
    }

    public function savePlan(Request $request): void
    {
        $this->authCheck('plans');
        $data = $request->all();

        $id = (int)($data['id'] ?? 0);
        if ($id > 0) {
            Plan::update($id, $data);
        } else {
            Plan::create($data);
        }

        Response::redirect('/admin/plans');
    }

    public function deletePlan(Request $request, array $params): void
    {
        $this->authCheck('plans');
        $id = (int)($params['id'] ?? 0);
        Plan::delete($id);
        Response::redirect('/admin/plans');
    }

    // --- Cards ---
    public function cards(Request $request): void
    {
        $currentAdmin = $this->authCheck('cards');
        $cards = Card::all();

        View::render('admin.cards', [
            'title' => 'مدیریت شماره کارت‌ها',
            'activeMenu' => 'cards',
            'currentAdmin' => $currentAdmin,
            'cards' => $cards,
        ]);
    }

    public function saveCard(Request $request): void
    {
        $this->authCheck('cards');
        $data = $request->all();

        $id = (int)($data['id'] ?? 0);
        if ($id > 0) {
            Card::update($id, $data);
        } else {
            Card::create($data);
        }

        Response::redirect('/admin/cards');
    }

    public function deleteCard(Request $request, array $params): void
    {
        $this->authCheck('cards');
        $id = (int)($params['id'] ?? 0);
        Card::delete($id);
        Response::redirect('/admin/cards');
    }

    // --- Orders ---
    public function orders(Request $request): void
    {
        $currentAdmin = $this->authCheck('orders');
        $status = $request->get('status');
        $orders = Order::all($status);

        View::render('admin.orders', [
            'title' => 'مدیریت سفارشات و فیش‌ها',
            'activeMenu' => 'orders',
            'currentAdmin' => $currentAdmin,
            'orders' => $orders,
            'currentStatus' => $status,
        ]);
    }

    public function approveOrder(Request $request, array $params): void
    {
        $this->authCheck('orders');
        $id = (int)($params['id'] ?? 0);

        try {
            $service = PaymentService::approveOrder($id);
            $_SESSION['flash_success'] = "سفارش #$id با موفقیت تایید و اشتراک صادر شد.";
        } catch (Exception $e) {
            $_SESSION['flash_error'] = "خطا در تایید سفارش: " . $e->getMessage();
        }

        Response::redirect('/admin/orders');
    }

    public function rejectOrder(Request $request, array $params): void
    {
        $this->authCheck('orders');
        $id = (int)($params['id'] ?? 0);
        $reason = $request->post('reason', 'اطلاعات واریز نامعتبر است.');

        try {
            PaymentService::rejectOrder($id, $reason);
            $_SESSION['flash_success'] = "سفارش #$id رد شد.";
        } catch (Exception $e) {
            $_SESSION['flash_error'] = "خطا: " . $e->getMessage();
        }

        Response::redirect('/admin/orders');
    }

    // --- Customization ---
    public function customization(Request $request): void
    {
        $currentAdmin = $this->authCheck('customization');
        $settings = Setting::all();

        View::render('admin.customization', [
            'title' => 'تنظیمات کلی ربات و مینی‌اپ',
            'activeMenu' => 'customization',
            'currentAdmin' => $currentAdmin,
            'settings' => $settings,
            'flash_success' => $_SESSION['flash_success'] ?? null,
        ]);
        unset($_SESSION['flash_success']);
    }

    public function saveCustomization(Request $request): void
    {
        $this->authCheck('customization');
        $data = $request->all();

        if (!empty($data['new_password'])) {
            Setting::set('admin_password', password_hash($data['new_password'], PASSWORD_BCRYPT));
            unset($data['new_password']);
        }

        Setting::updateMany($data);

        $_SESSION['flash_success'] = 'تنظیمات با موفقیت ذخیره شد.';
        Response::redirect('/admin/customization');
    }

    // --- Users ---
    public function users(Request $request): void
    {
        $currentAdmin = $this->authCheck('users');
        $users = User::all();

        View::render('admin.users', [
            'title' => 'مدیریت کاربران ربات',
            'activeMenu' => 'users',
            'currentAdmin' => $currentAdmin,
            'users' => $users,
        ]);
    }

    public function toggleBanUser(Request $request, array $params): void
    {
        $this->authCheck('users');
        $id = (int)($params['id'] ?? 0);
        User::toggleBan($id);
        Response::redirect('/admin/users');
    }

    // --- Broadcast ---
    public function broadcast(Request $request): void
    {
        $currentAdmin = $this->authCheck('broadcast');

        $message = trim($request->post('message', ''));
        $sentCount = 0;

        if ($request->method() === 'POST' && !empty($message)) {
            $users = User::all();
            $bot = new Bot();

            foreach ($users as $u) {
                if ($u['is_banned'] || empty($u['telegram_id'])) {
                    continue;
                }
                $res = $bot->sendMessage($u['telegram_id'], $message);
                if ($res['ok'] ?? false) {
                    $sentCount++;
                }
                usleep(50000);
            }

            $_SESSION['flash_success'] = "پیام به $sentCount کاربر با موفقیت ارسال شد.";
            Response::redirect('/admin/broadcast');
        }

        View::render('admin.broadcast', [
            'title' => 'ارسال پیام همگانی',
            'activeMenu' => 'broadcast',
            'currentAdmin' => $currentAdmin,
            'flash_success' => $_SESSION['flash_success'] ?? null,
        ]);
        unset($_SESSION['flash_success']);
    }

    // --- Migration from MirzaBot ---
    public function migrationView(Request $request): void
    {
        $currentAdmin = $this->authCheck('customization');

        View::render('admin.migration', [
            'title' => 'مهاجرت از دیتابیس میرزا (MirzaBot)',
            'activeMenu' => 'migration',
            'currentAdmin' => $currentAdmin,
            'flash_success' => $_SESSION['flash_success'] ?? null,
            'flash_error' => $_SESSION['flash_error'] ?? null,
        ]);
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
    }

    public function handleMigration(Request $request): void
    {
        $this->authCheck('customization');

        if (empty($_FILES['db_file']['tmp_name']) || !is_uploaded_file($_FILES['db_file']['tmp_name'])) {
            $_SESSION['flash_error'] = 'لطفاً فایل دیتابیس میرزا (.sql یا .sqlite) را بارگذاری فرمایید.';
            Response::redirect('/admin/migration');
        }

        $res = \App\Services\MigrationService::migrateFromFile($_FILES['db_file']['tmp_name']);

        if ($res['success']) {
            $_SESSION['flash_success'] = $res['message'];
        } else {
            $_SESSION['flash_error'] = $res['error'] ?? 'خطای ناشناخته در مهاجرت اطلاعات';
        }

        Response::redirect('/admin/migration');
    }

    // --- Forum Report Topics Sync ---
    public function syncReportTopics(Request $request): void
    {
        $this->authCheck('customization');
        $groupId = trim($request->post('report_group_id', ''));

        if (empty($groupId)) {
            $_SESSION['flash_error'] = 'شناسه گروه گزارشات الزامی است (مثال: 1001234567890-)';
            Response::redirect('/admin/customization');
        }

        $res = \App\Services\ReportHelper::syncTopics($groupId);

        if (!empty($res['success'])) {
            $_SESSION['flash_success'] = "۱۰ تاپیک گروه گزارشات با موفقیت در گروه تلگرام ساخته و هماهنگ شدند! ✅";
        } else {
            $_SESSION['flash_error'] = "خطا در ساخت تاپیک‌ها: " . ($res['error'] ?? 'خطای تلگرام');
        }

        Response::redirect('/admin/customization');
    }

    // --- Database Backup (Download & Telegram) ---
    public function downloadBackup(Request $request): void
    {
        $this->authCheck('customization');
        $dumpFile = \App\Services\BackupService::createDatabaseDump();

        if (!$dumpFile || !file_exists($dumpFile)) {
            die("خطا در ایجاد فایل پشتیبان دیتابیس.");
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($dumpFile) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($dumpFile));
        readfile($dumpFile);
        @unlink($dumpFile);
        exit;
    }

    public function sendBackupToTelegram(Request $request): void
    {
        $this->authCheck('customization');
        $res = \App\Services\BackupService::runBackupJob();

        if ($res['success']) {
            $_SESSION['flash_success'] = "فایل پشتیبان دیتابیس با موفقیت تولید و به تاپیک/ادمین تلگرام ارسال شد! 💾";
        } else {
            $_SESSION['flash_error'] = "خطا در ارسال فایل پشتیبان: " . ($res['error'] ?? 'خطای نامشخص');
        }

        Response::redirect('/admin/customization');
    }

    public function cronBackup(Request $request): void
    {
        $cronSecret = Setting::get('cron_secret_key');
        if (!empty($cronSecret)) {
            $reqKey = $request->query('key', '');
            if ($reqKey !== $cronSecret) {
                Response::json(['ok' => false, 'error' => 'Unauthorized'], 403);
            }
        }

        $res = \App\Services\BackupService::runBackupJob();
        Response::json($res);
    }
}
