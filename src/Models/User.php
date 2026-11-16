<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class User
{
    public static function all(): array
    {
        $pdo = Database::getInstance();
        return $pdo->query("SELECT * FROM users ORDER BY id DESC")->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findByTelegramId(int|string $telegramId): ?array
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE telegram_id = ?");
        $stmt->execute([(string)$telegramId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findOrCreate(array $from): array
    {
        $telegramId = (string)($from['id'] ?? '');
        $user = self::findByTelegramId($telegramId);

        if (!$user) {
            $pdo = Database::getInstance();
            $stmt = $pdo->prepare("INSERT INTO users (telegram_id, username, first_name, last_name, balance, step, step_data) 
                VALUES (:telegram_id, :username, :first_name, :last_name, 0, 'idle', '{}')");
            $stmt->execute([
                'telegram_id' => $telegramId,
                'username' => $from['username'] ?? '',
                'first_name' => $from['first_name'] ?? '',
                'last_name' => $from['last_name'] ?? '',
            ]);
            return self::findByTelegramId($telegramId);
        }

        // Update profile information if changed
        if (($from['username'] ?? '') !== $user['username'] || ($from['first_name'] ?? '') !== $user['first_name']) {
            $pdo = Database::getInstance();
            $pdo->prepare("UPDATE users SET username = ?, first_name = ?, last_name = ? WHERE id = ?")
                ->execute([$from['username'] ?? '', $from['first_name'] ?? '', $from['last_name'] ?? '', $user['id']]);
            $user['username'] = $from['username'] ?? '';
            $user['first_name'] = $from['first_name'] ?? '';
            $user['last_name'] = $from['last_name'] ?? '';
        }

        return $user;
    }

    public static function setStep(int $userId, string $step, array $stepData = []): void
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("UPDATE users SET step = ?, step_data = ? WHERE id = ?");
        $stmt->execute([$step, json_encode($stepData, JSON_UNESCAPED_UNICODE), $userId]);
    }

    public static function getStepData(array $user): array
    {
        $data = json_decode($user['step_data'] ?? '{}', true);
        return is_array($data) ? $data : [];
    }

    public static function addBalance(int $userId, int $amount): void
    {
        $pdo = Database::getInstance();
        $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?")->execute([$amount, $userId]);
    }

    public static function deductBalance(int $userId, int $amount): bool
    {
        $pdo = Database::getInstance();
        $user = self::find($userId);
        if (!$user || $user['balance'] < $amount) {
            return false;
        }
        $pdo->prepare("UPDATE users SET balance = balance - ? WHERE id = ?")->execute([$amount, $userId]);
        return true;
    }

    public static function toggleBan(int $userId): bool
    {
        $pdo = Database::getInstance();
        $user = self::find($userId);
        if (!$user) return false;
        $newStatus = $user['is_banned'] ? 0 : 1;
        $pdo->prepare("UPDATE users SET is_banned = ? WHERE id = ?")->execute([$newStatus, $userId]);
        return (bool)$newStatus;
    }
}
