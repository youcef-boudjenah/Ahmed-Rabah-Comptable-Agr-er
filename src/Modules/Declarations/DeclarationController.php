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
            'title' => 'Déclarations',
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
            'title' => 'Revue déclaration',
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
        View::flash('success', 'Brouillon mis à jour.');
        View::redirect('/declarations/' . $id);
    }

    public static function approve(int $id): void
    {
        Auth::requireAuth();
        if (!Auth::canApprove()) {
            View::flash('error', 'Vous n\'avez pas la permission d\'approuver.');
            View::redirect('/declarations/' . $id);
        }
        DeclarationRepository::approve($id);
        if (SettingsService::bool('auto_pdf_on_approve')) {
            PdfGenerationService::generate($id);
        }
        View::flash('success', SettingsService::bool('auto_pdf_on_approve')
            ? 'Déclaration approuvée. Bordereau généré et archivé dans le GED.'
            : 'Déclaration approuvée.');
        View::redirect('/declarations/' . $id);
    }

    public static function submit(int $id): void
    {
        Auth::requireAuth();
        if (!Auth::canSubmit()) {
            View::flash('error', 'Dépôt réservé aux administrateurs.');
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
        View::flash('success', 'Déclaration déposée. Quittance et bordereau archivés dans le dossier client.');
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
        echo \App\Modules\Automation\BordereauRenderer::renderHtml($declaration, $periodLabel);
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
        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename="' . basename($path) . '"');
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
        if ($pdfPath && is_file($pdfPath)) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="bordereau_' . $id . '.pdf"');
            readfile($pdfPath);
            exit;
        }
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: inline; filename="bordereau_' . $id . '.html"');
        readfile($path);
        exit;
    }

    public static function aiReview(int $id): void
    {
        Auth::requireAuth();
        $review = AiAutomationService::reviewDeclaration($id);
        if (!$review) {
            View::flash('error', 'Analyse IA indisponible (OpenRouter).');
        } else {
            View::flash('success', 'Analyse IA enregistrée.');
        }
        View::redirect('/declarations/' . $id);
    }

    public static function generatePdf(int $id): void
    {
        Auth::requireAuth();
        $path = PdfGenerationService::generate($id);
        View::flash($path ? 'success' : 'error', $path ? 'Bordereau généré et archivé dans le GED.' : 'Échec génération.');
        View::redirect('/declarations/' . $id);
    }
}
