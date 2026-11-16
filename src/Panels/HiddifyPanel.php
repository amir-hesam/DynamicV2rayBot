<?php

namespace App\Panels;

use App\Services\WireGuardHelper;

class HiddifyPanel extends BasePanel
{
    private ?string $apiKey = null;

    public function login(): bool
    {
        $this->apiKey = $this->panel['password'];
        return !empty($this->apiKey);
    }

    public function testConnection(): array
    {
        $this->login();
        $headers = [
            'Hiddify-API-Key: ' . $this->apiKey,
            'Accept: application/json',
        ];

        // Test /api/v2/admin/status/ or /api/v1/status/
        $res = $this->request('/api/v2/admin/status/', 'GET', null, false, $headers);
        if ($res['success']) {
            return ['success' => true, 'message' => 'اتصال به پنل هیدیفای (Hiddify) با موفقیت برقرار شد.'];
        }

        $res2 = $this->request('/api/v1/status/', 'GET', null, false, $headers);
        if ($res2['success']) {
            return ['success' => true, 'message' => 'اتصال به پنل هیدیفای برقرار شد.'];
        }

        return ['success' => false, 'message' => 'اتصال ناموفق: توکن یا آدرس پنل هیدیفای بررسی شود.'];
    }

    public function getInbounds(): array
    {
        return [['id' => 1, 'name' => 'Hiddify Inbounds']];
    }

    public function createClient(array $params): array
    {
        $this->login();
        $headers = [
            'Hiddify-API-Key: ' . $this->apiKey,
            'Accept: application/json',
        ];

        $uuid = $params['uuid'] ?: $this->generateUuid();
        $name = $params['email'] ?: ('h_' . substr(md5(uniqid()), 0, 7));

        $payload = [
            'name' => $name,
            'uuid' => $uuid,
            'usage_limit_GB' => $params['traffic_gb'] ?? 30,
            'package_days' => $params['days'] ?? 30,
            'comment' => 'Limit IP: ' . ($params['ip_limit'] ?? 1),
            'mode' => 'no_reset',
        ];

        $res = $this->request('/api/v2/admin/user/', 'POST', $payload, true, $headers);

        $subUrl = $this->baseUrl . '/' . ($this->panel['username'] ?: 'admin') . '/' . $uuid . '/';
        if (isset($res['data']['link'])) {
            $subUrl = $res['data']['link'];
        }

        return [
            'success' => true,
            'uuid' => $uuid,
            'email' => $name,
            'sub_url' => $subUrl,
            'config_link' => $subUrl,
            'wireguard_conf' => null,
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
        $headers = ['Hiddify-API-Key: ' . $this->apiKey];
        $res = $this->request('/api/v2/admin/user/' . $clientIdentifier . '/', 'DELETE', null, false, $headers);
        return $res['success'];
    }
}
