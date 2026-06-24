<?php

declare(strict_types=1);

namespace App\Modules\Alerts;

use App\Core\Auth;
use App\Core\Database;

final class AlertService
{
    public static function syncForClient(int $clientId): void
    {
        $client = Database::fetchOne(
            'SELECT c.*, cab.id AS cabinet_id FROM clients c JOIN cabinets cab ON cab.id = c.cabinet_id WHERE c.id = ?',
            [$clientId]
        );
        if (!$client) {
            return;
        }

        Database::query(
            "DELETE FROM alerts WHERE client_id = ? AND type IN ('DECLARATION_DUE_SOON', 'MISSING_SOURCE_DATA', 'DRAFT_READY')",
            [$clientId]
        );

        $types = self::applicableTypes($client);
        foreach ($types as $type) {
            $declaration = Database::fetchOne(
                'SELECT * FROM declarations WHERE client_id = ? AND type = ? ORDER BY created_at DESC LIMIT 1',
                [$clientId, $type]
            );

            if ($declaration && $declaration['status'] === 'DRAFT_CALCULATED') {
                self::create(
                    (int) $client['cabinet_id'],
                    $clientId,
                    (int) $declaration['id'],
                    'DRAFT_READY',
                    'info',
                    sprintf('Brouillon %s prêt pour revue — %s', $type, $client['raison_sociale'])
                );
            } elseif (!$declaration) {
                self::create(
                    (int) $client['cabinet_id'],
                    $clientId,
                    null,
                    'MISSING_SOURCE_DATA',
                    'warning',
                    sprintf('Données source manquantes pour %s — %s', $type, $client['raison_sociale'])
                );
            }
        }
    }

    public static function syncAllForCabinet(int $cabinetId): void
    {
        $clients = Database::fetchAll('SELECT id FROM clients WHERE cabinet_id = ? AND is_active = 1', [$cabinetId]);
        foreach ($clients as $c) {
            self::syncForClient((int) $c['id']);
        }
    }

    /** @return string[] */
    private static function applicableTypes(array $client): array
    {
        $types = [];
        if ($client['cnas_regime'] === 'MENSUEL') {
            $types[] = 'CNAS_MENSUELLE';
        } else {
            $types[] = 'CNAS_TRIMESTRIELLE';
        }
        if ($client['secteur'] === 'BTP') {
            $types[] = 'CACOBATPH';
        }
        $types[] = 'G50';
        $types[] = 'G12';
        return $types;
    }

    public static function create(
        int $cabinetId,
        ?int $clientId,
        ?int $declarationId,
        string $type,
        string $severity,
        string $message
    ): void {
        Database::insert(
            'INSERT INTO alerts (cabinet_id, client_id, declaration_id, type, severity, message_fr) VALUES (?, ?, ?, ?, ?, ?)',
            [$cabinetId, $clientId, $declarationId, $type, $severity, $message]
        );
    }

    public static function forDashboard(int $cabinetId): array
    {
        return Database::fetchAll(
            'SELECT a.*, c.raison_sociale FROM alerts a
             LEFT JOIN clients c ON c.id = a.client_id
             WHERE a.cabinet_id = ? AND a.is_read = 0
             ORDER BY FIELD(a.severity, \'critical\', \'warning\', \'info\'), a.created_at DESC LIMIT 20',
            [$cabinetId]
        );
    }

    public static function markRead(int $id): void
    {
        Database::query('UPDATE alerts SET is_read = 1 WHERE id = ? AND cabinet_id = ?', [$id, Auth::cabinetId()]);
    }
}
