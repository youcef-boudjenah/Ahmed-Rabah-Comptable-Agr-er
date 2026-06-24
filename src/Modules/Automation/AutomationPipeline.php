<?php

declare(strict_types=1);

namespace App\Modules\Automation;

use App\Core\Auth;
use App\Core\Database;
use App\Modules\AI\AiAutomationService;
use App\Modules\Documents\DocumentController;
use App\Modules\Documents\DocumentRepository;
use App\Modules\Admin\SettingsService;
use App\Modules\Tasks\AutoTaskService;

final class AutomationPipeline
{
    /** @return array<string, mixed> */
    public static function getPreview(int $cabinetId): array
    {
        $payroll = (int) Database::fetchOne(
            'SELECT COUNT(*) AS c FROM payroll_entries pe JOIN clients c ON c.id = pe.client_id WHERE c.cabinet_id = ?',
            [$cabinetId]
        )['c'];
        $sales = (int) Database::fetchOne(
            'SELECT COUNT(*) AS c FROM sales_entries se JOIN clients c ON c.id = se.client_id WHERE c.cabinet_id = ?',
            [$cabinetId]
        )['c'];
        $missingPdf = (int) Database::fetchOne(
            "SELECT COUNT(*) AS c FROM declarations d JOIN clients c ON c.id = d.client_id
             WHERE c.cabinet_id = ? AND d.status IN ('DRAFT_CALCULATED','APPROVED')
             AND (d.generated_pdf_path IS NULL OR d.generated_pdf_path = '')",
            [$cabinetId]
        )['c'];
        $ocrQueue = (int) Database::fetchOne(
            "SELECT COUNT(*) AS c FROM job_queue WHERE status = 'pending' AND type = 'OCR_EXTRACT'"
        )['c'];
        $docsPending = (int) Database::fetchOne(
            "SELECT COUNT(*) AS c FROM documents WHERE cabinet_id = ? AND status IN ('pending','processing')",
            [$cabinetId]
        )['c'];
        $toClassify = (int) Database::fetchOne(
            "SELECT COUNT(*) AS c FROM documents WHERE cabinet_id = ? AND category = 'divers' AND status IN ('awaiting_review','done')",
            [$cabinetId]
        )['c'];
        $draftsAi = (int) Database::fetchOne(
            "SELECT COUNT(*) AS c FROM declarations d JOIN clients c ON c.id = d.client_id
             WHERE c.cabinet_id = ? AND d.status = 'DRAFT_CALCULATED' AND d.ai_review_json IS NULL",
            [$cabinetId]
        )['c'];

        return [
            'payroll_entries' => $payroll,
            'sales_entries' => $sales,
            'declarations_missing_pdf' => $missingPdf,
            'ocr_queue' => $ocrQueue,
            'documents_pending_ocr' => $docsPending,
            'documents_to_classify' => $toClassify,
            'drafts_for_ai_review' => $draftsAi,
            'open_tasks' => (int) Database::fetchOne(
                'SELECT COUNT(*) AS c FROM cabinet_tasks WHERE cabinet_id = ? AND is_done = 0',
                [$cabinetId]
            )['c'],
        ];
    }

    /**
     * @param array<string, bool> $steps
     * @return array{run_id: int, steps: list<array<string, mixed>>, summary: array<string, mixed>, duration_ms: int}
     */
    public static function runControlled(int $cabinetId, int $userId, array $steps, string $runType = 'custom'): array
    {
        $log = [];
        $started = microtime(true);

        if (!empty($steps['recalc'])) {
            $log[] = self::runStep('recalc', 'Recalcul des déclarations (CNAS, CACOBATPH, G50…)', function () use ($cabinetId) {
                $r = BatchService::recalculateCabinet($cabinetId);
                $msg = sprintf('%d saisie(s) paie et %d vente(s) relues.', $r['payroll'], $r['sales']);
                if ($r['payroll'] === 0 && $r['sales'] === 0) {
                    $msg = 'Aucune saisie paie/vente — ajoutez des données dans Saisie paie ou Saisie ventes.';
                }
                return ['message' => $msg, 'details' => $r];
            });
        }

        if (!empty($steps['tasks'])) {
            $log[] = self::runStep('tasks', 'Création des tâches (retards / données manquantes)', function () use ($cabinetId) {
                if (!SettingsService::bool('auto_sync_tasks', $cabinetId)) {
                    return ['message' => 'Désactivé dans Paramètres admin.', 'details' => ['created' => 0, 'skipped' => 0], 'status' => 'skipped'];
                }
                $r = AutoTaskService::syncFromObligations($cabinetId);
                return [
                    'message' => sprintf('%d tâche(s) créée(s), %d déjà existante(s).', $r['created'], $r['skipped']),
                    'details' => $r,
                ];
            });
        }

        if (!empty($steps['pdfs'])) {
            $log[] = self::runStep('pdfs', 'Génération des bordereaux imprimables', function () use ($cabinetId) {
                $r = PdfGenerationService::generateAllDraftsAndApproved($cabinetId);
                if ($r['generated'] === 0) {
                    $msg = 'Aucun bordereau à créer — tous les PDF existent déjà.';
                } else {
                    $msg = sprintf('%d bordereau(x) créé(s).', $r['generated']);
                    if (!empty($r['items'])) {
                        $msg .= ' ' . implode(', ', array_slice(array_column($r['items'], 'label'), 0, 5));
                        if (count($r['items']) > 5) {
                            $msg .= '…';
                        }
                    }
                }
                return ['message' => $msg, 'details' => $r];
            });
        }

        if (!empty($steps['ocr'])) {
            $log[] = self::runStep('ocr', 'Traitement file OCR (documents scannés)', function () {
                $processed = [];
                $errors = [];
                for ($i = 0; $i < 10; $i++) {
                    $before = Database::fetchOne(
                        "SELECT id, payload FROM job_queue WHERE status = 'pending' AND type = 'OCR_EXTRACT' ORDER BY id LIMIT 1"
                    );
                    if (!$before) {
                        break;
                    }
                    $payload = json_decode($before['payload'], true) ?: [];
                    $docId = (int) ($payload['document_id'] ?? 0);
                    try {
                        DocumentController::processQueueOnce();
                        $processed[] = ['document_id' => $docId, 'status' => 'ok'];
                    } catch (\Throwable $e) {
                        $errors[] = ['document_id' => $docId, 'error' => $e->getMessage()];
                    }
                }
                return [
                    'message' => count($processed) === 0
                        ? 'Aucun document OCR en attente dans la file.'
                        : sprintf('%d document(s) OCR traité(s)%s.', count($processed), $errors ? ', ' . count($errors) . ' erreur(s)' : ''),
                    'details' => ['processed' => $processed, 'errors' => $errors],
                    'status' => $errors && !$processed ? 'error' : 'ok',
                ];
            });
        }

        if (!empty($steps['ai_review'])) {
            $log[] = self::runStep('ai_review', 'Analyse IA des brouillons', function () use ($cabinetId) {
                if (!SettingsService::bool('auto_ai_review_pipeline', $cabinetId)) {
                    return ['message' => 'Désactivé dans Paramètres admin.', 'details' => [], 'status' => 'skipped'];
                }
                $r = AiAutomationService::batchReviewDrafts($cabinetId, 3);
                return [
                    'message' => sprintf('%d brouillon(s) analysé(s).', $r['reviewed']),
                    'details' => $r,
                    'status' => !empty($r['errors']) ? 'warning' : 'ok',
                ];
            });
        }

        if (!empty($steps['ai_classify'])) {
            $log[] = self::runStep('ai_classify', 'Classification GED par IA', function () use ($cabinetId) {
                $r = self::classifyPendingDocumentsDetailed($cabinetId, 15);
                return [
                    'message' => sprintf('%d document(s) classé(s).', $r['count']),
                    'details' => $r,
                ];
            });
        }

        if ($log === []) {
            $log[] = [
                'id' => 'none',
                'label' => '—',
                'status' => 'skipped',
                'duration_ms' => 0,
                'message' => 'Aucune étape sélectionnée.',
                'details' => [],
            ];
        }

        $durationMs = (int) round((microtime(true) - $started) * 1000);
        $summary = self::buildSummary($log);

        $result = [
            'steps' => $log,
            'summary' => $summary,
            'duration_ms' => $durationMs,
            'steps_enabled' => array_keys(array_filter($steps)),
        ];

        $runId = Database::insert(
            'INSERT INTO automation_runs (cabinet_id, user_id, run_type, result_json) VALUES (?, ?, ?, ?)',
            [$cabinetId, $userId, $runType, json_encode($result, JSON_UNESCAPED_UNICODE)]
        );

        return ['run_id' => $runId, 'steps' => $log, 'summary' => $summary, 'duration_ms' => $durationMs];
    }

    /** @return array<string, mixed> */
    public static function findRun(int $runId, int $cabinetId): ?array
    {
        $row = Database::fetchOne(
            'SELECT ar.*, u.name AS user_name FROM automation_runs ar
             LEFT JOIN users u ON u.id = ar.user_id
             WHERE ar.id = ? AND ar.cabinet_id = ?',
            [$runId, $cabinetId]
        );
        if (!$row) {
            return null;
        }
        $row['result'] = json_decode($row['result_json'], true) ?: [];
        return $row;
    }

    public static function runFull(int $cabinetId, int $userId, bool $withAi = true): array
    {
        $steps = [
            'recalc' => true,
            'tasks' => true,
            'pdfs' => true,
            'ocr' => true,
            'ai_review' => $withAi,
            'ai_classify' => $withAi,
        ];
        return self::runControlled($cabinetId, $userId, $steps, 'full_pipeline');
    }

    /** @return array{count: int, items: list<array<string, mixed>>} */
    public static function classifyPendingDocumentsDetailed(int $cabinetId, int $limit = 5): array
    {
        if (!SettingsService::bool('auto_ai_classify', $cabinetId)) {
            return ['count' => 0, 'items' => [], 'skipped' => true];
        }

        $docs = Database::fetchAll(
            "SELECT d.id, d.original_name, d.client_id, c.secteur, c.raison_sociale
             FROM documents d
             LEFT JOIN clients c ON c.id = d.client_id
             WHERE d.cabinet_id = ? AND d.category = 'divers' AND d.status IN ('awaiting_review', 'done')
             ORDER BY d.created_at DESC LIMIT " . max(1, min(30, $limit)),
            [$cabinetId]
        );

        $items = [];
        foreach ($docs as $doc) {
            $ocr = DocumentRepository::ocrResult((int) $doc['id']);
            $text = $ocr['raw_text'] ?? '';
            if (strlen(trim($text)) < 20) {
                $items[] = ['id' => $doc['id'], 'name' => $doc['original_name'], 'status' => 'skipped', 'reason' => 'Texte OCR insuffisant'];
                continue;
            }
            $meta = AiAutomationService::classifyDocument($doc['original_name'], $text, $doc['secteur'] ?? null);
            if (!$meta) {
                $items[] = ['id' => $doc['id'], 'name' => $doc['original_name'], 'status' => 'failed', 'reason' => 'IA indisponible'];
                continue;
            }
            Database::query(
                'UPDATE documents SET category = ?, title = COALESCE(NULLIF(title, original_name), ?), doc_type = ?, updated_at = NOW() WHERE id = ?',
                [$meta['category'], $meta['title'], $meta['doc_type'], $doc['id']]
            );
            $items[] = [
                'id' => $doc['id'],
                'name' => $doc['original_name'],
                'client' => $doc['raison_sociale'] ?? '—',
                'status' => 'ok',
                'category' => $meta['category'],
                'title' => $meta['title'],
            ];
        }

        return ['count' => count(array_filter($items, fn ($i) => ($i['status'] ?? '') === 'ok')), 'items' => $items];
    }

    public static function classifyPendingDocuments(int $cabinetId, int $limit = 5): int
    {
        return self::classifyPendingDocumentsDetailed($cabinetId, $limit)['count'];
    }

    /** @return list<array<string, mixed>> */
    public static function recentRuns(int $cabinetId, int $limit = 10): array
    {
        $limit = max(1, min(50, $limit));
        return Database::fetchAll(
            "SELECT ar.*, u.name AS user_name FROM automation_runs ar
             LEFT JOIN users u ON u.id = ar.user_id
             WHERE ar.cabinet_id = ? ORDER BY ar.created_at DESC LIMIT {$limit}",
            [$cabinetId]
        );
    }

    /** @param callable(): array{message: string, details: mixed, status?: string} $fn */
    private static function runStep(string $id, string $label, callable $fn): array
    {
        $t0 = microtime(true);
        try {
            $out = $fn();
            $status = $out['status'] ?? 'ok';
            return [
                'id' => $id,
                'label' => $label,
                'status' => $status,
                'duration_ms' => (int) round((microtime(true) - $t0) * 1000),
                'message' => $out['message'],
                'details' => $out['details'] ?? [],
            ];
        } catch (\Throwable $e) {
            return [
                'id' => $id,
                'label' => $label,
                'status' => 'error',
                'duration_ms' => (int) round((microtime(true) - $t0) * 1000),
                'message' => $e->getMessage(),
                'details' => [],
            ];
        }
    }

    /** @param list<array<string, mixed>> $log */
    private static function buildSummary(array $log): array
    {
        $summary = ['ok' => 0, 'skipped' => 0, 'error' => 0, 'warning' => 0];
        foreach ($log as $step) {
            $s = $step['status'] ?? 'ok';
            $summary[$s] = ($summary[$s] ?? 0) + 1;
        }
        return $summary;
    }
}
