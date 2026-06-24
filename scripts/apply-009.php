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

$columns = [
    'contact_email' => 'VARCHAR(255) NULL',
    'contact_phone' => 'VARCHAR(30) NULL',
    'contact_name' => 'VARCHAR(255) NULL',
];

foreach ($columns as $name => $def) {
    if (!columnExists($pdo, 'clients', $name)) {
        $after = $name === 'contact_email' ? 'adresse' : ($name === 'contact_phone' ? 'contact_email' : 'contact_phone');
        $pdo->exec("ALTER TABLE clients ADD COLUMN {$name} {$def} AFTER {$after}");
        echo "Added clients.{$name}\n";
    }
}

echo "Migration 009 done.\n";
