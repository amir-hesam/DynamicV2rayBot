<?php

namespace App\Panels;

use App\Services\WireGuardHelper;

class ManualPanel extends BasePanel
{
    public function testConnection(): array
    {
        return [
            'success' => true,
            'message' => 'پنل فروش دستی فعال و آماده تحویل کانفیگ‌ها است.',
            'details' => [],
        ];
    }

    public function getInbounds(): array
    {
        return [['id' => 1, 'name' => 'کانفیگ‌های دستی و آماده']];
    }

    public function createClient(array $params): array
    {
        $rawConfig = $this->panel['password'] ?: ($this->panel['url'] ?? '');
        $username = $params['email'] ?: ('manual_' . substr(md5(uniqid()), 0, 6));

        $wireguardConf = null;
        if (WireGuardHelper::isWireGuard($rawConfig)) {
            $wireguardConf = WireGuardHelper::toConf($rawConfig, $username);
        }

        return [
            'success' => true,
            'uuid' => $username,
            'email' => $username,
            'sub_url' => '',
            'config_link' => $rawConfig,
            'wireguard_conf' => $wireguardConf,
            'inbound_id' => 0,
        ];
    }

    public function getClient(string $clientIdentifier): ?array
    {
        return null;
    }

    public function deleteClient(int $inboundId, string $clientIdentifier): bool
    {
        return true;
    }
}
