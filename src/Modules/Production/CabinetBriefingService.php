<?php

declare(strict_types=1);

namespace App\Modules\Production;

use App\Core\Database;
use App\Modules\Automation\DeadlineService;

final class CabinetBriefingService
{
    /** @return array<string, mixed> */
    public static function forDashboard(int $cabinetId): array
    {
        $year = (int) date('Y');
        $currentMonth = (int) date('n');
        $prodMonth = $currentMonth > 1 ? $currentMonth - 1 : 12;
        if ($currentMonth === 1) {
            $year--;
        }

        $production = ProductionService::monthly($cabinetId, $year, $prodMonth);
        $stats = $production['stats'];
        $done = $stats['submitted'];
        $total = max(1, $stats['total_obligations']);
        $progress = (int) round(($done / $total) * 100);

        $payrollCount = (int) Database::fetchOne(
            'SELECT COUNT(*) AS c FROM payroll_entries pe JOIN clients c ON c.id = pe.client_id
             WHERE c.cabinet_id = ? AND pe.period_year = ? AND pe.period_month = ?',
            [$cabinetId, $year, $prodMonth]
        )['c'];

        $actions = [];
        if ($payrollCount === 0 && $stats['missing_data'] > 0) {
            $actions[] = [
                'label' => 'Importer la paie du mois (' . $production['month_label'] . ')',
                'url' => '/entries/payroll/import?year=' . $year . '&month=' . $prodMonth . '&redirect=/production',
                'priority' => 'high',
            ];
        }
        if ($stats['missing_data'] > 0) {
            $actions[] = [
                'label' => $stats['missing_data'] . ' client(s) sans données — relancer',
                'url' => '/production?year=' . $year . '&month=' . $prodMonth . '&status=missing_data',
                'priority' => 'high',
            ];
        }
        if ($stats['draft_ready'] > 0) {
            $actions[] = [
                'label' => $stats['draft_ready'] . ' brouillon(s) à approuver',
                'url' => '/production?year=' . $year . '&month=' . $prodMonth . '&status=draft_ready',
                'priority' => 'normal',
            ];
        }
        if ($stats['approved'] > 0) {
            $actions[] = [
                'label' => $stats['approved'] . ' déclaration(s) approuvées — à déposer',
                'url' => '/production?year=' . $year . '&month=' . $prodMonth . '&status=approved',
                'priority' => 'normal',
            ];
        }
        if ($stats['overdue'] > 0) {
            $actions[] = [
                'label' => $stats['overdue'] . ' en retard — traiter en urgence',
                'url' => '/production?year=' . $year . '&month=' . $prodMonth . '&status=overdue',
                'priority' => 'critical',
            ];
        }
        if ($actions === []) {
            $actions[] = [
                'label' => 'Production à jour — consulter le détail',
                'url' => '/production?year=' . $year . '&month=' . $prodMonth,
                'priority' => 'low',
            ];
        }

        $deadlineStats = DeadlineService::cabinetStats($cabinetId);

        return [
            'production' => $production,
            'progress' => $progress,
            'payroll_entries_month' => $payrollCount,
            'actions' => array_slice($actions, 0, 5),
            'deadline_stats' => $deadlineStats,
            'prod_year' => $year,
            'prod_month' => $prodMonth,
        ];
    }
}
