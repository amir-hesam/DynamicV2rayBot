<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Admin
{
    public static function all(): array
    {
        $pdo = Database::getInstance();
        return $pdo->query("SELECT * FROM admins ORDER BY id ASC")->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findByUsername(string $username): ?array
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(array $data): int
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("INSERT INTO admins (username, password, name, role, permissions, is_active) 
            VALUES (:username, :password, :name, :role, :permissions, :is_active)");

        $stmt->execute([
            'username' => trim($data['username']),
            'password' => password_hash($data['password'], PASSWORD_BCRYPT),
            'name' => trim($data['name']),
            'role' => $data['role'] ?? 'operator',
            'permissions' => json_encode($data['permissions'] ?? []),
            'is_active' => (int)($data['is_active'] ?? 1),
        ]);

        return (int)$pdo->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $pdo = Database::getInstance();
        $params = [
            'id' => $id,
            'name' => trim($data['name']),
            'role' => $data['role'] ?? 'operator',
            'permissions' => json_encode($data['permissions'] ?? []),
            'is_active' => (int)($data['is_active'] ?? 1),
        ];

        $sql = "UPDATE admins SET name = :name, role = :role, permissions = :permissions, is_active = :is_active";

        if (!empty($data['password'])) {
            $sql .= ", password = :password";
            $params['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }

        $sql .= " WHERE id = :id";

        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public static function delete(int $id): bool
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("DELETE FROM admins WHERE id = ? AND role != 'super_admin'");
        return $stmt->execute([$id]);
    }

    public static function hasPermission(array $admin, string $permission): bool
    {
        if ($admin['role'] === 'super_admin') {
            return true;
        }

        $perms = json_decode($admin['permissions'] ?? '[]', true);
        if (!is_array($perms)) {
            return false;
        }

        return in_array('*', $perms) || in_array($permission, $perms);
    }
}
