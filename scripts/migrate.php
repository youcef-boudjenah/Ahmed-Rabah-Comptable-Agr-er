<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

use App\Core\Database;

$config = require ROOT_PATH . '/config/database.php';

try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%d;charset=%s', $config['host'], $config['port'], $config['charset']),
        $config['username'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '', $config['database']) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    echo "Database ensured.\n";
} catch (PDOException $e) {
    echo "Warning: " . $e->getMessage() . "\n";
}

Database::execFile(ROOT_PATH . '/migrations/001_initial.sql');
echo "Migrations applied.\n";
