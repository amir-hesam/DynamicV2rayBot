<?php

namespace App\Panels;

use App\Services\WireGuardHelper;

class MarzneshinPanel extends BasePanel
{
    private ?string $token = null;

    public function login(): bool
    {
        $res = $this->request('/api/admins/token', 'POST', [
            'username' => $this->panel['username'],
            'password' => $this->panel['password'],
        ], false);

        if (isset($res['data']['access_token'])) {
            $this->token = $res['data']['access_token'];
            return true;
        }

        return false;
    }

    public function testConnection(): array
    {
        if (!$this->login()) {
            return [
                'success' => false,
                'message' => 'اتصال ناموفق: اطلاعات ورود به پنل مرزنشین نادرست است.',
                'details' => [],
            ];
        }

        return [
            'success' => true,
            'message' => 'اتصال به پنل مرزنشین با موفقیت برقرار شد.',
            'details' => [],
        ];
    }

    public function getInbounds(): array
    {
        $this->login();
        if (!$this->token) return [];
        $res = $this->request('/api/inbounds', 'GET', null, false, ['Authorization: Bearer ' . $this->token]);
        return is_array($res['data']) ? $res['data'] : [];
    }

    public function createClient(array $params): array
    {
        $this->login();
        if (!$this->token) {
            return ['success' => false, 'error' => 'عدم دسترسی به پنل مرزنشین'];
        }

        $username = $params['email'] ?: ('mn_' . substr(md5(uniqid()), 0, 7));
        $trafficBytes = ($params['traffic_gb'] > 0) ? ($params['traffic_gb'] * 1073741824) : 0;
        $expireTimestamp = ($params['days'] > 0) ? (time() + ($params['days'] * 86400)) : 0;

        $payload = [
            'username' => $username,
            'expire' => $expireTimestamp,
            'data_limit' => $trafficBytes,
            'status' => 'active',
            'note' => 'Limit IP: ' . ($params['ip_limit'] ?? 1),
        ];

        $res = $this->request('/api/users', 'POST', $payload, true, ['Authorization: Bearer ' . $this->token]);

        if ($res['success'] && isset($res['data']['username'])) {
            $u = $res['data'];
            $subUrl = $u['subscription_url'] ?? ($this->baseUrl . '/sub/' . ($u['subscription_token'] ?? ''));
            $links = $u['links'] ?? [];
            $configLink = !empty($links) ? $links[0] : '';

            $wireguardConf = null;
            foreach ($links as $lnk) {
                if (WireGuardHelper::isWireGuard($lnk)) {
                    $wireguardConf = WireGuardHelper::toConf($lnk, $username);
                    break;
                }
            }

            return [
                'success' => true,
                'uuid' => $u['username'],
                'email' => $u['username'],
                'sub_url' => $subUrl,
                'config_link' => $configLink,
                'wireguard_conf' => $wireguardConf,
                'inbound_id' => 0,
            ];
        }

        return ['success' => false, 'error' => $res['data']['detail'] ?? 'خطا در ایجاد کاربر مرزنشین'];
    }

    public function getClient(string $clientIdentifier): ?array
    {
        $this->login();
        if (!$this->token) return null;
        $res = $this->request('/api/users/' . urlencode($clientIdentifier), 'GET', null, false, ['Authorization: Bearer ' . $this->token]);
        if ($res['success'] && isset($res['data']['username'])) {
            return $res['data'];
        }
        return null;
    }

    public function deleteClient(int $inboundId, string $clientIdentifier): bool
    {
        $this->login();
        if (!$this->token) return false;
        $res = $this->request('/api/users/' . urlencode($clientIdentifier), 'DELETE', null, false, ['Authorization: Bearer ' . $this->token]);
        return $res['success'];
    }
}
