<?php

namespace App\Telegram;

use App\Models\BotButton;
use App\Models\Setting;

class Keyboard
{
    /**
     * Main Menu Reply Keyboard built dynamically from database button layout & positions
     */
    public static function mainMenu(bool $isAdmin = false, int|string $telegramId = ''): array
    {
        $miniAppUrl = Setting::get('miniapp_url');
        $grid = BotButton::getActiveGrid();
        $keyboard = [];

        if ($isAdmin) {
            $keyboard[] = [['text' => '🛠 پنل مدیریت و آمار']];
        }

        foreach ($grid as $rowNum => $buttonsInRow) {
            $row = [];
            foreach ($buttonsInRow as $btn) {
                // If this is the MiniApp button and URL is configured, attach WebApp with user_id parameter
                if ($btn['key'] === 'btn_miniapp' && !empty($miniAppUrl)) {
                    $appUrl = $miniAppUrl;
                    if (!empty($telegramId)) {
                        $appUrl .= (str_contains($appUrl, '?') ? '&' : '?') . 'user_id=' . $telegramId;
                    }
                    $row[] = [
                        'text' => $btn['title'],
                        'web_app' => ['url' => $appUrl]
                    ];
                } else {
                    $row[] = [
                        'text' => $btn['title']
                    ];
                }
            }
            if (!empty($row)) {
                $keyboard[] = $row;
            }
        }

        // Fallback default if no buttons configured
        if (empty($keyboard)) {
            $appUrl = $miniAppUrl;
            if (!empty($telegramId) && !empty($appUrl)) {
                $appUrl .= (str_contains($appUrl, '?') ? '&' : '?') . 'user_id=' . $telegramId;
            }
            $keyboard = [
                !empty($appUrl) ? [['text' => '🚀 ورود به مینی‌اپ', 'web_app' => ['url' => $appUrl]]] : [['text' => '🚀 ورود به مینی‌اپ']],
                [['text' => '🛍 خرید اشتراک جدید'], ['text' => '📱 سرویس‌های فعال من']],
                [['text' => '📋 تعرفه و پلن‌ها'], ['text' => '💳 کارت به کارت و شارژ']],
                [['text' => '📖 راهنمای اتصال'], ['text' => '📞 پشتیبانی تلگرام']]
            ];
            // NOTE: no admin row needed here — when $isAdmin is true the keyboard
            // already contains the admin button above, so this block is only
            // ever reached for regular users.
        }

        return [
            'keyboard' => $keyboard,
            'resize_keyboard' => true,
            'persistent' => true,
        ];
    }

    /**
     * Panels Selection Keyboard for Bot (Step 1 of buying service)
     */
    public static function panelsKeyboard(array $panels): array
    {
        $inline = [];
        $row = [];
        foreach ($panels as $panel) {
            $name = $panel['name'];
            $row[] = ['text' => "🌐 {$name}", 'callback_data' => 'select_panel_' . $panel['id']];
            if (count($row) === 2) {
                $inline[] = $row;
                $row = [];
            }
        }
        if (!empty($row)) {
            $inline[] = $row;
        }

        $inline[] = [
            ['text' => '🔙 بازگشت به منوی اصلی', 'callback_data' => 'back_to_main']
        ];

        return ['inline_keyboard' => $inline];
    }

    /**
     * Plans Inline Keyboard for a specific panel (Step 2 of buying service)
     */
    public static function plansKeyboard(array $plans, int $panelId = 0): array
    {
        $inline = [];
        foreach ($plans as $plan) {
            $priceFormatted = number_format($plan['price']) . ' تومان';
            $limitParts = [];
            if ($plan['ip_limit'] > 0) $limitParts[] = "{$plan['ip_limit']} IP";
            if (!empty($plan['hwid_limit']) && $plan['hwid_limit'] > 0) $limitParts[] = "{$plan['hwid_limit']} HWID";
            $limitText = !empty($limitParts) ? ' (' . implode(' | ', $limitParts) . ')' : ' (نامحدود)';
            $text = "{$plan['name']} | {$plan['traffic_gb']} گیگ{$limitText} - {$priceFormatted}";
            $inline[] = [
                ['text' => $text, 'callback_data' => 'buy_plan_' . $plan['id']]
            ];
        }

        $backCallback = $panelId > 0 ? 'back_to_panels' : 'back_to_main';
        $inline[] = [
            ['text' => '🔙 بازگشت', 'callback_data' => $backCallback]
        ];

        return ['inline_keyboard' => $inline];
    }

    /**
     * Plan Checkout Confirmation Keyboard
     */
    public static function planCheckoutKeyboard(int $planId, bool $isAdmin = false, int $userBalance = 0, int $planPrice = 0): array
    {
        $buttons = [];

        // 1. Admin Instant Test Purchase
        if ($isAdmin) {
            $buttons[] = [
                ['text' => '⚡ تحویل آنی ویژه مدیر (تست و فعالسازی فوری)', 'callback_data' => 'admin_test_buy_' . $planId]
            ];
        }

        // 2. Pay via Wallet if sufficient balance
        if ($userBalance >= $planPrice && $planPrice > 0) {
            $buttons[] = [
                ['text' => '💰 پرداخت آنی از کیف پول (' . number_format($userBalance) . ' ت موجودی)', 'callback_data' => 'pay_wallet_' . $planId]
            ];
        }

        // 3. Pay via Card to Card
        $buttons[] = [
            ['text' => '💳 پرداخت کارت به کارت (ارسال فیش)', 'callback_data' => 'pay_card_' . $planId]
        ];

        // 4. Cancel
        $buttons[] = [
            ['text' => '❌ انصراف و بازگشت', 'callback_data' => 'cancel_order']
        ];

        return [
            'inline_keyboard' => $buttons
        ];
    }

    /**
     * Services List Keyboard
     */
    public static function servicesKeyboard(array $services): array
    {
        $inline = [];
        foreach ($services as $srv) {
            $text = "🔑 سرویس #{$srv['id']} ({$srv['traffic_gb']}GB - {$srv['panel_name']})";
            $inline[] = [
                ['text' => $text, 'callback_data' => 'view_service_' . $srv['id']]
            ];
        }

        $inline[] = [
            ['text' => '🔙 بازگشت', 'callback_data' => 'back_to_main']
        ];

        return ['inline_keyboard' => $inline];
    }

    /**
     * Admin Order Verification Inline Keyboard
     */
    public static function adminOrderKeyboard(int $orderId): array
    {
        return [
            'inline_keyboard' => [
                [
                    ['text' => '✅ تایید و صدور خودکار کانفیگ', 'callback_data' => 'admin_approve_' . $orderId],
                    ['text' => '❌ رد فیش', 'callback_data' => 'admin_reject_' . $orderId]
                ]
            ]
        ];
    }

    /**
     * Admin Panel Selection Keyboard matching exact screenshot layout
     */
    public static function panelTypesKeyboard(): array
    {
        return [
            'inline_keyboard' => [
                [
                    ['text' => '3x-ui (سنایی)', 'callback_data' => 'select_panel_sanaei'],
                    ['text' => 'مرزبان', 'callback_data' => 'select_panel_marzban'],
                ],
                [
                    ['text' => 'پاسارگاد', 'callback_data' => 'select_panel_pasargad'],
                    ['text' => 'هیدیفای', 'callback_data' => 'select_panel_hiddify'],
                ],
                [
                    ['text' => 'مرزنشین', 'callback_data' => 'select_panel_marzneshin'],
                    ['text' => 'فروش دستی', 'callback_data' => 'select_panel_manual'],
                ],
                [
                    ['text' => 'WGDashboard', 'callback_data' => 'select_panel_wgdashboard'],
                    ['text' => 'WG-Mate', 'callback_data' => 'select_panel_wgmate'],
                ],
                [
                    ['text' => 'میکروتیک', 'callback_data' => 'select_panel_mikrotik'],
                    ['text' => 'ibsng', 'callback_data' => 'select_panel_ibsng'],
                ],
                [
                    ['text' => 'Nexora', 'callback_data' => 'select_panel_nexora'],
                    ['text' => 'ربکا', 'callback_data' => 'select_panel_rebka'],
                ],
                [
                    ['text' => '🏠 بازگشت به منوی مدیریت', 'callback_data' => 'back_to_admin_menu'],
                ]
            ]
        ];
    }

    /**
     * Cancel step reply keyboard
     */
    public static function cancelKeyboard(): array
    {
        return [
            'keyboard' => [
                [['text' => '❌ انصراف']]
            ],
            'resize_keyboard' => true,
        ];
    }
}
