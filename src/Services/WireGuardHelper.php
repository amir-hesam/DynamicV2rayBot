<?php

namespace App\Services;

class WireGuardHelper
{
    /**
     * Build standard WireGuard .conf content
     */
    public static function buildConf(
        string $privateKey,
        string $address,
        string $serverPublicKey,
        string $serverEndpoint,
        ?string $presharedKey = null,
        string $dns = '1.1.1.1, 8.8.8.8',
        int $mtu = 1420,
        string $allowedIps = '0.0.0.0/0, ::/0',
        int $keepalive = 25
    ): string {
        $conf = "[Interface]\n";
        $conf .= "PrivateKey = " . trim($privateKey) . "\n";
        $conf .= "Address = " . trim($address) . "\n";
        if (!empty($dns)) {
            $conf .= "DNS = " . trim($dns) . "\n";
        }
        if ($mtu > 0) {
            $conf .= "MTU = {$mtu}\n";
        }

        $conf .= "\n[Peer]\n";
        $conf .= "PublicKey = " . trim($serverPublicKey) . "\n";
        if (!empty($presharedKey)) {
            $conf .= "PresharedKey = " . trim($presharedKey) . "\n";
        }
        $conf .= "Endpoint = " . trim($serverEndpoint) . "\n";
        $conf .= "AllowedIPs = {$allowedIps}\n";
        $conf .= "PersistentKeepalive = {$keepalive}\n";

        return $conf;
    }

    /**
     * Check if a string is a WireGuard .conf content or wireguard URL
     */
    public static function isWireGuard(string $text): bool
    {
        $trim = trim($text);
        if (str_contains($trim, '[Interface]') && str_contains($trim, '[Peer]')) {
            return true;
        }
        if (str_starts_with($trim, 'wireguard://')) {
            return true;
        }
        return false;
    }

    /**
     * Convert wireguard:// URL or raw text to .conf format
     */
    public static function toConf(string $input, string $fallbackName = 'client'): ?string
    {
        $input = trim($input);
        if (str_contains($input, '[Interface]') && str_contains($input, '[Peer]')) {
            return $input;
        }

        if (str_starts_with($input, 'wireguard://')) {
            // Parse wireguard://privateKey@endpoint:port?publickey=...&address=...
            $parsed = parse_url($input);
            $privateKey = $parsed['user'] ?? '';
            $host = $parsed['host'] ?? '';
            $port = $parsed['port'] ?? 51820;
            $endpoint = "{$host}:{$port}";

            $query = [];
            if (!empty($parsed['query'])) {
                parse_str($parsed['query'], $query);
            }

            $publicKey = $query['publickey'] ?? ($query['public_key'] ?? '');
            $address = $query['address'] ?? ($query['ip'] ?? '10.0.0.2/32');
            $presharedKey = $query['presharedkey'] ?? null;
            $dns = $query['dns'] ?? '1.1.1.1, 8.8.8.8';
            $mtu = (int)($query['mtu'] ?? 1420);

            if (!empty($privateKey) && !empty($publicKey)) {
                return self::buildConf($privateKey, $address, $publicKey, $endpoint, $presharedKey, $dns, $mtu);
            }
        }

        return null;
    }

    /**
     * Save .conf content to a temporary file on disk and return file path
     */
    public static function saveTempConfFile(string $username, string $confContent): string
    {
        $dir = sys_get_temp_dir() . '/vpn_wireguard_confs';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        // Sanitize username
        $safeName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $username);
        if (empty($safeName)) {
            $safeName = 'wireguard_' . substr(md5(uniqid()), 0, 6);
        }

        $filePath = $dir . '/' . $safeName . '.conf';
        file_put_contents($filePath, $confContent);

        return $filePath;
    }

    /**
     * Generate 32 bytes random base64 key
     */
    public static function generateRandomKey(): string
    {
        return base64_encode(random_bytes(32));
    }
}
