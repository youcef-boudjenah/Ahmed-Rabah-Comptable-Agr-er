<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

use App\Core\Database;

$pdo = Database::connection();

function columnExists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $stmt->execute([$table, $column]);
    return (int) $stmt->fetchColumn() > 0;
}

$alters = [
    'clients' => ['folder_path' => 'VARCHAR(500) NULL'],
    'documents' => [
        'category' => "VARCHAR(50) NULL DEFAULT 'divers'",
        'subfolder' => 'VARCHAR(100) NULL',
        'title' => 'VARCHAR(255) NULL',
        'notes' => 'TEXT NULL',
        'file_size' => 'INT NULL',
        'ged_status' => "ENUM('a_traiter','en_cours','traite','archive') NOT NULL DEFAULT 'a_traiter'",
        'tags' => 'VARCHAR(500) NULL',
    ],
];

foreach ($alters as $table => $columns) {
    foreach ($columns as $col => $def) {
        if (!columnExists($pdo, $table, $col)) {
            $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$col` $def");
            echo "Added $table.$col\n";
        }
    }
}

Database::execFile(ROOT_PATH . '/migrations/002_ged_chat.sql');
echo "GED + Chat tables ready.\n";

$clients = Database::fetchAll('SELECT id FROM clients');
foreach ($clients as $c) {
    \App\Modules\Documents\ClientFolderService::ensure((int) $c['id']);
    echo "Folder client #{$c['id']}\n";
}

echo "Done.\n";
