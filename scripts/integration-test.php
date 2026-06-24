<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

use App\Core\Database;
use App\Modules\Entries\EntryRepository;

$client = Database::fetchOne("SELECT id FROM clients WHERE raison_sociale = 'BOUALAM MOHAMED'");
if (!$client) {
    echo "Client not found\n";
    exit(1);
}

$id = EntryRepository::savePayroll((int) $client['id'], [
    'period_year' => 2026,
    'period_month' => 1,
    'masse_salariale' => 173781.80,
    'effectif' => 7,
    'entrees' => 1,
    'sorties' => 1,
    'nombre_assurees' => 22,
    'source' => 'manual',
    'notes' => 'Integration test',
]);

$decl = Database::fetchOne(
    "SELECT computed_fields FROM declarations WHERE client_id = ? AND type = 'CNAS_MENSUELLE' ORDER BY id DESC LIMIT 1",
    [$client['id']]
);
$cf = json_decode($decl['computed_fields'], true);
$total = $cf['total'] ?? 0;
echo "Payroll entry #$id created.\n";
echo "CNAS total: $total (expected 61049.55)\n";
echo abs($total - 61049.55) < 0.02 ? "INTEGRATION OK\n" : "INTEGRATION FAIL\n";
