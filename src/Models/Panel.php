<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Panel
{
    public static function all(bool $activeOnly = false): array
    {
        $pdo = Database::getInstance();
        $sql = "SELECT * FROM panels" . ($activeOnly ? " WHERE is_active = 1" : "") . " ORDER BY id DESC";
        return $pdo->query($sql)->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM panels WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(array $data): int
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("INSERT INTO panels (name, type, url, username, password, inbounds_json, is_active, capacity, current_clients, custom_service_enabled, price_per_gb, price_per_day, min_gb, max_gb, min_days, max_days, categories_json, linksubx) 
            VALUES (:name, :type, :url, :username, :password, :inbounds_json, :is_active, :capacity, :current_clients, :custom_service_enabled, :price_per_gb, :price_per_day, :min_gb, :max_gb, :min_days, :max_days, :categories_json, :linksubx)");
        
        $stmt->execute([
            'name' => $data['name'],
            'type' => $data['type'],
            'url' => rtrim($data['url'], '/'),
            'username' => $data['username'],
            'password' => $data['password'],
            'inbounds_json' => $data['inbounds_json'] ?? '[]',
            'is_active' => (int)($data['is_active'] ?? 1),
            'capacity' => (int)($data['capacity'] ?? 500),
            'current_clients' => (int)($data['current_clients'] ?? 0),
            'custom_service_enabled' => (int)($data['custom_service_enabled'] ?? 0),
            'price_per_gb' => (int)($data['price_per_gb'] ?? 4000),
            'price_per_day' => (int)($data['price_per_day'] ?? 1000),
            'min_gb' => (int)($data['min_gb'] ?? 5),
            'max_gb' => (int)($data['max_gb'] ?? 500),
            'min_days' => (int)($data['min_days'] ?? 1),
            'max_days' => (int)($data['max_days'] ?? 365),
            'categories_json' => $data['categories_json'] ?? '["یک ماهه", "دو ماهه", "سه ماهه", "نامحدود"]',
            'linksubx' => $data['linksubx'] ?? null,
        ]);

        return (int)$pdo->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("UPDATE panels SET 
            name = :name, 
            type = :type, 
            url = :url, 
            username = :username, 
            password = :password, 
            inbounds_json = :inbounds_json, 
            is_active = :is_active, 
            capacity = :capacity,
            custom_service_enabled = :custom_service_enabled,
            price_per_gb = :price_per_gb,
            price_per_day = :price_per_day,
            min_gb = :min_gb,
            max_gb = :max_gb,
            min_days = :min_days,
            max_days = :max_days,
            categories_json = :categories_json,
            linksubx = :linksubx
            WHERE id = :id");

        return $stmt->execute([
            'id' => $id,
            'name' => $data['name'],
            'type' => $data['type'],
            'url' => rtrim($data['url'], '/'),
            'username' => $data['username'],
            'password' => $data['password'],
            'inbounds_json' => $data['inbounds_json'] ?? '[]',
            'is_active' => (int)($data['is_active'] ?? 1),
            'capacity' => (int)($data['capacity'] ?? 500),
            'custom_service_enabled' => (int)($data['custom_service_enabled'] ?? 0),
            'price_per_gb' => (int)($data['price_per_gb'] ?? 4000),
            'price_per_day' => (int)($data['price_per_day'] ?? 1000),
            'min_gb' => (int)($data['min_gb'] ?? 5),
            'max_gb' => (int)($data['max_gb'] ?? 500),
            'min_days' => (int)($data['min_days'] ?? 1),
            'max_days' => (int)($data['max_days'] ?? 365),
            'categories_json' => $data['categories_json'] ?? '["یک ماهه", "دو ماهه", "سه ماهه", "نامحدود"]',
            'linksubx' => $data['linksubx'] ?? null,
        ]);
    }

    public static function updateInbounds(int $id, array $inboundIds): bool
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("UPDATE panels SET inbounds_json = ? WHERE id = ?");
        return $stmt->execute([json_encode(array_values(array_unique(array_map('intval', $inboundIds)))), $id]);
    }

    public static function delete(int $id): bool
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("DELETE FROM panels WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public static function incrementClients(int $id): void
    {
        $pdo = Database::getInstance();
        $pdo->prepare("UPDATE panels SET current_clients = current_clients + 1 WHERE id = ?")->execute([$id]);
    }

    public static function getAvailablePanel(int $preferredId = 0): ?array
    {
        if ($preferredId > 0) {
            $panel = self::find($preferredId);
            if ($panel && $panel['is_active'] && $panel['current_clients'] < $panel['capacity']) {
                return $panel;
            }
        }

        // Auto-select panel with least clients that has capacity
        $pdo = Database::getInstance();
        $stmt = $pdo->query("SELECT * FROM panels WHERE is_active = 1 AND current_clients < capacity ORDER BY current_clients ASC LIMIT 1");
        $panel = $stmt->fetch();
        return $panel ?: null;
    }
}
