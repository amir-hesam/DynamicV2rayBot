<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Plan
{
    public static function all(bool $activeOnly = false): array
    {
        $pdo = Database::getInstance();
        $sql = "SELECT p.*, pn.name as panel_name FROM plans p 
                LEFT JOIN panels pn ON p.panel_id = pn.id" . 
                ($activeOnly ? " WHERE p.is_active = 1" : "") . 
                " ORDER BY p.sort_order ASC, p.id DESC";
        return $pdo->query($sql)->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT p.*, pn.name as panel_name FROM plans p 
                               LEFT JOIN panels pn ON p.panel_id = pn.id 
                               WHERE p.id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(array $data): int
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("INSERT INTO plans (name, description, price, traffic_gb, days, ip_limit, panel_id, is_active, sort_order) 
            VALUES (:name, :description, :price, :traffic_gb, :days, :ip_limit, :panel_id, :is_active, :sort_order)");

        $stmt->execute([
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'price' => (int)$data['price'],
            'traffic_gb' => (int)$data['traffic_gb'],
            'days' => (int)($data['days'] ?? 30),
            'ip_limit' => (int)($data['ip_limit'] ?? 1),
            'panel_id' => (int)($data['panel_id'] ?? 0),
            'is_active' => (int)($data['is_active'] ?? 1),
            'sort_order' => (int)($data['sort_order'] ?? 0),
        ]);

        return (int)$pdo->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("UPDATE plans SET 
            name = :name, 
            description = :description, 
            price = :price, 
            traffic_gb = :traffic_gb, 
            days = :days, 
            ip_limit = :ip_limit, 
            panel_id = :panel_id, 
            is_active = :is_active, 
            sort_order = :sort_order 
            WHERE id = :id");

        return $stmt->execute([
            'id' => $id,
            'name' => $data['name'],
            'description' => $data['description'] ?? '',
            'price' => (int)$data['price'],
            'traffic_gb' => (int)$data['traffic_gb'],
            'days' => (int)($data['days'] ?? 30),
            'ip_limit' => (int)($data['ip_limit'] ?? 1),
            'panel_id' => (int)($data['panel_id'] ?? 0),
            'is_active' => (int)($data['is_active'] ?? 1),
            'sort_order' => (int)($data['sort_order'] ?? 0),
        ]);
    }

    public static function delete(int $id): bool
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("DELETE FROM plans WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
