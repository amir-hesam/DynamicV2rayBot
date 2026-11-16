<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Setting
{
    private static array $cache = [];

    public static function get(string $key, mixed $default = null): mixed
    {
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT `value` FROM settings WHERE `key` = ?");
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();

        if ($val === false) {
            return $default;
        }

        self::$cache[$key] = $val;
        return $val;
    }

    public static function set(string $key, mixed $value): void
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON CONFLICT(`key`) DO UPDATE SET `value` = excluded.`value`");
        $stmt->execute([$key, (string)$value]);
        self::$cache[$key] = (string)$value;
    }

    public static function all(): array
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->query("SELECT `key`, `value` FROM settings");
        $result = [];
        while ($row = $stmt->fetch()) {
            $result[$row['key']] = $row['value'];
        }
        return $result;
    }

    public static function updateMany(array $settings): void
    {
        foreach ($settings as $key => $val) {
            self::set($key, $val);
        }
    }
}
