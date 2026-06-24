<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

use App\Core\Database;

$pdo = Database::connection();

$pdo->exec("CREATE TABLE IF NOT EXISTS cabinet_tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cabinet_id INT NOT NULL,
    client_id INT NULL,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    due_date DATE NULL,
    priority ENUM('low','normal','high') NOT NULL DEFAULT 'normal',
    is_done TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    FOREIGN KEY (cabinet_id) REFERENCES cabinets(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_cabinet_done (cabinet_id, is_done, due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

echo "Migration 004 done.\n";
