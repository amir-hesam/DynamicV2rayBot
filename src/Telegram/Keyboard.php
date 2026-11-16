<?php

namespace App\Telegram;

use App\Models\BotButton;
use App\Models\Setting;

class Keyboard
{
    /**
     * Main Menu Reply Keyboard built dynamically from database button layout & positions
     */
    public static function mainMenu(): array
    {
        $miniAppUrl = Setting::get('miniapp_url');
        $grid = BotButton::getActiveGrid();
        $keyboard = [];

        foreach ($grid as $rowNum => $buttonsInRow) {
            $row = [];
            foreach ($buttonsInRow as $btn) {
                // If this is the MiniApp button and URL is configured, attach WebApp
                if ($btn['key'] === 'btn_miniapp' && !empty($miniAppUrl)) {
                    $row[] = [
                        'text' => $btn['title'],
                        'web_app' => ['url' => $miniAppUrl]
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
            $keyboard = [
                [['text' => '🛍 خرید اشتراک جدید'], ['text' => '📱 سرویس‌های فعال من']],
                [['text' => '📋 تعرفه و پلن‌ها'], ['text' => '💳 کارت به کارت و شارژ']],
                [['text' => '📖 راهنمای اتصال'], ['text' => '📞 پشتیبانی تلگرام']]
            ];
        }

        return [
            'keyboard' => $keyboard,
            'resize_keyboard' => true,
            'persistent' => true,
        ];
    }

    /**
     * Plans Inline Keyboard
     */
    public static function plansKeyboard(array $plans): array
    {
        $inline = [];
        foreach ($plans as $plan) {
            $priceFormatted = number_format($plan['price']) . ' تومان';
            $limitText = ($plan['ip_limit'] > 0) ? " ({$plan['ip_limit']} کاربر)" : " (نامحدود)";
            $text = "{$plan['name']} | {$plan['traffic_gb']} گیگ{$limitText} - {$priceFormatted}";
            $inline[] = [
                ['text' => $text, 'callback_data' => 'buy_plan_' . $plan['id']]
            ];
        }

        $inline[] = [
            ['text' => '🔙 بازگشت به منوی اصلی', 'callback_data' => 'back_to_main']
        ];

        return ['inline_keyboard' => $inline];
    }

    /**
     * Plan Checkout Confirmation Keyboard
     */
    public static function planCheckoutKeyboard(int $planId): array
    {
        return [
            'inline_keyboard' => [
                [
                    ['text' => '💳 پرداخت کارت به کارت', 'callback_data' => 'pay_card_' . $planId],
                ],
                [
                    ['text' => '❌ انصراف و بازگشت', 'callback_data' => 'cancel_order']
                ]
            ]
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
