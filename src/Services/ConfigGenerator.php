<?php

namespace App\Services;

use App\Models\Panel;
use App\Models\Plan;
use App\Models\Service;
use App\Panels\PanelManager;
use Exception;

class ConfigGenerator
{
    /**
     * Create VPN config for user according to plan and panel.
     */
    public static function createForOrder(array $order): array
    {
        $plan = Plan::find($order['plan_id']);
        if (!$plan) {
            throw new Exception("پلن مورد نظر یافت نشد.");
        }

        // Determine panel
        $panelId = $order['panel_id'] ?: ($plan['panel_id'] ?: 0);
        $panel = Panel::getAvailablePanel($panelId);

        if (!$panel) {
            throw new Exception("هیچ پنل فعالی با ظرفیت خالی برای ساخت کانفیگ یافت نشد.");
        }

        $panelAdapter = PanelManager::make($panel);

        // Generate client details
        $clientEmail = 'u' . $order['user_id'] . '_' . substr(md5(uniqid()), 0, 6);
        $trafficGb = (int)$plan['traffic_gb'];
        $days = (int)$plan['days'];
        $ipLimit = (int)$plan['ip_limit'];

        // Pick inbound if set in plan or panel
        $inboundId = 0;
        $inboundsJson = json_decode($panel['inbounds_json'] ?? '[]', true);
        if (!empty($inboundsJson) && is_array($inboundsJson)) {
            $inboundId = (int)$inboundsJson[0];
        }

        $result = $panelAdapter->createClient([
            'email' => $clientEmail,
            'uuid' => '',
            'traffic_gb' => $trafficGb,
            'days' => $days,
            'ip_limit' => $ipLimit,
            'inbound_id' => $inboundId,
        ]);

        if (!$result['success']) {
            throw new Exception("خطا از سمت سرور VPN: " . ($result['error'] ?? 'نامشخص'));
        }

        // Calculate expires_at
        $expiresAt = ($days > 0) ? date('Y-m-d H:i:s', time() + ($days * 86400)) : null;

        // Save service to database
        $wireguardConf = $result['wireguard_conf'] ?? null;
        if (empty($wireguardConf) && \App\Services\WireGuardHelper::isWireGuard($result['config_link'] ?? '')) {
            $wireguardConf = \App\Services\WireGuardHelper::toConf($result['config_link'], $result['email'] ?? $clientEmail);
        }

        $serviceId = Service::create([
            'order_id' => $order['id'],
            'user_id' => $order['user_id'],
            'panel_id' => $panel['id'],
            'client_uuid' => $result['uuid'] ?? $clientEmail,
            'client_email' => $result['email'] ?? $clientEmail,
            'inbound_id' => $result['inbound_id'] ?? $inboundId,
            'subscription_url' => $result['sub_url'] ?? '',
            'config_link' => $result['config_link'] ?? '',
            'wireguard_conf' => $wireguardConf,
            'traffic_gb' => $trafficGb,
            'days' => $days,
            'ip_limit' => $ipLimit,
            'expires_at' => $expiresAt,
        ]);

        // Increment client count on panel
        Panel::incrementClients($panel['id']);

        return [
            'service_id' => $serviceId,
            'panel_name' => $panel['name'],
            'client_email' => $result['email'] ?? $clientEmail,
            'sub_url' => $result['sub_url'] ?? '',
            'config_link' => $result['config_link'] ?? '',
            'wireguard_conf' => $wireguardConf,
            'traffic_gb' => $trafficGb,
            'days' => $days,
            'expires_at' => $expiresAt,
            'ip_limit' => $ipLimit,
        ];
    }
}
