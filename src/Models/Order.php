<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Order
{
    public static function all(?string $status = null): array
    {
        $pdo = Database::getInstance();
        // LEFT JOIN: wallet top-up orders have plan_id = 0 and must still be listed
        $sql = "SELECT o.*, u.telegram_id, u.username, u.first_name, 
                COALESCE(p.name, 'شارژ کیف پول / بدون پلن') as plan_name, 
                COALESCE(p.traffic_gb, 0) as traffic_gb, COALESCE(p.days, 0) as days, pn.name as panel_name 
                FROM orders o 
                JOIN users u ON o.user_id = u.id 
                LEFT JOIN plans p ON o.plan_id = p.id 
                LEFT JOIN panels pn ON o.panel_id = pn.id";
        
        if ($status) {
            $sql .= " WHERE o.status = :status";
        }
        $sql .= " ORDER BY o.id DESC";

        $stmt = $pdo->prepare($sql);
        if ($status) {
            $stmt->execute(['status' => $status]);
        } else {
            $stmt->execute();
        }
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT o.*, u.telegram_id, u.username, u.first_name, 
                COALESCE(p.name, 'شارژ کیف پول / بدون پلن') as plan_name, 
                COALESCE(p.traffic_gb, 0) as traffic_gb, COALESCE(p.days, 0) as days, 
                COALESCE(p.ip_limit, 0) as ip_limit, pn.name as panel_name 
                FROM orders o 
                JOIN users u ON o.user_id = u.id 
                LEFT JOIN plans p ON o.plan_id = p.id 
                LEFT JOIN panels pn ON o.panel_id = pn.id 
                WHERE o.id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(int $userId, int $planId, int $panelId, int $amount, string $receiptText = '', ?string $receiptPhoto = null): int
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, plan_id, panel_id, amount, status, receipt_photo, receipt_text) 
            VALUES (:user_id, :plan_id, :panel_id, :amount, 'pending', :receipt_photo, :receipt_text)");

        $stmt->execute([
            'user_id' => $userId,
            'plan_id' => $planId,
            'panel_id' => $panelId,
            'amount' => $amount,
            'receipt_photo' => $receiptPhoto,
            'receipt_text' => $receiptText,
        ]);

        return (int)$pdo->lastInsertId();
    }

    public static function approve(int $id): bool
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("UPDATE orders SET status = 'approved', processed_at = CURRENT_TIMESTAMP WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public static function reject(int $id, string $reason = ''): bool
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("UPDATE orders SET status = 'rejected', reject_reason = ?, processed_at = CURRENT_TIMESTAMP WHERE id = ?");
        return $stmt->execute([$reason, $id]);
    }
}
