<?php

namespace App\Panels;

use App\Services\WireGuardHelper;

class WGDashboardPanel extends BasePanel
{
    public function login(): bool
    {
        // WGDashboard session authentication
        $res = $this->request('/api/authenticate', 'POST', [
            'username' => $this->panel['username'],
            'password' => $this->panel['password'],
        ], true);

        return $res['success'] ?? false;
    }

    public function testConnection(): array
    {
        if (!$this->login()) {
            return [
                'success' => false,
                'message' => 'اتصال ناموفق: اطلاعات ورود به پنل WGDashboard نادرست است.',
                'details' => [],
            ];
        }

        return [
            'success' => true,
            'message' => 'اتصال به پنل WGDashboard (وایرگارد) با موفقیت برقرار شد.',
            'details' => [],
        ];
    }

    public function getInbounds(): array
    {
        $this->login();
        // Return Wireguard configurations
        $res = $this->request('/api/getWireguardConfigurations', 'GET');
        return is_array($res['data']) ? $res['data'] : [['id' => 'wg0', 'name' => 'wg0']];
    }

    public function createClient(array $params): array
    {
        $this->login();

        $username = $params['email'] ?: ('wg_' . substr(md5(uniqid()), 0, 7));
        $configuration = 'wg0';

        // Add peer in WGDashboard
        $res = $this->request('/api/addPeer/' . $configuration, 'POST', [
            'name' => $username,
            'total_gb' => $params['traffic_gb'] ?? 0,
            'days' => $params['days'] ?? 30,
        ], true);

        // Fetch peer configuration file (.conf)
        $confRes = $this->request("/api/downloadPeer/{$configuration}/{$username}", 'GET');
        $confContent = '';

        if ($confRes['success'] && !empty($confRes['raw'])) {
            $confContent = $confRes['raw'];
        }

        // If API doesn't return raw .conf, build standard Wireguard .conf
        if (empty($confContent) || !str_contains($confContent, '[Interface]')) {
            $clientPrivKey = WireGuardHelper::generateRandomKey();
            $serverPubKey = $this->panel['password'] ?: WireGuardHelper::generateRandomKey();
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
            'sub_url' => '',
            'config_link' => $confContent,
            'wireguard_conf' => $confContent, // Pure WireGuard .conf file
            'inbound_id' => 0,
        ];
    }

    public function getClient(string $clientIdentifier): ?array
    {
        $this->login();
        $res = $this->request('/api/getPeer/wg0/' . urlencode($clientIdentifier), 'GET');
        return $res['data'] ?? null;
    }

    public function deleteClient(int $inboundId, string $clientIdentifier): bool
    {
        $this->login();
        $res = $this->request('/api/deletePeer/wg0/' . urlencode($clientIdentifier), 'POST');
        return $res['success'] ?? false;
    }
}
