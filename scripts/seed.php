<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

use App\Core\Database;
use App\Core\Encryption;

$pdo = Database::connection();

$cabinet = Database::fetchOne('SELECT id FROM cabinets LIMIT 1');
if (!$cabinet) {
    $cabinetId = Database::insert('INSERT INTO cabinets (name) VALUES (?)', ['Cabinet Ahmed Rabah']);
    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    Database::insert(
        'INSERT INTO users (cabinet_id, name, email, password_hash, role) VALUES (?, ?, ?, ?, ?)',
        [$cabinetId, 'Administrateur', 'admin@cabinet.dz', $hash, 'admin']
    );
    echo "Created cabinet + admin (admin@cabinet.dz / admin123)\n";
} else {
    $cabinetId = (int) $cabinet['id'];
}

$rateCount = (int) Database::fetchOne('SELECT COUNT(*) AS c FROM cotisation_rate_tables')['c'];
if ($rateCount === 0) {
    $rates = [
        ['R22', 'Régime général', 34.50, 'BTP', 'CNAS_MENSUELLE'],
        ['R98', 'FNPOS régime général', 0.50, 'BTP', 'CNAS_MENSUELLE'],
        ['R38', 'OPREBAT', 0.13, 'BTP', 'CNAS_MENSUELLE'],
        ['R22', 'Régime général', 34.50, 'BTP', 'CNAS_TRIMESTRIELLE'],
        ['R98', 'FNPOS régime général', 0.50, 'BTP', 'CNAS_TRIMESTRIELLE'],
        ['R38', 'OPREBAT', 0.13, 'BTP', 'CNAS_TRIMESTRIELLE'],
        ['CP', 'Congés payés', 12.21, 'BTP', 'CACOBATPH'],
        ['CI', 'Chômage intempéries', 0.75, 'BTP', 'CACOBATPH'],
        ['IFU_BIENS', 'IFU production/vente biens', 5.00, null, 'G12'],
        ['IFU_SERVICES', 'IFU services', 12.00, null, 'G12'],
        ['IFU_AUTO', 'IFU auto-entrepreneur', 0.50, 'AUTO_ENTREPRENEUR', 'G12'],
    ];
    foreach ($rates as [$code, $label, $taux, $secteur, $type]) {
        Database::insert(
            'INSERT INTO cotisation_rate_tables (code, label, taux, secteur, declaration_type, valid_from) VALUES (?, ?, ?, ?, ?, ?)',
            [$code, $label, $taux, $secteur, $type, '2025-01-01']
        );
    }
    echo "Seeded cotisation rates.\n";
}

$deadlineCount = (int) Database::fetchOne('SELECT COUNT(*) AS c FROM deadline_rules')['c'];
if ($deadlineCount === 0) {
    $deadlines = [
        ['CNAS_MENSUELLE', 'monthly', 20, null, 'CNAS mensuelle — 20 jours après la période'],
        ['CNAS_TRIMESTRIELLE', 'quarterly', 20, null, 'CNAS trimestrielle — 20 jours après le trimestre'],
        ['CACOBATPH', 'quarterly', 20, null, 'CACOBATPH trimestrielle — 20 jours après le trimestre'],
        ['G50', 'monthly', 20, null, 'G50 — 20 jours après la période'],
        ['G12', 'annual', 30, 6, 'G12 prévisionnelle — 30 juin'],
        ['G12_BIS', 'annual', 20, 1, 'G12 Bis définitive — 20 janvier N+1'],
    ];
    foreach ($deadlines as [$type, $freq, $day, $month, $label]) {
        Database::insert(
            'INSERT INTO deadline_rules (declaration_type, frequency, due_day, due_month, label_fr) VALUES (?, ?, ?, ?, ?)',
            [$type, $freq, $day, $month, $label]
        );
    }
    echo "Seeded deadline rules.\n";
}

$clientCount = (int) Database::fetchOne('SELECT COUNT(*) AS c FROM clients WHERE cabinet_id = ?', [$cabinetId])['c'];
if ($clientCount === 0) {
    Database::insert(
        'INSERT INTO clients (cabinet_id, raison_sociale, nif_encrypted, numero_cotisant, secteur, regime_fiscal, cnas_regime, wilaya, adresse, activite) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [
            $cabinetId,
            'BOUALAM MOHAMED',
            Encryption::encrypt('2292889130'),
            '2292889130',
            'BTP',
            'MENSUEL',
            'MENSUEL',
            'Sidi Bel Abbès',
            'Ben Badis, Sidi Bel Abbès',
            'Entreprise travaux bâtiment',
        ]
    );
    Database::insert(
        'INSERT INTO clients (cabinet_id, raison_sociale, nif_encrypted, numero_cotisant, secteur, regime_fiscal, cnas_regime, wilaya, adresse, activite) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [
            $cabinetId,
            'SEKRANE BOUZIANE',
            Encryption::encrypt('1976310200025830'),
            null,
            'SERVICES',
            'MENSUEL',
            'MENSUEL',
            'Sidi Bel Abbès',
            'Cité Derrar Essalama N°48',
            'Services transport & groupe commercial Douaa-Business',
        ]
    );
    echo "Seeded demo clients.\n";
}

$autoCount = (int) Database::fetchOne('SELECT COUNT(*) AS c FROM automation_rules')['c'];
if ($autoCount === 0) {
    Database::insert(
        'INSERT INTO automation_rules (event_type, action_json) VALUES (?, ?)',
        ['PAYROLL_ENTRY_SAVED', json_encode(['action' => 'calculate_payroll_declarations'])]
    );
    Database::insert(
        'INSERT INTO automation_rules (event_type, action_json) VALUES (?, ?)',
        ['SALES_ENTRY_SAVED', json_encode(['action' => 'calculate_sales_declarations'])]
    );
    echo "Seeded automation rules.\n";
}

echo "Seed complete.\n";
