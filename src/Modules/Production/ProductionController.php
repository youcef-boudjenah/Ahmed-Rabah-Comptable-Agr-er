<?php

declare(strict_types=1);

namespace App\Modules\Production;

use App\Core\Auth;
use App\Core\View;
use App\Modules\Automation\AutomationPipeline;
use App\Modules\Automation\BatchService;
use App\Modules\Automation\DeadlineService;
use App\Modules\Automation\PdfGenerationService;
use App\Modules\Declarations\DeclarationRepository;
use App\Modules\Admin\SettingsService;
use App\Modules\Relances\RelanceExportService;

final class ProductionController
{
    public static function index(): void
    {
        Auth::requireAuth();
        $year = isset($_GET['year']) ? (int) $_GET['year'] : (int) date('Y');
        if (isset($_GET['month'])) {
            $month = (int) $_GET['month'];
        } else {
            $currentMonth = (int) date('n');
            $month = $currentMonth > 1 ? $currentMonth - 1 : 12;
            if ($currentMonth === 1) {
                $year--;
            }
        }

        $data = ProductionService::monthly(Auth::cabinetId(), $year, $month, [
            'status' => $_GET['status'] ?? '',
            'type' => $_GET['type'] ?? '',
            'q' => $_GET['q'] ?? '',
        ]);

        $cabinetId = Auth::cabinetId();
        $showAutomation = isset($_GET['panel']) && $_GET['panel'] === 'automation';
        $highlightRun = null;
        if (isset($_GET['run'])) {
            $highlightRun = AutomationPipeline::findRun((int) $_GET['run'], $cabinetId);
            $showAutomation = $showAutomation || $highlightRun !== null;
        }

        View::render('production/index', [
            'title' => 'Production mensuelle',
            'production' => $data,
            'showAutomation' => $showAutomation,
            'highlightRun' => $highlightRun,
            'automationPreview' => AutomationPipeline::getPreview($cabinetId),
            'automationStats' => DeadlineService::cabinetStats($cabinetId),
            'automationRuns' => AutomationPipeline::recentRuns($cabinetId, 8),
            'hasOpenRouter' => (require ROOT_PATH . '/config/app.php')['openrouter_api_key'] !== '',
        ]);
    }

    public static function processMonth(): void
    {
        Auth::requireAuth();
        $cabinetId = Auth::cabinetId();

        $recalc = BatchService::recalculateCabinet($cabinetId);
        $pipeline = AutomationPipeline::runControlled($cabinetId, Auth::id(), [
            'recalc' => true,
            'tasks' => true,
            'pdfs' => true,
        ], 'production_mensuelle');

        View::flashT('success', 'flash.production_done', [
            'period' => $_POST['period_label'] ?? '',
            'payroll' => $recalc['payroll'],
            'sales' => $recalc['sales'],
        ]);
        View::redirect('/production?' . http_build_query([
            'year' => (int) ($_POST['year'] ?? date('Y')),
            'month' => (int) ($_POST['month'] ?? date('n')),
            'run' => $pipeline['run_id'] ?? null,
        ]));
    }

    public static function approveDrafts(): void
    {
        Auth::requireAuth();
        if (!Auth::canApprove()) {
            View::flashT('error', 'flash.production_approve_denied');
            View::redirect('/production');
        }

        $year = (int) ($_POST['year'] ?? date('Y'));
        $month = (int) ($_POST['month'] ?? date('n'));
        $data = ProductionService::monthly(Auth::cabinetId(), $year, $month);
        $ids = array_values(array_filter(array_map(
            fn ($r) => $r['declaration_id'] ?? null,
            array_filter($data['rows'], fn ($r) => $r['status'] === 'draft_ready' && !empty($r['declaration_id']))
        )));

        $result = DeclarationRepository::approveBatch($ids);
        if (SettingsService::bool('auto_pdf_on_approve')) {
            foreach ($ids as $id) {
                PdfGenerationService::generate((int) $id);
            }
        }

        View::flashT('success', 'flash.production_bulk_approved', [
            'approved' => $result['approved'],
            'skipped' => $result['skipped'],
        ]);
        View::redirect('/production?' . http_build_query(['year' => $year, 'month' => $month, 'status' => 'approved']));
    }

    public static function exportRelances(): void
    {
        Auth::requireAuth();
        $year = (int) ($_GET['year'] ?? date('Y'));
        $month = (int) ($_GET['month'] ?? date('n'));
        $data = ProductionService::monthly(Auth::cabinetId(), $year, $month);
        $rows = array_filter(
            $data['rows'],
            fn ($r) => in_array($r['status'], ['missing_data', 'overdue'], true)
        );
        RelanceExportService::download(
            array_values($rows),
            sprintf('relances_%d_%02d.csv', $year, $month)
        );
    }
}
