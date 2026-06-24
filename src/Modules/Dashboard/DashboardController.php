<?php

declare(strict_types=1);

namespace App\Modules\Dashboard;

use App\Core\Auth;
use App\Core\View;
use App\Modules\Alerts\AlertService;
use App\Modules\Automation\DeadlineService;
use App\Modules\Declarations\DeclarationRepository;
use App\Modules\Documents\DocumentController;
use App\Modules\Clients\ClientRepository;
use App\Modules\Automation\AutomationPipeline;
use App\Modules\Automation\BatchService;
use App\Modules\Relances\RelanceService;
use App\Modules\Production\CabinetBriefingService;
use App\Modules\Tasks\TaskRepository;

final class DashboardController
{
    public static function index(): void
    {
        Auth::requireAuth();
        DocumentController::processQueueOnce();
        AlertService::syncAllForCabinet(Auth::cabinetId());

        $stats = DeclarationRepository::stats();
        $deadlineStats = DeadlineService::cabinetStats(Auth::cabinetId());
        $clientsStatus = DeadlineService::topClientsNeedingAttention(Auth::cabinetId(), 10);
        $alerts = AlertService::forDashboard(Auth::cabinetId());
        $recentDrafts = DeclarationRepository::allForCabinet('DRAFT_CALCULATED');
        $recentDrafts = array_slice($recentDrafts, 0, 5);
        $tasks = TaskRepository::openForCabinet(Auth::cabinetId());

        $relances = RelanceService::pendingForCabinet(Auth::cabinetId());

        $highlightRun = null;
        if (isset($_GET['run'])) {
            $highlightRun = AutomationPipeline::findRun((int) $_GET['run'], Auth::cabinetId());
        }
        $automationPreview = AutomationPipeline::getPreview(Auth::cabinetId());
        $briefing = CabinetBriefingService::forDashboard(Auth::cabinetId());

        View::render('dashboard/index', [
            'title' => 'Tableau de bord',
            'stats' => $stats,
            'deadlineStats' => $deadlineStats,
            'briefing' => $briefing,
            'clientsStatus' => $clientsStatus,
            'alerts' => $alerts,
            'recentDrafts' => $recentDrafts,
            'tasks' => $tasks,
            'relances' => $relances,
            'highlightRun' => $highlightRun,
            'automationPreview' => $automationPreview,
        ]);
    }
}
