<?php
require_once '../../includes/csrf.php';
declare(strict_types=1);

/**
 * 数据库封装 — MariaDB(MySQL) + PDO
 * 宝塔部署：在 .env 中配置 MariaDB/MySQL 连接信息
 * 本地开发：APP_DEBUG=true 时可自动降级到 SQLite
 */

class DB {
    private static ?PDO $pdo = null;
    private static string $driver = '';

    /** 当前使用的数据库引擎：mysql / sqlite */
    public static function driver(): string { return self::$driver; }

    /** 获取 PDO 连接 */
    public static function conn(): PDO {
        if (self::$pdo !== null) return self::$pdo;

        $cfg = require __DIR__ . '/config.php';
        $isDebug = !empty($cfg['debug']);

        // === MariaDB 连接 ===
        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $cfg['host'], $cfg['port'], $cfg['dbname'], $cfg['charset']
            );
            self::$pdo = new PDO($dsn, $cfg['username'], $cfg['password'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            self::$driver = 'mysql';
            self::initTable($cfg['prefix'], 'mysql');
            return self::$pdo;
        } catch (\PDOException $e) {
            // debug 模式：降级 SQLite
            if ($isDebug) {
                // fall through to SQLite
            } else {
                // 生产模式：直接抛出并由调用方记录安全错误信息
                throw $e;
            }
        }

        // === SQLite 降级（仅 debug 模式） ===
        $dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $dbPath = $dir . DIRECTORY_SEPARATOR . 'messages.db';
        self::$pdo = new PDO("sqlite:{$dbPath}", null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        self::$pdo->exec('PRAGMA journal_mode = WAL');
        self::$driver = 'sqlite';
        self::initTable($cfg['prefix'], 'sqlite');
        return self::$pdo;
    }

    // ========== 建表 ==========

    private static function initTable(string $prefix, string $engine): void {
        $table = $prefix . 'contacts';
        $productsTable = $prefix . 'products';
        $settingsTable = $prefix . 'settings';
        $ordersTable = $prefix . 'payment_orders';
        $refundsTable = $prefix . 'payment_refunds';
        if ($engine === 'mysql') {
            self::$pdo->exec("CREATE TABLE IF NOT EXISTS `{$table}` (
                `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `name`       VARCHAR(50)  NOT NULL DEFAULT '' COMMENT '姓名',
                `company`    VARCHAR(120) NOT NULL DEFAULT '' COMMENT '公司',
                `phone`      VARCHAR(30)  NOT NULL DEFAULT '' COMMENT '手机号',
                `email`      VARCHAR(120) NOT NULL DEFAULT '' COMMENT '邮箱',
                `interest`   VARCHAR(80)  NOT NULL DEFAULT '' COMMENT '关注方向',
                `message`    TEXT         NOT NULL COMMENT '需求描述',
                `ip`         VARCHAR(45)  NOT NULL DEFAULT '' COMMENT 'IP地址',
                `user_agent` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '浏览器标识',
                `status`     ENUM('new','read','replied') NOT NULL DEFAULT 'new' COMMENT '状态',
                `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '提交时间',
                INDEX `idx_status` (`status`),
                INDEX `idx_created` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            COMMENT='官网咨询留言表'");
            self::$pdo->exec("CREATE TABLE IF NOT EXISTS `{$productsTable}` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(120) NOT NULL,
                `price` DECIMAL(10,2) NOT NULL DEFAULT 0,
                `description` TEXT NOT NULL,
                `image_path` VARCHAR(255) NOT NULL DEFAULT '',
                `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_product_status` (`status`),
                INDEX `idx_product_created` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            COMMENT='梵燊商城商品表'");
            self::$pdo->exec("CREATE TABLE IF NOT EXISTS `{$settingsTable}` (`setting_key` VARCHAR(80) PRIMARY KEY, `setting_value` LONGTEXT NOT NULL, `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            self::$pdo->exec("CREATE TABLE IF NOT EXISTS `{$ordersTable}` (`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, `out_trade_no` VARCHAR(40) NOT NULL UNIQUE, `amount` INT UNSIGNED NOT NULL, `description` VARCHAR(127) NOT NULL, `status` VARCHAR(20) NOT NULL DEFAULT 'NOTPAY', `transaction_id` VARCHAR(64) NOT NULL DEFAULT '', `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, `paid_at` DATETIME NULL, INDEX `idx_payment_status` (`status`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            self::$pdo->exec("CREATE TABLE IF NOT EXISTS `{$refundsTable}` (`id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, `out_trade_no` VARCHAR(40) NOT NULL, `out_refund_no` VARCHAR(40) NOT NULL UNIQUE, `refund_id` VARCHAR(64) NOT NULL DEFAULT '', `amount` INT UNSIGNED NOT NULL, `reason` VARCHAR(80) NOT NULL DEFAULT '', `status` VARCHAR(24) NOT NULL DEFAULT 'PROCESSING', `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, INDEX `idx_refund_order` (`out_trade_no`)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } else {
            self::$pdo->exec("CREATE TABLE IF NOT EXISTS `{$table}` (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                name       TEXT NOT NULL DEFAULT '',
                company    TEXT NOT NULL DEFAULT '',
                phone      TEXT NOT NULL DEFAULT '',
                email      TEXT NOT NULL DEFAULT '',
                interest   TEXT NOT NULL DEFAULT '',
                message    TEXT NOT NULL DEFAULT '',
                ip         TEXT NOT NULL DEFAULT '',
                user_agent TEXT NOT NULL DEFAULT '',
                status     TEXT NOT NULL DEFAULT 'new',
                created_at TEXT NOT NULL DEFAULT (datetime('now','localtime'))
            )");
            self::$pdo->exec("CREATE TABLE IF NOT EXISTS `{$productsTable}` (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                price NUMERIC NOT NULL DEFAULT 0,
                description TEXT NOT NULL DEFAULT '',
                image_path TEXT NOT NULL DEFAULT '',
                status TEXT NOT NULL DEFAULT 'active',
                created_at TEXT NOT NULL DEFAULT (datetime('now','localtime')),
                updated_at TEXT NOT NULL DEFAULT (datetime('now','localtime'))
            )");
            self::$pdo->exec("CREATE TABLE IF NOT EXISTS `{$settingsTable}` (setting_key TEXT PRIMARY KEY, setting_value TEXT NOT NULL, updated_at TEXT NOT NULL DEFAULT (datetime('now','localtime')))");
            self::$pdo->exec("CREATE TABLE IF NOT EXISTS `{$ordersTable}` (id INTEGER PRIMARY KEY AUTOINCREMENT, out_trade_no TEXT NOT NULL UNIQUE, amount INTEGER NOT NULL, description TEXT NOT NULL, status TEXT NOT NULL DEFAULT 'NOTPAY', transaction_id TEXT NOT NULL DEFAULT '', created_at TEXT NOT NULL DEFAULT (datetime('now','localtime')), paid_at TEXT)");
            self::$pdo->exec("CREATE TABLE IF NOT EXISTS `{$refundsTable}` (id INTEGER PRIMARY KEY AUTOINCREMENT, out_trade_no TEXT NOT NULL, out_refund_no TEXT NOT NULL UNIQUE, refund_id TEXT NOT NULL DEFAULT '', amount INTEGER NOT NULL, reason TEXT NOT NULL DEFAULT '', status TEXT NOT NULL DEFAULT 'PROCESSING', created_at TEXT NOT NULL DEFAULT (datetime('now','localtime')), updated_at TEXT NOT NULL DEFAULT (datetime('now','localtime')))");
        }
    }

    // ========== CRUD ==========

    public static function insert(array $data): bool {
        $pdo = self::conn();
        $cfg = require __DIR__ . '/config.php';
        $table = $cfg['prefix'] . 'contacts';
        $sql = "INSERT INTO `{$table}` (name,company,phone,email,interest,message,ip,user_agent)
                VALUES (?,?,?,?,?,?,?,?)";
        return $pdo->prepare($sql)->execute([
            $data['name']     ?? '',
            $data['company']  ?? '',
            $data['phone']    ?? '',
            $data['email']    ?? '',
            $data['interest'] ?? '',
            $data['message']  ?? '',
            $data['ip']       ?? '',
            $data['user_agent'] ?? '',
        ]);
    }

    public static function select(string $search = '', string $statusFilter = '', int $page = 1, int $perPage = 20): array {
        $pdo = self::conn();
        $cfg = require __DIR__ . '/config.php';
        $table = $cfg['prefix'] . 'contacts';

        $where = [];
        $params = [];
        if ($search !== '') {
            $where[] = "(name LIKE ? OR phone LIKE ? OR company LIKE ?)";
            $s = "%{$search}%";
            $params[] = $s; $params[] = $s; $params[] = $s;
        }
        if ($statusFilter !== '' && in_array($statusFilter, ['new','read','replied'], true)) {
            $where[] = "status = ?";
            $params[] = $statusFilter;
        }
        $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `{$table}` {$whereSQL}");
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $stmt = $pdo->prepare("SELECT * FROM `{$table}` {$whereSQL} ORDER BY created_at DESC LIMIT {$perPage} OFFSET {$offset}");
        $stmt->execute($params);
        return ['total' => $total, 'rows' => $stmt->fetchAll(), 'page' => $page, 'perPage' => $perPage];
    }

    public static function updateStatus(int $id, string $status): bool {
        $pdo = self::conn();
        $cfg = require __DIR__ . '/config.php';
        $table = $cfg['prefix'] . 'contacts';
        return $pdo->prepare("UPDATE `{$table}` SET status = ? WHERE id = ?")->execute([$status, $id]);
    }

    public static function delete(int $id): bool {
        $pdo = self::conn();
        $cfg = require __DIR__ . '/config.php';
        $table = $cfg['prefix'] . 'contacts';
        return $pdo->prepare("DELETE FROM `{$table}` WHERE id = ?")->execute([$id]);
    }

    public static function stats(): array {
        $pdo = self::conn();
        $cfg = require __DIR__ . '/config.php';
        $table = $cfg['prefix'] . 'contacts';
        return $pdo->query("SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN status='new' THEN 1 ELSE 0 END) AS new_count,
            SUM(CASE WHEN status='read' THEN 1 ELSE 0 END) AS read_count,
            SUM(CASE WHEN status='replied' THEN 1 ELSE 0 END) AS replied_count
        FROM `{$table}`")->fetch() ?: ['total'=>0,'new_count'=>0,'read_count'=>0,'replied_count'=>0];
    }

    public static function products(bool $activeOnly = false): array {
        $pdo = self::conn();
        $cfg = require __DIR__ . '/config.php';
        $table = $cfg['prefix'] . 'products';
        $sql = "SELECT * FROM `{$table}`" . ($activeOnly ? " WHERE status = 'active'" : '') . ' ORDER BY created_at DESC, id DESC';
        return $pdo->query($sql)->fetchAll();
    }

    public static function product(int $id): ?array {
        $pdo = self::conn();
        $cfg = require __DIR__ . '/config.php';
        $table = $cfg['prefix'] . 'products';
        $stmt = $pdo->prepare("SELECT * FROM `{$table}` WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function saveProduct(array $data, int $id = 0): bool {
        $pdo = self::conn();
        $cfg = require __DIR__ . '/config.php';
        $table = $cfg['prefix'] . 'products';
        if ($id > 0) {
            $sql = "UPDATE `{$table}` SET name=?, price=?, description=?, image_path=?, status=?, updated_at=CURRENT_TIMESTAMP WHERE id=?";
            return $pdo->prepare($sql)->execute([$data['name'], $data['price'], $data['description'], $data['image_path'], $data['status'], $id]);
        }
        $sql = "INSERT INTO `{$table}` (name,price,description,image_path,status) VALUES (?,?,?,?,?)";
        return $pdo->prepare($sql)->execute([$data['name'], $data['price'], $data['description'], $data['image_path'], $data['status']]);
    }

    public static function deleteProduct(int $id): bool {
        $pdo = self::conn();
        $cfg = require __DIR__ . '/config.php';
        $table = $cfg['prefix'] . 'products';
        return $pdo->prepare("DELETE FROM `{$table}` WHERE id = ?")->execute([$id]);
    }

    public static function setting(string $key, string $default = ''): string {
        $pdo = self::conn(); $cfg = require __DIR__ . '/config.php'; $table = $cfg['prefix'] . 'settings';
        $stmt = $pdo->prepare("SELECT setting_value FROM `{$table}` WHERE setting_key=?"); $stmt->execute([$key]);
        $value = $stmt->fetchColumn(); return $value === false ? $default : (string) $value;
    }

    public static function setSetting(string $key, string $value): void {
        $pdo = self::conn(); $cfg = require __DIR__ . '/config.php'; $table = $cfg['prefix'] . 'settings';
        if (self::$driver === 'mysql') $sql = "INSERT INTO `{$table}` (setting_key,setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)";
        else $sql = "INSERT INTO `{$table}` (setting_key,setting_value) VALUES (?,?) ON CONFLICT(setting_key) DO UPDATE SET setting_value=excluded.setting_value, updated_at=datetime('now','localtime')";
        $pdo->prepare($sql)->execute([$key, $value]);
    }

    public static function createPaymentOrder(string $number, int $amount, string $description): void {
        $pdo=self::conn(); $cfg=require __DIR__.'/config.php'; $table=$cfg['prefix'].'payment_orders';
        $pdo->prepare("INSERT INTO `{$table}` (out_trade_no,amount,description) VALUES (?,?,?)")->execute([$number,$amount,$description]);
    }

    public static function paymentOrder(string $number): ?array {
        $pdo=self::conn(); $cfg=require __DIR__.'/config.php'; $table=$cfg['prefix'].'payment_orders';
        $stmt=$pdo->prepare("SELECT * FROM `{$table}` WHERE out_trade_no=?"); $stmt->execute([$number]); return $stmt->fetch() ?: null;
    }

    public static function updatePaymentOrder(string $number, string $status, string $transactionId=''): void {
        $pdo=self::conn(); $cfg=require __DIR__.'/config.php'; $table=$cfg['prefix'].'payment_orders';
        $paid=$status==='SUCCESS' ? date('Y-m-d H:i:s') : null;
        $pdo->prepare("UPDATE `{$table}` SET status=?,transaction_id=?,paid_at=? WHERE out_trade_no=?")->execute([$status,$transactionId,$paid,$number]);
    }

    public static function paymentOrders(int $limit=50): array {
        $pdo=self::conn(); $cfg=require __DIR__.'/config.php'; $table=$cfg['prefix'].'payment_orders';
        $orders=$pdo->query("SELECT * FROM `{$table}` ORDER BY id DESC LIMIT ".max(1,min(200,$limit)))->fetchAll();
        foreach($orders as &$order) $order['refund']=self::refundForOrder((string)$order['out_trade_no']);
        return $orders;
    }

    public static function createRefund(string $orderNo,string $refundNo,int $amount,string $reason,string $status,string $refundId=''): void {
        $pdo=self::conn();$cfg=require __DIR__.'/config.php';$table=$cfg['prefix'].'payment_refunds';
        $pdo->prepare("INSERT INTO `{$table}` (out_trade_no,out_refund_no,refund_id,amount,reason,status) VALUES (?,?,?,?,?,?)")->execute([$orderNo,$refundNo,$refundId,$amount,$reason,$status]);
    }

    public static function refundForOrder(string $orderNo): ?array {
        $pdo=self::conn();$cfg=require __DIR__.'/config.php';$table=$cfg['prefix'].'payment_refunds';
        $stmt=$pdo->prepare("SELECT * FROM `{$table}` WHERE out_trade_no=? ORDER BY id DESC LIMIT 1");$stmt->execute([$orderNo]);return $stmt->fetch()?:null;
    }

    public static function updateRefund(string $refundNo,string $status,string $refundId=''): void {
        $pdo=self::conn();$cfg=require __DIR__.'/config.php';$table=$cfg['prefix'].'payment_refunds';
        $pdo->prepare("UPDATE `{$table}` SET status=?,refund_id=?,updated_at=CURRENT_TIMESTAMP WHERE out_refund_no=?")->execute([$status,$refundId,$refundNo]);
    }

    /** 测试数据库连接 */
    public static function test(): array {
        try {
            self::conn();
            $engine = self::$driver === 'mysql' ? 'MariaDB/MySQL' : 'SQLite';
            return ['ok' => true, 'message' => "{$engine} 连接成功", 'engine' => self::$driver];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage(), 'engine' => ''];
        }
    }
}
