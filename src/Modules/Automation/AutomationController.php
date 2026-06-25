<?php

declare(strict_types=1);

namespace App\Modules\Automation;

use App\Core\Auth;
use App\Core\View;
use App\Modules\AI\AiAutomationService;

final class AutomationController
{
    public static function index(): void
    {
        Auth::requireAuth();
        $query = $_SERVER['QUERY_STRING'] ?? '';
        $target = '/production' . ($query !== '' ? '?' . $query : '');
        if (!str_contains($query, 'panel=automation')) {
            $target .= ($query !== '' ? '&' : '?') . 'panel=automation';
        }
        View::redirect($target);
    }

    public static function showRun(int $id): void
    {
        Auth::requireAuth();
        $run = AutomationPipeline::findRun($id, Auth::cabinetId());
        if (!$run) {
            View::redirect('/production?panel=automation');
        }
        View::render('automation/run', [
            'title' => 'Rapport traitement #' . $id,
            'run' => $run,
        ]);
    }

    /** @return array<string, bool> */
    private static function stepsFromPost(): array
    {
        return [
            'recalc' => isset($_POST['step_recalc']),
            'tasks' => isset($_POST['step_tasks']),
            'pdfs' => isset($_POST['step_pdfs']),
            'ocr' => isset($_POST['step_ocr']),
            'ai_review' => isset($_POST['step_ai_review']),
            'ai_classify' => isset($_POST['step_ai_classify']),
        ];
    }

    public static function runCustom(): void
    {
        Auth::requireAuth();
        $steps = self::stepsFromPost();
        if (!array_filter($steps)) {
            $steps = ['recalc' => true, 'tasks' => true, 'pdfs' => true, 'ocr' => true,
                'ai_review' => isset($_POST['with_ai']), 'ai_classify' => isset($_POST['with_ai'])];
        }
        $result = AutomationPipeline::runControlled(Auth::cabinetId(), Auth::id(), $steps, 'custom');
        self::redirectWithReport($result, '/production?panel=automation');
    }

    public static function batchRecalculate(): void
    {
        Auth::requireAuth();
        $result = AutomationPipeline::runControlled(
            Auth::cabinetId(),
            Auth::id(),
            ['recalc' => true],
            'recalc_only'
        );
        self::redirectWithReport($result, '/production?panel=automation');
    }

    public static function runFull(): void
    {
        Auth::requireAuth();
        $hasKey = (require ROOT_PATH . '/config/app.php')['openrouter_api_key'] !== '';
        $withAi = $hasKey && isset($_POST['with_ai']);
        $result = AutomationPipeline::runFull(Auth::cabinetId(), Auth::id(), $withAi);
        self::redirectWithReport($result, '/production?panel=automation');
    }

    /** Traitement rapide depuis le tableau de bord — sans IA, retour sur / */
    public static function runFromDashboard(): void
    {
        Auth::requireAuth();
        $result = AutomationPipeline::runControlled(
            Auth::cabinetId(),
            Auth::id(),
            ['recalc' => true, 'tasks' => true, 'pdfs' => true, 'ocr' => true],
            'dashboard_quick'
        );
        self::redirectWithReport($result, '/');
    }

    public static function generatePdfs(): void
    {
        Auth::requireAuth();
        $result = AutomationPipeline::runControlled(
            Auth::cabinetId(),
            Auth::id(),
            ['pdfs' => true],
            'pdfs_only'
        );
        self::redirectWithReport($result, '/production?panel=automation');
    }

    public static function classifyDocuments(): void
    {
        Auth::requireAuth();
        $result = AutomationPipeline::runControlled(
            Auth::cabinetId(),
            Auth::id(),
            ['ai_classify' => true],
            'classify_only'
        );
        self::redirectWithReport($result, '/production?panel=automation');
    }

    /** @param array{run_id: int, steps: list<array<string, mixed>>, summary: array<string, mixed>, duration_ms: int} $result */
    public static function redirectWithReport(array $result, string $returnPath): never
    {
        View::flash('success', self::humanSummary($result));
        $sep = str_contains($returnPath, '?') ? '&' : '?';
        View::redirect($returnPath . $sep . 'run=' . $result['run_id']);
    }

    /** @param array{run_id: int, steps: list<array<string, mixed>>, duration_ms: int} $result */
    public static function humanSummary(array $result): string
    {
        $lines = [];
        foreach ($result['steps'] ?? [] as $step) {
            $status = $step['status'] ?? 'ok';
            $msg = (string) ($step['message'] ?? '');
            if ($status === 'error') {
                $lines[] = 'Erreur : ' . ($step['label'] ?? '') . ' — ' . $msg;
            } elseif ($status !== 'skipped') {
                $lines[] = $msg;
            }
        }
        $sec = round(($result['duration_ms'] ?? 0) / 1000, 1);
        $head = "Terminé en {$sec} s.";
        return $head . ($lines ? ' ' . implode(' · ', $lines) : '');
    }

    public static function aiRelance(): void
    {
        Auth::requireAuth();
        header('Content-Type: application/json; charset=utf-8');
        $input = json_decode(file_get_contents('php://input') ?: '{}', true) ?: [];
        $clientId = (int) ($input['client_id'] ?? 0);
        $obligation = trim($input['obligation'] ?? '');
        $status = trim($input['status'] ?? '');
        if ($clientId <= 0 || $obligation === '') {
            echo json_encode(['error' => 'Paramètres manquants']);
            return;
        }
        $message = AiAutomationService::generateRelanceMessage($clientId, $obligation, $status);
        echo json_encode(['message' => $message ?? 'IA indisponible — vérifiez OpenRouter.']);
    }
}
