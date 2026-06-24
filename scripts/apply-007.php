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

if (!columnExists($pdo, 'documents', 'declaration_id')) {
    $pdo->exec('ALTER TABLE documents ADD COLUMN declaration_id INT NULL AFTER client_id');
    $pdo->exec('ALTER TABLE documents ADD INDEX idx_doc_declaration (declaration_id)');
    echo "Added documents.declaration_id\n";
}

echo "Migration 007 done.\n";
