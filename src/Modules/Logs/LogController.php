<?php

declare(strict_types=1);

namespace App\Modules\Logs;

use App\Core\Auth;
use App\Core\View;

final class LogController
{
    public static function index(): void
    {
        Auth::requireAuth();
        $cabinetId = Auth::cabinetId();
        $tab = $_GET['tab'] ?? 'audit';
        if (!in_array($tab, ['audit', 'automation', 'jobs'], true)) {
            $tab = 'audit';
        }

        View::render('logs/index', [
            'title' => 'Journaux d\'activité',
            'tab' => $tab,
            'counts' => ActivityLogService::counts($cabinetId),
            'auditLogs' => $tab === 'audit' ? ActivityLogService::auditLogs($cabinetId) : [],
            'automationRuns' => $tab === 'automation' ? ActivityLogService::automationRuns($cabinetId) : [],
            'jobs' => $tab === 'jobs' ? ActivityLogService::jobQueue() : [],
        ]);
    }
}
