<?php

namespace App\Panels;

use App\Services\WireGuardHelper;

class SanaeiPanel extends BasePanel
{
    protected ?string $apiToken = null;
    protected bool $isTokenAuth = false;
    protected ?string $csrfToken = null;

    public function __construct(array $panel)
    {
        parent::__construct($panel);

        // Normalize base URL: strip trailing /panel if provided by user
        $cleanUrl = preg_replace('#/panel/?$#i', '', $this->baseUrl);
        $this->baseUrl = rtrim($cleanUrl, '/');

        // Check if API token authentication is used
        // In 3x-ui v3.0.2+, token can be in password (when username is 'null'/empty or token format)
        $user = strtolower(trim((string)($panel['username'] ?? '')));
        $pass = trim((string)($panel['password'] ?? ''));

        if ($user === 'null' || $user === '' || (strlen($pass) >= 32 && !str_contains($pass, ' ') && ($user === 'admin' || $user === 'null' || $user === ''))) {
            $this->apiToken = $pass;
            $this->isTokenAuth = true;
        }
    }

    /**
     * Custom request wrapper for SanaeiPanel that automatically includes
     * Bearer token or CSRF headers and handles timeouts properly
     */
    protected function sanaeiRequest(
        string $endpoint,
        string $method = 'GET',
        mixed $data = null,
        bool $json = true,
        array $headers = [],
        int $timeout = 25
    ): array {
        if ($this->isTokenAuth && !empty($this->apiToken)) {
            $headers[] = 'Authorization: Bearer ' . $this->apiToken;
        } elseif (!empty($this->csrfToken)) {
            $headers[] = 'X-CSRF-Token: ' . $this->csrfToken;
        }

        $headers[] = 'Accept: application/json';

        return $this->request($endpoint, $method, $data, $json, $headers, $timeout);
    }

    public function login(): bool
    {
        // 1. If in API token mode, test token directly via inbounds list
        if ($this->isTokenAuth && !empty($this->apiToken)) {
            $res = $this->sanaeiRequest('/panel/api/inbounds/list', 'GET');
            if (isset($res['data']['success']) && $res['data']['success'] === true) {
                return true;
            }
            // If token mode failed, fallback to cookie mode if username is not null
            $user = strtolower(trim((string)($this->panel['username'] ?? '')));
            if ($user === 'null' || $user === '') {
                return false;
            }
        }

        // 2. Check if current session cookie is already valid
        $check = $this->request('/panel/api/inbounds/list', 'GET', null, false, ['Accept: application/json'], 10);
        if (isset($check['data']['success']) && $check['data']['success'] === true) {
            return true;
        }

        // 3. Step 1: GET login page to obtain initial session cookies & CSRF token
        $getRes = $this->request('/login', 'GET', null, false, [], 10);
        if (!empty($getRes['raw']) && is_string($getRes['raw'])) {
            if (preg_match('/<meta\s+name=["\']csrf-token["\']\s+content=["\']([^"\']+)["\']/i', $getRes['raw'], $m)) {
                $this->csrfToken = $m[1];
            }
        }

        // 4. Step 2: POST credentials to /login (JSON format)
        $loginData = [
            'username' => $this->panel['username'],
            'password' => $this->panel['password'],
        ];

        $loginHeaders = ['Content-Type: application/json'];
        if ($this->csrfToken) {
            $loginHeaders[] = 'X-CSRF-Token: ' . $this->csrfToken;
        }

        $res = $this->request('/login', 'POST', $loginData, true, $loginHeaders, 15);

        if (isset($res['data']['success']) && $res['data']['success'] === true) {
            return true;
        }

        // Fallback: try form-urlencoded if JSON failed
        $formRes = $this->request('/login', 'POST', $loginData, false, $this->csrfToken ? ['X-CSRF-Token: ' . $this->csrfToken] : [], 15);
        if (isset($formRes['data']['success']) && $formRes['data']['success'] === true) {
            return true;
        }

        // Fallback: try /xui/login if 404
        if ($res['code'] === 404 || $formRes['code'] === 404) {
            $xuiRes = $this->request('/xui/login', 'POST', $loginData, true, $loginHeaders, 15);
            if (isset($xuiRes['data']['success']) && $xuiRes['data']['success'] === true) {
                return true;
            }
        }

        // Final verification check via inbounds list
        $verify = $this->request('/panel/api/inbounds/list', 'GET', null, false, ['Accept: application/json'], 10);
        return (isset($verify['data']['success']) && $verify['data']['success'] === true);
    }

    public function testConnection(): array
    {
        if (!$this->login()) {
            return [
                'success' => false,
                'message' => 'اتصال ناموفق: امکان برقراری ارتباط با پنل سنایی (3x-ui) وجود ندارد. لطفاً توکن API، نام کاربری یا آدرس سرور را بررسی فرمایید.',
                'details' => [],
            ];
        }

        $inbounds = $this->getInbounds();
        return [
            'success' => true,
            'message' => 'اتصال به پنل سنایی (3x-ui) با موفقیت برقرار شد. تعداد اینباندها: ' . count($inbounds),
            'details' => [
                'inbounds_count' => count($inbounds),
                'auth_type' => $this->isTokenAuth ? 'API Token (Bearer)' : 'Session Cookie',
            ],
        ];
    }

    public function getInbounds(): array
    {
        $this->login();
        $res = $this->sanaeiRequest('/panel/api/inbounds/list', 'GET', null, false, [], 25);
        
        if (isset($res['data']['obj']) && is_array($res['data']['obj'])) {
            return $res['data']['obj'];
        }

        $res = $this->sanaeiRequest('/xui/API/inbounds/', 'GET', null, false, [], 25);
        if (isset($res['data']['obj']) && is_array($res['data']['obj'])) {
            return $res['data']['obj'];
        }

        return [];
    }

    /**
     * Search all inbounds in this panel to find which inbounds contain a given client
     * (by client email, remark, or id)
     */
    public function findInboundsForClient(string $clientName): array
    {
        $inbounds = $this->getInbounds();
        $matched = [];
        $cleanTarget = strtolower(trim($clientName));

        if (empty($cleanTarget) || empty($inbounds)) {
            return [];
        }

        foreach ($inbounds as $in) {
            $foundInThisInbound = false;
            $inboundId = (int)($in['id'] ?? 0);

            // 1. Check settings.clients
            $settings = json_decode($in['settings'] ?? '{}', true);
            $clients = $settings['clients'] ?? [];
            if (is_array($clients)) {
                foreach ($clients as $c) {
                    $cEmail = strtolower(trim($c['email'] ?? ''));
                    $cId = strtolower(trim($c['id'] ?? ''));
                    if ($cEmail === $cleanTarget || $cId === $cleanTarget || str_contains($cEmail, $cleanTarget)) {
                        $foundInThisInbound = true;
                        break;
                    }
                }
            }

            // 2. Check clientStats if available
            if (!$foundInThisInbound && !empty($in['clientStats']) && is_array($in['clientStats'])) {
                foreach ($in['clientStats'] as $stat) {
                    $sEmail = strtolower(trim($stat['email'] ?? ''));
                    $sUuid = strtolower(trim($stat['uuid'] ?? ($stat['id'] ?? '')));
                    if ($sEmail === $cleanTarget || $sUuid === $cleanTarget || str_contains($sEmail, $cleanTarget)) {
                        $foundInThisInbound = true;
                        break;
                    }
                }
            }

            if ($foundInThisInbound) {
                $matched[] = [
                    'id' => $inboundId,
                    'remark' => $in['remark'] ?? "اینباند {$inboundId}",
                    'protocol' => $in['protocol'] ?? 'vless',
                    'port' => $in['port'] ?? 0,
                ];
            }
        }

        return $matched;
    }

    public function createClient(array $params): array
    {
        $this->login();

        $allInbounds = $this->getInbounds();
        if (empty($allInbounds)) {
            return ['success' => false, 'error' => 'هیچ اینباندی در پنل سنایی یافت نشد.'];
        }

        // Determine target inbounds:
        // Priority 1: $params['inbound_ids'] (array)
        // Priority 2: $this->panel['inbounds_json'] (array of IDs)
        // Priority 3: $params['inbound_id'] (single int)
        // Priority 4: first available inbound
        $targetInboundIds = [];
        if (!empty($params['inbound_ids']) && is_array($params['inbound_ids'])) {
            $targetInboundIds = array_map('intval', $params['inbound_ids']);
        } elseif (!empty($this->panel['inbounds_json'])) {
            $decoded = json_decode($this->panel['inbounds_json'], true);
            if (is_array($decoded) && !empty($decoded)) {
                $targetInboundIds = array_map('intval', $decoded);
            }
        } elseif (!empty($params['inbound_id'])) {
            $targetInboundIds = [(int)$params['inbound_id']];
        }

        // Map all inbounds by ID
        $inboundMap = [];
        foreach ($allInbounds as $in) {
            $inboundMap[(int)$in['id']] = $in;
        }

        // Filter valid target inbounds
        $targetInbounds = [];
        foreach ($targetInboundIds as $id) {
            if (isset($inboundMap[$id])) {
                $targetInbounds[] = $inboundMap[$id];
            }
        }

        // If none found, fallback to the first available inbound
        if (empty($targetInbounds)) {
            $targetInbounds = [$allInbounds[0]];
        }

        $uuid = $params['uuid'] ?: $this->generateUuid();
        $email = $params['email'] ?: ('u' . substr(md5(uniqid()), 0, 7));
        $subId = substr(bin2hex(random_bytes(8)), 0, 16);
        $ipLimit = (int)($params['ip_limit'] ?? 1);
        $trafficBytes = ($params['traffic_gb'] > 0) ? ($params['traffic_gb'] * 1073741824) : 0;
        $expiryTimeMs = ($params['days'] > 0) ? (time() + ($params['days'] * 86400)) * 1000 : 0;

        $configLinks = [];
        $wireguardConf = null;
        $createdInboundIds = [];
        $errors = [];

        // Add client to EACH target inbound
        foreach ($targetInbounds as $inbound) {
            $inboundId = (int)$inbound['id'];
            $protocol = strtolower($inbound['protocol'] ?? 'vless');

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

                $host = parse_url($this->baseUrl, PHP_URL_HOST);
                $port = $inbound['port'] ?? 51820;
                $serverPubKey = $inbound['settings']['peers'][0]['publicKey'] ?? 'ServerPublicKey==';

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
                    'tgId' => (string)($params['telegram_id'] ?? ''),
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

            $res = $this->sanaeiRequest('/panel/api/inbounds/addClient', 'POST', $payload, true, [], 15);

            if (isset($res['data']['success']) && $res['data']['success'] === true) {
                $createdInboundIds[] = $inboundId;
                $configLinks[] = $this->generateDirectLink($inbound, $clientData);
            } else {
                $errors[] = "اینباند {$inboundId}: " . ($res['data']['msg'] ?? ($res['error'] ?? 'خطا'));
            }
        }

        if (empty($createdInboundIds)) {
            return [
                'success' => false,
                'error' => 'خطا در افزودن کلاینت به پنل سنایی: ' . implode(' | ', $errors),
            ];
        }

        // Subscription link construction:
        // If panel has a custom subscription URL prefix (e.g. from Mirza linksubx), use it.
        $subPrefix = $this->panel['linksubx'] ?? ($this->panel['sublink_url'] ?? '');
        if (!empty($subPrefix) && str_starts_with($subPrefix, 'http')) {
            $subUrl = rtrim($subPrefix, '/') . '/' . $email;
        } else {
            $subUrl = $this->baseUrl . '/sub/' . $subId;
        }

        return [
            'success' => true,
            'uuid' => $uuid,
            'email' => $email,
            'sub_url' => $subUrl,
            'config_link' => implode("\n\n", array_filter($configLinks)),
            'wireguard_conf' => $wireguardConf,
            'inbound_id' => $createdInboundIds[0] ?? 0,
            'inbound_ids' => $createdInboundIds,
        ];
    }

    public function getClient(string $clientIdentifier): ?array
    {
        $this->login();

        // 1. Try direct getClientTraffics endpoint first
        $res = $this->sanaeiRequest('/panel/api/inbounds/getClientTraffics/' . rawurlencode($clientIdentifier), 'GET', null, false, [], 10);
        if (isset($res['data']['success']) && $res['data']['success'] === true && !empty($res['data']['obj'])) {
            $obj = $res['data']['obj'];
            return [
                'id' => $obj['uuid'] ?? ($obj['id'] ?? $clientIdentifier),
                'email' => $obj['email'] ?? $clientIdentifier,
                'up' => (int)($obj['up'] ?? 0),
                'down' => (int)($obj['down'] ?? 0),
                'used_traffic' => (int)($obj['up'] ?? 0) + (int)($obj['down'] ?? 0),
                'total' => (int)($obj['total'] ?? 0),
                'expiryTime' => $obj['expiryTime'] ?? 0,
                'enable' => $obj['enable'] ?? true,
            ];
        }

        // 2. Fallback: inspect inbounds list
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
        $res = $this->sanaeiRequest("/panel/api/inbounds/$inboundId/delClient/$clientIdentifier", 'POST', null, false, [], 15);
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
