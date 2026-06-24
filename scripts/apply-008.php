<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

use App\Core\Database;

$pdo = Database::connection();

function indexExists(PDO $pdo, string $table, string $index): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?'
    );
    $stmt->execute([$table, $index]);
    return (int) $stmt->fetchColumn() > 0;
}

$indexes = [
    ['clients', 'idx_clients_cabinet_name', 'CREATE INDEX idx_clients_cabinet_name ON clients (cabinet_id, raison_sociale)'],
    ['clients', 'idx_clients_cabinet_secteur', 'CREATE INDEX idx_clients_cabinet_secteur ON clients (cabinet_id, secteur)'],
    ['clients', 'idx_clients_cabinet_wilaya', 'CREATE INDEX idx_clients_cabinet_wilaya ON clients (cabinet_id, wilaya)'],
    ['alerts', 'idx_alerts_client_read', 'CREATE INDEX idx_alerts_client_read ON alerts (cabinet_id, client_id, is_read, severity)'],
    ['declarations', 'idx_decl_client_status', 'CREATE INDEX idx_decl_client_status ON declarations (client_id, status)'],
];

foreach ($indexes as [$table, $name, $sql]) {
    if (!indexExists($pdo, $table, $name)) {
        $pdo->exec($sql);
        echo "Created {$name}\n";
    }
}

echo "Migration 008 done.\n";
