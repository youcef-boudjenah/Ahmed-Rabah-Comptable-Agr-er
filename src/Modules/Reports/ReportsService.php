<?php

declare(strict_types=1);

namespace App\Modules\Reports;

use App\Core\Auth;
use App\Core\Database;

final class ReportsService
{
    /** @return array<string, mixed> */
    public static function cabinetAnalytics(): array
    {
        $cabinetId = Auth::cabinetId();
        $year = (int) date('Y');

        $byType = Database::fetchAll(
            "SELECT d.type, COUNT(*) AS count, SUM(JSON_EXTRACT(d.computed_fields, '$.total')) AS total
             FROM declarations d JOIN clients c ON c.id = d.client_id
             WHERE c.cabinet_id = ? AND d.period_year = ?
             GROUP BY d.type ORDER BY total DESC",
            [$cabinetId, $year]
        );

        $byMonth = Database::fetchAll(
            "SELECT d.period_month AS m, SUM(JSON_EXTRACT(d.computed_fields, '$.total')) AS total
             FROM declarations d JOIN clients c ON c.id = d.client_id
             WHERE c.cabinet_id = ? AND d.period_year = ? AND d.period_month IS NOT NULL
             GROUP BY d.period_month ORDER BY m",
            [$cabinetId, $year]
        );

        $topClients = Database::fetchAll(
            "SELECT c.raison_sociale, c.id, SUM(JSON_EXTRACT(d.computed_fields, '$.total')) AS total
             FROM declarations d JOIN clients c ON c.id = d.client_id
             WHERE c.cabinet_id = ? AND d.period_year = ?
             GROUP BY c.id ORDER BY total DESC LIMIT 5",
            [$cabinetId, $year]
        );

        $gedStats = Database::fetchOne(
            "SELECT
                SUM(CASE WHEN ged_status = 'a_traiter' THEN 1 ELSE 0 END) AS a_traiter,
                SUM(CASE WHEN ged_status = 'traite' THEN 1 ELSE 0 END) AS traite,
                COUNT(*) AS total
             FROM documents WHERE cabinet_id = ?",
            [$cabinetId]
        );

        $statusBreakdown = Database::fetchAll(
            "SELECT status, COUNT(*) AS c FROM declarations d
             JOIN clients c ON c.id = d.client_id WHERE c.cabinet_id = ? GROUP BY status",
            [$cabinetId]
        );

        return [
            'year' => $year,
            'by_type' => $byType,
            'by_month' => $byMonth,
            'top_clients' => $topClients,
            'ged' => $gedStats,
            'status_breakdown' => $statusBreakdown,
            'total_ytd' => array_sum(array_column($byType, 'total')),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public static function clientActivity(int $clientId, int $limit = 25): array
    {
        return Database::fetchAll(
            'SELECT a.*, u.name AS user_name FROM audit_logs a
             LEFT JOIN users u ON u.id = a.user_id
             WHERE a.entity = ? AND a.entity_id = ?
             OR (a.entity IN (\'payroll_entries\',\'sales_entries\',\'declarations\',\'documents\') AND a.meta LIKE ?)
             ORDER BY a.created_at DESC LIMIT ' . (int) $limit,
            ['clients', $clientId, '%"client_id":' . $clientId . '%']
        );
    }
}
