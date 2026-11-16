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

        $text = trim($message['text'] ?? '');
        $photo = $message['photo'] ?? null;
        $caption = trim($message['caption'] ?? '');

        // Cancel action
        if ($text === '❌ انصراف') {
            User::setStep($user['id'], 'idle');
            $this->bot->sendMessage($from['id'], "عملیات لغو شد.", Keyboard::mainMenu());
            return;
        }

        // Handle active step (like waiting for receipt photo or text)
        if ($user['step'] === 'waiting_receipt') {
            $this->handleReceiptSubmission($user, $message);
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
            $welcome = Setting::get('welcome_msg', "سلام %first_name% عزیز!\nبه ربات ما خوش آمدید.");
            $welcome = str_replace(
                ['%first_name%', '%username%', '%user_id%'],
                [$user['first_name'] ?: 'کاربر', $user['username'] ?: '', $user['telegram_id']],
                $welcome
            );
            $this->bot->sendMessage($from['id'], $welcome, Keyboard::mainMenu());
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
            $this->bot->sendMessage($from['id'], $helpMsg, Keyboard::mainMenu());
            return;
        }

        if ($text === $btnSupport || $text === '/support') {
            $supportMsg = Setting::get('support_text', 'پشتیبانی');
            $this->bot->sendMessage($from['id'], $supportMsg, Keyboard::mainMenu());
            return;
        }

        // Default response
        $this->bot->sendMessage($from['id'], "لطفا یک گزینه را از منوی زیر انتخاب نمایید:", Keyboard::mainMenu());
    }

    private function showPlans(int|string $chatId): void
    {
        $plans = Plan::all(true);
        if (empty($plans)) {
            $this->bot->sendMessage($chatId, "در حال حاضر هیچ پلن فعالی موجود نیست. لطفاً بعداً بررسی نمایید.");
            return;
        }

        $text = "📋 <b>لیست تعرفه‌ها و پلن‌های پرسرعت:</b>\n\n";
        $text .= "جهت خرید هر یک از پلن‌ها، روی دکمه مربوطه کلیک کنید:";

        $this->bot->sendMessage($chatId, $text, Keyboard::plansKeyboard($plans));
    }

    private function showMyServices(array $user): void
    {
        $services = Service::getUserServices($user['id']);
        if (empty($services)) {
            $this->bot->sendMessage($user['telegram_id'], "شما هیچ سرویس فعالی ندارید. می‌توانید از بخش خرید اشتراک یک سرویس تهیه کنید.", Keyboard::mainMenu());
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

        $this->bot->sendMessage($chatId, $text, Keyboard::mainMenu());
    }

    private function handleReceiptSubmission(array $user, array $message): void
    {
        $stepData = User::getStepData($user);
        $planId = (int)($stepData['plan_id'] ?? 0);
        $plan = Plan::find($planId);

        if (!$plan) {
            User::setStep($user['id'], 'idle');
            $this->bot->sendMessage($user['telegram_id'], "خطایی در پلن انتخابی رخ داد. لطفاً مجدداً خرید را انجام دهید.", Keyboard::mainMenu());
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

            $this->bot->sendMessage($user['telegram_id'], $msg, Keyboard::mainMenu());
        } catch (Exception $e) {
            $this->bot->sendMessage($user['telegram_id'], "خطا در ثبت سفارش: " . $e->getMessage(), Keyboard::mainMenu());
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
            $limitTxt = ($plan['ip_limit'] > 0) ? "{$plan['ip_limit']} کاربر همزمان" : "نامحدود";

            $text = "📦 <b>مشخصات پلن انتخابی:</b>\n\n";
            $text .= "🔹 <b>نام:</b> {$plan['name']}\n";
            $text .= "📊 <b>حجم:</b> {$plan['traffic_gb']} گیگابایت\n";
            $text .= "⏳ <b>مدت اعتبار:</b> {$plan['days']} روز\n";
            $text .= "👥 <b>محدودیت کاربر:</b> {$limitTxt}\n";
            $text .= "💰 <b>مبلغ قابل پرداخت:</b> <b>{$priceFmt}</b>\n\n";

            if ($card) {
                $text .= "💳 <b>شماره کارت جهت واریز:</b>\n";
                $text .= "بانک: {$card['bank_name']}\n";
                $text .= "به نام: {$card['holder_name']}\n";
                $text .= "شماره کارت: <code>{$card['card_number']}</code>\n\n";
            }

            $text .= "جهت تکمیل خرید و ارسال فیش روی دکمه زیر کلیک نمایید:";

            $this->bot->answerCallbackQuery($callbackId);
            $this->bot->editMessageText($chatId, $messageId, $text, Keyboard::planCheckoutKeyboard($planId));
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
            $service = Service::find($srvId);
            if (!$service || $service['user_id'] != $user['id']) {
                $this->bot->answerCallbackQuery($callbackId, "سرویس یافت نشد.", true);
                return;
            }

            $trafficGb = $service['traffic_gb'];
            $usedGb = round($service['used_traffic_bytes'] / 1073741824, 2);
            $ipLim = ($service['ip_limit'] > 0) ? "{$service['ip_limit']} کاربر" : "نامحدود";

            $text = "🔑 <b>اطلاعات اشتراک #{$service['id']}:</b>\n\n";
            $text .= "🌐 <b>سرور:</b> {$service['panel_name']}\n";
            $text .= "📊 <b>میزان مصرف:</b> {$usedGb} GB از {$trafficGb} GB\n";
            $text .= "👥 <b>محدودیت اتصال:</b> {$ipLim}\n";
            $text .= "⏳ <b>انقضا:</b> " . ($service['expires_at'] ?: 'نامحدود') . "\n\n";

            if (!empty($service['subscription_url'])) {
                $text .= "🔗 <b>لینک سابسکریپشن:</b>\n<code>{$service['subscription_url']}</code>\n\n";
            }
            if (!empty($service['config_link'])) {
                $text .= "📋 <b>کانفیگ مستقیم:</b>\n<code>{$service['config_link']}</code>\n\n";
            }

            $this->bot->answerCallbackQuery($callbackId);
            $this->bot->sendMessage($chatId, $text, Keyboard::mainMenu());

            // If WireGuard conf exists, send the file as document
            if (!empty($service['wireguard_conf'])) {
                $confPath = \App\Services\WireGuardHelper::saveTempConfFile($service['client_email'], $service['wireguard_conf']);
                $this->bot->sendDocument($chatId, $confPath, "📁 فایل کانفیگ وایرگارد اشتراک #{$service['id']} (.conf)");
            }
            return;
        }

        // Admin approve order
        if (str_starts_with($data, 'admin_approve_')) {
            $adminId = Setting::get('admin_telegram_id');
            if ((string)$from['id'] !== (string)$adminId) {
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
            $adminId = Setting::get('admin_telegram_id');
            if ((string)$from['id'] !== (string)$adminId) {
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

        if ($data === 'cancel_order' || $data === 'back_to_main') {
            User::setStep($user['id'], 'idle');
            $this->bot->answerCallbackQuery($callbackId);
            $this->bot->sendMessage($chatId, "به منوی اصلی بازگشتید.", Keyboard::mainMenu());
            return;
        }

        $this->bot->answerCallbackQuery($callbackId);
    }
}
