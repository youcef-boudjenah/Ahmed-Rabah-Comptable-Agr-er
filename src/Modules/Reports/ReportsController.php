<?php

declare(strict_types=1);

namespace App\Modules\Reports;

use App\Core\Auth;
use App\Core\Database;
use App\Core\View;

final class ReportsController
{
    public static function index(): void
    {
        Auth::requireAuth();
        View::render('reports/index', [
            'title' => 'Rapports & analytique',
            'data' => ReportsService::cabinetAnalytics(),
        ]);
    }

    public static function audit(): void
    {
        Auth::requireAuth();
        $logs = Database::fetchAll(
            'SELECT a.*, u.name AS user_name FROM audit_logs a
             LEFT JOIN users u ON u.id = a.user_id
             WHERE a.cabinet_id = ? ORDER BY a.created_at DESC LIMIT 100',
            [Auth::cabinetId()]
        );
        View::render('reports/audit', [
            'title' => 'Journal d\'audit',
            'logs' => $logs,
        ]);
    }
}
