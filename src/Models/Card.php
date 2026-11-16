<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Card
{
    public static function all(bool $activeOnly = false): array
    {
        $pdo = Database::getInstance();
        $sql = "SELECT * FROM bank_cards" . ($activeOnly ? " WHERE is_active = 1" : "") . " ORDER BY id DESC";
        return $pdo->query($sql)->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM bank_cards WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function getActiveCard(): ?array
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->query("SELECT * FROM bank_cards WHERE is_active = 1 ORDER BY RANDOM() LIMIT 1");
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(array $data): int
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("INSERT INTO bank_cards (card_number, holder_name, bank_name, is_active) 
            VALUES (:card_number, :holder_name, :bank_name, :is_active)");

        $stmt->execute([
            'card_number' => preg_replace('/\s+/', '', $data['card_number']),
            'holder_name' => $data['holder_name'],
            'bank_name' => $data['bank_name'] ?? 'بانک',
            'is_active' => (int)($data['is_active'] ?? 1),
        ]);

        return (int)$pdo->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("UPDATE bank_cards SET 
            card_number = :card_number, 
            holder_name = :holder_name, 
            bank_name = :bank_name, 
            is_active = :is_active 
            WHERE id = :id");

        return $stmt->execute([
            'id' => $id,
            'card_number' => preg_replace('/\s+/', '', $data['card_number']),
            'holder_name' => $data['holder_name'],
            'bank_name' => $data['bank_name'],
            'is_active' => (int)($data['is_active'] ?? 1),
        ]);
    }

    public static function delete(int $id): bool
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("DELETE FROM bank_cards WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
