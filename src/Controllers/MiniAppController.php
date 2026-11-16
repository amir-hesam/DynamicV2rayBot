<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Models\Card;
use App\Models\Plan;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use App\Services\PaymentService;
use Exception;

class MiniAppController
{
    public function index(Request $request): void
    {
        $settings = Setting::all();

        View::render('miniapp.index', [
            'settings' => $settings,
            'primaryColor' => $settings['miniapp_primary_color'] ?? '#2563eb',
            'accentColor' => $settings['miniapp_accent_color'] ?? '#10b981',
            'textColor' => $settings['miniapp_btn_text_color'] ?? '#ffffff',
            'btnRadius' => $settings['miniapp_btn_radius'] ?? '12px',
        ]);
    }

    public function getData(Request $request): void
    {
        $telegramId = $request->get('telegram_id');
        $user = null;
        $services = [];

        if (!empty($telegramId)) {
            $user = User::findByTelegramId($telegramId);
            if ($user) {
                $services = Service::getUserServices($user['id']);
            }
        }

        $plans = Plan::all(true);
        $cards = Card::all(true);
        $settings = Setting::all();

        Response::json([
            'success' => true,
            'user' => $user,
            'plans' => $plans,
            'cards' => $cards,
            'services' => array_map(function($s) {
                return [
                    'id' => $s['id'],
                    'panel_name' => $s['panel_name'],
                    'traffic_gb' => $s['traffic_gb'],
                    'used_gb' => round(($s['used_traffic_bytes'] ?? 0) / 1073741824, 2),
                    'days' => $s['days'],
                    'ip_limit' => $s['ip_limit'],
                    'expires_at' => $s['expires_at'],
                    'sub_url' => $s['subscription_url'],
                    'config_link' => $s['config_link'],
                    'wireguard_conf' => $s['wireguard_conf'] ?? null,
                ];
            }, $services),
            'theme' => [
                'primary_color' => $settings['miniapp_primary_color'] ?? '#2563eb',
                'accent_color' => $settings['miniapp_accent_color'] ?? '#10b981',
                'btn_text_color' => $settings['miniapp_btn_text_color'] ?? '#ffffff',
                'btn_radius' => $settings['miniapp_btn_radius'] ?? '12px',
            ],
            'texts' => [
                'welcome' => $settings['welcome_msg'] ?? '',
                'help' => $settings['help_msg'] ?? '',
                'support' => $settings['support_text'] ?? '',
                'card_instruction' => $settings['card_instruction'] ?? '',
            ]
        ]);
    }

    public function submitOrder(Request $request): void
    {
        $data = $request->all();
        $telegramId = $data['telegram_id'] ?? null;
        $planId = (int)($data['plan_id'] ?? 0);
        $receiptText = trim($data['receipt_text'] ?? '');

        if (empty($telegramId) || empty($planId)) {
            Response::json(['success' => false, 'error' => 'اطلاعات ارسالی ناقص است.'], 400);
        }

        $user = User::findByTelegramId($telegramId);
        if (!$user) {
            $user = User::findOrCreate([
                'id' => $telegramId,
                'first_name' => $data['first_name'] ?? 'کاربر مینی اپ',
                'username' => $data['username'] ?? '',
            ]);
        }

        try {
            $orderId = PaymentService::submitCardOrder($user['id'], $planId, $receiptText, null);
            Response::json([
                'success' => true,
                'order_id' => $orderId,
                'message' => 'سفارش شما با موفقیت ثبت شد و پس از بررسی فعال خواهد شد.',
            ]);
        } catch (Exception $e) {
            Response::json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
