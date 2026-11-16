<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class BotButton
{
    public static function all(): array
    {
        $pdo = Database::getInstance();
        return $pdo->query("SELECT * FROM bot_buttons ORDER BY row_num ASC, order_in_row ASC, id ASC")->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM bot_buttons WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findByKey(string $key): ?array
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM bot_buttons WHERE `key` = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function createButton(array $data): int
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("INSERT INTO bot_buttons (`key`, title, row_num, order_in_row, is_active, color, text_color) 
            VALUES (:key, :title, :row_num, :order_in_row, :is_active, :color, :text_color)");

        $key = !empty($data['key']) ? trim($data['key']) : ('btn_custom_' . substr(md5(uniqid()), 0, 6));

        $stmt->execute([
            'key' => $key,
            'title' => trim($data['title']),
            'row_num' => (int)($data['row_num'] ?? 1),
            'order_in_row' => (int)($data['order_in_row'] ?? 1),
            'is_active' => (int)($data['is_active'] ?? 1),
            'color' => $data['color'] ?? '#2563eb',
            'text_color' => $data['text_color'] ?? '#ffffff',
        ]);

        return (int)$pdo->lastInsertId();
    }

    public static function updateButton(int $id, array $data): bool
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("UPDATE bot_buttons SET 
            title = :title, 
            row_num = :row_num, 
            order_in_row = :order_in_row, 
            is_active = :is_active, 
            color = :color, 
            text_color = :text_color 
            WHERE id = :id");

        return $stmt->execute([
            'id' => $id,
            'title' => trim($data['title']),
            'row_num' => (int)($data['row_num'] ?? 1),
            'order_in_row' => (int)($data['order_in_row'] ?? 1),
            'is_active' => (int)($data['is_active'] ?? 1),
            'color' => $data['color'] ?? '#2563eb',
            'text_color' => $data['text_color'] ?? '#ffffff',
        ]);
    }

    public static function deleteButton(int $id): bool
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("DELETE FROM bot_buttons WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public static function restoreDefaults(): void
    {
        $pdo = Database::getInstance();
        $pdo->exec("DELETE FROM bot_buttons");

        $btnStmt = $pdo->prepare("INSERT INTO bot_buttons (`key`, title, row_num, order_in_row, is_active, color, text_color) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $defaultBtns = [
            ['btn_miniapp', '🚀 ورود به مینی‌اپ', 1, 1, 1, '#3b82f6', '#ffffff'],
            ['btn_buy', '🛍 خرید اشتراک جدید', 2, 1, 1, '#2563eb', '#ffffff'],
            ['btn_my_services', '📱 سرویس‌های فعال من', 2, 2, 1, '#10b981', '#ffffff'],
            ['btn_plans', '📋 تعرفه و پلن‌ها', 3, 1, 1, '#6366f1', '#ffffff'],
            ['btn_wallet', '💳 کارت به کارت و شارژ', 3, 2, 1, '#f59e0b', '#ffffff'],
            ['btn_help', '📖 راهنمای اتصال', 4, 1, 1, '#06b6d4', '#ffffff'],
            ['btn_support', '📞 پشتیبانی تلگرام', 4, 2, 1, '#ec4899', '#ffffff'],
            ['btn_test_account', '🎁 اکانت تست رایگان', 5, 1, 1, '#8b5cf6', '#ffffff'],
        ];

        foreach ($defaultBtns as $b) {
            $btnStmt->execute($b);
        }
    }

    public static function getActiveGrid(): array
    {
        $buttons = self::all();
        $rows = [];

        foreach ($buttons as $btn) {
            if (!$btn['is_active']) {
                continue;
            }
            $r = (int)$btn['row_num'];
            if (!isset($rows[$r])) {
                $rows[$r] = [];
            }
            $rows[$r][] = $btn;
        }

        ksort($rows);

        foreach ($rows as $r => &$items) {
            usort($items, fn($a, $b) => $a['order_in_row'] <=> $b['order_in_row']);
        }

        return $rows;
    }
}
