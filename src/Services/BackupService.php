<?php

namespace App\Services;

use App\Core\Database;
use App\Models\Setting;
use App\Telegram\Bot;
use PDO;
use Exception;
use CURLFile;

class BackupService
{
    /**
     * Generate database dump file
     * Compatible with pure PHP (cPanel shared hosting, zero exec/SSH required)
     */
    public static function createDatabaseDump(): ?string
    {
        $config = require __DIR__ . '/../../config/database.php';
        $driver = $config['driver'] ?? 'sqlite';
        $tempDir = __DIR__ . '/../../storage/backups';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $dateStr = date('Y-m-d_H-i-s');

        // 1. SQLite Database Backup
        if ($driver === 'sqlite') {
            $sqlitePath = $config['sqlite']['database'];
            if (!file_exists($sqlitePath)) {
                return null;
            }

            $targetSqlite = $tempDir . "/backup_{$dateStr}.sqlite";
            if (copy($sqlitePath, $targetSqlite)) {
                return $targetSqlite;
            }
            return null;
        }

        // 2. MySQL / MariaDB Pure PHP PDO Dump (Identical to MirzaBot backupDumpDatabaseWithPdo)
        $targetFile = $tempDir . "/backup_{$dateStr}.sql";
        $pdo = Database::getInstance();
        $handle = @fopen($targetFile, 'w');

        if (!$handle) {
            error_log('Unable to open backup file for writing: ' . $targetFile);
            return null;
        }

        try {
            fwrite($handle, "-- VPN Bot Database Backup\n-- Date: " . date('Y-m-d H:i:s') . "\n");
            fwrite($handle, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\nSET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n");

            $tables = $pdo->query('SHOW FULL TABLES')->fetchAll(PDO::FETCH_NUM);
            foreach ($tables as $table) {
                $tableName = $table[0];
                $name = '`' . str_replace('`', '``', $tableName) . '`';
                $isView = isset($table[1]) && strtoupper($table[1]) === 'VIEW';

                $create = $pdo->query('SHOW CREATE TABLE ' . $name)->fetch(PDO::FETCH_NUM);
                fwrite($handle, 'DROP ' . ($isView ? 'VIEW' : 'TABLE') . ' IF EXISTS ' . $name . ";\n" . $create[1] . ";\n\n");

                if ($isView) {
                    continue;
                }

                $offset = 0;
                $chunkSize = 500;
                do {
                    $rows = $pdo->query('SELECT * FROM ' . $name . ' LIMIT ' . $chunkSize . ' OFFSET ' . $offset)->fetchAll(PDO::FETCH_ASSOC);
                    if (!$rows) {
                        break;
                    }

                    $columns = [];
                    foreach (array_keys($rows[0]) as $column) {
                        $columns[] = '`' . str_replace('`', '``', $column) . '`';
                    }

                    $values = [];
                    foreach ($rows as $row) {
                        $cells = [];
                        foreach ($row as $cell) {
                            $cells[] = $cell === null ? 'NULL' : $pdo->quote((string)$cell);
                        }
                        $values[] = '(' . implode(',', $cells) . ')';
                    }

                    fwrite($handle, 'INSERT INTO ' . $name . ' (' . implode(',', $columns) . ") VALUES\n" . implode(",\n", $values) . ";\n");
                    $offset += $chunkSize;
                } while (count($rows) === $chunkSize);

                fwrite($handle, "\n");
            }

            fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
            fclose($handle);

            return is_file($targetFile) && filesize($targetFile) > 0 ? $targetFile : null;
        } catch (Exception $e) {
            error_log('PDO database backup failed: ' . $e->getMessage());
            fclose($handle);
            @unlink($targetFile);
            return null;
        }
    }

    /**
     * Execute full backup and send to Telegram (MirzaBot Cron Backup pattern)
     */
    public static function runBackupJob(): array
    {
        $bot = new Bot();
        $groupId = Setting::get('report_group_id') ?: Setting::get('telegram_report_group_id');
        $adminId = Setting::get('admin_telegram_id');

        $chatId = !empty($groupId) ? $groupId : $adminId;
        if (empty($chatId)) {
            return ['success' => false, 'error' => 'هیچ آیدی ادمین یا گروه گزارشاتی جهت ارسال بک‌آپ تنظیم نشده است.'];
        }

        $backupThreadId = null;
        if (!empty($groupId)) {
            $backupThreadId = ReportHelper::getTopicThreadId(ReportHelper::TOPIC_BACKUP);
        }

        $dumpFile = self::createDatabaseDump();

        if (!$dumpFile || !file_exists($dumpFile)) {
            $errorMsg = "🚨 <b>خطا در فرآیند پشتیبان‌گیری خودکار!</b>\nپایگاه داده در دسترس نیست یا امکان ایجاد فایل بک‌آپ وجود ندارد.";
            if ($backupThreadId) {
                ReportHelper::sendToTopic(ReportHelper::TOPIC_ERROR, $errorMsg);
            } else {
                $bot->sendMessage($chatId, $errorMsg);
            }
            return ['success' => false, 'error' => 'خطا در ایجاد فایل بک‌آپ'];
        }

        $fileSizeMb = round(filesize($dumpFile) / (1024 * 1024), 2);
        $caption = "💾 <b>پشتیبان‌گیری خودکار دیتابیس ربات (MirzaBot Cron Style)</b>\n\n";
        $caption .= "📅 <b>تاریخ و ساعت:</b> " . date('Y-m-d H:i:s') . "\n";
        $caption .= "📦 <b>حجم فایل:</b> {$fileSizeMb} MB\n";
        $caption .= "🔐 <b>نوع پشتیبان:</b> دیتابیس کامل (کاربران، تراکنش‌ها، پنل‌ها، سرویس‌ها)";

        // Send via Telegram Bot
        if (!empty($groupId) && $backupThreadId) {
            $res = ReportHelper::sendDocumentToTopic(ReportHelper::TOPIC_BACKUP, $dumpFile, $caption);
        } else {
            $res = $bot->sendDocument($chatId, $dumpFile, $caption);
        }

        // Clean up temporary file
        @unlink($dumpFile);

        if (!empty($res['ok'])) {
            return ['success' => true, 'message' => 'پشتیبان با موفقیت ایجاد و به تلگرام ارسال شد.'];
        }

        return ['success' => false, 'error' => $res['description'] ?? 'خطای تلگرام در ارسال فایل'];
    }
}
