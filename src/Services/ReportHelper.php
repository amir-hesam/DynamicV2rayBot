<?php

namespace App\Services;

use App\Models\Setting;
use App\Telegram\Bot;

class ReportHelper
{
    // The 10 standard topics from D:\PanelSaz & MirzaBot
    const TOPIC_PURCHASE   = 'topic_purchase';      // 📣 گزارش خرید
    const TOPIC_TEST       = 'topic_test';          // 🧪 گزارش اکانت تست
    const TOPIC_BACKUP     = 'topic_backup';        // 💾 بک اپ ربات
    const TOPIC_NOTIFY     = 'topic_notify';        // 📢 اطلاع رسانی
    const TOPIC_DAILY      = 'topic_daily';         // 📊 گزارش شبانه
    const TOPIC_FINANCIAL  = 'topic_financial';     // 💰 گزارش مالی
    const TOPIC_OTHER      = 'topic_other';         // 👥 سایر گزارشات
    const TOPIC_ERROR      = 'topic_error';         // 🚨 گزارش خطا
    const TOPIC_RENEWAL    = 'topic_renewal';       // 🔄 گزارش تمدید و خدمات
    const TOPIC_COMMISSION = 'topic_commission';    // 💎 گزارش پورسانت ها

    public static function getTopicDefinitions(): array
    {
        return [
            self::TOPIC_PURCHASE   => ['name' => '📣 گزارش خرید',        'color' => 0x6FB9F0],
            self::TOPIC_TEST       => ['name' => '🧪 گزارش اکانت تست',   'color' => 0xFFD67E],
            self::TOPIC_BACKUP     => ['name' => '💾 بک اپ ربات',         'color' => 0xCB86DB],
            self::TOPIC_NOTIFY     => ['name' => '📢 اطلاع رسانی',        'color' => 0x8EEE98],
            self::TOPIC_DAILY      => ['name' => '📊 گزارش شبانه',        'color' => 0xFF93B2],
            self::TOPIC_FINANCIAL  => ['name' => '💰 گزارش مالی',         'color' => 0xFB6F5F],
            self::TOPIC_OTHER      => ['name' => '👥 سایر گزارشات',       'color' => 0x6FB9F0],
            self::TOPIC_ERROR      => ['name' => '🚨 گزارش خطا',         'color' => 0xFB6F5F],
            self::TOPIC_RENEWAL    => ['name' => '🔄 گزارش تمدید و خدمات', 'color' => 0xFFD67E],
            self::TOPIC_COMMISSION => ['name' => '💎 گزارش پورسانت ها',    'color' => 0x8EEE98],
        ];
    }

    public static function getGroupId(): ?string
    {
        $id = Setting::get('report_group_id');
        if (empty($id)) {
            $id = Setting::get('telegram_report_group_id');
        }
        return !empty($id) ? (string)$id : null;
    }

    public static function getTopicThreadId(string $topicKey): ?int
    {
        $val = Setting::get($topicKey);
        if ($val === null || $val === '') {
            $val = Setting::get($topicKey . '_id');
        }
        return ($val !== null && is_numeric($val) && (int)$val > 0) ? (int)$val : null;
    }

    /**
     * Create and synchronize all 10 topics in the Telegram Forum group
     * Based on MirzaBot syncReportTopics & PanelSaz
     */
    public static function syncTopics(string $chatId): array
    {
        $bot = new Bot();
        $definitions = self::getTopicDefinitions();
        $results = [];

        // Save report group ID
        Setting::set('report_group_id', $chatId);

        foreach ($definitions as $key => $info) {
            $name = $info['name'];
            $color = $info['color'] ?? null;
            $existingId = self::getTopicThreadId($key);

            if ($existingId) {
                // Try to edit existing topic to test if it's alive
                $editRes = $bot->editForumTopic($chatId, $existingId, $name);
                if (!empty($editRes['ok']) || str_contains($editRes['description'] ?? '', 'TOPIC_NOT_MODIFIED')) {
                    $results[$key] = [
                        'status' => 'exists',
                        'thread_id' => $existingId,
                        'name' => $name,
                    ];
                    continue;
                }
            }

            // Create new forum topic
            $createRes = $bot->createForumTopic($chatId, $name, $color);
            if (!empty($createRes['ok']) && !empty($createRes['result']['message_thread_id'])) {
                $threadId = (int)$createRes['result']['message_thread_id'];
                Setting::set($key, (string)$threadId);
                $results[$key] = [
                    'status' => 'created',
                    'thread_id' => $threadId,
                    'name' => $name,
                ];
            } else {
                $results[$key] = [
                    'status' => 'failed',
                    'error' => $createRes['description'] ?? 'خطا در ساخت تاپیک',
                    'name' => $name,
                ];
            }
        }

        return [
            'success' => true,
            'chat_id' => $chatId,
            'topics' => $results,
        ];
    }

    /**
     * Send HTML text message to a specific topic
     */
    public static function sendToTopic(string $topicKey, string $text, array $replyMarkup = []): array
    {
        $groupId = self::getGroupId();
        if (!$groupId) {
            return ['ok' => false, 'error' => 'گروه گزارشات تنظیم نشده است.'];
        }

        $bot = new Bot();
        $threadId = self::getTopicThreadId($topicKey);

        if (!$threadId) {
            return $bot->sendMessage($groupId, $text, $replyMarkup);
        }

        $res = $bot->sendMessage($groupId, $text, $replyMarkup, 'HTML', $threadId);
        if (empty($res['ok'])) {
            $desc = strtolower($res['description'] ?? '');
            if (str_contains($desc, 'thread not found') || str_contains($desc, 'topic_deleted')) {
                Setting::set($topicKey, '');
                return $bot->sendMessage($groupId, "⚠️ [ارسال در عمومی گروه - تاپیک مربوطه در تلگرام حذف شده است]:\n\n" . $text, $replyMarkup);
            }
        }

        return $res;
    }

    /**
     * Send document file to a specific topic
     */
    public static function sendDocumentToTopic(string $topicKey, string $filePath, string $caption = '', array $replyMarkup = []): array
    {
        $groupId = self::getGroupId();
        if (!$groupId) {
            return ['ok' => false, 'error' => 'گروه گزارشات تنظیم نشده است.'];
        }

        $bot = new Bot();
        $threadId = self::getTopicThreadId($topicKey);

        if (!$threadId) {
            return $bot->sendDocument($groupId, $filePath, $caption, $replyMarkup);
        }

        $res = $bot->sendDocument($groupId, $filePath, $caption, $replyMarkup, 'HTML', $threadId);
        if (empty($res['ok'])) {
            $desc = strtolower($res['description'] ?? '');
            if (str_contains($desc, 'thread not found') || str_contains($desc, 'topic_deleted')) {
                Setting::set($topicKey, '');
                return $bot->sendDocument($groupId, $filePath, $caption, $replyMarkup);
            }
        }

        return $res;
    }

    /**
     * Report a new purchase or approved service
     */
    public static function reportPurchase(array $order, array $user, array $service, ?string $panelName = null): void
    {
        $text = "🛍 <b>گزارش خرید اشتراک جدید</b>\n\n";
        $text .= "👤 <b>کاربر:</b> " . htmlspecialchars($user['first_name'] ?? 'بی‌نام') . " (@" . ($user['username'] ?: 'ندارد') . ")\n";
        $text .= "🆔 <b>شناسه تلگرام:</b> <code>{$user['telegram_id']}</code>\n";
        $text .= "📦 <b>سرویس:</b> #{$service['id']} ({$service['traffic_gb']} GB - {$service['days']} روز)\n";
        $text .= "🌐 <b>سرور متصل:</b> " . ($panelName ?: 'سرور پیش‌فرض') . "\n";
        $text .= "💰 <b>مبلغ پرداختی:</b> " . number_format($order['amount']) . " تومان\n";
        $text .= "📅 <b>تاریخ ثبت:</b> " . date('Y-m-d H:i:s');

        self::sendToTopic(self::TOPIC_PURCHASE, $text);
    }

    /**
     * Report receipt upload / financial event
     */
    public static function reportFinancial(array $order, array $user, string $note = ''): void
    {
        $text = "💰 <b>گزارش مالی و کارت‌به‌کارت جدید</b>\n\n";
        $text .= "🧾 <b>سفارش:</b> #{$order['id']}\n";
        $text .= "👤 <b>کاربر:</b> " . htmlspecialchars($user['first_name'] ?? '') . " (<code>{$user['telegram_id']}</code>)\n";
        $text .= "💳 <b>مبلغ فیش:</b> " . number_format($order['amount']) . " تومان\n";
        if ($note) {
            $text .= "📝 <b>توضیحات:</b> {$note}\n";
        }
        $text .= "📅 <b>زمان:</b> " . date('Y-m-d H:i:s');

        self::sendToTopic(self::TOPIC_FINANCIAL, $text);
    }

    /**
     * Report an error
     */
    public static function reportError(string $title, string $details): void
    {
        $text = "🚨 <b>گزارش خطای سیستمی</b>\n\n";
        $text .= "⚠️ <b>عنوان:</b> {$title}\n";
        $text .= "📋 <b>جزئیات:</b> <pre>" . htmlspecialchars(substr($details, 0, 1000)) . "</pre>\n";
        $text .= "📅 <b>زمان:</b> " . date('Y-m-d H:i:s');

        self::sendToTopic(self::TOPIC_ERROR, $text);
    }
}
