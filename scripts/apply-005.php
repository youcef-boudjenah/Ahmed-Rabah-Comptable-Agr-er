<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

use App\Core\Database;

$pdo = Database::connection();

function columnExists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $stmt->execute([$table, $column]);
    return (int) $stmt->fetchColumn() > 0;
}

if (!columnExists($pdo, 'declarations', 'generated_pdf_path')) {
    $pdo->exec('ALTER TABLE declarations ADD COLUMN generated_pdf_path VARCHAR(500) NULL');
    echo "Added declarations.generated_pdf_path\n";
}
if (!columnExists($pdo, 'declarations', 'ai_review_json')) {
    $pdo->exec('ALTER TABLE declarations ADD COLUMN ai_review_json JSON NULL');
    echo "Added declarations.ai_review_json\n";
}

$pdo->exec("CREATE TABLE IF NOT EXISTS automation_runs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cabinet_id INT NOT NULL,
    user_id INT NULL,
    run_type VARCHAR(50) NOT NULL,
    result_json JSON NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cabinet_id) REFERENCES cabinets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

echo "Migration 005 done.\n";
