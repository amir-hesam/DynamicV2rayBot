<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Core\Database;
use App\Models\Card;
use App\Models\Panel;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\User;
use App\Panels\PanelManager;
use App\Telegram\Bot;

$cmd = $argv[1] ?? 'help';

Database::initSchema();

switch ($cmd) {
    case 'init':
        echo "✅ پایگاه داده و جداول با موفقیت مقداردهی اولیه شدند.\n";
        break;

    case 'set-token':
        $token = $argv[2] ?? '';
        if (empty($token)) {
            echo "❌ لطفا توکن را وارد کنید: php cli.php set-token <TOKEN>\n";
            exit(1);
        }
        Setting::set('bot_token', $token);
        echo "✅ توکن ربات با موفقیت ذخیره شد.\n";
        break;

    case 'set-admin':
        $adminId = $argv[2] ?? '';
        if (empty($adminId)) {
            echo "❌ لطفا شناسه عددی را وارد کنید: php cli.php set-admin <TELEGRAM_ID>\n";
            exit(1);
        }
        Setting::set('admin_telegram_id', $adminId);
        echo "✅ شناسه ادمین تلگرام تنظیم شد: $adminId\n";
        break;

    case 'set-webhook':
        $url = $argv[2] ?? '';
        if (empty($url)) {
            echo "❌ لطفا آدرس را وارد کنید: php cli.php set-webhook https://yourdomain.com/webhook\n";
            exit(1);
        }
        $bot = new Bot();
        $res = $bot->setWebhook($url);
        echo "نتیجه ثبت وبهوک: " . json_encode($res, JSON_UNESCAPED_UNICODE) . "\n";
        break;

    case 'reset-admin':
        Setting::set('admin_username', 'admin');
        Setting::set('admin_password', password_hash('admin123', PASSWORD_BCRYPT));
        echo "✅ مشخصات وب پنل به admin / admin123 بازنشانی شد.\n";
        break;

    case 'test-panels':
        $panels = Panel::all();
        if (empty($panels)) {
            echo "هیچ پنلی ثبت نشده است.\n";
            exit;
        }
        echo "🔍 در حال تست " . count($panels) . " پنل...\n";
        foreach ($panels as $p) {
            echo "--- {$p['name']} ({$p['type']}) ---\n";
            $adapter = PanelManager::make($p);
            $res = $adapter->testConnection();
            echo ($res['success'] ? "✅ " : "❌ ") . $res['message'] . "\n\n";
        }
        break;

    case 'status':
        echo "📊 وضعیت ربات:\n";
        echo "- توکن ربات: " . (Setting::get('bot_token') ? 'تنظیم شده' : 'خالی') . "\n";
        echo "- آیدی ادمین: " . (Setting::get('admin_telegram_id') ?: 'تنظیم نشده') . "\n";
        echo "- پنل‌ها: " . count(Panel::all()) . "\n";
        echo "- پلن‌ها: " . count(Plan::all()) . "\n";
        echo "- کارت‌های بانکی: " . count(Card::all()) . "\n";
        echo "- کاربران: " . count(User::all()) . "\n";
        break;

    default:
        echo "دستورات موجود:\n";
        echo "  php cli.php init             - مقداردهی اولیه دیتابیس\n";
        echo "  php cli.php set-token <T>    - تنظیم توکن ربات تلگرام\n";
        echo "  php cli.php set-admin <ID>   - تنظیم شناسه عددی ادمین\n";
        echo "  php cli.php set-webhook <URL>- فعال‌سازی وبهوک\n";
        echo "  php cli.php reset-admin      - بازنشانی رمز ادمین به admin / admin123\n";
        echo "  php cli.php test-panels      - تست اتصال تمام پنل‌های سنایی و پاسارگاد\n";
        echo "  php cli.php status           - مشاهده وضعیت و آمار کلی\n";
        break;
}
