<?php

namespace App\Panels;

use App\Services\WireGuardHelper;

class NexoraPanel extends BasePanel
{
    private ?string $token = null;

    public function login(): bool
    {
        $res = $this->request('/api/login', 'POST', [
            'username' => $this->panel['username'],
            'password' => $this->panel['password'],
        ], true);

        if (isset($res['data']['token'])) {
            $this->token = $res['data']['token'];
            return true;
        }

        return false;
    }

    public function testConnection(): array
    {
        if (!$this->login()) {
            return [
                'success' => false,
                'message' => 'اتصال ناموفق: اطلاعات ورود به پنل Nexora نادرست است.',
            ];
        }

        return [
            'success' => true,
            'message' => 'اتصال به پنل Nexora با موفقیت برقرار شد.',
        ];
    }

    public function getInbounds(): array
    {
        return [['id' => 1, 'name' => 'Nexora Inbounds']];
    }

    public function createClient(array $params): array
    {
        $this->login();
        $headers = $this->token ? ['Authorization: Bearer ' . $this->token] : [];

        $username = $params['email'] ?: ('nx_' . substr(md5(uniqid()), 0, 7));
        $payload = [
            'username' => $username,
            'traffic' => $params['traffic_gb'] ?? 30,
            'expire_days' => $params['days'] ?? 30,
        ];

        $res = $this->request('/api/users', 'POST', $payload, true, $headers);

        $subUrl = $res['data']['sub_url'] ?? ($this->baseUrl . '/sub/' . $username);
        $link = $res['data']['config'] ?? ($res['data']['link'] ?? '');

        $wireguardConf = null;
        if (!empty($link) && WireGuardHelper::isWireGuard($link)) {
            $wireguardConf = WireGuardHelper::toConf($link, $username);
        }

        return [
            'success' => true,
            'uuid' => $username,
            'email' => $username,
            'sub_url' => $subUrl,
            'config_link' => $link ?: $subUrl,
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
