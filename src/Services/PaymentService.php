<?php

namespace App\Services;

use App\Models\Card;
use App\Models\Order;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\User;
use App\Telegram\Bot;
use App\Telegram\Keyboard;
use Exception;

class PaymentService
{
    /**
     * Submit a new card-to-card order with receipt
     */
    public static function submitCardOrder(int $userId, int $planId, string $receiptText = '', ?string $receiptPhoto = null): int
    {
        $plan = Plan::find($planId);
        if (!$plan) {
            throw new Exception("پلن انتخابی معتبر نیست.");
        }

        $orderId = Order::create(
            userId: $userId,
            planId: $planId,
            panelId: $plan['panel_id'] ?? 0,
            amount: $plan['price'],
            receiptText: $receiptText,
            receiptPhoto: $receiptPhoto
        );

        // Notify Admin on Telegram
        self::notifyAdminNewOrder($orderId);

        return $orderId;
    }

    /**
     * Approve order and automatically generate config on Sanaei / Pasargad panel
     */
    public static function approveOrder(int $orderId): array
    {
        $order = Order::find($orderId);
        if (!$order) {
            throw new Exception("سفارش یافت نشد.");
        }

        if ($order['status'] === 'approved') {
            throw new Exception("این سفارش قبلاً تایید و تحویل داده شده است.");
        }

        $user = User::find((int)$order['user_id']);

        // Wallet top-up order (no plan attached): credit the balance instead of creating a service
        if ((int)($order['plan_id'] ?? 0) === 0) {
            $amount = (int)$order['amount'];
            if ($amount <= 0) {
                throw new Exception("مبلغ شارژ نامعتبر است.");
            }
            Order::approve($orderId);
            User::addBalance((int)$order['user_id'], $amount);

            $bot = new Bot();
            if ($user && !empty($user['telegram_id'])) {
                $bot->sendMessage(
                    $user['telegram_id'],
                    "✅ <b>شارژ کیف پول شما تایید شد!</b>\n\n💰 مبلغ: " . number_format($amount) . " تومان\n💳 موجودی فعلی: " . number_format((int)$user['balance'] + $amount) . " تومان",
                    Keyboard::mainMenu()
                );
            }

            try {
                ReportHelper::reportFinancial($order, $user ?: ['telegram_id' => 0], 'شارژ کیف پول تایید شد');
            } catch (\Throwable $e) {
                // Ignore reporting errors
            }

            return [
                'service_id' => 0,
                'topup' => true,
                'amount' => $amount,
                'traffic_gb' => 0,
                'days' => 0,
                'ip_limit' => 0,
                'panel_name' => 'شارژ کیف پول',
            ];
        }

        // Generate VPN config
        $serviceData = ConfigGenerator::createForOrder($order);

        // Mark order as approved
        Order::approve($orderId);

        // Notify user via Telegram
        $bot = new Bot();
        if ($user && !empty($user['telegram_id'])) {
            $msg = "🎉 <b>سفارش شما تایید و اشتراک فعال شد!</b>\n\n";
            $msg .= "📦 <b>پلن:</b> {$order['plan_name']}\n";
            $msg .= "📊 <b>حجم:</b> {$serviceData['traffic_gb']} گیگابایت\n";
            $msg .= "⏳ <b>اعتبار:</b> {$serviceData['days']} روز\n";
            $msg .= "👥 <b>محدودیت کاربر:</b> " . ($serviceData['ip_limit'] ?: 'نامحدود') . "\n";
            $msg .= "🌐 <b>سرور:</b> {$serviceData['panel_name']}\n\n";

            if (!empty($serviceData['sub_url'])) {
                $msg .= "🔗 <b>لینک سابسکریپشن:</b>\n<code>{$serviceData['sub_url']}</code>\n\n";
            }

            if (!empty($serviceData['config_link'])) {
                $msg .= "🔑 <b>کانفیگ مستقیم:</b>\n<code>{$serviceData['config_link']}</code>\n\n";
            }

            if (!empty($serviceData['wireguard_conf'])) {
                $msg .= "📁 <b>فایل کانفیگ WireGuard (.conf) در پیام زیر ارسال شد.</b>\n\n";
            }

            $msg .= "💡 <i>کافیست روی لینک یا کانفیگ لمس کنید تا کپی شود و سپس در نرم‌افزار خود وارد کنید.</i>";

            $bot->sendMessage($user['telegram_id'], $msg, Keyboard::mainMenu());

            // Automatically send WireGuard .conf file if available
            if (!empty($serviceData['wireguard_conf'])) {
                $confPath = \App\Services\WireGuardHelper::saveTempConfFile($serviceData['client_email'], $serviceData['wireguard_conf']);
                $docCaption = "📁 <b>فایل کانفیگ وایرگارد شما (.conf)</b>\n\n📥 این فایل را دانلود کرده و در نرم‌افزار WireGuard با گزینه Import tunnel(s) from file وارد نمایید.";
                $bot->sendDocument($user['telegram_id'], $confPath, $docCaption);
            }
        }

        // 5. Send Report to Forum Topic (if configured)
        try {
            ReportHelper::reportPurchase($order, $user ?: ['telegram_id' => 0], $serviceData, $serviceData['panel_name'] ?? null);
        } catch (\Throwable $e) {
            // Ignore reporting errors
        }

        return $serviceData;
    }

    /**
     * Reject order and notify user
     */
    public static function rejectOrder(int $orderId, string $reason = 'اطلاعات واریز نامعتبر است.'): void
    {
        $order = Order::find($orderId);
        if (!$order) {
            throw new Exception("سفارش یافت نشد.");
        }

        Order::reject($orderId, $reason);

        // Notify user
        $bot = new Bot();
        $user = User::find($order['user_id']);
        if ($user && !empty($user['telegram_id'])) {
            $msg = "❌ <b>سفارش شما تایید نشد!</b>\n\n";
            $msg .= "📦 <b>پلن:</b> {$order['plan_name']}\n";
            $msg .= "⚠️ <b>دلیل عدم تایید:</b> {$reason}\n\n";
            $msg .= "در صورت بروز اشتباه یا نیاز به راهنمایی با پشتیبانی در ارتباط باشید.";

            $bot->sendMessage($user['telegram_id'], $msg, Keyboard::mainMenu());
        }
    }

    /**
     * Resolve a stored receipt value into something Telegram accepts:
     * - Telegram file_id (bot flow) → returned as-is
     * - Local path from Mini App (/storage/receipts/...) → CURLFile from disk
     * - Missing / unreadable → null (caller should send a plain message)
     */
    public static function resolveReceiptPhoto(?string $receiptPhoto): string|\CURLFile|null
    {
        if (empty($receiptPhoto)) {
            return null;
        }

        if (str_starts_with($receiptPhoto, '/')) {
            $fsPath = __DIR__ . '/../../public' . $receiptPhoto;
            if (!is_file($fsPath)) {
                return null;
            }
            $mime = @mime_content_type($fsPath) ?: 'image/jpeg';
            return new \CURLFile($fsPath, $mime, basename($fsPath));
        }

        return $receiptPhoto;
    }

    /**
     * Send alert to Admin on Telegram
     */
    public static function notifyAdminNewOrder(int $orderId): void
    {
        $adminId = Setting::get('admin_telegram_id');
        if (empty($adminId)) {
            return;
        }

        $order = Order::find($orderId);
        if (!$order) {
            return;
        }

        $bot = new Bot();
        $amountFmt = number_format($order['amount']) . ' تومان';
        $userText = ($order['first_name'] ?? '') . ($order['username'] ? " (@{$order['username']})" : "");

        $caption = "🔔 <b>رسید پرداخت جدید ثبت شد!</b>\n\n";
        $caption .= "🆔 <b>کد سفارش:</b> #{$order['id']}\n";
        $caption .= "👤 <b>کاربر:</b> {$userText} (<code>{$order['telegram_id']}</code>)\n";
        $caption .= "📦 <b>پلن درخواستی:</b> {$order['plan_name']}\n";
        $caption .= "💰 <b>مبلغ:</b> {$amountFmt}\n";
        if (!empty($order['receipt_text'])) {
            $caption .= "📝 <b>کد پیگیری / متن:</b> <code>{$order['receipt_text']}</code>\n";
        }
        $caption .= "\nجهت تایید یا رد سفارش از دکمه‌های زیر استفاده کنید:";

        $keyboard = Keyboard::adminOrderKeyboard($order['id']);

        $photoParam = self::resolveReceiptPhoto($order['receipt_photo'] ?? null);
        if ($photoParam !== null) {
            $bot->sendPhoto($adminId, $photoParam, $caption, $keyboard);
        } else {
            $bot->sendMessage($adminId, $caption, $keyboard);
        }

        // Also report to financial topic if group is configured
        try {
            ReportHelper::reportFinancial($order, ['first_name' => $order['first_name'] ?? '', 'telegram_id' => $order['telegram_id'] ?? 0], $order['receipt_text'] ?? '');
        } catch (\Throwable $e) {
            // Ignore
        }
    }
}
