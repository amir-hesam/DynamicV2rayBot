<?php

namespace App\Services;

use App\Models\Setting;
use App\Telegram\Bot;

class ReportHelper
{
    // The 10 standard topics matching MirzaBot
    const TOPIC_BUY        = 'buyreport';          // 🛒 خریدها و سفارشات جدید
    const TOPIC_SERVICE    = 'otherservice';       // ⚡ سرویس‌ها و تمدید اشتراک
    const TOPIC_TEST       = 'reporttest';         // 🧪 اکانت‌های تست و آزمایشی
    const TOPIC_PAYMENT    = 'paymentreport';      // 💳 پرداخت‌ها و واریزی‌های شتاب
    const TOPIC_AFFILIATE  = 'porsantreport';      // 👥 بازاریابی و امور معرفین
    const TOPIC_ERROR      = 'errorreport';        // 🚨 خطاها و لاگ‌های سیستم
    const TOPIC_NIGHT      = 'reportnight';        // 🌙 آمار روزانه و شبانه
    const TOPIC_CRON       = 'reportcron';         // ⏱ وضعیت کرون‌جاب‌ها
    const TOPIC_BACKUP     = 'backupfile';         // 💾 فایل‌های پشتیبان دیتابیس
    const TOPIC_OTHER      = 'otherreport';        // 📋 سایر گزارشات و رویدادها

    // Backward compatibility aliases
    const TOPIC_PURCHASE   = 'buyreport';
    const TOPIC_FINANCIAL  = 'paymentreport';
    const TOPIC_DAILY      = 'reportnight';
    const TOPIC_COMMISSION = 'porsantreport';
    const TOPIC_RENEWAL    = 'otherservice';
    const TOPIC_NOTIFY     = 'otherreport';

    public static function getTopicDefinitions(): array
    {
        return [
            self::TOPIC_BUY       => ['name' => '🛒 خریدها و سفارشات جدید',    'color' => 0x6FB9F0],
            self::TOPIC_SERVICE   => ['name' => '⚡ سرویس‌ها و تمدید اشتراک',  'color' => 0xFFD67E],
            self::TOPIC_TEST      => ['name' => '🧪 اکانت‌های تست و آزمایشی', 'color' => 0x8EEE98],
            self::TOPIC_PAYMENT   => ['name' => '💳 پرداخت‌ها و واریزی‌ها',   'color' => 0xFB6F5F],
            self::TOPIC_AFFILIATE => ['name' => '👥 بازاریابی و امور معرفین', 'color' => 0xCB86DB],
            self::TOPIC_ERROR     => ['name' => '🚨 خطاها و لاگ‌های سیستم',    'color' => 0xFB6F5F],
            self::TOPIC_NIGHT     => ['name' => '🌙 آمار روزانه و شبانه',      'color' => 0xFF93B2],
            self::TOPIC_CRON      => ['name' => '⏱ وضعیت کرون‌جاب‌ها',       'color' => 0x8EEE98],
            self::TOPIC_BACKUP    => ['name' => '💾 فایل‌های پشتیبان دیتابیس', 'color' => 0xCB86DB],
            self::TOPIC_OTHER     => ['name' => '📋 سایر گزارشات و رویدادها', 'color' => 0x6FB9F0],
        ];
    }

    public static function getGroupId(): ?string
    {
        $id = Setting::get('report_group_id');
        if (empty($id)) {
            $id = Setting::get('Channel_Report');
        }
        if (empty($id)) {
            $id = Setting::get('telegram_report_group_id');
        }
        return !empty($id) ? (string)$id : null;
    }

    public static function getTopicThreadId(string $topicKey): ?int
    {
        // Try direct key, e.g. buyreport or backupfile
        $val = Setting::get($topicKey);
        if ($val === null || $val === '') {
            $val = Setting::get($topicKey . '_id');
        }

        // Aliases fallback
        if (($val === null || $val === '') && $topicKey === self::TOPIC_BACKUP) {
            $val = Setting::get('topic_backup') ?: Setting::get('backupfile');
        }
        if (($val === null || $val === '') && $topicKey === self::TOPIC_BUY) {
            $val = Setting::get('topic_purchase') ?: Setting::get('buyreport');
        }
        if (($val === null || $val === '') && $topicKey === self::TOPIC_PAYMENT) {
            $val = Setting::get('topic_financial') ?: Setting::get('paymentreport');
        }

        return ($val !== null && is_numeric($val) && (int)$val > 0) ? (int)$val : null;
    }

    /**
     * Create and synchronize all 10 topics in the Telegram Forum group
     * Exact MirzaBot admin.php topic creation logic with forum check
     */
    public static function syncTopics(string $chatId): array
    {
        $bot = new Bot();
        $definitions = self::getTopicDefinitions();
        $results = [];

        // 1. Test sending a message to verify bot membership & admin permissions
        $testRes = $bot->sendMessage($chatId, "🔄 <b>در حال بررسی اتصال ربات و ساخت تاپیک‌های گروه گزارشات...</b>");
        if (empty($testRes['ok'])) {
            return [
                'success' => false,
                'error' => 'ربات امکان ارسال پیام به این گروه را ندارد. اطمینان حاصل کنید ربات در گروه عضو و ادمین است: ' . ($testRes['description'] ?? 'عدم دسترسی')
            ];
        }

        $chat = $testRes['result']['chat'] ?? [];
        if (empty($chat['is_forum'])) {
            return [
                'success' => false,
                'error' => 'گروه ارسالی سوپرگروه با قابلیت تاپیک (Forum / Topics) نیست! لطفاً در تنظیمات گروه تلگرام گزینه Topics را فعال نمایید.'
            ];
        }

        // Save report group ID in both modern & MirzaBot keys
        Setting::set('report_group_id', $chatId);
        Setting::set('Channel_Report', $chatId);

        $createdCount = 0;
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

            // Create new forum topic via Telegram Bot API (MirzaBot pattern)
            $createRes = $bot->createForumTopic($chatId, $name, $color);
            if (!empty($createRes['ok']) && !empty($createRes['result']['message_thread_id'])) {
                $threadId = (int)$createRes['result']['message_thread_id'];
                // Save by Mirza key and legacy key
                Setting::set($key, (string)$threadId);
                Setting::set('topic_' . $key, (string)$threadId);
                $results[$key] = [
                    'status' => 'created',
                    'thread_id' => $threadId,
                    'name' => $name,
                ];
                $createdCount++;
            } else {
                $results[$key] = [
                    'status' => 'failed',
                    'error' => $createRes['description'] ?? 'خطا در ساخت تاپیک',
                    'name' => $name,
                ];
            }
        }

        // Send a pin welcome message into General topic
        $welcomeText = "✅ <b>گروه گزارشات ربات هوشمند با موفقیت پیکربندی شد!</b>\n\n";
        $welcomeText .= "تعداد ۱۰ تاپیک تخصصی جهت ثبت رویدادها، خریدها، تراکنش‌های مالی، خطاها و بکاپ‌های روزانه آماده به کار هستند.";
        $bot->sendMessage($chatId, $welcomeText);

        return [
            'success' => true,
            'chat_id' => $chatId,
            'topics' => $results,
            'created_count' => $createdCount,
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
