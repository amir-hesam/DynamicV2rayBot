<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Core\Database;
use App\Models\Setting;
use App\Telegram\Bot;
use App\Telegram\Handler;

echo "========================================\n";
echo "   ⚡ VPN Telegram Bot Worker (PHP)     \n";
echo "   Sanaei (3x-ui) & Pasargad Panels    \n";
echo "========================================\n\n";

Database::initSchema();

$token = Setting::get('bot_token');
if (empty($token)) {
    echo "❌ خطا: توکن ربات تلگرام در تنظیمات ثبت نشده است!\n";
    echo "لطفاً ابتدا به وب پنل ادمین (http://localhost:8000/admin) مراجعه کرده و توکن را وارد کنید، یا دستور زیر را اجرا نمایید:\n";
    echo "php cli.php set-token <YOUR_TOKEN>\n\n";
    exit(1);
}

$bot = new Bot($token);
$me = $bot->getMe();

if (!($me['ok'] ?? false)) {
    echo "❌ خطا در اتصال به تلگرام: " . ($me['description'] ?? 'نامشخص') . "\n";
    echo "توکن بررسی شود: " . substr($token, 0, 10) . "...\n";
    exit(1);
}

$botUsername = $me['result']['username'] ?? '';
Setting::set('bot_username', $botUsername);

echo "✅ با موفقیت به ربات @{$botUsername} متصل شدید!\n";
echo "🔄 در حال حذف وبهوک قبلی جهت فعال‌سازی Long Polling...\n";
$bot->deleteWebhook();

echo "🚀 ربات آماده دریافت پیام‌ها و سفارشات کاربران است (برای توقف Ctrl+C بزنید)...\n\n";

$handler = new Handler($bot);
$offset = 0;

while (true) {
    try {
        $updates = $bot->getUpdates($offset, 50, 25);

        if (($updates['ok'] ?? false) && !empty($updates['result'])) {
            foreach ($updates['result'] as $up) {
                $offset = $up['update_id'] + 1;
                $handler->handleUpdate($up);
            }
        }
    } catch (\Throwable $e) {
        echo "⚠️ خطا: " . $e->getMessage() . "\n";
        sleep(2);
    }

    usleep(100000); // 100ms
}
