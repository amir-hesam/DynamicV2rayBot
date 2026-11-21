<?php

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;
    private static bool $schemaChecked = false;

    /**
     * Bump this whenever migrations or default seeds change, so existing
     * installations re-run initSchema() work exactly once after an upgrade.
     * (Keeps the per-request overhead on shared hosting to a single SELECT.)
     */
    public const SCHEMA_VERSION = '2';

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $config = require __DIR__ . '/../../config/database.php';
            $driver = $config['driver'] ?? 'sqlite';

            try {
                if ($driver === 'sqlite') {
                    $dbPath = $config['sqlite']['database'];
                    $dir = dirname($dbPath);
                    if (!is_dir($dir)) {
                        mkdir($dir, 0777, true);
                    }
                    self::$instance = new PDO("sqlite:" . $dbPath);
                    self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                    self::$instance->exec('PRAGMA journal_mode = WAL;');
                } else {
                    $m = $config['mysql'];
                    $charset = $m['charset'] ?? 'utf8mb4';
                    $dsn = "mysql:host={$m['host']};port={$m['port']};dbname={$m['database']};charset={$charset}";
                    self::$instance = new PDO($dsn, $m['username'], $m['password'], [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ]);
                    try {
                        self::$instance->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;");
                        self::$instance->exec("SET CHARACTER SET utf8mb4;");
                        self::$instance->exec("SET collation_connection = 'utf8mb4_unicode_ci';");
                        self::$instance->exec("SET SQL_MODE='';");
                    } catch (\Throwable $e) {}
                }
            } catch (PDOException $e) {
                die("Database connection failed: " . $e->getMessage());
            }
        }

        return self::$instance;
    }

    public static function initSchema(bool $force = false): void
    {
        if (self::$schemaChecked && !$force) {
            return;
        }
        self::$schemaChecked = true;

        $config = require __DIR__ . '/../../config/database.php';
        $driver = $config['driver'] ?? 'sqlite';
        $pdo = self::getInstance();

        // Fast path: schema already migrated to the current version → skip all work.
        // (Previously every single request re-ran ~16 failing ALTER TABLE statements
        // plus a full seed pass, which is very costly on shared hosting.)
        $currentVersion = null;
        try {
            $currentVersion = $pdo->query("SELECT `value` FROM settings WHERE `key` = 'schema_version'")->fetchColumn();
        } catch (\Throwable $e) {
            // settings table does not exist yet — fall through to full init
        }

        if (!$force
            && $currentVersion !== false
            && $currentVersion !== null
            && (string)$currentVersion === self::SCHEMA_VERSION
        ) {
            self::$schemaChecked = true;
            return;
        }

        $tablesExist = false;
        if (!$force) {
            try {
                // If tables already exist, skip table creation but still run column migrations
                $test = $pdo->query("SELECT 1 FROM settings LIMIT 1");
                if ($test !== false) {
                    $tablesExist = true;
                }
            } catch (\Throwable $e) {
                // Settings table does not exist, proceed with creation
            }
        }

        if (!$tablesExist) {
            if ($driver === 'mysql') {
                try {
                    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;");
                    $pdo->exec("SET CHARACTER SET utf8mb4;");
                    $pdo->exec("SET collation_connection = 'utf8mb4_unicode_ci';");
                    $pdo->exec("SET SQL_MODE='';");
                } catch (\Throwable $e) {}
            }

            $schema = file_get_contents(__DIR__ . '/../../database/schema.sql');

            if ($driver === 'mysql') {
                // Adapt SQLite syntax to MySQL
                $schema = str_replace('INTEGER PRIMARY KEY AUTOINCREMENT', 'INT PRIMARY KEY AUTO_INCREMENT', $schema);
                $schema = str_replace('DATETIME DEFAULT CURRENT_TIMESTAMP', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP', $schema);
                $schema = preg_replace('/TEXT\s+DEFAULT\s+[\'"][^\'"]*[\'"]/i', 'TEXT', $schema);
                $schema = preg_replace('/DEFAULT\s+[\'"][^\x00-\x7F]+[\'"]/u', '', $schema);
            }

            // array_filter() already dropped empty statements
            $statements = array_filter(array_map('trim', explode(';', $schema)));

            foreach ($statements as $stmt) {
                if ($driver === 'mysql' && stripos($stmt, 'CREATE TABLE') !== false) {
                    $stmt .= ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
                }
                try {
                    $pdo->exec($stmt);
                } catch (\Throwable $e) {
                    // Ignore if table already exists or minor syntax warning
                }
            }
        }

        // Migrate columns if missing
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
            "ALTER TABLE panels ADD COLUMN linksubx TEXT DEFAULT NULL",
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
            } catch (\Throwable $e) {
                // Ignore if column already exists
            }
        }

        // Convert all existing tables and columns to utf8mb4_unicode_ci for MySQL
        if ($driver === 'mysql') {
            $tables = ['settings', 'panels', 'plans', 'bank_cards', 'users', 'orders', 'services', 'admins', 'bot_buttons'];
            foreach ($tables as $t) {
                try {
                    $pdo->exec("ALTER TABLE `{$t}` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                } catch (\Throwable $e) {}
            }
        }

        // Initialize default settings if not exists
        try {
            self::seedDefaults();
        } catch (\Throwable $e) {}

        // Persist schema version so the next request takes the fast path
        try {
            $driverName = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driverName === 'sqlite') {
                $stmt = $pdo->prepare("INSERT INTO settings (`key`, `value`) VALUES ('schema_version', ?) ON CONFLICT(`key`) DO UPDATE SET `value` = excluded.`value`");
            } else {
                $stmt = $pdo->prepare("INSERT INTO settings (`key`, `value`) VALUES ('schema_version', ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
            }
            $stmt->execute([self::SCHEMA_VERSION]);
        } catch (\Throwable $e) {
            // Non-critical: will retry on next request
        }
    }

    private static function seedDefaults(): void
    {
        $pdo = self::getInstance();

        $defaultSettings = [
            'bot_token' => '',
            'bot_username' => '',
            'admin_telegram_id' => '',
            'miniapp_url' => '',
            'admin_username' => 'admin',
            'admin_password' => password_hash('admin123', PASSWORD_BCRYPT),

            // Secret key protecting /cron/backup.php and /cron/backup endpoints.
            // Generated once per installation; shown in the admin panel.
            'cron_secret_key' => bin2hex(random_bytes(16)),

            // Customizable Button Texts
            'btn_buy' => '🛍 خرید اشتراک جدید',
            'btn_my_services' => '📱 سرویس‌های فعال من',
            'btn_plans' => '📋 تعرفه و پلن‌ها',
            'btn_wallet' => '💳 کارت به کارت و شارژ',
            'btn_help' => '📖 راهنمای اتصال',
            'btn_support' => '📞 پشتیبانی تلگرام',
            'btn_test_account' => '🎁 اکانت تست رایگان',
            'btn_miniapp' => '🚀 ورود به مینی‌اپ',

            // Customizable Mini App & Buttons Colors
            'miniapp_primary_color' => '#2563eb', // Royal Blue
            'miniapp_accent_color' => '#10b981',  // Emerald Green
            'miniapp_btn_text_color' => '#ffffff',
            'miniapp_btn_radius' => '12px',
            'miniapp_theme_mode' => 'dark',

            // System Texts
            'welcome_msg' => "سلام %first_name% عزیز! 🌟\nبه ربات هوشمند ارائه سرویس‌های پرسرعت اینترنت آزاد خوش آمدید.\nجهت شروع یکی از گزینه‌های زیر یا مینی‌اپ را انتخاب فرمایید:",
            'help_msg' => "📖 راهنمای اتصال به سرویس‌ها:\n\n۱. برای اندروید نرم‌افزار v2rayNG یا v2rayN یا Hiddify را نصب کنید.\n۲. برای iOS نرم‌افزار v2box یا Streisand یا Foxray را نصب کنید.\n۳. برای ویندوز نرم‌افزار v2rayN یا Nekoray را دانلود کنید.\n\nپس از خرید اشتراک، کافیست لینک اتصال یا QR Code داده شده را در نرم‌افزار ایمپورت نمایید.",
            'support_text' => "جهت ارتباط با پشتیبانی، لطفا به آیدی زیر پیام دهید:\n@your_support_id",
            'card_instruction' => "💳 شماره کارت فعال جهت واریز:\nشماره کارت: %card_number%\nبه نام: %holder_name%\nبانک: %bank_name%\n\nلطفا پس از واریز مبلغ %amount% تومان، تصویر رسید پرداختی خود را به عنوان عکس در ربات بفرستید.",
        ];

        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $insertIgnoreSql = ($driver === 'sqlite') 
            ? "INSERT OR IGNORE INTO settings (`key`, `value`) VALUES (:key, :value)"
            : "INSERT IGNORE INTO settings (`key`, `value`) VALUES (:key, :value)";
        $stmt = $pdo->prepare($insertIgnoreSql);
        foreach ($defaultSettings as $key => $val) {
            $stmt->execute(['key' => $key, 'value' => $val]);
        }

        // Seed sample card if table is empty
        $count = $pdo->query("SELECT COUNT(*) FROM bank_cards")->fetchColumn();
        if ($count == 0) {
            $cardStmt = $pdo->prepare("INSERT INTO bank_cards (card_number, holder_name, bank_name, is_active) VALUES (?, ?, ?, 1)");
            $cardStmt->execute(['6037997412345678', 'مدیریت سرور', 'بانک ملی']);
        }

        // Seed sample plans if empty
        $countPlans = $pdo->query("SELECT COUNT(*) FROM plans")->fetchColumn();
        if ($countPlans == 0) {
            $planStmt = $pdo->prepare("INSERT INTO plans (name, description, price, traffic_gb, days, ip_limit, panel_id, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?, 0, 1, ?)");
            $planStmt->execute(['پلن اقتصادی ۱ ماهه', '۳۰ گیگابایت - ۱ کاربره - سرور پرسرعت آلمان', 89000, 30, 30, 1, 1]);
            $planStmt->execute(['پلن نقره‌ای ۱ ماهه', '۶۰ گیگابایت - ۲ کاربره همزمان - پینگ پایین', 149000, 60, 30, 2, 2]);
            $planStmt->execute(['پلن طلایی ۳ ماهه', '۱۲۰ گیگابایت - ۲ کاربره همزمان - بدون قطعی', 279000, 120, 90, 2, 3]);
        }

        // Seed default super admin in admins table
        $countAdmins = $pdo->query("SELECT COUNT(*) FROM admins")->fetchColumn();
        if ($countAdmins == 0) {
            $adminStmt = $pdo->prepare("INSERT INTO admins (username, password, name, role, permissions, is_active) VALUES (?, ?, ?, ?, ?, 1)");
            $adminStmt->execute([
                'admin',
                password_hash('admin123', PASSWORD_BCRYPT),
                'مدیر ارشد',
                'super_admin',
                json_encode(['*'])
            ]);
        }

        // Seed default bot buttons with positions and colors
        $countBtns = $pdo->query("SELECT COUNT(*) FROM bot_buttons")->fetchColumn();
        if ($countBtns == 0) {
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
    }
}
