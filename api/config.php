<?php
declare(strict_types=1);

require_once __DIR__ . '/env.php';

/**
 * 数据库配置 — 生产环境从项目根目录 .env 读取
 * 
 * 宝塔部署步骤：
 * 1. 宝塔面板 → 数据库 → 添加数据库（记下库名/用户名/密码）
 * 2. 在项目根目录 .env 中填写数据库连接信息
 * 3. 首次访问网站时程序会自动创建所需数据表
 */

return [
    // ========== 数据库连接（宝塔部署时修改这几行） ==========
    'host'     => envValue('DB_HOST', '127.0.0.1'),
    'port'     => (int) envValue('DB_PORT', '3306'),
    'dbname'   => envValue('DB_NAME', 'fanshen'),
    'username' => envValue('DB_USER', 'fanshen'),
    'password' => envValue('DB_PASS', ''),
    'charset'  => 'utf8mb4',
    'prefix'   => 'fs_',              // 表前缀

    // ========== 运行模式 ==========
    // debug=true 时 MariaDB 不可用会降级到 SQLite；生产环境应保持 false。
    'debug' => envBool('APP_DEBUG', false),
];
