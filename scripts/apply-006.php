<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

use App\Core\Database;

$pdo = Database::connection();

$pdo->exec("CREATE TABLE IF NOT EXISTS cabinet_settings (
    cabinet_id INT PRIMARY KEY,
    settings_json JSON NOT NULL,
    updated_at DATETIME NULL,
    FOREIGN KEY (cabinet_id) REFERENCES cabinets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

echo "Migration 006 done.\n";
