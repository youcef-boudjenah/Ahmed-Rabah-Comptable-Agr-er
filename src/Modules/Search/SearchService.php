<?php

declare(strict_types=1);

namespace App\Modules\Search;

use App\Core\Auth;
use App\Core\Database;

final class SearchService
{
    /** @return array<string, array<int, array<string, mixed>>> */
    public static function global(string $query): array
    {
        if (strlen($query) < 2) {
            return ['clients' => [], 'declarations' => [], 'documents' => []];
        }
        $cabinetId = Auth::cabinetId();
        $like = '%' . $query . '%';

        $clients = Database::fetchAll(
            'SELECT id, raison_sociale, secteur, wilaya, numero_cotisant FROM clients
             WHERE cabinet_id = ? AND is_active = 1 AND (raison_sociale LIKE ? OR wilaya LIKE ? OR numero_cotisant LIKE ? OR activite LIKE ?)
             LIMIT 10',
            [$cabinetId, $like, $like, $like, $like]
        );

        $declarations = Database::fetchAll(
            'SELECT d.id, d.type, d.status, d.period_year, d.period_month, c.raison_sociale
             FROM declarations d JOIN clients c ON c.id = d.client_id
             WHERE c.cabinet_id = ? AND (d.type LIKE ? OR c.raison_sociale LIKE ?)
             ORDER BY d.created_at DESC LIMIT 15',
            [$cabinetId, $like, $like]
        );

        $documents = Database::fetchAll(
            'SELECT d.id, d.title, d.original_name, d.category, d.ged_status, c.raison_sociale
             FROM documents d LEFT JOIN clients c ON c.id = d.client_id
             WHERE d.cabinet_id = ? AND (d.original_name LIKE ? OR d.title LIKE ? OR d.tags LIKE ? OR d.notes LIKE ?)
             ORDER BY d.created_at DESC LIMIT 15',
            [$cabinetId, $like, $like, $like, $like]
        );

        return compact('clients', 'declarations', 'documents');
    }
}
