<?php

declare(strict_types=1);

namespace App\Modules\Declarations;

use App\Core\Auth;
use App\Core\View;
use App\Modules\Automation\DeadlineService;
use App\Modules\Admin\SettingsService;
use App\Modules\Automation\PdfGenerationService;
use App\Modules\Automation\WorkflowService;
use App\Modules\AI\AiAutomationService;

final class DeclarationController
{
    public static function index(): void
    {
        Auth::requireAuth();
        $status = $_GET['status'] ?? null;
        $view = $_GET['view'] ?? 'list';
        $declarations = DeclarationRepository::allForCabinet($status);

        $kanban = [
            'DRAFT_CALCULATED' => [],
            'APPROVED' => [],
            'SUBMITTED' => [],
        ];
        foreach (DeclarationRepository::allForCabinet(null) as $d) {
            $key = $d['status'];
            if (isset($kanban[$key])) {
                $kanban[$key][] = $d;
            }
        }

        View::render('declarations/index', [
            'title' => __('nav.declarations'),
            'declarations' => $declarations,
            'kanban' => $kanban,
            'filterStatus' => $status,
            'view' => $view,
        ]);
    }

    public static function show(int $id): void
    {
        Auth::requireAuth();
        $declaration = DeclarationRepository::find($id);
        if (!$declaration) {
            View::redirect('/declarations');
        }
        View::render('declarations/show', [
            'title' => __('declarations.review_title'),
            'declaration' => $declaration,
            'source' => DeclarationRepository::sourceData($id),
            'previous' => DeclarationRepository::previousPeriod($declaration),
            'typeLabel' => DeadlineService::typeLabel($declaration['type']),
            'periodLabel' => DeadlineService::periodLabel($declaration['type'], [
                'year' => (int) $declaration['period_year'],
                'month' => $declaration['period_month'] ? (int) $declaration['period_month'] : null,
                'quarter' => $declaration['period_quarter'] ? (int) $declaration['period_quarter'] : null,
            ]),
            'aiReview' => json_decode($declaration['ai_review_json'] ?? 'null', true),
            'canApprove' => Auth::canApprove(),
            'canSubmit' => Auth::canSubmit(),
            'nextStep' => WorkflowService::nextStep($declaration),
            'gedDocs' => WorkflowService::documentsForDeclaration($id),
            'bordereauMeta' => self::bordereauMeta($declaration),
        ]);
    }

    public static function update(int $id): void
    {
        Auth::requireAuth();
        $declaration = DeclarationRepository::find($id);
        if (!$declaration || $declaration['status'] !== 'DRAFT_CALCULATED') {
            View::redirect('/declarations');
        }

        $computed = $declaration['computed_fields'];
        if (isset($_POST['lines']) && is_array($_POST['lines'])) {
            foreach ($_POST['lines'] as $i => $montant) {
                if (isset($computed['lines'][$i])) {
                    $computed['lines'][$i]['montant'] = (float) str_replace(',', '.', $montant);
                }
            }
            $computed['total'] = array_sum(array_column($computed['lines'], 'montant'));
        }
        if (isset($_POST['total'])) {
            $computed['total'] = (float) str_replace(',', '.', $_POST['total']);
        }

        DeclarationRepository::updateComputed($id, $computed);
        View::flashT('success', 'flash.declaration_draft_updated');
        View::redirect('/declarations/' . $id);
    }

    public static function approve(int $id): void
    {
        Auth::requireAuth();
        if (!Auth::canApprove()) {
            View::flashT('error', 'flash.declaration_approve_denied');
            View::redirect('/declarations/' . $id);
        }
        DeclarationRepository::approve($id);
        if (SettingsService::bool('auto_pdf_on_approve')) {
            PdfGenerationService::generate($id);
        }
        View::flash('success', SettingsService::bool('auto_pdf_on_approve')
            ? __('flash.declaration_approved_pdf')
            : __('flash.declaration_approved'));
        View::redirect('/declarations/' . $id);
    }

    public static function submit(int $id): void
    {
        Auth::requireAuth();
        if (!Auth::canSubmit()) {
            View::flashT('error', 'flash.declaration_submit_admin');
            View::redirect('/declarations/' . $id);
        }
        $checklist = [
            'bordereau_imprime' => isset($_POST['bordereau_imprime']),
            'montants_verifies' => isset($_POST['montants_verifies']),
            'quittance_jointe' => isset($_POST['quittance_jointe']),
        ];
        $receiptPath = null;
        if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] === UPLOAD_ERR_OK) {
            $decl = DeclarationRepository::find($id);
            if ($decl) {
                $dir = ROOT_PATH . '/storage/uploads/receipts/' . date('Y/m');
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                $dest = $dir . '/receipt_' . $id . '_' . basename($_FILES['receipt']['name']);
                move_uploaded_file($_FILES['receipt']['tmp_name'], $dest);
                $receiptPath = $dest;
            }
        }
        DeclarationRepository::submit($id, $checklist, $receiptPath);
        if ($receiptPath) {
            WorkflowService::onReceiptUploaded($id, $receiptPath);
        }
        $decl = DeclarationRepository::find($id);
        if ($decl && !empty($decl['generated_pdf_path']) && is_file($decl['generated_pdf_path'])) {
            WorkflowService::onBordereauGenerated($id, $decl['generated_pdf_path']);
        }
        View::flashT('success', 'flash.declaration_submitted');
        View::redirect('/declarations/' . $id);
    }

    public static function print(int $id): void
    {
        Auth::requireAuth();
        $declaration = DeclarationRepository::find($id);
        if (!$declaration) {
            View::redirect('/declarations');
        }
        $periodLabel = DeadlineService::periodLabel($declaration['type'], [
            'year' => (int) $declaration['period_year'],
            'month' => $declaration['period_month'] ? (int) $declaration['period_month'] : null,
            'quarter' => $declaration['period_quarter'] ? (int) $declaration['period_quarter'] : null,
        ]);
        echo self::injectPreviewBanner(
            \App\Modules\Automation\BordereauRenderer::renderHtml($declaration, $periodLabel)
        );
        exit;
    }

    public static function exportCsv(int $id): void
    {
        Auth::requireAuth();
        $declaration = DeclarationRepository::find($id);
        if (!$declaration) {
            View::redirect('/declarations');
        }

        $cf = is_array($declaration['computed_fields'])
            ? $declaration['computed_fields']
            : json_decode($declaration['computed_fields'] ?? '{}', true);
        $periodLabel = DeadlineService::periodLabel($declaration['type'], [
            'year' => (int) $declaration['period_year'],
            'month' => $declaration['period_month'] ? (int) $declaration['period_month'] : null,
            'quarter' => $declaration['period_quarter'] ? (int) $declaration['period_quarter'] : null,
        ]);

        $filename = sprintf('declaration_%d_%s.csv', $id, date('Ymd'));
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        if ($out === false) {
            exit;
        }

        fprintf($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Client', $declaration['raison_sociale']], ';');
        fputcsv($out, ['Type', DeadlineService::typeLabel($declaration['type'])], ';');
        fputcsv($out, ['Période', $periodLabel], ';');
        fputcsv($out, ['Statut', $declaration['status']], ';');
        fputcsv($out, [], ';');
        fputcsv($out, ['Code', 'Libellé', 'Assiette', 'Taux', 'Montant (DA)'], ';');

        foreach ($cf['lines'] ?? [] as $line) {
            fputcsv($out, [
                $line['code'] ?? '',
                $line['label'] ?? '',
                $line['assiette'] ?? $line['ca'] ?? '',
                isset($line['taux']) ? $line['taux'] . '%' : '',
                $line['montant'] ?? 0,
            ], ';');
        }

        fputcsv($out, [], ';');
        fputcsv($out, ['TOTAL', '', '', '', $cf['total'] ?? 0], ';');
        fclose($out);
        exit;
    }

    public static function receipt(int $id): void
    {
        Auth::requireAuth();
        $declaration = DeclarationRepository::find($id);
        if (!$declaration || empty($declaration['receipt_path']) || !is_file($declaration['receipt_path'])) {
            http_response_code(404);
            exit('Fichier introuvable');
        }
        $path = $declaration['receipt_path'];
        $mime = mime_content_type($path) ?: 'application/octet-stream';
        $download = isset($_GET['download']);
        header('Content-Type: ' . $mime);
        header('Content-Disposition: ' . ($download ? 'attachment' : 'inline') . '; filename="' . basename($path) . '"');
        readfile($path);
        exit;
    }

    public static function generatedPdf(int $id): void
    {
        Auth::requireAuth();
        $declaration = DeclarationRepository::find($id);
        if (!$declaration || empty($declaration['generated_pdf_path']) || !is_file($declaration['generated_pdf_path'])) {
            http_response_code(404);
            exit('Bordereau non généré');
        }
        $path = $declaration['generated_pdf_path'];
        $pdfPath = preg_replace('/\.html$/', '.pdf', $path);
        $download = isset($_GET['download']);
        if ($pdfPath && is_file($pdfPath)) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: ' . ($download ? 'attachment' : 'inline') . '; filename="bordereau_' . $id . '.pdf"');
            readfile($pdfPath);
            exit;
        }
        if ($download) {
            header('Content-Type: text/html; charset=utf-8');
            header('Content-Disposition: attachment; filename="bordereau_' . $id . '.html"');
            readfile($path);
            exit;
        }
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: inline; filename="bordereau_' . $id . '.html"');
        $html = file_get_contents($path) ?: '';
        echo self::injectArchivedBanner($html, (int) filemtime($path));
        exit;
    }

    public static function aiReview(int $id): void
    {
        Auth::requireAuth();
        $review = AiAutomationService::reviewDeclaration($id);
        if (!$review) {
            View::flashT('error', 'flash.declaration_ai_unavailable');
        } else {
            View::flashT('success', 'flash.declaration_ai_saved');
        }
        View::redirect('/declarations/' . $id);
    }

    public static function generatePdf(int $id): void
    {
        Auth::requireAuth();
        $path = PdfGenerationService::generate($id);
        View::flashT($path ? 'success' : 'error', $path ? 'flash.declaration_pdf_ok' : 'flash.declaration_pdf_failed');
        View::redirect('/declarations/' . $id);
    }

    public static function destroy(int $id): void
    {
        Auth::requireAuth();
        if (!DeclarationRepository::deleteDraft($id)) {
            View::flashT('error', 'flash.declaration_delete_draft_only');
            View::redirect('/declarations');
        }
        View::flashT('success', 'flash.declaration_draft_deleted');
        View::redirect('/declarations?status=DRAFT_CALCULATED');
    }

    public static function bulk(): void
    {
        Auth::requireAuth();
        $ids = array_map('intval', $_POST['ids'] ?? []);
        $action = $_POST['bulk_action'] ?? '';

        if (empty($ids)) {
            View::flashT('error', 'flash.declaration_bulk_none');
            View::redirect('/declarations');
        }

        if ($action === 'delete') {
            $count = DeclarationRepository::bulkDeleteDrafts($ids);
            View::flashT('success', 'flash.declaration_bulk_deleted', ['count' => $count]);
        } elseif ($action === 'approve' && Auth::canApprove()) {
            $result = DeclarationRepository::approveBatch($ids);
            View::flashT('success', 'flash.declaration_bulk_approved', [
                'approved' => $result['approved'],
                'skipped' => $result['skipped'],
            ]);
        } else {
            View::flashT('error', 'flash.declaration_bulk_unauthorized');
        }

        View::redirect($_POST['redirect'] ?? '/declarations');
    }

    /** @return array{has_archive: bool, has_pdf: bool, generated_at: int|null, size: int|null} */
    private static function bordereauMeta(array $declaration): array
    {
        $path = (string) ($declaration['generated_pdf_path'] ?? '');
        $hasArchive = $path !== '' && is_file($path);
        $pdfPath = $hasArchive ? preg_replace('/\.html$/', '.pdf', $path) : null;

        return [
            'has_archive' => $hasArchive,
            'has_pdf' => $pdfPath !== null && is_file($pdfPath),
            'generated_at' => $hasArchive ? (int) filemtime($path) : null,
            'size' => $hasArchive ? (int) filesize($path) : null,
        ];
    }

    private static function injectPreviewBanner(string $html): string
    {
        $banner = '<div class="no-print preview-banner"><div><strong>Aperçu en direct</strong> — '
            . 'montants actuels de la déclaration (non archivé)</div>'
            . '<button type="button" class="btn-print" onclick="window.print()" style="margin:0">Imprimer</button></div>';

        if (str_contains($html, '<body>')) {
            return preg_replace('/<body>/', '<body>' . $banner, $html, 1) ?? $html;
        }

        return $banner . $html;
    }

    private static function injectArchivedBanner(string $html, int $generatedAt): string
    {
        $date = date('d/m/Y H:i', $generatedAt);
        $banner = '<div class="no-print preview-banner" style="background:#eff6ff;border-color:#93c5fd">'
            . '<div><strong>Version archivée</strong> — générée le ' . htmlspecialchars($date)
            . ' · <a href="?download=1" style="color:#1d4ed8">Télécharger</a></div>'
            . '<button type="button" class="btn-print" onclick="window.print()" style="margin:0">Imprimer</button></div>';

        if (str_contains($html, '<body>')) {
            return preg_replace('/<body>/', '<body>' . $banner, $html, 1) ?? $html;
        }

        return $banner . $html;
    }
}
