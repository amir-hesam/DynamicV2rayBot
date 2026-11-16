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
                WHERE s.user_id = ? AND s.is_active = 1 
                ORDER BY s.id DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function create(array $data): int
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("INSERT INTO services (order_id, user_id, panel_id, client_uuid, client_email, inbound_id, subscription_url, config_link, wireguard_conf, traffic_gb, days, ip_limit, used_traffic_bytes, expires_at, is_active) 
            VALUES (:order_id, :user_id, :panel_id, :client_uuid, :client_email, :inbound_id, :subscription_url, :config_link, :wireguard_conf, :traffic_gb, :days, :ip_limit, 0, :expires_at, 1)");

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
}
