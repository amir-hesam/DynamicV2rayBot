<?php

namespace App\Services;

use App\Core\Database;
use App\Models\Panel;
use App\Models\Plan;
use App\Models\Service;
use App\Models\User;
use PDO;
use Throwable;

class MigrationService
{
    /**
     * Migrate data from MirzaBot database file (either .sqlite/.db or .sql dump)
     */
    public static function migrateFromFile(string $filePath, ?PDO $targetPdo = null): array
    {
        if (!file_exists($filePath)) {
            return ['success' => false, 'error' => 'فایل دیتابیس انتخاب شده یافت نشد.'];
        }

        $targetPdo = $targetPdo ?: Database::getInstance();

        // 1. Detect SQLite vs SQL Dump
        $header = file_get_contents($filePath, false, null, 0, 16);
        $isSqlite = str_starts_with($header, 'SQLite format 3');

        $extractedData = $isSqlite 
            ? self::extractFromSqlite($filePath)
            : self::extractFromSqlDump($filePath);

        if (!$extractedData['success']) {
            return $extractedData;
        }

        $users = $extractedData['users'] ?? [];
        $panels = $extractedData['panels'] ?? [];
        $products = $extractedData['products'] ?? [];
        $invoices = $extractedData['invoices'] ?? [];

        // 2. Import into Target Database
        $stats = [
            'users' => 0,
            'panels' => 0,
            'plans' => 0,
            'services' => 0,
        ];

        try {
            $isMysql = ($targetPdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql');
            if ($isMysql) {
                try {
                    $targetPdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;");
                    $targetPdo->exec("SET CHARACTER SET utf8mb4;");
                    $targetPdo->exec("SET collation_connection = 'utf8mb4_unicode_ci';");
                    $targetPdo->exec("SET SQL_MODE='';");

                    // Ensure all tables are converted to utf8mb4
                    $tbls = ['users', 'panels', 'plans', 'services', 'settings', 'admins', 'bank_cards', 'orders', 'bot_buttons'];
                    foreach ($tbls as $tbl) {
                        try {
                            $targetPdo->exec("ALTER TABLE `{$tbl}` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
                        } catch (\Throwable $e) {}
                    }
                } catch (\Throwable $e) {}
            }

            $collate = $isMysql ? " COLLATE utf8mb4_unicode_ci" : "";

            // A. Import Users & Balances
            $userMap = []; // telegram_id => local_user_id
            $stmtUserCheck = $targetPdo->prepare("SELECT id FROM users WHERE telegram_id = ?");
            $stmtUserInsert = $targetPdo->prepare("INSERT INTO users (telegram_id, username, first_name, balance, step, created_at) 
                VALUES (:telegram_id, :username, :first_name, :balance, 'idle', :created_at)");
            $stmtUserUpdate = $targetPdo->prepare("UPDATE users SET username = :username, first_name = :first_name, balance = :balance WHERE id = :id");

            foreach ($users as $u) {
                try {
                    $tgId = (string)($u['id'] ?? ($u['telegram_id'] ?? ($u['id_user'] ?? ($u[0] ?? ''))));
                    $tgId = trim($tgId, "'\" \t\r\n");
                    if (empty($tgId) || !is_numeric($tgId)) {
                        continue;
                    }

                    $username = null;
                    if (!empty($u['username']) && $u['username'] !== 'none') {
                        $username = trim($u['username'], "'\" \t\r\n");
                    } elseif (!empty($u[3]) && $u[3] !== 'none') {
                        $username = trim($u[3], "'\" \t\r\n");
                    }

                    $firstName = 'کاربر میرزا';
                    if (!empty($u['namecustom']) && $u['namecustom'] !== 'none') {
                        $firstName = trim($u['namecustom'], "'\" \t\r\n");
                    } elseif (!empty($u[19]) && $u[19] !== 'none') {
                        $firstName = trim($u[19], "'\" \t\r\n");
                    } elseif ($username) {
                        $firstName = $username;
                    }

                    $balance = 0;
                    if (isset($u['Balance']) && is_numeric($u['Balance'])) {
                        $balance = (int)$u['Balance'];
                    } elseif (isset($u['balance']) && is_numeric($u['balance'])) {
                        $balance = (int)$u['balance'];
                    } elseif (isset($u[11]) && is_numeric($u[11])) {
                        $balance = (int)$u[11];
                    }
                    
                    $regTime = $u['register'] ?? ($u[21] ?? null);
                    $createdAt = (is_numeric($regTime) && $regTime > 1000000000) 
                        ? date('Y-m-d H:i:s', (int)$regTime) 
                        : date('Y-m-d H:i:s');

                    $stmtUserCheck->execute([$tgId]);
                    $existingId = $stmtUserCheck->fetchColumn();

                    if ($existingId) {
                        $stmtUserUpdate->execute([
                            'id' => $existingId,
                            'username' => $username,
                            'first_name' => $firstName,
                            'balance' => $balance,
                        ]);
                        $userMap[$tgId] = (int)$existingId;
                    } else {
                        $stmtUserInsert->execute([
                            'telegram_id' => $tgId,
                            'username' => $username,
                            'first_name' => $firstName,
                            'balance' => $balance,
                            'created_at' => $createdAt,
                        ]);
                        $newId = (int)$targetPdo->lastInsertId();
                        $userMap[$tgId] = $newId;
                        $stats['users']++;
                    }
                } catch (\Throwable $e) {
                    error_log("User import skip: " . $e->getMessage());
                }
            }

            // B. Import Panels
            $panelNameMap = []; // name_panel => local_panel_id
            $stmtPanelCheck = $targetPdo->prepare("SELECT id FROM panels WHERE name = ?{$collate}");
            $stmtPanelInsert = $targetPdo->prepare("INSERT INTO panels (name, type, url, username, password, inbounds_json, is_active, custom_service_enabled, price_per_gb, price_per_day, min_gb, max_gb, min_days, max_days, categories_json) 
                VALUES (:name, :type, :url, :username, :password, :inbounds_json, :is_active, :custom_service_enabled, :price_per_gb, :price_per_day, :min_gb, :max_gb, :min_days, :max_days, :categories_json)");

            foreach ($panels as $p) {
                try {
                    $name = trim($p['name_panel'] ?? ($p['name'] ?? ''));
                    if (empty($name)) {
                        continue;
                    }

                    $type = strtolower(trim($p['type'] ?? 'sanaei'));
                    // Normalize panel type
                    if ($type === '3x-ui' || $type === 'x-ui_single' || $type === 'xui') $type = 'sanaei';
                    if ($type === 'rebeca') $type = 'rebka';
                    if ($type === 'manualsale') $type = 'manual';
                    if ($type === 'wg_mate') $type = 'wgmate';

                    $url = rtrim($p['url_panel'] ?? ($p['url'] ?? ''), '/');
                    $username = $p['username_panel'] ?? ($p['username'] ?? '');
                    $password = $p['password_panel'] ?? ($p['password'] ?? '');
                    $isActive = (isset($p['status']) && strtolower($p['status']) === 'active') ? 1 : 1;

                    // Custom service configs
                    $customVolJson = $p['customvolume'] ?? '';
                    $customVol = is_string($customVolJson) ? json_decode($customVolJson, true) : null;
                    $customEnabled = (is_array($customVol) && !empty($customVol['f']) && $customVol['f'] != '0') ? 1 : 0;

                    $priceGb = 4000;
                    $priceGbJson = $p['pricecustomvolume'] ?? '';
                    if ($priceGbJson) {
                        $d = json_decode($priceGbJson, true);
                        if (is_array($d) && !empty($d['f'])) $priceGb = (int)$d['f'];
                    }

                    $priceDay = 1000;
                    $priceDayJson = $p['pricecustomtime'] ?? '';
                    if ($priceDayJson) {
                        $d = json_decode($priceDayJson, true);
                        if (is_array($d) && !empty($d['f'])) $priceDay = (int)$d['f'];
                    }

                    $minGb = 5;
                    $minGbJson = $p['mainvolume'] ?? '';
                    if ($minGbJson) {
                        $d = json_decode($minGbJson, true);
                        if (is_array($d) && !empty($d['f'])) $minGb = (int)$d['f'];
                    }

                    $maxGb = 500;
                    $maxGbJson = $p['maxvolume'] ?? '';
                    if ($maxGbJson) {
                        $d = json_decode($maxGbJson, true);
                        if (is_array($d) && !empty($d['f'])) $maxGb = (int)$d['f'];
                    }

                    $minDays = 1;
                    $minDaysJson = $p['maintime'] ?? '';
                    if ($minDaysJson) {
                        $d = json_decode($minDaysJson, true);
                        if (is_array($d) && !empty($d['f'])) $minDays = (int)$d['f'];
                    }

                    $maxDays = 365;
                    $maxDaysJson = $p['maxtime'] ?? '';
                    if ($maxDaysJson) {
                        $d = json_decode($maxDaysJson, true);
                        if (is_array($d) && !empty($d['f'])) $maxDays = (int)$d['f'];
                    }

                    $inboundId = $p['inboundid'] ?? '1';
                    $inboundsJson = is_numeric($inboundId) ? json_encode([(int)$inboundId]) : (is_string($inboundId) && str_starts_with($inboundId, '[') ? $inboundId : json_encode([$inboundId]));

                    $stmtPanelCheck->execute([$name]);
                    $existingPanelId = $stmtPanelCheck->fetchColumn();

                    if ($existingPanelId) {
                        $panelNameMap[$name] = (int)$existingPanelId;
                    } else {
                        $stmtPanelInsert->execute([
                            'name' => $name,
                            'type' => $type,
                            'url' => $url,
                            'username' => $username,
                            'password' => $password,
                            'inbounds_json' => $inboundsJson,
                            'is_active' => $isActive,
                            'custom_service_enabled' => $customEnabled,
                            'price_per_gb' => $priceGb,
                            'price_per_day' => $priceDay,
                            'min_gb' => $minGb,
                            'max_gb' => $maxGb,
                            'min_days' => $minDays,
                            'max_days' => $maxDays,
                            'categories_json' => '["یک ماهه", "دو ماهه", "سه ماهه", "نامحدود"]',
                        ]);
                        $newPanelId = (int)$targetPdo->lastInsertId();
                        $panelNameMap[$name] = $newPanelId;
                        $stats['panels']++;
                    }
                } catch (\Throwable $e) {
                    error_log("Panel import skip: " . $e->getMessage());
                }
            }

            // C. Import Products / Plans
            $stmtPlanCheck = $targetPdo->prepare("SELECT id FROM plans WHERE name = ?{$collate}");
            $stmtPlanInsert = $targetPdo->prepare("INSERT INTO plans (name, description, category, price, traffic_gb, days, ip_limit, hwid_limit, panel_id, is_active, sort_order) 
                VALUES (:name, :description, :category, :price, :traffic_gb, :days, 1, 0, :panel_id, 1, :sort_order)");

            foreach ($products as $idx => $prod) {
                try {
                    $name = trim($prod['name_product'] ?? ($prod['name'] ?? ''));
                    if (empty($name)) continue;

                    $price = (int)($prod['price_product'] ?? ($prod['price'] ?? 0));
                    $vol = (int)($prod['Volume'] ?? ($prod['volume'] ?? 0));
                    $days = (int)($prod['Service_time'] ?? ($prod['days'] ?? 30));
                    $cat = trim($prod['category'] ?? 'یک ماهه') ?: 'یک ماهه';

                    $stmtPlanCheck->execute([$name]);
                    if (!$stmtPlanCheck->fetchColumn()) {
                        $stmtPlanInsert->execute([
                            'name' => $name,
                            'description' => "پلن وارد شده از میرزابوت ({$vol} گیگابایت - {$days} روز)",
                            'category' => $cat,
                            'price' => $price,
                            'traffic_gb' => $vol,
                            'days' => $days,
                            'panel_id' => 0, // Auto balance
                            'sort_order' => $idx + 1,
                        ]);
                        $stats['plans']++;
                    }
                } catch (\Throwable $e) {
                    error_log("Plan import skip: " . $e->getMessage());
                }
            }

            // D. Import Invoices (Services) & Enable Live Inquiry
            $stmtServiceCheck = $targetPdo->prepare("SELECT id FROM services WHERE client_email = ?{$collate} OR client_uuid = ?{$collate}");
            $stmtServiceInsert = $targetPdo->prepare("INSERT INTO services (order_id, user_id, panel_id, client_uuid, client_email, inbound_id, subscription_url, config_link, wireguard_conf, traffic_gb, days, ip_limit, hwid_limit, used_traffic_bytes, expires_at, is_active, created_at) 
                VALUES (NULL, :user_id, :panel_id, :client_uuid, :client_email, 0, :subscription_url, :config_link, :wireguard_conf, :traffic_gb, :days, 1, 0, 0, :expires_at, :is_active, :created_at)");

            // Fallback panel if service_location not found
            $defaultPanelId = !empty($panelNameMap) ? reset($panelNameMap) : 1;

            foreach ($invoices as $inv) {
                try {
                    $tgUserId = (string)($inv['id_user'] ?? '');
                    $userId = $userMap[$tgUserId] ?? null;

                    if (!$userId) {
                        // Create minimal user placeholder if not exists
                        if (!empty($tgUserId) && is_numeric($tgUserId)) {
                            $targetPdo->prepare("INSERT INTO users (telegram_id, username, first_name, balance, step, created_at) VALUES (?, NULL, 'کاربر میرزا', 0, 'idle', ?)")
                                ->execute([$tgUserId, date('Y-m-d H:i:s')]);
                            $userId = (int)$targetPdo->lastInsertId();
                            $userMap[$tgUserId] = $userId;
                            $stats['users']++;
                        } else {
                            continue;
                        }
                    }

                    $configUsername = trim($inv['username'] ?? '');
                    if (empty($configUsername)) {
                        continue;
                    }

                    $loc = trim($inv['Service_location'] ?? '');
                    $panelId = $panelNameMap[$loc] ?? $defaultPanelId;

                    $vol = (int)($inv['Volume'] ?? 0);
                    $days = (int)($inv['Service_time'] ?? 30);
                    $uuid = $inv['uuid'] ?? $configUsername;
                    $statusStr = strtolower($inv['Status'] ?? 'active');
                    $isActive = ($statusStr === 'active' || $statusStr === '0' || $statusStr === '') ? 1 : 0;

                    // Parse user_info json if available
                    $subUrl = '';
                    $configLink = '';
                    $wireguardConf = null;

                    $userInfoStr = $inv['user_info'] ?? '';
                    if ($userInfoStr) {
                        $uInfo = json_decode($userInfoStr, true);
                        if (is_array($uInfo)) {
                            $subUrl = $uInfo['subscription_url'] ?? '';
                            if (!empty($uInfo['configs']) && is_array($uInfo['configs'])) {
                                $configLink = $uInfo['configs'][0] ?? '';
                            }
                        }
                    }

                    if (empty($subUrl) && str_starts_with($uuid, 'http')) {
                        $subUrl = $uuid;
                    }
                    if (empty($configLink) && (str_starts_with($uuid, 'vmess://') || str_starts_with($uuid, 'vless://') || str_starts_with($uuid, 'trojan://') || str_starts_with($uuid, 'ss://'))) {
                        $configLink = $uuid;
                    }

                    // WireGuard conf detection
                    if (WireGuardHelper::isWireGuard($configLink)) {
                        $wireguardConf = WireGuardHelper::toConf($configLink, $configUsername);
                    }

                    // Expiration time calculation
                    $timeSell = $inv['time_sell'] ?? null;
                    $createdTs = (is_numeric($timeSell) && $timeSell > 1000000000) ? (int)$timeSell : time();
                    $createdAt = date('Y-m-d H:i:s', $createdTs);
                    $expiresAt = ($days > 0) ? date('Y-m-d H:i:s', $createdTs + ($days * 86400)) : null;

                    // Check for duplicate service
                    $stmtServiceCheck->execute([$configUsername, $configUsername]);
                    if (!$stmtServiceCheck->fetchColumn()) {
                        $stmtServiceInsert->execute([
                            'user_id' => $userId,
                            'panel_id' => $panelId,
                            'client_uuid' => $uuid,
                            'client_email' => $configUsername,
                            'subscription_url' => $subUrl,
                            'config_link' => $configLink,
                            'wireguard_conf' => $wireguardConf,
                            'traffic_gb' => $vol,
                            'days' => $days,
                            'expires_at' => $expiresAt,
                            'is_active' => $isActive,
                            'created_at' => $createdAt,
                        ]);
                        $stats['services']++;
                    }
                } catch (\Throwable $e) {
                    error_log("Invoice import skip: " . $e->getMessage());
                }
            }

            return [
                'success' => true,
                'message' => "مهاجرت با موفقیت تکمیل شد! تعداد کاربران: {$stats['users']}، پنل‌ها: {$stats['panels']}، پلن‌ها: {$stats['plans']}، سرویس‌ها: {$stats['services']}",
                'stats' => $stats,
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'error' => 'خطا در فرآیند درج اطلاعات مهاجرت: ' . $e->getMessage(),
                'stats' => $stats,
            ];
        }
    }

    /**
     * Extract data from SQLite database file
     */
    private static function extractFromSqlite(string $filePath): array
    {
        try {
            $sqlite = new PDO("sqlite:" . $filePath);
            $sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
            $sqlite->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            $users = [];
            $panels = [];
            $products = [];
            $invoices = [];

            // 1. Users
            $q = $sqlite->query("SELECT * FROM user");
            if (!$q) $q = $sqlite->query("SELECT * FROM users");
            if ($q) $users = $q->fetchAll();

            // 2. Panels
            $q = $sqlite->query("SELECT * FROM marzban_panel");
            if (!$q) $q = $sqlite->query("SELECT * FROM panels");
            if ($q) $panels = $q->fetchAll();

            // 3. Products
            $q = $sqlite->query("SELECT * FROM product");
            if (!$q) $q = $sqlite->query("SELECT * FROM products");
            if ($q) $products = $q->fetchAll();

            // 4. Invoices
            $q = $sqlite->query("SELECT * FROM invoice");
            if (!$q) $q = $sqlite->query("SELECT * FROM invoices");
            if ($q) $invoices = $q->fetchAll();
            $q = $sqlite->query("SELECT * FROM product");
            if ($q) $products = $q->fetchAll();

            // 4. Invoices
            $q = $sqlite->query("SELECT * FROM invoice");
            if ($q) $invoices = $q->fetchAll();

            return [
                'success' => true,
                'users' => $users,
                'panels' => $panels,
                'products' => $products,
                'invoices' => $invoices,
            ];
        } catch (Throwable $e) {
            return ['success' => false, 'error' => 'خطا در خواندن فایل SQLite: ' . $e->getMessage()];
        }
    }

    /**
     * Fast, streaming and multiline-aware parser for MySQL .sql dump files
     */
    private static function extractFromSqlDump(string $filePath): array
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return ['success' => false, 'error' => 'امکان باز کردن فایل SQL جهت پردازش وجود ندارد.'];
        }

        $users = [];
        $panels = [];
        $products = [];
        $invoices = [];

        $buffer = '';
        $inStatement = false;
        $currentTable = null;
        $inQuote = false;
        $quoteChar = null;

        while (!feof($handle)) {
            $line = fgets($handle);
            if ($line === false) break;

            $trimmed = trim($line);

            if (!$inStatement) {
                if (empty($trimmed) || str_starts_with($trimmed, '--') || str_starts_with($trimmed, '/*') || str_starts_with($trimmed, '#')) {
                    continue;
                }

                // Detect start of INSERT INTO or REPLACE INTO
                if (preg_match('/^(?:INSERT(?:\s+IGNORE)?|REPLACE)\s+INTO\s+[`\'"]?([a-zA-Z0-9_]+)[`\'"]?/i', $trimmed, $m)) {
                    $tableCandidate = strtolower($m[1]);
                    if (in_array($tableCandidate, ['user', 'users', 'marzban_panel', 'panels', 'product', 'products', 'invoice', 'invoices'])) {
                        $inStatement = true;
                        $currentTable = $tableCandidate;
                        $buffer = $line;
                        $inQuote = false;
                        $quoteChar = null;
                    } else {
                        continue;
                    }
                } else {
                    continue;
                }
            } else {
                $buffer .= $line;
            }

            // Semicolon detection outside of quotes
            $len = strlen($line);
            for ($i = 0; $i < $len; $i++) {
                $ch = $line[$i];

                if ($inQuote) {
                    if ($ch === '\\') {
                        $i++;
                        continue;
                    }
                    if ($ch === $quoteChar) {
                        $inQuote = false;
                        $quoteChar = null;
                    }
                } else {
                    if ($ch === "'" || $ch === '"') {
                        $inQuote = true;
                        $quoteChar = $ch;
                    } elseif ($ch === ';') {
                        // Statement is complete
                        self::processInsertStatement($buffer, $currentTable, $users, $panels, $products, $invoices);
                        $inStatement = false;
                        $currentTable = null;
                        $buffer = '';
                        $inQuote = false;
                        $quoteChar = null;
                        break;
                    }
                }
            }
        }

        if ($inStatement && !empty($buffer) && $currentTable !== null) {
            self::processInsertStatement($buffer, $currentTable, $users, $panels, $products, $invoices);
        }

        fclose($handle);

        return [
            'success' => true,
            'users' => $users,
            'panels' => $panels,
            'products' => $products,
            'invoices' => $invoices,
        ];
    }

    /**
     * Process a complete INSERT INTO statement buffer
     */
    private static function processInsertStatement(string $sql, string $table, array &$users, array &$panels, array &$products, array &$invoices): void
    {
        $pos = stripos($sql, 'VALUES');
        if ($pos === false) return;

        $prefix = substr($sql, 0, $pos);
        $valuesPart = substr($sql, $pos + 6);
        $valuesPart = rtrim(trim($valuesPart), ';');

        // Extract column names if specified in prefix
        $columns = [];
        if (preg_match('/\((.*?)\)\s*$/s', trim($prefix), $cm)) {
            $colRaw = $cm[1];
            $columns = array_map(function ($c) {
                return trim($c, " `'\"\r\n\t");
            }, explode(',', $colRaw));
        }

        $normTable = match ($table) {
            'users' => 'user',
            'panels' => 'marzban_panel',
            'products' => 'product',
            'invoices' => 'invoice',
            default => $table
        };

        if (empty($columns)) {
            $columns = self::getDefaultColumnsForTable($normTable);
        }

        // Parse tuples with robust parser
        $rows = self::parseSqlTuples($valuesPart);

        foreach ($rows as $rowVals) {
            $row = [];
            foreach ($columns as $idx => $colName) {
                if (isset($rowVals[$idx])) {
                    $row[$colName] = $rowVals[$idx];
                }
            }
            // Also keep numeric indices for fallback
            foreach ($rowVals as $idx => $val) {
                $row[$idx] = $val;
            }

            if ($normTable === 'user') {
                $users[] = $row;
            } elseif ($normTable === 'marzban_panel') {
                $panels[] = $row;
            } elseif ($normTable === 'product') {
                $products[] = $row;
            } elseif ($normTable === 'invoice') {
                $invoices[] = $row;
            }
        }
    }

    /**
     * Parse tuples of VALUES (...) in SQL dump with full support for escaped quotes, newlines & JSON
     */
    private static function parseSqlTuples(string $sql): array
    {
        $rows = [];
        $currentRow = [];
        $currentCell = '';
        $inParen = false;
        $inQuote = false;
        $wasQuoted = false;
        $len = strlen($sql);

        for ($i = 0; $i < $len; $i++) {
            $c = $sql[$i];

            if ($inQuote) {
                if ($c === '\\') {
                    $i++;
                    if ($i < $len) {
                        $next = $sql[$i];
                        $currentCell .= match($next) {
                            'n' => "\n",
                            'r' => "\r",
                            't' => "\t",
                            '0' => "\0",
                            default => $next
                        };
                    }
                    continue;
                }
                if ($c === "'") {
                    // Check for standard SQL double-quote escape: ''
                    if ($i + 1 < $len && $sql[$i + 1] === "'") {
                        $currentCell .= "'";
                        $i++;
                        continue;
                    }
                    $inQuote = false;
                    continue;
                }
                $currentCell .= $c;
                continue;
            }

            // Outside quotes
            if ($c === "'") {
                $inQuote = true;
                $wasQuoted = true;
                continue;
            }
            if ($c === '(' && !$inParen) {
                $inParen = true;
                $currentRow = [];
                $currentCell = '';
                $wasQuoted = false;
                continue;
            }
            if ($c === ')' && $inParen) {
                $inParen = false;
                $val = $wasQuoted ? $currentCell : (trim($currentCell) === 'NULL' ? null : trim($currentCell));
                $currentRow[] = $val;
                $currentCell = '';
                $wasQuoted = false;
                $rows[] = $currentRow;
                $currentRow = [];
                continue;
            }
            if ($c === ',' && $inParen) {
                $val = $wasQuoted ? $currentCell : (trim($currentCell) === 'NULL' ? null : trim($currentCell));
                $currentRow[] = $val;
                $currentCell = '';
                $wasQuoted = false;
                continue;
            }
            if ($inParen) {
                $currentCell .= $c;
            }
        }

        return $rows;
    }

    private static function getDefaultColumnsForTable(string $table): array
    {
        return match ($table) {
            'user' => [
                'id', 'limit_usertest', 'roll_Status', 'username', 'Processing_value', 'Processing_value_one',
                'Processing_value_tow', 'Processing_value_four', 'step', 'description_blocking', 'number',
                'Balance', 'User_Status', 'pagenumber', 'message_count', 'last_message_time', 'agent',
                'affiliatescount', 'affiliates', 'namecustom', 'number_username', 'register', 'verify',
                'cardpayment', 'codeInvitation', 'pricediscount', 'hide_mini_app_instruction',
                'maxbuyagent', 'joinchannel', 'checkstatus', 'bottype', 'score', 'limitchangeloc',
                'status_cron', 'expire', 'token', 'lang'
            ],
            'marzban_panel' => [
                'id', 'code_panel', 'name_panel', 'status', 'url_panel', 'username_panel', 'password_panel',
                'agent', 'sublink', 'config', 'MethodUsername', 'TestAccount', 'limit_panel', 'namecustom',
                'Methodextend', 'conecton', 'linksubx', 'inboundid', 'type', 'inboundstatus', 'inbound_deactive',
                'time_usertest', 'val_usertest', 'secret_code', 'priceChangeloc', 'priceextravolume',
                'pricecustomvolume', 'pricecustomtime', 'priceextratime', 'mainvolume', 'maxvolume',
                'maintime', 'maxtime', 'status_extend', 'datelogin', 'proxies', 'inbounds', 'subvip',
                'changeloc', 'on_hold_test', 'version_panel', 'customvolume', 'hide_user', 'display_order'
            ],
            'product' => [
                'id', 'name_product', 'price_product', 'Volume', 'Service_time', 'category', 'inboundid', 'Status'
            ],
            'invoice' => [
                'id_invoice', 'id_user', 'username', 'Service_location', 'time_sell', 'name_product',
                'price_product', 'Volume', 'Service_time', 'uuid', 'note', 'user_info', 'bottype',
                'refral', 'time_cron', 'notifctions', 'Status', 'id_payment', 'payable_amount'
            ],
            default => []
        };
    }
}
