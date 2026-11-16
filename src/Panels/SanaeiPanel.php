<?php

namespace App\Panels;

use App\Services\WireGuardHelper;

class SanaeiPanel extends BasePanel
{
    public function login(): bool
    {
        $res = $this->request('/login', 'POST', [
            'username' => $this->panel['username'],
            'password' => $this->panel['password'],
        ]);

        if (isset($res['data']['success']) && $res['data']['success'] === true) {
            return true;
        }

        // Try /xui/login if 404
        if ($res['code'] === 404) {
            $res = $this->request('/xui/login', 'POST', [
                'username' => $this->panel['username'],
                'password' => $this->panel['password'],
            ]);
            return (isset($res['data']['success']) && $res['data']['success'] === true);
        }

        return false;
    }

    public function testConnection(): array
    {
        if (!$this->login()) {
            return [
                'success' => false,
                'message' => 'اتصال ناموفق: نام کاربری یا رمز عبور پنل سنایی (3x-ui) نادرست است یا آدرس در دسترس نیست.',
                'details' => [],
            ];
        }

        $inbounds = $this->getInbounds();
        return [
            'success' => true,
            'message' => 'اتصال به پنل سنایی (3x-ui) با موفقیت برقرار شد. تعداد اینباندها: ' . count($inbounds),
            'details' => [
                'inbounds_count' => count($inbounds),
            ],
        ];
    }

    public function getInbounds(): array
    {
        $this->login();
        $res = $this->request('/panel/api/inbounds/list', 'GET');
        
        if (isset($res['data']['obj']) && is_array($res['data']['obj'])) {
            return $res['data']['obj'];
        }

        $res = $this->request('/xui/API/inbounds/', 'GET');
        if (isset($res['data']['obj']) && is_array($res['data']['obj'])) {
            return $res['data']['obj'];
        }

        return [];
    }

    public function createClient(array $params): array
    {
        $this->login();

        $inbounds = $this->getInbounds();
        if (empty($inbounds)) {
            return ['success' => false, 'error' => 'هیچ اینباندی در پنل سنایی یافت نشد.'];
        }

        $targetInbound = null;
        if (!empty($params['inbound_id'])) {
            foreach ($inbounds as $in) {
                if (($in['id'] ?? null) == $params['inbound_id']) {
                    $targetInbound = $in;
                    break;
                }
            }
        }
        if (!$targetInbound) {
            $targetInbound = $inbounds[0];
        }

        $inboundId = $targetInbound['id'];
        $protocol = strtolower($targetInbound['protocol'] ?? 'vless');

        $uuid = $params['uuid'] ?: $this->generateUuid();
        $email = $params['email'] ?: ('u' . substr(md5(uniqid()), 0, 7));
        $subId = substr(bin2hex(random_bytes(8)), 0, 16);
        $ipLimit = (int)($params['ip_limit'] ?? 1);
        $trafficBytes = ($params['traffic_gb'] > 0) ? ($params['traffic_gb'] * 1073741824) : 0;
        $expiryTimeMs = ($params['days'] > 0) ? (time() + ($params['days'] * 86400)) * 1000 : 0;

        $wireguardConf = null;

        // WireGuard Protocol Handling in 3x-ui
        if ($protocol === 'wireguard') {
            $clientPrivKey = WireGuardHelper::generateRandomKey();
            $clientPubKey = WireGuardHelper::generateRandomKey();
            $assignedIp = '10.0.0.' . rand(2, 250) . '/32';

            $clientData = [
                'id' => $uuid,
                'email' => $email,
                'publicKey' => $clientPubKey,
                'ip' => $assignedIp,
                'limitIp' => $ipLimit,
                'totalGB' => $trafficBytes,
                'expiryTime' => $expiryTimeMs,
                'enable' => true,
                'subId' => $subId,
            ];

            // Build Wireguard .conf file content
            $host = parse_url($this->baseUrl, PHP_URL_HOST);
            $port = $targetInbound['port'] ?? 51820;
            $serverPubKey = $targetInbound['settings']['peers'][0]['publicKey'] ?? 'ServerPublicKey==';

            $wireguardConf = WireGuardHelper::buildConf(
                privateKey: $clientPrivKey,
                address: $assignedIp,
                serverPublicKey: $serverPubKey,
                serverEndpoint: "{$host}:{$port}"
            );
        } else {
            $clientData = [
                'id' => $uuid,
                'email' => $email,
                'limitIp' => $ipLimit,
                'totalGB' => $trafficBytes,
                'expiryTime' => $expiryTimeMs,
                'enable' => true,
                'tgId' => '',
                'subId' => $subId,
            ];
            if ($protocol === 'vless') {
                $clientData['flow'] = 'xtls-rprx-vision';
            }
        }

        $payload = [
            'id' => $inboundId,
            'settings' => json_encode(['clients' => [$clientData]]),
        ];

        $res = $this->request('/panel/api/inbounds/addClient', 'POST', $payload);

        if (!isset($res['data']['success']) || $res['data']['success'] !== true) {
            $errMsg = $res['data']['msg'] ?? ($res['error'] ?? 'خطا در افزودن کلاینت به پنل سنایی');
            return ['success' => false, 'error' => $errMsg];
        }

        $subUrl = $this->baseUrl . '/sub/' . $subId;
        $configLink = $this->generateDirectLink($targetInbound, $clientData);

        return [
            'success' => true,
            'uuid' => $uuid,
            'email' => $email,
            'sub_url' => $subUrl,
            'config_link' => $configLink,
            'wireguard_conf' => $wireguardConf,
            'inbound_id' => $inboundId,
        ];
    }

    public function getClient(string $clientIdentifier): ?array
    {
        $inbounds = $this->getInbounds();
        foreach ($inbounds as $inbound) {
            $settings = json_decode($inbound['settings'] ?? '{}', true);
            $clients = $settings['clients'] ?? [];
            foreach ($clients as $client) {
                if (($client['email'] ?? '') === $clientIdentifier || ($client['id'] ?? '') === $clientIdentifier) {
                    return [
                        'id' => $client['id'] ?? '',
                        'email' => $client['email'] ?? '',
                        'up' => $client['up'] ?? 0,
                        'down' => $client['down'] ?? 0,
                        'total' => $client['totalGB'] ?? 0,
                        'expiryTime' => $client['expiryTime'] ?? 0,
                        'enable' => $client['enable'] ?? true,
                    ];
                }
            }
        }
        return null;
    }

    public function deleteClient(int $inboundId, string $clientIdentifier): bool
    {
        $this->login();
        $res = $this->request("/panel/api/inbounds/$inboundId/delClient/$clientIdentifier", 'POST');
        return isset($res['data']['success']) && $res['data']['success'] === true;
    }

    private function generateDirectLink(array $inbound, array $client): string
    {
        $protocol = strtolower($inbound['protocol'] ?? 'vless');
        $port = $inbound['port'] ?? 443;
        $host = parse_url($this->baseUrl, PHP_URL_HOST);
        $remark = rawurlencode(($inbound['remark'] ?? 'VPN') . '-' . ($client['email'] ?? 'user'));

        $stream = json_decode($inbound['streamSettings'] ?? '{}', true);
        $net = $stream['network'] ?? 'tcp';
        $security = $stream['security'] ?? 'none';

        if ($protocol === 'vless') {
            $flow = !empty($client['flow']) ? "&flow=" . $client['flow'] : "";
            $sni = $stream['realitySettings']['serverNames'][0] ?? ($stream['tlsSettings']['serverName'] ?? $host);
            $pbk = $stream['realitySettings']['settings']['publicKey'] ?? '';
            $sid = $stream['realitySettings']['shortIds'][0] ?? '';
            $fp = $stream['realitySettings']['settings']['fingerprint'] ?? 'chrome';

            $realityParams = "";
            if ($security === 'reality') {
                $realityParams = "&security=reality&pbk={$pbk}&fp={$fp}" . ($sid ? "&sid={$sid}" : "");
            } elseif ($security === 'tls') {
                $realityParams = "&security=tls&sni={$sni}";
            }

            return "vless://{$client['id']}@{$host}:{$port}?type={$net}{$flow}{$realityParams}#{$remark}";
        }

        if ($protocol === 'trojan') {
            return "trojan://{$client['id']}@{$host}:{$port}?security={$security}&type={$net}#{$remark}";
        }

        if ($protocol === 'vmess') {
            $vmessJson = [
                'v' => '2',
                'ps' => ($inbound['remark'] ?? '') . '-' . ($client['email'] ?? ''),
                'add' => $host,
                'port' => (string)$port,
                'id' => $client['id'],
                'aid' => '0',
                'net' => $net,
                'type' => 'none',
                'host' => '',
                'path' => '',
                'tls' => $security,
            ];
            return 'vmess://' . base64_encode(json_encode($vmessJson));
        }

        if ($protocol === 'wireguard') {
            return "wireguard://{$client['publicKey']}@{$host}:{$port}?address=" . urlencode($client['ip'] ?? '10.0.0.2/32') . "#{$remark}";
        }

        return "vless://{$client['id']}@{$host}:{$port}#{$remark}";
    }
}
