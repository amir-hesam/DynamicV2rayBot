<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Service
{
    public static function all(): array
    {
        $pdo = Database::getInstance();
        $sql = "SELECT s.*, u.telegram_id, u.username, u.first_name, pn.name as panel_name, pn.type as panel_type 
                FROM services s 
                JOIN users u ON s.user_id = u.id 
                LEFT JOIN panels pn ON s.panel_id = pn.id 
                ORDER BY s.id DESC";
        return $pdo->query($sql)->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT s.*, u.telegram_id, u.username, u.first_name, pn.name as panel_name, pn.type as panel_type, pn.url as panel_url 
                FROM services s 
                JOIN users u ON s.user_id = u.id 
                LEFT JOIN panels pn ON s.panel_id = pn.id 
                WHERE s.id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function getUserServices(int $userId): array
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT s.*, pn.name as panel_name, pn.type as panel_type 
                FROM services s 
                LEFT JOIN panels pn ON s.panel_id = pn.id 
                WHERE s.user_id = ? 
                ORDER BY s.is_active DESC, s.id DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function create(array $data): int
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("INSERT INTO services (order_id, user_id, panel_id, client_uuid, client_email, inbound_id, subscription_url, config_link, wireguard_conf, traffic_gb, days, ip_limit, hwid_limit, used_traffic_bytes, expires_at, is_active) 
            VALUES (:order_id, :user_id, :panel_id, :client_uuid, :client_email, :inbound_id, :subscription_url, :config_link, :wireguard_conf, :traffic_gb, :days, :ip_limit, :hwid_limit, 0, :expires_at, 1)");

        $stmt->execute([
            'order_id' => $data['order_id'] ?? null,
            'user_id' => $data['user_id'],
            'panel_id' => $data['panel_id'],
            'client_uuid' => $data['client_uuid'],
            'client_email' => $data['client_email'],
            'inbound_id' => (int)($data['inbound_id'] ?? 0),
            'subscription_url' => $data['subscription_url'] ?? '',
            'config_link' => $data['config_link'] ?? '',
            'wireguard_conf' => $data['wireguard_conf'] ?? null,
            'traffic_gb' => (int)$data['traffic_gb'],
            'days' => (int)$data['days'],
            'ip_limit' => (int)($data['ip_limit'] ?? 1),
            'hwid_limit' => (int)($data['hwid_limit'] ?? 0),
            'expires_at' => $data['expires_at'],
        ]);

        return (int)$pdo->lastInsertId();
    }

    public static function updateUsage(int $id, int $usedBytes): void
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("UPDATE services SET used_traffic_bytes = ? WHERE id = ?");
        $stmt->execute([$usedBytes, $id]);
    }

    public static function deactivate(int $id): void
    {
        $pdo = Database::getInstance();
        $pdo->prepare("UPDATE services SET is_active = 0 WHERE id = ?")->execute([$id]);
    }

    /**
     * Live sync service usage and status directly with the remote VPN panel
     */
    public static function syncWithPanel(int $id): ?array
    {
        $service = self::find($id);
        if (!$service) {
            return null;
        }

        $panel = Panel::find((int)$service['panel_id']);
        if (!$panel || $panel['type'] === 'manual') {
            return $service;
        }

        try {
            $adapter = \App\Panels\PanelManager::make($panel);
            $identifier = $service['client_email'] ?: $service['client_uuid'];
            $clientData = $adapter->getClient($identifier);

            if ($clientData) {
                $pdo = Database::getInstance();

                // 1. Used bytes
                $usedBytes = null;
                if (isset($clientData['used_traffic'])) {
                    $usedBytes = (int)$clientData['used_traffic'];
                } elseif (isset($clientData['used_bytes'])) {
                    $usedBytes = (int)$clientData['used_bytes'];
                } elseif (isset($clientData['up']) || isset($clientData['down'])) {
                    $usedBytes = (int)($clientData['up'] ?? 0) + (int)($clientData['down'] ?? 0);
                }

                if ($usedBytes !== null && $usedBytes >= 0) {
                    $pdo->prepare("UPDATE services SET used_traffic_bytes = ? WHERE id = ?")->execute([$usedBytes, $id]);
                    $service['used_traffic_bytes'] = $usedBytes;
                }

                // 2. Expiration date
                $expireTime = $clientData['expire'] ?? ($clientData['expiryTime'] ?? ($clientData['expire_date'] ?? null));
                if ($expireTime) {
                    $expireTs = is_numeric($expireTime) ? (int)$expireTime : strtotime($expireTime);
                    // Convert ms to s if > year 2100
                    if ($expireTs > 4102444800) {
                        $expireTs = (int)($expireTs / 1000);
                    }
                    if ($expireTs > 0) {
                        $expiresAt = date('Y-m-d H:i:s', $expireTs);
                        $pdo->prepare("UPDATE services SET expires_at = ? WHERE id = ?")->execute([$expiresAt, $id]);
                        $service['expires_at'] = $expiresAt;
                    }
                }

                // 3. Status
                if (isset($clientData['status']) && in_array(strtolower($clientData['status']), ['disabled', 'expired', 'limited'])) {
                    $pdo->prepare("UPDATE services SET is_active = 0 WHERE id = ?")->execute([$id]);
                    $service['is_active'] = 0;
                } elseif (isset($clientData['enable']) && $clientData['enable'] === false) {
                    $pdo->prepare("UPDATE services SET is_active = 0 WHERE id = ?")->execute([$id]);
                    $service['is_active'] = 0;
                }
            }
        } catch (\Throwable $e) {
            error_log("Failed to sync service #{$id} with panel: " . $e->getMessage());
        }

        return $service;
    }
}
