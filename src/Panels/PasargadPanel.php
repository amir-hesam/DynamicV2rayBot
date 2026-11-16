<?php

namespace App\Panels;

use App\Services\WireGuardHelper;

class PasargadPanel extends BasePanel
{
    private ?string $token = null;

    public function login(): bool
    {
        // 1. Standard Pasargad / Marzban API token
        $res = $this->request('/api/admin/token', 'POST', [
            'username' => $this->panel['username'],
            'password' => $this->panel['password'],
        ], false);

        if (isset($res['data']['access_token'])) {
            $this->token = $res['data']['access_token'];
            return true;
        }

        // 2. JSON token auth
        $resJson = $this->request('/api/admin/token', 'POST', [
            'username' => $this->panel['username'],
            'password' => $this->panel['password'],
        ], true);

        if (isset($resJson['data']['access_token'])) {
            $this->token = $resJson['data']['access_token'];
            return true;
        }

        // 3. Pasargad X-UI cookie login (/login)
        $resCookie = $this->request('/login', 'POST', [
            'username' => $this->panel['username'],
            'password' => $this->panel['password'],
        ], false);

        if (isset($resCookie['data']['success']) && $resCookie['data']['success'] === true) {
            return true;
        }

        return false;
    }

    public function testConnection(): array
    {
        if (!$this->login()) {
            return [
                'success' => false,
                'message' => 'اتصال ناموفق: اطلاعات ورود به پنل پاسارگاد نادرست است یا پنل در دسترس نیست.',
                'details' => [],
            ];
        }

        $inbounds = $this->getInbounds();
        return [
            'success' => true,
            'message' => 'اتصال به پنل پاسارگاد با موفقیت برقرار شد.',
            'details' => [
                'inbounds_count' => count($inbounds),
                'auth_type' => $this->token ? 'Bearer Token (API)' : 'Session Cookie',
            ],
        ];
    }

    public function getInbounds(): array
    {
        $this->login();

        $headers = [];
        if ($this->token) {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }

        $res = $this->request('/api/inbounds', 'GET', null, false, $headers);
        if (is_array($res['data']) && !isset($res['data']['detail'])) {
            return $res['data'];
        }

        $resList = $this->request('/panel/api/inbounds/list', 'GET');
        if (isset($resList['data']['obj']) && is_array($resList['data']['obj'])) {
            return $resList['data']['obj'];
        }

        return [];
    }

    public function createClient(array $params): array
    {
        $this->login();

        $username = $params['email'] ?: ('pas_' . substr(md5(uniqid()), 0, 8));
        $uuid = $params['uuid'] ?: $this->generateUuid();
        $trafficBytes = ($params['traffic_gb'] > 0) ? ($params['traffic_gb'] * 1073741824) : 0;
        $expireTimestamp = ($params['days'] > 0) ? (time() + ($params['days'] * 86400)) : 0;

        if ($this->token) {
            $payload = [
                'username' => $username,
                'proxies' => [
                    'vless' => ['flow' => 'xtls-rprx-vision'],
                    'vmess' => [],
                    'trojan' => [],
                    'wireguard' => [],
                ],
                'inbounds' => [],
                'expire' => $expireTimestamp,
                'data_limit' => $trafficBytes,
                'data_limit_reset_strategy' => 'no_reset',
                'status' => 'active',
                'note' => 'Limit IP: ' . ($params['ip_limit'] ?? 1),
            ];

            $headers = ['Authorization: Bearer ' . $this->token];
            $res = $this->request('/api/user', 'POST', $payload, true, $headers);

            if ($res['success'] && isset($res['data']['username'])) {
                $userData = $res['data'];
                $subUrl = $userData['subscription_url'] ?? '';
                if (empty($subUrl) && !empty($userData['subscription_token'])) {
                    $subUrl = $this->baseUrl . '/sub/' . $userData['subscription_token'];
                }
                $links = $userData['links'] ?? [];
                $configLink = !empty($links) ? $links[0] : '';

                // Extract WireGuard .conf if available in links
                $wireguardConf = null;
                foreach ($links as $lnk) {
                    if (WireGuardHelper::isWireGuard($lnk)) {
                        $wireguardConf = WireGuardHelper::toConf($lnk, $username);
                        break;
                    }
                }

                // If user data contains wireguard profile directly
                if (!$wireguardConf && !empty($userData['wireguard_conf'])) {
                    $wireguardConf = $userData['wireguard_conf'];
                }

                return [
                    'success' => true,
                    'uuid' => $uuid,
                    'email' => $username,
                    'sub_url' => $subUrl,
                    'config_link' => $configLink,
                    'wireguard_conf' => $wireguardConf,
                    'inbound_id' => 0,
                ];
            }
        }

        // Fallback for X-UI based Pasargad
        $sanaeiCompat = new SanaeiPanel($this->panel);
        return $sanaeiCompat->createClient($params);
    }

    public function getClient(string $clientIdentifier): ?array
    {
        $this->login();
        if ($this->token) {
            $headers = ['Authorization: Bearer ' . $this->token];
            $res = $this->request('/api/user/' . urlencode($clientIdentifier), 'GET', null, false, $headers);
            if ($res['success'] && isset($res['data']['username'])) {
                $u = $res['data'];
                return [
                    'id' => $u['username'],
                    'email' => $u['username'],
                    'used_traffic' => $u['used_traffic'] ?? 0,
                    'data_limit' => $u['data_limit'] ?? 0,
                    'expire' => $u['expire'] ?? 0,
                    'status' => $u['status'] ?? 'active',
                    'sub_url' => $u['subscription_url'] ?? '',
                ];
            }
        }

        $sanaeiCompat = new SanaeiPanel($this->panel);
        return $sanaeiCompat->getClient($clientIdentifier);
    }

    public function deleteClient(int $inboundId, string $clientIdentifier): bool
    {
        $this->login();
        if ($this->token) {
            $headers = ['Authorization: Bearer ' . $this->token];
            $res = $this->request('/api/user/' . urlencode($clientIdentifier), 'DELETE', null, false, $headers);
            return $res['success'];
        }

        $sanaeiCompat = new SanaeiPanel($this->panel);
        return $sanaeiCompat->deleteClient($inboundId, $clientIdentifier);
    }
}
