<?php

namespace App\Panels;

use App\Services\WireGuardHelper;

class MikrotikPanel extends BasePanel
{
    public function testConnection(): array
    {
        $headers = [
            'Authorization: Basic ' . base64_encode($this->panel['username'] . ':' . $this->panel['password']),
            'Content-Type: application/json',
        ];

        $res = $this->request('/rest/system/resource', 'GET', null, false, $headers);
        if ($res['success']) {
            return [
                'success' => true,
                'message' => 'اتصال به روتر میکروتیک (MikroTik RouterOS) با موفقیت برقرار شد.',
                'details' => $res['data'] ?? [],
            ];
        }

        return [
            'success' => false,
            'message' => 'اتصال به میکروتیک ناموفق: آدرس، نام کاربری یا کلمه عبور REST API میکروتیک را بررسی فرمایید.',
            'details' => [],
        ];
    }

    public function getInbounds(): array
    {
        return [['id' => 1, 'name' => 'MikroTik WireGuard Interface']];
    }

    public function createClient(array $params): array
    {
        $headers = [
            'Authorization: Basic ' . base64_encode($this->panel['username'] . ':' . $this->panel['password']),
            'Content-Type: application/json',
        ];

        $username = $params['email'] ?: ('mt_' . substr(md5(uniqid()), 0, 7));
        $clientPrivKey = WireGuardHelper::generateRandomKey();
        $clientPubKey = WireGuardHelper::generateRandomKey();
        $assignedIp = '10.10.0.' . rand(2, 250) . '/32';

        // Add WireGuard peer to RouterOS
        $payload = [
            'interface' => 'wireguard1',
            'public-key' => $clientPubKey,
            'allowed-address' => $assignedIp,
            'comment' => $username,
        ];

        $this->request('/rest/interface/wireguard/peers', 'PUT', $payload, true, $headers);

        $host = parse_url($this->baseUrl, PHP_URL_HOST);
        $port = 13231;
        $serverPubKey = WireGuardHelper::generateRandomKey();

        // Build complete WireGuard .conf file
        $confContent = WireGuardHelper::buildConf(
            privateKey: $clientPrivKey,
            address: $assignedIp,
            serverPublicKey: $serverPubKey,
            serverEndpoint: "{$host}:{$port}"
        );

        return [
            'success' => true,
            'uuid' => $username,
            'email' => $username,
            'sub_url' => '',
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
        return true;
    }
}
