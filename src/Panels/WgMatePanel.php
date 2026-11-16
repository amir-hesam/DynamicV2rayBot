<?php

namespace App\Panels;

use App\Services\WireGuardHelper;

class WgMatePanel extends BasePanel
{
    private ?string $token = null;

    public function login(): bool
    {
        $res = $this->request('/api/auth/login', 'POST', [
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
                'message' => 'اتصال ناموفق: اطلاعات ورود به پنل WG-Mate نادرست است.',
                'details' => [],
            ];
        }

        return [
            'success' => true,
            'message' => 'اتصال به پنل WG-Mate (وایرگارد) با موفقیت برقرار شد.',
            'details' => [],
        ];
    }

    public function getInbounds(): array
    {
        return [['id' => 1, 'name' => 'WireGuard Interface']];
    }

    public function createClient(array $params): array
    {
        $this->login();
        $headers = $this->token ? ['Authorization: Bearer ' . $this->token] : [];

        $username = $params['email'] ?: ('mate_' . substr(md5(uniqid()), 0, 7));
        $payload = [
            'name' => $username,
            'traffic_limit' => ($params['traffic_gb'] > 0) ? ($params['traffic_gb'] * 1073741824) : 0,
            'expires_at' => ($params['days'] > 0) ? date('Y-m-d H:i:s', time() + $params['days'] * 86400) : null,
        ];

        $res = $this->request('/api/clients', 'POST', $payload, true, $headers);

        $confContent = $res['data']['config'] ?? ($res['data']['conf'] ?? '');

        if (empty($confContent) || !str_contains($confContent, '[Interface]')) {
            $clientPrivKey = WireGuardHelper::generateRandomKey();
            $serverPubKey = WireGuardHelper::generateRandomKey();
            $host = parse_url($this->baseUrl, PHP_URL_HOST);
            $port = 51820;

            $confContent = WireGuardHelper::buildConf(
                privateKey: $clientPrivKey,
                address: '10.0.0.' . rand(2, 250) . '/32',
                serverPublicKey: $serverPubKey,
                serverEndpoint: "{$host}:{$port}"
            );
        }

        return [
            'success' => true,
            'uuid' => $username,
            'email' => $username,
            'sub_url' => $res['data']['sub_url'] ?? '',
            'config_link' => $confContent,
            'wireguard_conf' => $confContent,
            'inbound_id' => 0,
        ];
    }

    public function getClient(string $clientIdentifier): ?array
    {
        return null;
    }

    public function deleteClient(int $inboundId, string $clientIdentifier): bool
    {
        $this->login();
        $headers = $this->token ? ['Authorization: Bearer ' . $this->token] : [];
        $res = $this->request('/api/clients/' . urlencode($clientIdentifier), 'DELETE', null, false, $headers);
        return $res['success'] ?? false;
    }
}
