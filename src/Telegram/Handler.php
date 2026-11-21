<?php

namespace App\Telegram;

use App\Models\Card;
use App\Models\Order;
use App\Models\Plan;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use App\Services\PaymentService;
use Exception;

class Handler
{
    private Bot $bot;

    public function __construct(?Bot $bot = null)
    {
        $this->bot = $bot ?: new Bot();
    }

    public function handleUpdate(array $update): void
    {
        if (isset($update['message'])) {
            $this->handleMessage($update['message']);
        } elseif (isset($update['callback_query'])) {
            $this->handleCallbackQuery($update['callback_query']);
        }
    }

    private function handleMessage(array $message): void
    {
        $from = $message['from'] ?? null;
        if (!$from || ($from['is_bot'] ?? false)) {
            return;
        }

        $user = User::findOrCreate($from);
        if ($user['is_banned']) {
            $this->bot->sendMessage($from['id'], "🚫 دسترسی شما به این ربات مسدود شده است.");
            return;
        }

        // Must be resolved BEFORE any keyboard usage (cancel branch below uses it)
        $isAdmin = User::isAdmin($from['id']);

        $text = trim($message['text'] ?? '');
        $photo = $message['photo'] ?? null;
        $caption = trim($message['caption'] ?? '');

        // Cancel action
        if ($text === '❌ انصراف') {
            User::setStep($user['id'], 'idle');
            $this->bot->sendMessage($from['id'], "عملیات لغو شد.", Keyboard::mainMenu($isAdmin, $from['id']));
            return;
        }

        // Handle active step (like waiting for receipt photo or text)
        if ($user['step'] === 'waiting_receipt') {
            $this->handleReceiptSubmission($user, $message);
            return;
        }

        if ($user['step'] === 'waiting_report_group_id') {
            $groupId = trim($text);
            if (empty($groupId) || (!str_starts_with($groupId, '-') && !is_numeric($groupId))) {
                $this->bot->sendMessage($from['id'], "❌ شناسه گروه نامعتبر است. شناسه گروه باید با - شروع شود (مثال: <code>-1001234567890</code>). لطفاً مجدداً ارسال کنید یا لغو نمایید:", Keyboard::cancelKeyboard());
                return;
            }

            $this->bot->sendMessage($from['id'], "⏳ <b>در حال تست اتصال و ساخت خودکار ۱۰ تاپیک گزارشات در گروه...</b>");
            $res = \App\Services\ReportHelper::syncTopics($groupId);

            if (!empty($res['success'])) {
                User::setStep($user['id'], 'idle');
                $msg = "✅ <b>گروه گزارشات با موفقیت متصل و همگام شد!</b>\n\n";
                $msg .= "تعداد ۱۰ تاپیک تخصصی ایجاد شدند:\n";
                foreach ($res['topics'] as $t) {
                    $msg .= "• {$t['name']} (ID: <code>" . ($t['thread_id'] ?? 'فعال') . "</code>)\n";
                }
                $msg .= "\nاز این پس تمامی گزارش‌های خرید، تمدید، تراکنش مالی، تست، خطاها و بکاپ‌های دیتابیس مستقیماً در تاپیک‌های اختصاصی این گروه ثبت خواهند شد.";
                $this->bot->sendMessage($from['id'], $msg, Keyboard::mainMenu(true, $from['id']));
            } else {
                $this->bot->sendMessage($from['id'], "❌ <b>خطا در ساخت تاپیک‌ها:</b>\n" . ($res['error'] ?? 'خطای نامشخص') . "\n\nلطفاً از ادمین بودن ربات و روشن بودن گزینه Topics در تنظیمات گروه اطمینان حاصل کرده و مجدداً شناسه را بفرستید:", Keyboard::cancelKeyboard());
            }
            return;
        }

        // Waiting Inbound Client Name step (Smart inbounds assignment)
        if (str_starts_with($user['step'], 'waiting_inbound_client_')) {
            $panelId = (int)str_replace('waiting_inbound_client_', '', $user['step']);
            $panel = Panel::find($panelId);
            if (!$panel) {
                User::setStep($user['id'], 'idle');
                $this->bot->sendMessage($from['id'], "❌ سرور مورد نظر یافت نشد.", Keyboard::mainMenu($isAdmin, $from['id']));
                return;
            }

            $clientName = trim($text);
            if (empty($clientName)) {
                $this->bot->sendMessage($from['id'], "❌ لطفاً نام یا ایمیل کلاینت را ارسال فرمایید:", Keyboard::cancelKeyboard());
                return;
            }

            $this->bot->sendMessage($from['id'], "⏳ <b>در حال جستجو و استخراج اینباندهای متصل به کلاینت «" . htmlspecialchars($clientName) . "» در سرور...</b>");

            try {
                $adapter = \App\Panels\PanelManager::make($panel);
                $matched = $adapter->findInboundsForClient($clientName);

                if (empty($matched)) {
                    $msg = "❌ <b>کلاینتی با نام یا ایمیل «" . htmlspecialchars($clientName) . "» در این سرور یافت نشد!</b>\n\n";
                    $msg .= "لطفاً موارد زیر را بررسی فرمایید:\n";
                    $msg .= "۱. داخل پنل یک کلاینت با این نام ساخته و به اینباندهای دلخواه متصل نمایید.\n";
                    $msg .= "۲. دقت کنید نام وارد شده با نام کلاینت (Remark یا Email) دقیقاً مطابقت داشته باشد.\n\n";
                    $msg .= "لطفاً مجدداً <b>اسم کلاینت</b> را وارد کنید (یا دکمه انصراف را بزنید):";
                    $this->bot->sendMessage($from['id'], $msg, Keyboard::cancelKeyboard());
                    return;
                }

                $inboundIds = array_column($matched, 'id');
                Panel::updateInbounds($panelId, $inboundIds);
                User::setStep($user['id'], 'idle');

                $inboundListText = "";
                foreach ($matched as $m) {
                    $inboundListText .= "• <b>اینباند {$m['id']}:</b> " . htmlspecialchars($m['remark']) . " (" . strtoupper($m['protocol']) . ($m['port'] ? " - پورت {$m['port']}" : "") . ")\n";
                }

                $msg = "✅ <b>اینباندهای سرور «" . htmlspecialchars($panel['name']) . "» با موفقیت تنظیم شدند!</b>\n\n";
                $msg .= "👤 <b>کلاینت مرجع شناسایی‌شده:</b> <code>" . htmlspecialchars($clientName) . "</code>\n";
                $msg .= "📡 <b>اینباندهای متصل استخراج‌شده (" . count($matched) . " مورد):</b>\n" . $inboundListText . "\n";
                $msg .= "✨ <i>از این پس تمامی اکانت‌ها و کانفیگ‌هایی که روی این سرور ایجاد شوند، بر روی همین اینباندها ساخته خواهند شد.</i>\n";
                $msg .= "💡 <i>(حجم، تاریخ انقضا و محدودیت آی‌پی مربوط به هر کاربر به صورت مستقل و طبق پلن خریداری‌شده او اعمال خواهد شد.)</i>";

                $this->bot->sendMessage($from['id'], $msg, Keyboard::mainMenu($isAdmin, $from['id']));
            } catch (\Throwable $e) {
                $this->bot->sendMessage($from['id'], "❌ خطا در برقراری ارتباط با پنل: " . $e->getMessage(), Keyboard::cancelKeyboard());
            }
            return;
        }

        // Handle commands and customizable menu buttons
        $btnMap = [];
        foreach (\App\Models\BotButton::all() as $b) {
            $btnMap[$b['key']] = $b['title'];
        }

        $btnBuy = $btnMap['btn_buy'] ?? Setting::get('btn_buy', '🛍 خرید اشتراک جدید');
        $btnMyServices = $btnMap['btn_my_services'] ?? Setting::get('btn_my_services', '📱 سرویس‌های فعال من');
        $btnPlans = $btnMap['btn_plans'] ?? Setting::get('btn_plans', '📋 تعرفه و پلن‌ها');
        $btnWallet = $btnMap['btn_wallet'] ?? Setting::get('btn_wallet', '💳 کارت به کارت و شارژ');
        $btnHelp = $btnMap['btn_help'] ?? Setting::get('btn_help', '📖 راهنمای اتصال');
        $btnSupport = $btnMap['btn_support'] ?? Setting::get('btn_support', '📞 پشتیبانی تلگرام');
        $btnTest = $btnMap['btn_test_account'] ?? Setting::get('btn_test_account', '🎁 اکانت تست رایگان');


        if (str_starts_with($text, '/start')) {
            if ($isAdmin) {
                $welcome = "👑 <b>درود مدیر کل گرامی %first_name%!</b>\nبه ربات هوشمند مدیریت و فروش خوش آمدید.\nشما دسترسی کامل به مدیریت سیستم، آمار زنده و صدور تست سرویس‌ها دارید.";
            } else {
                $welcome = Setting::get('welcome_msg', "سلام %first_name% عزیز!\nبه ربات ما خوش آمدید.");
            }
            $welcome = str_replace(
                ['%first_name%', '%username%', '%user_id%'],
                [$user['first_name'] ?: 'کاربر', $user['username'] ?: '', $user['telegram_id']],
                $welcome
            );
            $this->bot->sendMessage($from['id'], $welcome, Keyboard::mainMenu($isAdmin, $from['id']));
            return;
        }

        if ($text === '/admin' || $text === '🛠 پنل مدیریت و آمار') {
            if (!$isAdmin) {
                $this->bot->sendMessage($from['id'], "🚫 شما دسترسی به بخش مدیریت ندارید.", Keyboard::mainMenu(false, $from['id']));
                return;
            }
            $this->showAdminDashboard($from['id']);
            return;
        }

        if ($text === $btnBuy || $text === $btnPlans || $text === '/plans') {
            $this->showPlans($from['id']);
            return;
        }

        if ($text === $btnMyServices || $text === '/services') {
            $this->showMyServices($user);
            return;
        }

        if ($text === $btnWallet) {
            $this->showWalletAndCards($from['id']);
            return;
        }

        if ($text === $btnHelp || $text === '/help') {
            $helpMsg = Setting::get('help_msg', 'راهنمای اتصال');
            $this->bot->sendMessage($from['id'], $helpMsg, Keyboard::mainMenu($isAdmin, $from['id']));
            return;
        }

        if ($text === $btnSupport || $text === '/support') {
            $supportMsg = Setting::get('support_text', 'پشتیبانی');
            $this->bot->sendMessage($from['id'], $supportMsg, Keyboard::mainMenu($isAdmin, $from['id']));
            return;
        }

        if ($text === $btnTest || $text === '/test') {
            $this->showPlans($from['id']);
            return;
        }

        // Default response
        $this->bot->sendMessage($from['id'], "لطفا یک گزینه را از منوی زیر انتخاب نمایید:", Keyboard::mainMenu($isAdmin, $from['id']));
    }

    private function showAdminDashboard(int|string $chatId): void
    {
        $pdo = \App\Core\Database::getInstance();
        $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $serviceCount = $pdo->query("SELECT COUNT(*) FROM services WHERE is_active = 1")->fetchColumn();
        $panelCount = $pdo->query("SELECT COUNT(*) FROM panels WHERE is_active = 1")->fetchColumn();
        $pendingOrders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();

        $text = "👑 <b>داشبورد و آمار زنده ربات (ویژه مدیر):</b>\n\n";
        $text .= "👥 <b>تعداد کل کاربران:</b> " . number_format($userCount) . " نفر\n";
        $text .= "🚀 <b>سرویس‌های فعال:</b> " . number_format($serviceCount) . " اشتراک\n";
        $text .= "🖥 <b>پنل‌ها و سرورهای فعال:</b> " . number_format($panelCount) . " سرور\n";
        $text .= "⏳ <b>سفارشات در انتظار بررسی:</b> <b>" . number_format($pendingOrders) . "</b> سفارش\n\n";
        $text .= "جهت مدیریت کامل و افزودن سرور، از پنل وب زیر استفاده کنید:";

        $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') ? "https://" : "http://";
        $domain = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $webUrl = $proto . $domain . '/admin/dashboard';

        $inline = [];
        if ($pendingOrders > 0) {
            $inline[] = [['text' => '⏳ بررسی و تایید سفارشات (' . $pendingOrders . ')', 'callback_data' => 'admin_list_pending']];
        }
        $inline[] = [
            ['text' => '🖥 مدیریت سرورها و تنظیم اینباند', 'callback_data' => 'admin_manage_panels'],
            ['text' => '🔑 فعال‌سازی و دریافت رمز وب پنل', 'callback_data' => 'adm_activate_web_panel']
        ];
        $inline[] = [
            ['text' => '📢 تنظیم گروه گزارشات (Topics)', 'callback_data' => 'admin_setup_report_group'],
            ['text' => '💾 بکاپ فوری دیتابیس', 'callback_data' => 'admin_trigger_backup']
        ];
        $inline[] = [['text' => '🌐 ورود به پنل تحت وب مدیریت', 'url' => $webUrl]];
        $inline[] = [['text' => '🔙 بازگشت به منوی اصلی', 'callback_data' => 'back_to_main']];

        $this->bot->sendMessage($chatId, $text, ['inline_keyboard' => $inline]);
    }

    /**
     * فعال‌سازی دسترسی وب پنل و تنظیم یوزرنیم به آیدی عددی ادمین همراه با تولید رمز عبور تصادفی جدید (مطابق PanelSaz)
     */
    private function activateWebPanelAccess(int|string $adminChatId): void
    {
        $newUsername = (string)$adminChatId;
        $chars = '23456789abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ';
        $newPassword = '';
        for ($i = 0; $i < 10; $i++) {
            $newPassword .= $chars[random_int(0, strlen($chars) - 1)];
        }

        // 1. Update/insert in admins table
        $existingAdmin = \App\Models\Admin::findByUsername($newUsername);
        if ($existingAdmin) {
            \App\Models\Admin::update((int)$existingAdmin['id'], [
                'name' => 'مدیر تلگرام (' . $newUsername . ')',
                'role' => 'super_admin',
                'permissions' => ['*'],
                'password' => $newPassword,
                'is_active' => 1,
            ]);
        } else {
            \App\Models\Admin::create([
                'username' => $newUsername,
                'name' => 'مدیر تلگرام (' . $newUsername . ')',
                'role' => 'super_admin',
                'permissions' => ['*'],
                'password' => $newPassword,
                'is_active' => 1,
            ]);
        }

        // 2. Also update Settings for legacy/direct verification
        Setting::set('admin_username', $newUsername);
        Setting::set('admin_password', password_hash($newPassword, PASSWORD_BCRYPT));

        // 3. Prepare login URL
        $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'] ?? (getenv('APP_URL') ? parse_url(getenv('APP_URL'), PHP_URL_HOST) : 'localhost');
        $panelUrl = (str_starts_with($host, 'http') ? $host : ($proto . $host)) . '/admin/login';

        $msg = "🔑 <b>دسترسی وب پنل مدیریت با موفقیت فعال و به‌روزرسانی شد!</b>\n\n"
             . "مشخصات جدید ورود به وب پنل برای شما صادر گردید:\n\n"
             . "🌐 <b>آدرس وب پنل:</b>\n<code>{$panelUrl}</code>\n\n"
             . "👤 <b>نام کاربری (آیدی عددی شما):</b>\n<code>{$newUsername}</code>\n\n"
             . "🔐 <b>رمز عبور جدید:</b>\n<code>{$newPassword}</code>\n\n"
             . "💡 <i>توجه: با هر بار فشردن این دکمه، رمز عبور تغییر کرده و نام کاربری روی آیدی عددی تلگرام شما تنظیم می‌شود. کلیه ورودها به وب پنل در تاپیک «سایر گزارشات» لاگ خواهد شد.</i>";

        $inline = [
            [['text' => '🌐 ورود مستقیم به وب پنل', 'url' => $panelUrl]],
            [['text' => '🔄 تولید مجدد رمز عبور', 'callback_data' => 'adm_activate_web_panel']],
            [['text' => '🔙 بازگشت به منوی مدیریت', 'callback_data' => 'admin_dashboard']],
        ];

        $this->bot->sendMessage($adminChatId, $msg, ['inline_keyboard' => $inline]);

        \App\Services\ReportHelper::sendToTopic(
            \App\Services\ReportHelper::TOPIC_OTHER,
            "🔑 <b>فعال‌سازی و صدور دسترسی وب پنل</b>\n\n"
            . "👤 <b>ادمین صادرکننده:</b> <code>{$adminChatId}</code>\n"
            . "🆔 <b>نام کاربری جدید پنل:</b> <code>{$newUsername}</code>\n"
            . "⏰ <b>زمان:</b> " . \App\Services\ReportHelper::toJalali(time())
        );
    }

    private function showAdminManagePanels(int|string $chatId, ?int $messageId = null): void
    {
        $panels = Panel::all();
        if (empty($panels)) {
            $text = "🖥 <b>مدیریت سرورها:</b>\n\nهنوز هیچ سروری ثبت نشده است. لطفاً از طریق وب پنل سرور جدید اضافه نمایید.";
            $kb = ['inline_keyboard' => [[['text' => '🔙 بازگشت به داشبورد', 'callback_data' => 'admin_dashboard']]]];
            if ($messageId) {
                $this->bot->editMessageText($chatId, $messageId, $text, $kb);
            } else {
                $this->bot->sendMessage($chatId, $text, $kb);
            }
            return;
        }

        $text = "🖥 <b>مدیریت سرورها و تنظیم هوشمند اینباندها:</b>\n\n";
        $text .= "جهت مشاهده جزئیات، تست اتصال زنده یا تنظیم اینباندهای هر سرور، سرور مورد نظر را انتخاب نمایید:\n\n";

        $inline = [];
        foreach ($panels as $p) {
            $statusEmoji = $p['is_active'] ? '🟢' : '🔴';
            $inbounds = json_decode($p['inbounds_json'] ?? '[]', true);
            $inboundBadge = !empty($inbounds) ? (" [" . count($inbounds) . " اینباند]") : " [پیش‌فرض]";
            $inline[] = [[
                'text' => "{$statusEmoji} {$p['name']}{$inboundBadge}",
                'callback_data' => 'admin_panel_view_' . $p['id']
            ]];
        }
        $inline[] = [['text' => '🔙 بازگشت به داشبورد مدیریت', 'callback_data' => 'admin_dashboard']];

        $kb = ['inline_keyboard' => $inline];
        if ($messageId) {
            $this->bot->editMessageText($chatId, $messageId, $text, $kb);
        } else {
            $this->bot->sendMessage($chatId, $text, $kb);
        }
    }

    private function showAdminPanelView(int|string $chatId, int $panelId, ?int $messageId = null): void
    {
        $panel = Panel::find($panelId);
        if (!$panel) {
            $this->showAdminManagePanels($chatId, $messageId);
            return;
        }

        $inbounds = json_decode($panel['inbounds_json'] ?? '[]', true);
        $inboundText = !empty($inbounds) ? implode(', ', $inbounds) : 'همه اینباندها (پیش‌فرض)';

        $text = "🖥 <b>سرور: " . htmlspecialchars($panel['name']) . "</b>\n\n";
        $text .= "📌 <b>نوع پنل:</b> " . htmlspecialchars($panel['type']) . "\n";
        $text .= "🌐 <b>آدرس:</b> <code>" . htmlspecialchars($panel['url']) . "</code>\n";
        $text .= "👥 <b>کاربران فعال:</b> {$panel['current_clients']} از {$panel['capacity']}\n";
        $text .= "📡 <b>شناسه اینباندهای تنظیم‌شده:</b> <code>{$inboundText}</code>\n\n";
        $text .= "💡 برای تنظیم اینباندها بر اساس یک کلاینت نمونه، روی دکمه زیر کلیک کنید:";

        $inline = [
            [['text' => '🎛 تنظیم اینباند', 'callback_data' => 'admin_panel_set_inbound_' . $panelId]],
            [['text' => '⚡ تست اتصال پنل', 'callback_data' => 'admin_panel_test_' . $panelId]],
            [['text' => '🔙 بازگشت به لیست سرورها', 'callback_data' => 'admin_manage_panels']],
        ];

        $kb = ['inline_keyboard' => $inline];
        if ($messageId) {
            $this->bot->editMessageText($chatId, $messageId, $text, $kb);
        } else {
            $this->bot->sendMessage($chatId, $text, $kb);
        }
    }
    }

    private function showAdminPendingOrders(int|string $chatId): void
    {
        $orders = Order::all('pending');
        if (empty($orders)) {
            $this->bot->sendMessage($chatId, "✅ در حال حاضر هیچ سفارشی در انتظار بررسی نیست.", Keyboard::mainMenu(true));
            return;
        }

        $this->bot->sendMessage($chatId, "⏳ <b>لیست سفارشات در انتظار بررسی (" . count($orders) . " مورد):</b>");

        foreach ($orders as $order) {
            $amountFmt = number_format($order['amount']) . ' تومان';
            $userText = ($order['first_name'] ?? '') . ($order['username'] ? " (@{$order['username']})" : "");

            $caption = "🆔 <b>سفارش:</b> #{$order['id']}\n";
            $caption .= "👤 <b>کاربر:</b> {$userText} (<code>{$order['telegram_id']}</code>)\n";
            $caption .= "📦 <b>پلن:</b> {$order['plan_name']}\n";
            $caption .= "💰 <b>مبلغ:</b> {$amountFmt}\n";
            if (!empty($order['receipt_text'])) {
                $caption .= "📝 <b>کد پیگیری:</b> <code>{$order['receipt_text']}</code>\n";
            }

            $keyboard = Keyboard::adminOrderKeyboard($order['id']);

            $photoParam = \App\Services\PaymentService::resolveReceiptPhoto($order['receipt_photo'] ?? null);
            if ($photoParam !== null) {
                $this->bot->sendPhoto($chatId, $photoParam, $caption, $keyboard);
            } else {
                $this->bot->sendMessage($chatId, $caption, $keyboard);
            }
        }
    }

    private function showPlans(int|string $chatId, ?int $messageId = null): void
    {
        $panels = Panel::all(true);
        if (empty($panels)) {
            $this->bot->sendMessage($chatId, "در حال حاضر هیچ سرور فعالی موجود نیست. لطفاً بعداً بررسی نمایید.");
            return;
        }

        $text = "🌐 <b>مرحله اول: انتخاب لوکیشن و سرور</b>\n\n";
        $text .= "لطفاً سرور مورد نظر خود را جهت مشاهده پلن‌ها انتخاب نمایید:";

        if ($messageId) {
            $this->bot->editMessageText($chatId, $messageId, $text, Keyboard::panelsKeyboard($panels));
        } else {
            $this->bot->sendMessage($chatId, $text, Keyboard::panelsKeyboard($panels));
        }
    }

    private function showPanelPlans(int|string $chatId, int $panelId, ?int $messageId = null): void
    {
        $panel = Panel::find($panelId);
        $plans = Plan::getByPanelId($panelId, true);

        if (empty($plans)) {
            $msg = "در حال حاضر هیچ پلن فعالی برای سرور <b>" . htmlspecialchars($panel['name'] ?? '') . "</b> ثبت نشده است.";
            $inline = [
                [['text' => '🔙 بازگشت به لیست سرورها', 'callback_data' => 'back_to_panels']]
            ];
            if ($messageId) {
                $this->bot->editMessageText($chatId, $messageId, $msg, ['inline_keyboard' => $inline]);
            } else {
                $this->bot->sendMessage($chatId, $msg, ['inline_keyboard' => $inline]);
            }
            return;
        }

        $panelName = $panel ? $panel['name'] : 'سرور انتخابی';
        $text = "📦 <b>پلن‌های فعال سرور {$panelName}:</b>\n\n";
        $text .= "جهت خرید هر یک از کانفیگ‌ها، روی پلن مورد نظر کلیک کنید:";

        if ($messageId) {
            $this->bot->editMessageText($chatId, $messageId, $text, Keyboard::plansKeyboard($plans, $panelId));
        } else {
            $this->bot->sendMessage($chatId, $text, Keyboard::plansKeyboard($plans, $panelId));
        }
    }

    private function showMyServices(array $user): void
    {
        $services = Service::getUserServices($user['id']);
        if (empty($services)) {
            $this->bot->sendMessage($user['telegram_id'], "شما هیچ سرویس فعالی ندارید. می‌توانید از بخش خرید اشتراک یک سرویس تهیه کنید.", Keyboard::mainMenu(User::isAdmin($user['telegram_id']), $user['telegram_id']));
            return;
        }

        $text = "📱 <b>سرویس‌های فعال شما:</b>\nجهت مشاهده جزئیات و لینک اتصال، سرویس مورد نظر را انتخاب کنید:";
        $this->bot->sendMessage($user['telegram_id'], $text, Keyboard::servicesKeyboard($services));
    }

    private function showWalletAndCards(int|string $chatId): void
    {
        $cards = Card::all(true);
        if (empty($cards)) {
            $this->bot->sendMessage($chatId, "شماره کارتی ثبت نشده است. لطفاً با پشتیبانی در ارتباط باشید.");
            return;
        }

        $text = "💳 <b>اطلاعات حساب و شماره کارت‌ها:</b>\n\n";
        foreach ($cards as $idx => $card) {
            $i = $idx + 1;
            $text .= "<b>کارت شماره {$i}:</b>\n";
            $text .= "🏦 بانک: {$card['bank_name']}\n";
            $text .= "👤 به نام: {$card['holder_name']}\n";
            $text .= "💳 شماره کارت: <code>{$card['card_number']}</code>\n\n";
        }
        $text .= "<i>(روی شماره کارت بزنید تا کپی شود)</i>";

        $this->bot->sendMessage($chatId, $text, Keyboard::mainMenu(User::isAdmin($chatId), $chatId));
    }

    private function handleReceiptSubmission(array $user, array $message): void
    {
        $stepData = User::getStepData($user);
        $planId = (int)($stepData['plan_id'] ?? 0);
        $plan = Plan::find($planId);
        $isAdmin = User::isAdmin($user['telegram_id']);

        if (!$plan) {
            User::setStep($user['id'], 'idle');
            $this->bot->sendMessage($user['telegram_id'], "خطایی در پلن انتخابی رخ داد. لطفاً مجدداً خرید را انجام دهید.", Keyboard::mainMenu($isAdmin, $user['telegram_id']));
            return;
        }

        $photo = $message['photo'] ?? null;
        $fileId = null;
        $receiptText = trim($message['text'] ?? ($message['caption'] ?? ''));

        if (!empty($photo)) {
            // Get highest resolution photo
            $fileId = end($photo)['file_id'];
        }

        if (empty($fileId) && empty($receiptText)) {
            $this->bot->sendMessage($user['telegram_id'], "لطفا تصویر فیش واریزی یا شماره پیگیری تراکنش را ارسال فرمایید:", Keyboard::cancelKeyboard());
            return;
        }

        try {
            $orderId = PaymentService::submitCardOrder($user['id'], $planId, $receiptText, $fileId);
            User::setStep($user['id'], 'idle');

            $msg = "✅ <b>فیش پرداختی شما با موفقیت ثبت شد!</b>\n\n";
            $msg .= "🆔 کد پیگیری سفارش: <b>#{$orderId}</b>\n";
            $msg .= "📦 پلن: <b>{$plan['name']}</b>\n";
            $msg .= "💰 مبلغ: <b>" . number_format($plan['price']) . " تومان</b>\n\n";
            $msg .= "⏳ کارشناسان ما پس از بررسی فیش، اشتراک شما را صادر خواهند کرد. کانفیگ بلافاصله در همین ربات برای شما ارسال می‌شود.";

            $this->bot->sendMessage($user['telegram_id'], $msg, Keyboard::mainMenu($isAdmin, $user['telegram_id']));
        } catch (Exception $e) {
            $this->bot->sendMessage($user['telegram_id'], "خطا در ثبت سفارش: " . $e->getMessage(), Keyboard::mainMenu($isAdmin, $user['telegram_id']));
        }
    }

    private function handleCallbackQuery(array $callback): void
    {
        $callbackId = $callback['id'];
        $data = $callback['data'] ?? '';
        $from = $callback['from'];
        $message = $callback['message'] ?? null;
        $chatId = $message['chat']['id'] ?? $from['id'];
        $messageId = $message['message_id'] ?? 0;

        $user = User::findOrCreate($from);
        $isAdmin = User::isAdmin($from['id']);

        // Select panel callback (Step 1 of buying service)
        if (str_starts_with($data, 'select_panel_')) {
            $panelId = (int)str_replace('select_panel_', '', $data);
            $this->bot->answerCallbackQuery($callbackId);
            $this->showPanelPlans($chatId, $panelId, $messageId);
            return;
        }

        // Back to panels list
        if ($data === 'back_to_panels') {
            $this->bot->answerCallbackQuery($callbackId);
            $this->showPlans($chatId, $messageId);
            return;
        }

        // Buy plan click
        if (str_starts_with($data, 'buy_plan_')) {
            $planId = (int)str_replace('buy_plan_', '', $data);
            $plan = Plan::find($planId);
            if (!$plan) {
                $this->bot->answerCallbackQuery($callbackId, "پلن یافت نشد!", true);
                return;
            }

            $card = Card::getActiveCard();
            $priceFmt = number_format($plan['price']) . ' تومان';
            
            $limitParts = [];
            if ($plan['ip_limit'] > 0) $limitParts[] = "{$plan['ip_limit']} IP";
            if (!empty($plan['hwid_limit']) && $plan['hwid_limit'] > 0) $limitParts[] = "{$plan['hwid_limit']} HWID";
            $limitTxt = !empty($limitParts) ? implode(' / ', $limitParts) : "نامحدود";

            $userBalance = (int)($user['balance'] ?? 0);
            $planPrice = (int)$plan['price'];

            $text = "📦 <b>مشخصات پلن انتخابی:</b>\n\n";
            $text .= "🔹 <b>نام:</b> {$plan['name']}\n";
            $text .= "📊 <b>حجم:</b> {$plan['traffic_gb']} گیگابایت\n";
            $text .= "⏳ <b>مدت اعتبار:</b> {$plan['days']} روز\n";
            $text .= "👥 <b>محدودیت اتصال:</b> {$limitTxt}\n";
            $text .= "💰 <b>مبلغ قابل پرداخت:</b> <b>{$priceFmt}</b>\n";
            $text .= "💳 <b>موجودی فعلی کیف پول شما:</b> " . number_format($userBalance) . " تومان\n\n";

            if ($card) {
                $text .= "💳 <b>شماره کارت جهت واریز:</b>\n";
                $text .= "بانک: {$card['bank_name']}\n";
                $text .= "به نام: {$card['holder_name']}\n";
                $text .= "شماره کارت: <code>{$card['card_number']}</code>\n\n";
            }

            $text .= "روش پرداخت مورد نظر خود را انتخاب نمایید:";

            $this->bot->answerCallbackQuery($callbackId);
            $this->bot->editMessageText($chatId, $messageId, $text, Keyboard::planCheckoutKeyboard($planId, $isAdmin, $userBalance, $planPrice));
            return;
        }

        // Admin instant test purchase
        if (str_starts_with($data, 'admin_test_buy_')) {
            if (!$isAdmin) {
                $this->bot->answerCallbackQuery($callbackId, "دسترسی غیرمجاز!", true);
                return;
            }

            $planId = (int)str_replace('admin_test_buy_', '', $data);
            $plan = Plan::find($planId);
            if (!$plan) {
                $this->bot->answerCallbackQuery($callbackId, "پلن نامعتبر است.", true);
                return;
            }

            $this->bot->answerCallbackQuery($callbackId, "⚡ در حال ساخت و اتصال آنی به سرور...");

            try {
                $orderId = Order::create(
                    userId: $user['id'],
                    planId: $planId,
                    panelId: $plan['panel_id'] ?: 0,
                    amount: 0,
                    receiptText: 'تست مستقیم توسط مدیر در ربات',
                    receiptPhoto: null
                );
                PaymentService::approveOrder($orderId);
                $this->bot->sendMessage($chatId, "✅ <b>کانفیگ اختصاصی تست سرور با موفقیت ایجاد و برای شما ارسال شد!</b>", Keyboard::mainMenu(true, $chatId));
            } catch (Exception $e) {
                $this->bot->sendMessage($chatId, "❌ خطا در ساخت آنی کانفیگ روی سرور:\n" . $e->getMessage(), Keyboard::mainMenu(true, $chatId));
            }
            return;
        }

        // Pay directly from wallet
        if (str_starts_with($data, 'pay_wallet_')) {
            $planId = (int)str_replace('pay_wallet_', '', $data);
            $plan = Plan::find($planId);
            if (!$plan) {
                $this->bot->answerCallbackQuery($callbackId, "پلن نامعتبر است.", true);
                return;
            }

            $price = (int)$plan['price'];
            if ((int)$user['balance'] < $price) {
                $this->bot->answerCallbackQuery($callbackId, "موجودی کیف پول شما کافی نیست!", true);
                return;
            }

            if (!User::deductBalance($user['id'], $price)) {
                $this->bot->answerCallbackQuery($callbackId, "خطا در کسر موجودی!", true);
                return;
            }

            $this->bot->answerCallbackQuery($callbackId, "در حال ایجاد کانفیگ...");

            try {
                $orderId = Order::create(
                    userId: $user['id'],
                    planId: $planId,
                    panelId: $plan['panel_id'] ?: 0,
                    amount: $price,
                    receiptText: 'پرداخت آنی از کیف پول در ربات',
                    receiptPhoto: null
                );
                PaymentService::approveOrder($orderId);
                $this->bot->sendMessage($chatId, "🎉 <b>مبلغ {$price} تومان از کیف پول کسر و اشتراک فوراً صادر شد!</b>", Keyboard::mainMenu($isAdmin, $chatId));
            } catch (Exception $e) {
                // Refund balance
                User::addBalance($user['id'], $price);
                $this->bot->sendMessage($chatId, "❌ خطا در فعالسازی خودکار در سرور:\n" . $e->getMessage() . "\nمبلغ کسر شده به کیف پول شما برگشت داده شد.", Keyboard::mainMenu($isAdmin, $chatId));
            }
            return;
        }

        // Pay with card
        if (str_starts_with($data, 'pay_card_')) {
            $planId = (int)str_replace('pay_card_', '', $data);
            $plan = Plan::find($planId);
            if (!$plan) {
                $this->bot->answerCallbackQuery($callbackId, "پلن نامعتبر است.", true);
                return;
            }

            User::setStep($user['id'], 'waiting_receipt', ['plan_id' => $planId]);
            $this->bot->answerCallbackQuery($callbackId);

            $msg = "📸 <b>ارسال فیش واریزی</b>\n\n";
            $msg .= "مبلغ: <b>" . number_format($plan['price']) . " تومان</b>\n\n";
            $msg .= "لطفاً تصویر فیش واریزی یا شماره پیگیری تراکنش خود را به عنوان عکس یا متن در همین صفحه ارسال فرمایید:";

            $this->bot->sendMessage($chatId, $msg, Keyboard::cancelKeyboard());
            return;
        }

        // View single service
        if (str_starts_with($data, 'view_service_')) {
            $srvId = (int)str_replace('view_service_', '', $data);
            $service = Service::syncWithPanel($srvId) ?: Service::find($srvId);
            if (!$service || $service['user_id'] != $user['id']) {
                $this->bot->answerCallbackQuery($callbackId, "سرویس یافت نشد.", true);
                return;
            }

            $trafficGb = (int)$service['traffic_gb'];
            $usedBytes = (int)$service['used_traffic_bytes'];
            $usedGb = round($usedBytes / 1073741824, 2);
            $remGb = ($trafficGb > 0) ? max(0, round($trafficGb - ($usedBytes / 1073741824), 2)) : 'نامحدود';
            $ipLim = ($service['ip_limit'] > 0) ? "{$service['ip_limit']} کاربر" : "نامحدود";

            // Progress bar
            $pct = ($trafficGb > 0) ? min(100, round(($usedBytes / ($trafficGb * 1073741824)) * 100)) : 0;
            $filledBlocks = (int)round($pct / 10);
            $emptyBlocks = 10 - $filledBlocks;
            $bar = str_repeat('🟩', $filledBlocks) . str_repeat('⬜', $emptyBlocks);

            $statusText = $service['is_active'] ? '🟢 فعال' : '🔴 غیرفعال / منقضی شده';

            $text = "🔑 <b>استعلام زنده اشتراک #{$service['id']}:</b>\n\n";
            $text .= "🌐 <b>سرور متصل:</b> {$service['panel_name']}\n";
            $text .= "📡 <b>وضعیت:</b> {$statusText}\n";
            $text .= "📊 <b>میزان مصرف:</b> {$usedGb} GB از {$trafficGb} GB ({$pct}%)\n";
            $text .= "📈 <b>پیشرفت مصرف:</b>\n{$bar}\n";
            $text .= "🎁 <b>حجم باقی‌مانده:</b> {$remGb} GB\n";
            $text .= "👥 <b>محدودیت اتصال:</b> {$ipLim}\n";
            $text .= "⏳ <b>تاریخ انقضا:</b> " . ($service['expires_at'] ?: 'نامحدود') . "\n\n";

            if (!empty($service['subscription_url'])) {
                $text .= "🔗 <b>لینک اتصال سابسکریپشن:</b>\n<code>{$service['subscription_url']}</code>\n\n";
            }
            if (!empty($service['config_link'])) {
                $text .= "📋 <b>کانفیگ مستقیم:</b>\n<code>{$service['config_link']}</code>\n\n";
            }

            $this->bot->answerCallbackQuery($callbackId);
            $this->bot->sendMessage($chatId, $text, Keyboard::mainMenu($isAdmin, $chatId));

            // If WireGuard conf exists, send the file as document
            if (!empty($service['wireguard_conf'])) {
                $confPath = \App\Services\WireGuardHelper::saveTempConfFile($service['client_email'], $service['wireguard_conf']);
                $this->bot->sendDocument($chatId, $confPath, "📁 فایل کانفیگ وایرگارد اشتراک #{$service['id']} (.conf)");
            }
            return;
        }

        // Admin list pending orders
        if ($data === 'admin_list_pending') {
            if (!$isAdmin) {
                $this->bot->answerCallbackQuery($callbackId, "عدم دسترسی", true);
                return;
            }
            $this->bot->answerCallbackQuery($callbackId);
            $this->showAdminPendingOrders($chatId);
            return;
        }

        // Admin approve order
        if (str_starts_with($data, 'admin_approve_')) {
            if (!$isAdmin) {
                $this->bot->answerCallbackQuery($callbackId, "شما مجاز به انجام این عملیات نیستید.", true);
                return;
            }

            $orderId = (int)str_replace('admin_approve_', '', $data);
            try {
                PaymentService::approveOrder($orderId);
                $this->bot->answerCallbackQuery($callbackId, "✅ سفارش تایید شد و اشتراک صادر گردید.", true);
                $this->bot->editMessageText($chatId, $messageId, "✅ <b>سفارش #{$orderId} با موفقیت تایید و اشتراک صادر گردید.</b>");
            } catch (Exception $e) {
                $this->bot->answerCallbackQuery($callbackId, "خطا: " . $e->getMessage(), true);
            }
            return;
        }

        // Admin reject order
        if (str_starts_with($data, 'admin_reject_')) {
            if (!$isAdmin) {
                $this->bot->answerCallbackQuery($callbackId, "عدم دسترسی", true);
                return;
            }

            $orderId = (int)str_replace('admin_reject_', '', $data);
            try {
                PaymentService::rejectOrder($orderId, "تصویر یا مشخصات فیش ارسالی نامعتبر است.");
                $this->bot->answerCallbackQuery($callbackId, "❌ سفارش رد شد و به کاربر اطلاع داده شد.", true);
                $this->bot->editMessageText($chatId, $messageId, "❌ <b>سفارش #{$orderId} رد شد.</b>");
            } catch (Exception $e) {
                $this->bot->answerCallbackQuery($callbackId, "خطا: " . $e->getMessage(), true);
            }
            return;
        }

        // Admin Setup Report Group
        if ($data === 'admin_setup_report_group') {
            if (!$isAdmin) {
                $this->bot->answerCallbackQuery($callbackId, "عدم دسترسی", true);
                return;
            }
            $this->bot->answerCallbackQuery($callbackId);
            User::setStep($user['id'], 'waiting_report_group_id');
            $msg = "📢 <b>اتصال و راه‌اندازی گروه گزارشات (Supergroup با قابلیت تاپیک):</b>\n\n";
            $msg .= "لطفاً شناسه عددی سوپرگروه را ارسال نمایید (مثال: <code>-1001234567890</code>).\n\n";
            $msg .= "⚠️ <b>نکات مهم قبل از ارسال:</b>\n";
            $msg .= "۱. گزینه <b>Topics</b> در تنظیمات گروه تلگرام باید حتماً فعال باشد.\n";
            $msg .= "۲. ربات را در گروه عضو کرده و به آن دسترسی <b>ادمین (مدیریت تاپیک‌ها)</b> بدهید.";
            $this->bot->sendMessage($chatId, $msg, Keyboard::cancelKeyboard());
            return;
        }

        // Admin Trigger Backup
        if ($data === 'admin_trigger_backup') {
            if (!$isAdmin) {
                $this->bot->answerCallbackQuery($callbackId, "عدم دسترسی", true);
                return;
            }
            $this->bot->answerCallbackQuery($callbackId, "در حال تولید و ارسال فایل بکاپ...", false);
            $res = \App\Services\BackupService::runBackupJob();
            if ($res['success']) {
                $this->bot->sendMessage($chatId, "✅ <b>پشتیبان دیتابیس با موفقیت تولید شد و به تاپیک بکاپ در گروه / پیام‌های ذخیره ارسال گردید!</b> 💾");
            } else {
                $this->bot->sendMessage($chatId, "❌ <b>خطا در تهیه پشتیبان:</b> " . ($res['error'] ?? 'خطای نامشخص'));
            }
            return;
        }

        // Admin Dashboard View
        if ($data === 'admin_dashboard') {
            if (!$isAdmin) {
                $this->bot->answerCallbackQuery($callbackId, "عدم دسترسی", true);
                return;
            }
            $this->bot->answerCallbackQuery($callbackId);
            $this->showAdminDashboard($chatId);
            return;
        }

        // Admin Activate Web Panel
        if ($data === 'adm_activate_web_panel') {
            if (!$isAdmin) {
                $this->bot->answerCallbackQuery($callbackId, "عدم دسترسی", true);
                return;
            }
            $this->bot->answerCallbackQuery($callbackId, "مشخصات جدید وب پنل صادر شد.");
            $this->activateWebPanelAccess($chatId);
            return;
        }

        // Admin Manage Panels
        if ($data === 'admin_manage_panels') {
            if (!$isAdmin) {
                $this->bot->answerCallbackQuery($callbackId, "عدم دسترسی", true);
                return;
            }
            $this->bot->answerCallbackQuery($callbackId);
            $this->showAdminManagePanels($chatId, $messageId);
            return;
        }

        // Admin Panel View
        if (str_starts_with($data, 'admin_panel_view_')) {
            if (!$isAdmin) {
                $this->bot->answerCallbackQuery($callbackId, "عدم دسترسی", true);
                return;
            }
            $panelId = (int)str_replace('admin_panel_view_', '', $data);
            $this->bot->answerCallbackQuery($callbackId);
            $this->showAdminPanelView($chatId, $panelId, $messageId);
            return;
        }

        // Admin Panel Set Inbounds
        if (str_starts_with($data, 'admin_panel_set_inbound_')) {
            if (!$isAdmin) {
                $this->bot->answerCallbackQuery($callbackId, "عدم دسترسی", true);
                return;
            }
            $panelId = (int)str_replace('admin_panel_set_inbound_', '', $data);
            $panel = Panel::find($panelId);
            $this->bot->answerCallbackQuery($callbackId);

            User::setStep($user['id'], 'waiting_inbound_client_' . $panelId);
            $msg = "🎛 <b>تنظیم هوشمند اینباندهای سرور «" . htmlspecialchars($panel['name'] ?? '') . "»:</b>\n\n";
            $msg .= "برای تنظیم اینباند یک کلاینت با اینباند های مورد نظر داخل پنل بسازید و سپس اسم کلاینت رو وارد کنید:\n\n";
            $msg .= "💡 <i>ربات به صورت خودکار تمامی اینباندهایی که به آن کلاینت وصل هستند را شناسایی کرده و برای این سرور ذخیره می‌نماید.</i>\n";
            $msg .= "⚠️ <i>(دقت کنید موقع تنظیم اینباند فقط اینباندها برای کلاینت‌های این سرور ست می‌شوند، نه حجم، روز یا آی‌پی لیمیت کلاینت نمونه.)</i>";

            $this->bot->sendMessage($chatId, $msg, Keyboard::cancelKeyboard());
            return;
        }

        // Admin Panel Test Connection
        if (str_starts_with($data, 'admin_panel_test_')) {
            if (!$isAdmin) {
                $this->bot->answerCallbackQuery($callbackId, "عدم دسترسی", true);
                return;
            }
            $panelId = (int)str_replace('admin_panel_test_', '', $data);
            $panel = Panel::find($panelId);
            if (!$panel) {
                $this->bot->answerCallbackQuery($callbackId, "پنل یافت نشد.", true);
                return;
            }
            $this->bot->answerCallbackQuery($callbackId, "در حال تست اتصال به سرور...", false);
            try {
                $adapter = \App\Panels\PanelManager::make($panel);
                $res = $adapter->testConnection();
                $msg = $res['success'] ? "✅ {$res['message']}" : "❌ {$res['message']}";
                $this->bot->sendMessage($chatId, "⚡ <b>نتیجه تست اتصال سرور «" . htmlspecialchars($panel['name']) . "»:</b>\n\n" . $msg);
            } catch (\Throwable $e) {
                $this->bot->sendMessage($chatId, "❌ خطا در تست اتصال: " . $e->getMessage());
            }
            return;
        }

        if ($data === 'cancel_order' || $data === 'back_to_main') {
            User::setStep($user['id'], 'idle');
            $this->bot->answerCallbackQuery($callbackId);
            $this->bot->sendMessage($chatId, "به منوی اصلی بازگشتید.", Keyboard::mainMenu($isAdmin, $chatId));
            return;
        }

        $this->bot->answerCallbackQuery($callbackId);
    }
}
