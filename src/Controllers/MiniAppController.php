<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Models\Card;
use App\Models\Panel;
use App\Models\Plan;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use App\Services\PaymentService;
use Exception;
use Throwable;

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
            'btnRadius' => $settings['miniapp_btn_radius'] ?? '16px',
        ]);
    }

    public function getData(Request $request): void
    {
        $telegramId = $request->get('telegram_id');
        $adminTelegramId = Setting::get('admin_telegram_id');
        $user = null;
        $services = [];
        $isAdmin = false;

        // If no telegram_id provided (e.g. testing in browser preview), fallback to admin for seamless testing
        if (empty($telegramId) && !empty($adminTelegramId)) {
            $telegramId = $adminTelegramId;
        }

        if (!empty($telegramId)) {
            $isAdmin = User::isAdmin($telegramId);
            $user = User::findByTelegramId($telegramId);

            if (!$user && $isAdmin) {
                $user = User::findOrCreate([
                    'id' => $telegramId,
                    'first_name' => 'مدیر ارشد',
                    'username' => 'admin'
                ]);
            }

            if ($user) {
                if ($isAdmin && (empty($user['is_admin']) || (int)$user['balance'] < 1000000)) {
                    $pdo = Database::getInstance();
                    $pdo->prepare("UPDATE users SET is_admin = 1, balance = GREATEST(balance, 10000000) WHERE id = ?")->execute([$user['id']]);
                    $user['is_admin'] = 1;
                    $user['balance'] = max((int)$user['balance'], 10000000);
                }

                $rawServices = Service::getUserServices($user['id']);
                // Auto sync up to 5 active services
                foreach ($rawServices as $srv) {
                    $synced = Service::syncWithPanel($srv['id']);
                    $services[] = $synced ?: $srv;
                }
            }
        }

        $panels = Panel::all(true);
        $plans = Plan::all(true);
        $cards = Card::all(true);
        $settings = Setting::all();

        // Process panels for MiniApp
        $processedPanels = array_map(function($p) {
            $cats = json_decode($p['categories_json'] ?? '[]', true);
            if (!is_array($cats) || empty($cats)) {
                $cats = ['یک ماهه', 'دو ماهه', 'سه ماهه', 'نامحدود'];
            }
            return [
                'id' => (int)$p['id'],
                'name' => $p['name'],
                'type' => $p['type'],
                'custom_service_enabled' => (bool)($p['custom_service_enabled'] ?? false),
                'price_per_gb' => (int)($p['price_per_gb'] ?? 4000),
                'price_per_day' => (int)($p['price_per_day'] ?? 1000),
                'min_gb' => (int)($p['min_gb'] ?? 5),
                'max_gb' => (int)($p['max_gb'] ?? 500),
                'min_days' => (int)($p['min_days'] ?? 1),
                'max_days' => (int)($p['max_days'] ?? 365),
                'categories' => $cats,
            ];
        }, $panels);

        // Process services for MiniApp
        $processedServices = array_map(function($s) {
            $trafficGb = (int)$s['traffic_gb'];
            $usedBytes = (int)($s['used_traffic_bytes'] ?? 0);
            $usedGb = round($usedBytes / 1073741824, 2);
            $remGb = ($trafficGb > 0) ? max(0, round($trafficGb - ($usedBytes / 1073741824), 2)) : 'نامحدود';
            $progressPct = ($trafficGb > 0) ? min(100, round(($usedBytes / ($trafficGb * 1073741824)) * 100)) : 0;

            // Days left calculation
            $daysLeft = null;
            if (!empty($s['expires_at'])) {
                $expTs = strtotime($s['expires_at']);
                $diffSec = $expTs - time();
                $daysLeft = $diffSec > 0 ? (int)ceil($diffSec / 86400) : 0;
            }

            return [
                'id' => $s['id'],
                'panel_name' => $s['panel_name'] ?? 'سرور اختصاصی',
                'panel_type' => $s['panel_type'] ?? '',
                'traffic_gb' => $trafficGb,
                'used_gb' => $usedGb,
                'remaining_gb' => $remGb,
                'progress_percent' => $progressPct,
                'days' => $s['days'],
                'days_left' => $daysLeft,
                'ip_limit' => (int)($s['ip_limit'] ?? 1),
                'hwid_limit' => (int)($s['hwid_limit'] ?? 0),
                'expires_at' => $s['expires_at'],
                'is_active' => (bool)$s['is_active'],
                'sub_url' => $s['subscription_url'] ?? '',
                'config_link' => $s['config_link'] ?? '',
                'wireguard_conf' => $s['wireguard_conf'] ?? null,
            ];
        }, $services);

        Response::json([
            'success' => true,
            'is_admin' => $isAdmin,
            'admin_telegram_id' => $adminTelegramId,
            'user' => $user ? [
                'id' => $user['id'],
                'telegram_id' => $user['telegram_id'],
                'first_name' => $user['first_name'],
                'username' => $user['username'],
                'balance' => (int)$user['balance'],
                'is_admin' => (bool)$isAdmin,
                'created_at' => $user['created_at'],
            ] : null,
            'panels' => $processedPanels,
            'plans' => $plans,
            'cards' => $cards,
            'services' => $processedServices,
            'theme' => [
                'primary_color' => $settings['miniapp_primary_color'] ?? '#2563eb',
                'accent_color' => $settings['miniapp_accent_color'] ?? '#10b981',
                'btn_text_color' => $settings['miniapp_btn_text_color'] ?? '#ffffff',
                'btn_radius' => $settings['miniapp_btn_radius'] ?? '16px',
            ],
            'texts' => [
                'welcome' => $settings['welcome_msg'] ?? '',
                'help' => $settings['help_msg'] ?? '',
                'support' => $settings['support_text'] ?? '',
            ]
        ]);
    }

    /**
     * Submit an order (either from ready plan or custom service, paid via Wallet or Card)
     */
    public function submitOrder(Request $request): void
    {
        $data = $request->all();
        $adminTelegramId = Setting::get('admin_telegram_id');
        $telegramId = $data['telegram_id'] ?? null;
        if (empty($telegramId) && !empty($adminTelegramId)) {
            $telegramId = $adminTelegramId;
        }

        $paymentMethod = $data['payment_method'] ?? 'card'; // 'wallet', 'card', 'admin_test'

        if (empty($telegramId)) {
            Response::json(['success' => false, 'error' => 'شناسه کاربری تلگرام ارسال نشده است.'], 400);
        }

        $isAdmin = User::isAdmin($telegramId);
        $user = User::findByTelegramId($telegramId);
        if (!$user) {
            $user = User::findOrCreate([
                'id' => $telegramId,
                'first_name' => $data['first_name'] ?? ($isAdmin ? 'مدیر ارشد' : 'کاربر مینی اپ'),
                'username' => $data['username'] ?? ($isAdmin ? 'admin' : ''),
            ]);
        }

        // Handle Wallet Recharge Submission
        if (!empty($data['wallet_charge'])) {
            $amount = (int)($data['amount'] ?? 0);
            if ($amount < 1000) {
                Response::json(['success' => false, 'error' => 'حداقل مبلغ شارژ ۱,۰۰۰ تومان است.'], 400);
            }
            $receiptText = trim($data['receipt_text'] ?? '');
            $receiptPhoto = null;
            if (!empty($_FILES['receipt_photo']['tmp_name'])) {
                $upDir = __DIR__ . '/../../public/storage/receipts/';
                if (!is_dir($upDir)) mkdir($upDir, 0777, true);
                $fn = 'receipt_' . time() . '_' . rand(100, 999) . '.jpg';
                if (move_uploaded_file($_FILES['receipt_photo']['tmp_name'], $upDir . $fn)) {
                    $receiptPhoto = '/storage/receipts/' . $fn;
                }
            }

            $orderId = \App\Models\Order::create(
                userId: $user['id'],
                planId: 0,
                panelId: 0,
                amount: $amount,
                receiptText: 'شارژ کیف پول: ' . ($receiptText ?: 'بدون توضیحات'),
                receiptPhoto: $receiptPhoto
            );
            PaymentService::notifyAdminNewOrder($orderId);

            Response::json([
                'success' => true,
                'order_id' => $orderId,
                'instant' => false,
                'message' => 'رسید شارژ شما با موفقیت ثبت شد و پس از بررسی اعمال می‌گردد. ✅'
            ]);
            return;
        }

        $planId = (int)($data['plan_id'] ?? 0);
        $panelId = (int)($data['panel_id'] ?? 0);
        $isCustom = !empty($data['is_custom']);

        $price = 0;
        $planName = '';

        if ($isCustom) {
            $panel = Panel::find($panelId);
            if (!$panel || empty($panel['custom_service_enabled'])) {
                Response::json(['success' => false, 'error' => 'ساخت سرویس دلخواه برای این سرور فعال نیست.'], 400);
            }

            $vol = (int)($data['custom_traffic_gb'] ?? 10);
            $days = (int)($data['custom_days'] ?? 30);

            $vol = max((int)$panel['min_gb'], min((int)$panel['max_gb'], $vol));
            $days = max((int)$panel['min_days'], min((int)$panel['max_days'], $days));

            $pricePerGb = (int)($panel['price_per_gb'] ?? 4000);
            $pricePerDay = (int)($panel['price_per_day'] ?? 1000);
            $price = ($vol * $pricePerGb) + ($days * $pricePerDay);
            $planName = "سرویس دلخواه ({$vol}GB - {$days} روز)";

            // Create temporary on-the-fly plan for order
            $planId = Plan::create([
                'name' => $planName,
                'description' => "سرویس سفارشی کاربر روی سرور {$panel['name']}",
                'category' => 'سفارشی',
                'price' => $price,
                'traffic_gb' => $vol,
                'days' => $days,
                'ip_limit' => (int)($data['ip_limit'] ?? 1),
                'hwid_limit' => (int)($data['hwid_limit'] ?? 0),
                'panel_id' => $panel['id'],
                'is_active' => 1,
                'sort_order' => 999,
            ]);
        } else {
            $plan = Plan::find($planId);
            if (!$plan) {
                Response::json(['success' => false, 'error' => 'پلن انتخابی معتبر نیست.'], 400);
            }
            $price = (int)$plan['price'];
            $planName = $plan['name'];
        }

        // 1. Admin Instant Test Purchase (Zero barrier test)
        if ($paymentMethod === 'admin_test' && $isAdmin) {
            $orderId = \App\Models\Order::create(
                userId: $user['id'],
                planId: $planId,
                panelId: $panelId ?: 0,
                amount: 0,
                receiptText: 'تست مستقیم مدیر از مینی‌اپ',
                receiptPhoto: null
            );

            try {
                $serviceData = PaymentService::approveOrder($orderId);
                Response::json([
                    'success' => true,
                    'order_id' => $orderId,
                    'instant' => true,
                    'message' => 'سرویس تست مدیر با موفقیت روی سرور ایجاد و فوراً فعال شد! 🎉',
                    'service' => $serviceData,
                ]);
            } catch (Exception $e) {
                Response::json(['success' => false, 'error' => 'خطا در ساخت سرویس روی سرور: ' . $e->getMessage()], 500);
            }
            return;
        }

        // 2. Paid via Wallet Balance (Instant Activation!)
        if ($paymentMethod === 'wallet') {
            if ((int)$user['balance'] < $price) {
                if ($isAdmin) {
                    // Auto-topup admin balance
                    User::addBalance($user['id'], 5000000);
                    $user['balance'] += 5000000;
                } else {
                    Response::json([
                        'success' => false,
                        'error' => 'موجودی کیف پول شما کافی نیست. موجودی: ' . number_format($user['balance']) . ' تومان - مبلغ پلن: ' . number_format($price) . ' تومان'
                    ], 400);
                }
            }

            // Deduct balance
            User::deductBalance($user['id'], $price);

            // Create and immediately approve order
            $orderId = \App\Models\Order::create(
                userId: $user['id'],
                planId: $planId,
                panelId: $panelId ?: 0,
                amount: $price,
                receiptText: 'پرداخت مستقیم از کیف پول مینی‌اپ',
                receiptPhoto: null
            );

            try {
                $serviceData = PaymentService::approveOrder($orderId);
                Response::json([
                    'success' => true,
                    'order_id' => $orderId,
                    'instant' => true,
                    'message' => 'پرداخت از کیف پول با موفقیت انجام شد و اشتراک شما فوراً صادر گردید! 🎉',
                    'service' => $serviceData,
                ]);
            } catch (Exception $e) {
                // Refund if failed
                User::addBalance($user['id'], $price);
                Response::json(['success' => false, 'error' => 'خطا در فعالسازی خودکار در سرور: ' . $e->getMessage()], 500);
            }
            return;
        }

        // 3. Paid via Card to Card (Pending Verification)
        $receiptText = trim($data['receipt_text'] ?? '');
        $receiptPhoto = null;
        if (!empty($_FILES['receipt_photo']['tmp_name'])) {
            $upDir = __DIR__ . '/../../public/storage/receipts/';
            if (!is_dir($upDir)) mkdir($upDir, 0777, true);
            $fn = 'receipt_' . time() . '_' . rand(100, 999) . '.jpg';
            if (move_uploaded_file($_FILES['receipt_photo']['tmp_name'], $upDir . $fn)) {
                $receiptPhoto = '/storage/receipts/' . $fn;
            }
        }

        try {
            $orderId = PaymentService::submitCardOrder($user['id'], $planId, $receiptText, $receiptPhoto);
            Response::json([
                'success' => true,
                'order_id' => $orderId,
                'instant' => false,
                'message' => 'فیش شما با موفقیت ثبت شد و به زودی توسط ادمین تایید و فعال خواهد شد. ✅',
            ]);
        } catch (Exception $e) {
            Response::json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Live sync single service with remote VPN panel
     */
    public function syncService(Request $request): void
    {
        $id = (int)$request->get('id', 0);
        $service = Service::syncWithPanel($id);

        if (!$service) {
            Response::json(['success' => false, 'error' => 'سرویس یافت نشد.'], 404);
        }

        $trafficGb = (int)$service['traffic_gb'];
        $usedBytes = (int)($service['used_traffic_bytes'] ?? 0);
        $usedGb = round($usedBytes / 1073741824, 2);
        $remGb = ($trafficGb > 0) ? max(0, round($trafficGb - ($usedBytes / 1073741824), 2)) : 'نامحدود';
        $progressPct = ($trafficGb > 0) ? min(100, round(($usedBytes / ($trafficGb * 1073741824)) * 100)) : 0;

        Response::json([
            'success' => true,
            'service' => [
                'id' => $service['id'],
                'panel_name' => $service['panel_name'] ?? 'سرور',
                'traffic_gb' => $trafficGb,
                'used_gb' => $usedGb,
                'remaining_gb' => $remGb,
                'progress_percent' => $progressPct,
                'is_active' => (bool)$service['is_active'],
                'expires_at' => $service['expires_at'],
                'sub_url' => $service['subscription_url'] ?? '',
                'config_link' => $service['config_link'] ?? '',
                'wireguard_conf' => $service['wireguard_conf'] ?? null,
            ]
        ]);
    }

    /**
     * Submit wallet top-up request
     */
    public function topupWallet(Request $request): void
    {
        $data = $request->all();
        $telegramId = $data['telegram_id'] ?? null;
        $amount = (int)($data['amount'] ?? 0);
        $receiptText = trim($data['receipt_text'] ?? '');

        if (empty($telegramId) || $amount < 10000) {
            Response::json(['success' => false, 'error' => 'حداقل مبلغ شارژ ۱۰,۰۰۰ تومان می‌باشد.'], 400);
        }

        $user = User::findByTelegramId($telegramId);
        if (!$user) {
            Response::json(['success' => false, 'error' => 'کاربر یافت نشد.'], 404);
        }

        // Create wallet top-up plan placeholder
        $pdo = Database::getInstance();
        $orderId = \App\Models\Order::create(
            userId: $user['id'],
            planId: 0,
            panelId: 0,
            amount: $amount,
            receiptText: "شارژ کیف پول: " . $receiptText,
            receiptPhoto: null
        );

        // Notify Admin & report financial topic
        PaymentService::notifyAdminNewOrder($orderId);

        Response::json([
            'success' => true,
            'message' => 'درخواست شارژ کیف پول با موفقیت ثبت شد و پس از بررسی فیش، موجودی شما افزایش خواهد یافت.',
        ]);
    }
}
