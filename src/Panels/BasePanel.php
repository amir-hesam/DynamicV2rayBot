<?php

namespace App\Panels;

use App\Services\WireGuardHelper;

abstract class BasePanel implements PanelInterface
{
    protected array $panel;
    protected string $baseUrl;
    protected string $cookieFile;

    public function __construct(array $panel)
    {
        $this->panel = $panel;
        $this->baseUrl = rtrim($panel['url'] ?? '', '/');

        $cookieDir = sys_get_temp_dir() . '/vpn_bot_cookies';
        if (!is_dir($cookieDir)) {
            mkdir($cookieDir, 0777, true);
        }
        $this->cookieFile = $cookieDir . '/panel_' . ($panel['type'] ?? 'p') . '_' . ($panel['id'] ?? 'temp') . '.txt';
    }

    protected function request(
        string $endpoint,
        string $method = 'GET',
        mixed $data = null,
        bool $json = false,
        array $headers = [],
        int $timeout = 20
    ): array {
        $url = str_starts_with($endpoint, 'http') ? $endpoint : ($this->baseUrl . $endpoint);
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); // 0 = skip hostname check (self-signed panel certs)
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieFile);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($json && is_array($data)) {
                $headers[] = 'Content-Type: application/json';
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            } elseif ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($data) ? http_build_query($data) : $data);
            }
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            if ($json && is_array($data)) {
                $headers[] = 'Content-Type: application/json';
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            } elseif ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($data) ? http_build_query($data) : $data);
            }
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        if (PHP_VERSION_ID < 80500) {
            @curl_close($ch);
        }

        if ($response === false) {
            return ['success' => false, 'code' => $httpCode, 'error' => $error];
        }

        $decoded = json_decode($response, true);
        return [
            'success' => ($httpCode >= 200 && $httpCode < 300),
            'code' => $httpCode,
            'data' => $decoded ?? $response,
            'raw' => $response,
        ];
    }

    protected function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
