<?php

/**
 * Web Installer for VPN Master Bot (cPanel & VPS)
 * Pure PHP 8.1+ (Zero SSH, Zero Composer required)
 */

session_start();

$rootDir = __DIR__;
$lockFile = $rootDir . '/database/installed.lock';

// 1. Check if already installed
if (file_exists($lockFile)) {
    ?>
    <!DOCTYPE html>
    <html lang="fa" dir="rtl">
    <head>
        <meta charset="UTF-8">
        <title>سیستم قبلاً نصب شده است</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet" type="text/css" />
        <style>body { font-family: 'Vazirmatn', sans-serif; }</style>
    </head>
    <body class="bg-slate-950 text-slate-100 min-h-screen flex items-center justify-center p-4">
        <div class="max-w-md w-full bg-slate-900 border border-slate-800 rounded-3xl p-8 text-center shadow-2xl space-y-4">
            <div class="w-16 h-16 bg-emerald-600/20 text-emerald-400 border border-emerald-500/30 rounded-2xl flex items-center justify-center mx-auto text-3xl">
                ✅
            </div>
            <h1 class="text-xl font-bold text-white">نرم‌افزار قبلاً نصب شده است</h1>
            <p class="text-xs text-slate-400 leading-relaxed">
                این سیستم قبلاً با موفقیت پیکربندی و راه‌اندازی شده است. جهت امنیت بیشتر، نصب‌کننده قفل است.
            </p>
            <div class="pt-4 flex flex-col gap-2">
                <a href="/admin/login" class="w-full py-3 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-xs font-medium transition shadow-lg shadow-blue-600/30">
                    ورود به پنل ادمین
                </a>
                <span class="text-[10px] text-slate-500 mt-2">
                    جهت نصب مجدد، فایل <code>database/installed.lock</code> را از هاست خود حذف کنید.
                </span>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// 2. Server Requirements Check
$requirements = [
    'PHP Version >= 8.1' => version_compare(PHP_VERSION, '8.1.0', '>='),
    'cURL Extension' => extension_loaded('curl'),
    'PDO Extension' => extension_loaded('pdo'),
    'PDO SQLite Extension' => extension_loaded('pdo_sqlite'),
    'PDO MySQL Extension' => extension_loaded('pdo_mysql'),
    'OpenSSL Extension' => extension_loaded('openssl'),
    'MBString Extension' => extension_loaded('mbstring'),
    'JSON Extension' => extension_loaded('json'),
    'Database Directory Writable' => is_writable($rootDir . '/database') || is_writable($rootDir),
    'Config Directory Writable' => is_writable($rootDir . '/config'),
];

$allRequiredPassed = $requirements['PHP Version >= 8.1'] &&
                     $requirements['cURL Extension'] &&
                     $requirements['PDO Extension'] &&
                     ($requirements['PDO SQLite Extension'] || $requirements['PDO MySQL Extension']) &&
                     $requirements['OpenSSL Extension'] &&
                     $requirements['MBString Extension'];

// 3. Process Installation Form Submission
$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbDriver = $_POST['db_driver'] ?? 'sqlite';
    $botToken = trim($_POST['bot_token'] ?? '');
    $adminTelegramId = trim($_POST['admin_telegram_id'] ?? '');
    $adminUsername = trim($_POST['admin_username'] ?? 'admin');
    $adminPassword = trim($_POST['admin_password'] ?? '');
    $adminName = trim($_POST['admin_name'] ?? 'مدیر ارشد');
    $autoWebhook = !empty($_POST['auto_webhook']);

    // MySQL details
    $mysqlHost = trim($_POST['mysql_host'] ?? '127.0.0.1');
    $mysqlPort = trim($_POST['mysql_port'] ?? '3306');
    $mysqlDatabase = trim($_POST['mysql_database'] ?? '');
    $mysqlUsername = trim($_POST['mysql_username'] ?? '');
    $mysqlPassword = $_POST['mysql_password'] ?? '';

    if (empty($adminUsername) || empty($adminPassword)) {
        $error = "لطفاً نام کاربری و رمز عبور ورود به پنل ادمین را وارد فرمایید.";
    } elseif (empty($botToken)) {
        $error = "وارد کردن توکن ربات تلگرام برای راه‌اندازی الزامی است.";
    } elseif (empty($adminTelegramId)) {
        $error = "وارد کردن شناسه عددی ادمین تلگرام جهت دریافت فیش‌ها الزامی است.";
    } else {
        try {
            $pdo = null;

            // 1. Establish PDO Connection
            if ($dbDriver === 'mysql') {
                if (empty($mysqlDatabase) || empty($mysqlUsername)) {
                    throw new Exception("لطفاً نام دیتابیس و نام کاربری MySQL را وارد کنید.");
                }
                $dsn = "mysql:host={$mysqlHost};port={$mysqlPort};dbname={$mysqlDatabase};charset=utf8mb4";
                $pdo = new PDO($dsn, $mysqlUsername, $mysqlPassword, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);

                // Update config/database.php
                $dbConfigContent = "<?php\n\nreturn [\n    'driver' => 'mysql',\n    'sqlite' => [\n        'database' => __DIR__ . '/../database/bot.db',\n    ],\n    'mysql' => [\n        'host' => '{$mysqlHost}',\n        'port' => {$mysqlPort},\n        'database' => '{$mysqlDatabase}',\n        'username' => '{$mysqlUsername}',\n        'password' => " . var_export($mysqlPassword, true) . ",\n        'charset' => 'utf8mb4',\n    ],\n];\n";
                file_put_contents($rootDir . '/config/database.php', $dbConfigContent);
            } else {
                $dbPath = $rootDir . '/database/bot.db';
                $pdo = new PDO("sqlite:" . $dbPath);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                $pdo->exec('PRAGMA journal_mode = WAL;');

                // Update config/database.php
                $dbConfigContent = "<?php\n\nreturn [\n    'driver' => 'sqlite',\n    'sqlite' => [\n        'database' => __DIR__ . '/../database/bot.db',\n    ],\n    'mysql' => [\n        'host' => '127.0.0.1',\n        'port' => 3306,\n        'database' => 'vpn_bot',\n        'username' => 'root',\n        'password' => '',\n        'charset' => 'utf8mb4',\n    ],\n];\n";
                file_put_contents($rootDir . '/config/database.php', $dbConfigContent);
            }

            // 2. Run Database Schema Migration
            $schemaFile = $rootDir . '/database/schema.sql';
            $sql = file_get_contents($schemaFile);

            if ($dbDriver === 'mysql') {
                try {
                    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;");
                    $pdo->exec("SET CHARACTER SET utf8mb4;");
                    $pdo->exec("SET collation_connection = 'utf8mb4_unicode_ci';");
                    $pdo->exec("SET SQL_MODE='';");
                    $pdo->exec("ALTER DATABASE `{$mysqlDatabase}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
                } catch (\Throwable $e) {}

                // Adapt SQLite syntax to MySQL
                $sql = str_replace('INTEGER PRIMARY KEY AUTOINCREMENT', 'INT PRIMARY KEY AUTO_INCREMENT', $sql);
                $sql = str_replace('DATETIME DEFAULT CURRENT_TIMESTAMP', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP', $sql);
                // MySQL 5.7/8.0 TEXT columns cannot have literal default values
                $sql = preg_replace('/TEXT\s+DEFAULT\s+[\'"][^\'"]*[\'"]/i', 'TEXT', $sql);
                // Strip any non-ASCII Persian/Arabic characters from DEFAULT values for MySQL compatibility
                $sql = preg_replace('/DEFAULT\s+[\'"][^\x00-\x7F]+[\'"]/u', '', $sql);
            }

            $statements = array_filter(array_map('trim', explode(';', $sql)));
            foreach ($statements as $stmt) {
                if (!empty($stmt)) {
                    if ($dbDriver === 'mysql' && stripos($stmt, 'CREATE TABLE') !== false) {
                        $stmt .= ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
                    }
                    try {
                        $pdo->exec($stmt);
                    } catch (\Throwable $ex) {
                        // If table already exists or minor syntax notice, log and proceed
                        error_log("Schema statement notice: " . $ex->getMessage());
                        if (!str_contains($ex->getMessage(), 'already exists')) {
                            throw $ex;
                        }
                    }
                }
            }

            // Ensure all tables and columns are converted to utf8mb4_unicode_ci
            if ($dbDriver === 'mysql') {
                $tables = ['settings', 'panels', 'plans', 'bank_cards', 'users', 'orders', 'services', 'admins', 'bot_buttons'];
                foreach ($tables as $t) {
                    try {
                        $pdo->exec("ALTER TABLE `{$t}` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    } catch (\Throwable $e) {}
                }
            }

            // Run column migrations in case tables already existed
            $migrations = [
                "ALTER TABLE services ADD COLUMN wireguard_conf TEXT",
                "ALTER TABLE panels ADD COLUMN custom_service_enabled INTEGER DEFAULT 0",
                "ALTER TABLE panels ADD COLUMN price_per_gb INTEGER DEFAULT 4000",
                "ALTER TABLE panels ADD COLUMN price_per_day INTEGER DEFAULT 1000",
                "ALTER TABLE panels ADD COLUMN min_gb INTEGER DEFAULT 5",
                "ALTER TABLE panels ADD COLUMN max_gb INTEGER DEFAULT 500",
                "ALTER TABLE panels ADD COLUMN min_days INTEGER DEFAULT 1",
                "ALTER TABLE panels ADD COLUMN max_days INTEGER DEFAULT 365",
                "ALTER TABLE panels ADD COLUMN categories_json TEXT",
                "ALTER TABLE plans ADD COLUMN category VARCHAR(100)",
                "ALTER TABLE plans ADD COLUMN ip_limit INTEGER NOT NULL DEFAULT 1",
                "ALTER TABLE plans ADD COLUMN hwid_limit INTEGER NOT NULL DEFAULT 0",
                "ALTER TABLE services ADD COLUMN ip_limit INTEGER NOT NULL DEFAULT 1",
                "ALTER TABLE services ADD COLUMN hwid_limit INTEGER NOT NULL DEFAULT 0",
                "ALTER TABLE users ADD COLUMN is_admin INTEGER NOT NULL DEFAULT 0",
                "ALTER TABLE admins ADD COLUMN telegram_id BIGINT DEFAULT NULL",
            ];
            foreach ($migrations as $mSql) {
                try {
                    $pdo->exec($mSql);
                } catch (\Throwable $e) {}
            }

            // 3. Seed Settings
            $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
            $domain = $_SERVER['HTTP_HOST'] ?? '127.0.0.1';
            $miniAppUrl = $proto . $domain . '/miniapp';

            $settings = [
                'bot_token' => $botToken,
                'admin_telegram_id' => $adminTelegramId,
                'miniapp_url' => $miniAppUrl,
                'admin_username' => $adminUsername,
                'admin_password' => password_hash($adminPassword, PASSWORD_BCRYPT),
                'welcome_msg' => "سلام %first_name% عزیز! 🌟\nبه ربات هوشمند ارائه سرویس‌های پرسرعت اینترنت آزاد خوش آمدید.\nجهت شروع یکی از گزینه‌های زیر یا مینی‌اپ را انتخاب فرمایید:",
                'help_msg' => "📖 راهنمای اتصال:\nنرم‌افزار v2rayNG / Streisand را نصب کرده و لینک سابسکریپشن را اضافه کنید.",
                'support_text' => "جهت ارتباط با پشتیبانی، لطفا به آیدی ادمین پیام دهید.",
                'miniapp_primary_color' => '#2563eb',
                'miniapp_accent_color' => '#10b981',
                'miniapp_btn_text_color' => '#ffffff',
                'miniapp_btn_radius' => '12px',
            ];

            foreach ($settings as $k => $v) {
                if ($dbDriver === 'sqlite') {
                    $stmt = $pdo->prepare("INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON CONFLICT(`key`) DO UPDATE SET `value` = excluded.`value`");
                } else {
                    $stmt = $pdo->prepare("INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
                }
                $stmt->execute([$k, $v]);
            }

            // 4. Create or Update Super Admin Account
            $stmtAdmin = $pdo->prepare("SELECT id FROM admins WHERE username = ?");
            $stmtAdmin->execute([$adminUsername]);
            $existingAdminId = $stmtAdmin->fetchColumn();

            if ($existingAdminId) {
                $stmt = $pdo->prepare("UPDATE admins SET password = ?, name = ?, telegram_id = ?, is_active = 1 WHERE id = ?");
                $stmt->execute([
                    password_hash($adminPassword, PASSWORD_BCRYPT),
                    $adminName,
                    $adminTelegramId ?: null,
                    $existingAdminId
                ]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO admins (username, password, name, telegram_id, role, permissions, is_active) VALUES (?, ?, ?, ?, 'super_admin', ?, 1)");
                $stmt->execute([
                    $adminUsername,
                    password_hash($adminPassword, PASSWORD_BCRYPT),
                    $adminName,
                    $adminTelegramId ?: null,
                    json_encode(['*'])
                ]);
            }

            // 4.1 Register / Link Admin in users table (with 10,000,000 Tomans initial balance for immediate test purchasing)
            if (!empty($adminTelegramId)) {
                $stmtUserCheck = $pdo->prepare("SELECT id FROM users WHERE telegram_id = ?");
                $stmtUserCheck->execute([$adminTelegramId]);
                $existingUserId = $stmtUserCheck->fetchColumn();

                if ($existingUserId) {
                    $pdo->prepare("UPDATE users SET is_admin = 1, balance = GREATEST(balance, 10000000) WHERE id = ?")
                        ->execute([$existingUserId]);
                } else {
                    $stmtUserInsert = $pdo->prepare("INSERT INTO users (telegram_id, username, first_name, last_name, balance, step, step_data, is_admin) 
                        VALUES (?, ?, ?, '', 10000000, 'idle', '{}', 1)");
                    $stmtUserInsert->execute([
                        $adminTelegramId,
                        $adminUsername,
                        $adminName ?: 'مدیر کل'
                    ]);
                }
            }

            // 5. Seed Default Buttons
            $btnCount = $pdo->query("SELECT COUNT(*) FROM bot_buttons")->fetchColumn();
            if ($btnCount == 0) {
                $btnStmt = $pdo->prepare("INSERT INTO bot_buttons (`key`, title, row_num, order_in_row, is_active, color, text_color) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $defaultBtns = [
                    ['btn_miniapp', '🚀 ورود به مینی‌اپ', 1, 1, 1, '#3b82f6', '#ffffff'],
                    ['btn_buy', '🛍 خرید اشتراک جدید', 2, 1, 1, '#2563eb', '#ffffff'],
                    ['btn_my_services', '📱 سرویس‌های فعال من', 2, 2, 1, '#10b981', '#ffffff'],
                    ['btn_plans', '📋 تعرفه و پلن‌ها', 3, 1, 1, '#6366f1', '#ffffff'],
                    ['btn_wallet', '💳 کارت به کارت و شارژ', 3, 2, 1, '#f59e0b', '#ffffff'],
                    ['btn_help', '📖 راهنمای اتصال', 4, 1, 1, '#06b6d4', '#ffffff'],
                    ['btn_support', '📞 پشتیبانی تلگرام', 4, 2, 1, '#ec4899', '#ffffff'],
                    ['btn_test_account', '🎁 اکانت تست رایگان', 5, 1, 1, '#8b5cf6', '#ffffff'],
                ];
                foreach ($defaultBtns as $b) {
                    $btnStmt->execute($b);
                }
            }

            // 6. Seed Sample Bank Card if empty
            $cardCount = $pdo->query("SELECT COUNT(*) FROM bank_cards")->fetchColumn();
            if ($cardCount == 0) {
                $cardStmt = $pdo->prepare("INSERT INTO bank_cards (card_number, holder_name, bank_name, is_active) VALUES (?, ?, ?, 1)");
                $cardStmt->execute(['6037997412345678', 'مدیریت سرور', 'بانک ملی']);
            }

            // 7. Seed Sample Plans if empty
            $plansCount = $pdo->query("SELECT COUNT(*) FROM plans")->fetchColumn();
            if ($plansCount == 0) {
                $planStmt = $pdo->prepare("INSERT INTO plans (name, description, price, traffic_gb, days, ip_limit, panel_id, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?, 0, 1, ?)");
                $planStmt->execute(['پلن اقتصادی ۱ ماهه', '۳۰ گیگابایت - ۱ کاربره - سرور پرسرعت آلمان', 89000, 30, 30, 1, 1]);
                $planStmt->execute(['پلن نقره‌ای ۱ ماهه', '۶۰ گیگابایت - ۲ کاربره همزمان - پینگ پایین', 149000, 60, 30, 2, 2]);
                $planStmt->execute(['پلن طلایی ۳ ماهه', '۱۲۰ گیگابایت - ۲ کاربره همزمان - بدون قطعی', 279000, 120, 90, 2, 3]);
            }

            // 8. Auto-register Webhook if selected
            $webhookResultMsg = null;
            if ($autoWebhook && !empty($botToken)) {
                $webhookUrl = $proto . $domain . '/webhook.php';
                $tgApi = "https://api.telegram.org/bot{$botToken}/setWebhook?url=" . urlencode($webhookUrl);

                $ch = curl_init($tgApi);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                $resp = curl_exec($ch);
                curl_close($ch);

                $dec = json_decode($resp, true);
                if ($dec['ok'] ?? false) {
                    $webhookResultMsg = "وبهوک تلگرام با موفقیت روی آدرس $webhookUrl تنظیم شد!";
                } else {
                    $webhookResultMsg = "تنظیم خودکار وبهوک با خطا مواجه شد: " . ($dec['description'] ?? 'خطای تلگرام');
                }
            }

            // 9. Process Optional MirzaBot Migration File
            $migrationResultMsg = null;
            if (!empty($_FILES['mirza_db_file']['tmp_name']) && is_uploaded_file($_FILES['mirza_db_file']['tmp_name'])) {
                require_once __DIR__ . '/src/autoload.php';
                $migRes = \App\Services\MigrationService::migrateFromFile($_FILES['mirza_db_file']['tmp_name'], $pdo);
                if ($migRes['success']) {
                    $migrationResultMsg = $migRes['message'];
                } else {
                    $migrationResultMsg = 'هشدار در مهاجرت دیتابیس میرزا: ' . ($migRes['error'] ?? 'خطای ناشناخته');
                }
            }

            // 10. Lock installer
            file_put_contents($lockFile, date('Y-m-d H:i:s'));

            $success = true;
        } catch (Exception $e) {
            $error = "خطا در نصب: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نصب‌کننده خودکار VPN Bot Master</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet" type="text/css" />
    <style>body { font-family: 'Vazirmatn', sans-serif; }</style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen py-10 px-4">

    <div class="max-w-2xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-blue-600 rounded-2xl flex items-center justify-center mx-auto mb-4 text-3xl shadow-lg shadow-blue-500/30">
                ⚡
            </div>
            <h1 class="text-2xl font-bold text-white">نصب و راه‌اندازی ربات فروش VPN</h1>
            <p class="text-xs text-slate-400 mt-1">پشتیبانی از پنل سنایی (3x-ui)، پاسارگاد و هاست اشتراکی cPanel بدون نیاز به SSH</p>
        </div>

        <?php if ($success): ?>
            <!-- Installation Complete Success Card -->
            <div class="bg-slate-900 border border-emerald-500/40 rounded-3xl p-8 shadow-2xl text-center space-y-6">
                <div class="w-20 h-20 bg-emerald-500/20 text-emerald-400 rounded-full flex items-center justify-center mx-auto text-4xl border border-emerald-500/30 animate-bounce">
                    🎉
                </div>
                <div>
                    <h2 class="text-xl font-bold text-white">نصب با موفقیت کامل شد!</h2>
                    <p class="text-xs text-slate-400 mt-2 leading-relaxed">
                        جداول پایگاه داده ایجاد شدند، دسترسی مدیر کل ثبت شد و تنظیمات ربات با موفقیت ذخیره گردید.
                    </p>
                    <?php if (!empty($webhookResultMsg)): ?>
                        <div class="mt-4 p-3 rounded-xl bg-slate-950 border border-slate-800 text-xs text-emerald-400">
                            <?= htmlspecialchars($webhookResultMsg) ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($migrationResultMsg)): ?>
                        <div class="mt-3 p-3 rounded-xl bg-amber-500/10 border border-amber-500/30 text-xs text-amber-300">
                            🚀 <b>وضعیت مهاجرت:</b> <?= htmlspecialchars($migrationResultMsg) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800 text-xs text-right space-y-2">
                    <div class="flex justify-between py-1 border-b border-slate-900">
                        <span class="text-slate-400">نام کاربری ادمین:</span>
                        <span class="font-mono text-white"><?= htmlspecialchars($adminUsername) ?></span>
                    </div>
                    <div class="flex justify-between py-1">
                        <span class="text-slate-400">نوع دیتابیس:</span>
                        <span class="text-blue-400 uppercase"><?= htmlspecialchars($dbDriver) ?></span>
                    </div>
                </div>

                <div class="pt-2">
                    <a href="/admin/login" class="block w-full py-3.5 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-sm font-medium shadow-lg shadow-blue-600/30 transition">
                        🚀 ورود به وب پنل ادمین
                    </a>
                </div>
            </div>
        <?php else: ?>

            <?php if (!empty($error)): ?>
                <div class="mb-6 p-4 rounded-xl bg-rose-950/60 border border-rose-600/40 text-rose-200 text-xs flex items-center gap-2">
                    <span>❌</span>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <!-- System Requirements Accordion/Card -->
            <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-5 mb-6 text-xs">
                <div class="flex items-center justify-between mb-3">
                    <span class="font-bold text-white flex items-center gap-2"><span>🔍</span> بررسی پیش‌نیازهای سرور / هاست</span>
                    <span class="<?= $allRequiredPassed ? 'text-emerald-400' : 'text-rose-400' ?>">
                        <?= $allRequiredPassed ? 'همه پیش‌نیازها آماده است ✅' : 'برخی پیش‌نیازها غیرفعال است ⚠️' ?>
                    </span>
                </div>
                <div class="grid grid-cols-2 gap-2 text-[11px] text-slate-400">
                    <?php foreach ($requirements as $name => $status): ?>
                        <div class="flex items-center justify-between p-2 rounded-lg bg-slate-950/80 border border-slate-800/80">
                            <span><?= $name ?></span>
                            <span><?= $status ? '✅' : '❌' ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Install Form -->
            <form action="" method="POST" enctype="multipart/form-data" class="bg-slate-900 border border-slate-800 rounded-3xl p-6 md:p-8 shadow-2xl space-y-6">

                <!-- 1. Database Configuration -->
                <div class="space-y-4">
                    <div class="flex items-center gap-2 pb-2 border-b border-slate-800">
                        <span class="w-6 h-6 rounded-lg bg-blue-600 flex items-center justify-center text-xs font-bold text-white">۱</span>
                        <h3 class="font-bold text-white text-sm">پیکربندی پایگاه داده (Database)</h3>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-2">نوع پایگاه داده</label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="flex items-center gap-2 p-3 rounded-xl bg-slate-950 border border-slate-800 cursor-pointer hover:border-blue-500 transition">
                                <input type="radio" name="db_driver" value="sqlite" checked onclick="toggleDbFields('sqlite')" class="text-blue-600">
                                <div>
                                    <div class="text-xs font-bold text-white">SQLite (پیشنهادی)</div>
                                    <div class="text-[10px] text-slate-400">بدون نیاز به ساخت دیتابیس در cPanel</div>
                                </div>
                            </label>

                            <label class="flex items-center gap-2 p-3 rounded-xl bg-slate-950 border border-slate-800 cursor-pointer hover:border-blue-500 transition">
                                <input type="radio" name="db_driver" value="mysql" onclick="toggleDbFields('mysql')" class="text-blue-600">
                                <div>
                                    <div class="text-xs font-bold text-white">MySQL / MariaDB</div>
                                    <div class="text-[10px] text-slate-400">با دیتابیس اختصاصی در phpMyAdmin</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- MySQL Specific Fields -->
                    <div id="mysql_fields" class="hidden space-y-3 p-4 rounded-xl bg-slate-950 border border-slate-800/80">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] text-slate-400 mb-1">هاست MySQL</label>
                                <input type="text" name="mysql_host" value="127.0.0.1" class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white font-mono">
                            </div>
                            <div>
                                <label class="block text-[11px] text-slate-400 mb-1">پورت</label>
                                <input type="text" name="mysql_port" value="3306" class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white font-mono">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] text-slate-400 mb-1">نام دیتابیس</label>
                                <input type="text" name="mysql_database" placeholder="cpanel_vpn" class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white font-mono">
                            </div>
                            <div>
                                <label class="block text-[11px] text-slate-400 mb-1">نام کاربری دیتابیس</label>
                                <input type="text" name="mysql_username" placeholder="cpanel_user" class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white font-mono">
                            </div>
                        </div>

                        <div>
                            <label class="block text-[11px] text-slate-400 mb-1">رمز عبور دیتابیس</label>
                            <input type="password" name="mysql_password" placeholder="••••••••" class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-2 text-xs text-white font-mono">
                        </div>
                    </div>
                </div>

                <!-- 2. Telegram Bot & Admin Telegram ID -->
                <div class="space-y-4">
                    <div class="flex items-center gap-2 pb-2 border-b border-slate-800">
                        <span class="w-6 h-6 rounded-lg bg-blue-600 flex items-center justify-center text-xs font-bold text-white">۲</span>
                        <h3 class="font-bold text-white text-sm">اطلاعات تلگرام (توکن و شناسه عددی ادمین)</h3>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1.5">توکن ربات تلگرام (Bot Token)</label>
                        <input type="text" name="bot_token" required placeholder="1234567890:ABCdefGhIJKlmNoPQRstuvWXyz" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-white text-xs font-mono focus:border-blue-500">
                        <span class="text-[10px] text-slate-500 mt-1 block">از ربات BotFather@ در تلگرام دریافت کنید.</span>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1.5">آیدی عددی تلگرام ادمین (Admin Telegram ID)</label>
                        <input type="text" name="admin_telegram_id" required placeholder="مثلاً: 123456789" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-white text-xs font-mono focus:border-blue-500">
                        <span class="text-[10px] text-slate-500 mt-1 block">شناسه عددی اکانت شما که فیش‌های واریزی و دکمه‌های تایید برای آن ارسال می‌شود (از ربات userinfobot@ بگیرید).</span>
                    </div>

                    <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 flex items-center gap-2">
                        <input type="checkbox" name="auto_webhook" id="auto_webhook" value="1" checked class="w-4 h-4 rounded text-blue-600 bg-slate-900 border-slate-700">
                        <label for="auto_webhook" class="text-xs text-emerald-400 font-medium cursor-pointer">
                            فعال‌سازی خودکار وبهوک در تلگرام پس از نصب (مناسب cPanel بدون SSH)
                        </label>
                    </div>
                </div>

                <!-- 3. Web Admin Credentials -->
                <div class="space-y-4">
                    <div class="flex items-center gap-2 pb-2 border-b border-slate-800">
                        <span class="w-6 h-6 rounded-lg bg-blue-600 flex items-center justify-center text-xs font-bold text-white">۳</span>
                        <h3 class="font-bold text-white text-sm">اطلاعات ورود به وب پنل ادمین</h3>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1.5">نام مدیر</label>
                            <input type="text" name="admin_name" value="مدیر ارشد" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-white text-xs">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1.5">نام کاربری ورود</label>
                            <input type="text" name="admin_username" value="admin" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-white text-xs font-mono">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1.5">رمز عبور ادمین</label>
                            <input type="password" name="admin_password" value="admin123" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-white text-xs">
                        </div>
                    </div>
                </div>

                <!-- 4. MirzaBot Migration (Optional) -->
                <div class="space-y-4">
                    <div class="flex items-center gap-2 pb-2 border-b border-slate-800">
                        <span class="w-6 h-6 rounded-lg bg-amber-600 flex items-center justify-center text-xs font-bold text-white">۴</span>
                        <h3 class="font-bold text-white text-sm">مهاجرت از دیتابیس ربات میرزا (اختیاری)</h3>
                    </div>
                    <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 space-y-3">
                        <p class="text-xs text-slate-400 leading-relaxed">
                            در صورتی که قبلاً از <b>ربات میرزا (MirzaBot)</b> استفاده می‌کردید، فایل خروجی دیتابیس آن (فایل <code>.sql</code> یا <code>.sqlite</code>) را انتخاب کنید تا تمام <b>کاربران، موجودی کیف پول‌ها، پنل‌ها، پلن‌ها، سرویس‌های فعال و سیستم استعلام‌گیری</b> بدون نقص به سیستم جدید منتقل شوند.
                        </p>
                        <div>
                            <label class="block text-[11px] text-slate-400 mb-1">انتخاب فایل دیتابیس میرزا (.sql یا .sqlite)</label>
                            <input type="file" name="mirza_db_file" accept=".sql,.sqlite,.db" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-2 text-xs text-slate-300 file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:bg-blue-600 file:text-white hover:file:bg-blue-500 cursor-pointer">
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="pt-4 border-t border-slate-800">
                    <button type="submit" class="w-full py-3.5 bg-blue-600 hover:bg-blue-500 text-white rounded-xl text-sm font-medium shadow-lg shadow-blue-600/30 transition flex items-center justify-center gap-2">
                        <span>⚡</span> شروع نصب و راه‌اندازی سیستم
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <script>
    function toggleDbFields(driver) {
        const fields = document.getElementById('mysql_fields');
        if (driver === 'mysql') {
            fields.classList.remove('hidden');
        } else {
            fields.classList.add('hidden');
        }
    }
    </script>
</body>
</html>
