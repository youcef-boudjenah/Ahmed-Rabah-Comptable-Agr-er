<?php

declare(strict_types=1);

namespace App\Modules\Documents;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Queue;
use App\Core\View;
use App\Modules\Automation\WorkflowService;
use App\Modules\Clients\ClientRepository;
use App\Modules\Entries\EntryRepository;

final class DocumentController
{
    public static function index(): void
    {
        Auth::requireAuth();
        View::render('documents/index', [
            'title' => 'Documents',
            'documents' => DocumentRepository::allForCabinet(),
            'selectedClientId' => isset($_GET['client']) ? (int) $_GET['client'] : null,
        ]);
    }

    public static function upload(): void
    {
        Auth::requireAuth();
        if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
            View::flash('error', 'Erreur upload.');
            View::redirect('/documents');
        }
        $clientId = $_POST['client_id'] !== '' ? (int) $_POST['client_id'] : null;
        $id = DocumentRepository::storeUpload($_FILES['document'], $clientId);

        if (isset($_POST['process_now'])) {
            OcrService::processDocument($id);
            View::flash('success', 'Document traité.');
        } else {
            self::processQueueOnce();
            View::flash('success', 'Document uploadé. Traitement OCR en cours.');
        }
        View::redirect('/documents/' . $id);
    }

    public static function show(int $id): void
    {
        Auth::requireAuth();
        $doc = DocumentRepository::find($id);
        if (!$doc) {
            View::redirect('/documents');
        }
        View::render('documents/show', [
            'title' => 'Document — ' . $doc['original_name'],
            'document' => $doc,
            'ocr' => DocumentRepository::ocrResult($id),
        ]);
    }

    public static function process(int $id): void
    {
        Auth::requireAuth();
        OcrService::processDocument($id);
        View::flash('success', 'OCR terminé.');
        View::redirect('/documents/' . $id);
    }

    public static function commit(int $id): void
    {
        Auth::requireAuth();
        $doc = DocumentRepository::find($id);
        $ocr = DocumentRepository::ocrResult($id);
        if (!$doc || !$ocr) {
            View::redirect('/documents');
        }

        $clientId = (int) ($_POST['client_id'] ?? $doc['client_id'] ?? 0);
        if ($clientId <= 0) {
            View::flash('error', 'Sélectionnez un client.');
            View::redirect('/documents/' . $id);
        }

        $extracted = $ocr['extracted_json'];
        if (isset($_POST['masse_salariale'])) {
            $extracted['masse_salariale'] = (float) str_replace(',', '.', $_POST['masse_salariale']);
            $extracted['entry_type'] = 'payroll';
        }
        if (isset($_POST['ca_biens']) || isset($_POST['ca_services'])) {
            $extracted['entry_type'] = 'sales';
            $extracted['ca_biens'] = (float) str_replace(',', '.', $_POST['ca_biens'] ?? '0');
            $extracted['ca_services'] = (float) str_replace(',', '.', $_POST['ca_services'] ?? '0');
        }
        if (isset($_POST['period_year'])) {
            $extracted['period_year'] = (int) $_POST['period_year'];
            $extracted['period_month'] = (int) $_POST['period_month'];
        }

        EntryRepository::createFromOcr($clientId, $extracted);
        DocumentRepository::updateStatus($id, 'done');
        if (!$doc['client_id']) {
            Database::query('UPDATE documents SET client_id = ? WHERE id = ?', [$clientId, $id]);
        }

        $declId = ($extracted['entry_type'] ?? '') === 'sales'
            ? WorkflowService::afterSalesSaved($clientId)
            : WorkflowService::afterPayrollSaved($clientId);

        View::flash('success', 'Données importées depuis OCR. Déclarations recalculées.');
        View::redirect($declId ? '/declarations/' . $declId : '/declarations?status=DRAFT_CALCULATED');
    }

    public static function processQueueOnce(): void
    {
        $job = Queue::claimNext('OCR_EXTRACT');
        if (!$job) {
            return;
        }
        try {
            OcrService::processDocument((int) $job['payload']['document_id']);
            Queue::complete((int) $job['id']);
        } catch (\Throwable $e) {
            Queue::fail((int) $job['id'], $e->getMessage());
        }
    }
}
