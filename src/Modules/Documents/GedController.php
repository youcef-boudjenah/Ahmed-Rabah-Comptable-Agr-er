<?php

declare(strict_types=1);

namespace App\Modules\Documents;

use App\Core\Auth;
use App\Core\View;
use App\Core\AuditLog;
use App\Modules\Clients\ClientRepository;

final class GedController
{
    public static function index(): void
    {
        Auth::requireAuth();
        $q = trim($_GET['q'] ?? '');
        View::render('ged/index', [
            'title' => 'GED — Gestion documentaire',
            'clients' => GedRepository::cabinetOverview(),
            'stats' => GedRepository::stats(),
            'results' => $q !== '' ? GedRepository::search($q) : [],
            'recent' => GedRepository::recentForCabinet(15),
            'query' => $q,
            'categories' => ClientFolderService::categoryLabels(),
        ]);
    }

    public static function clientDossier(int $clientId): void
    {
        Auth::requireAuth();
        $client = ClientRepository::find($clientId);
        if (!$client) {
            View::redirect('/ged');
        }
        ClientFolderService::ensure($clientId);
        $category = $_GET['cat'] ?? null;
        View::render('ged/dossier', [
            'title' => 'Dossier — ' . $client['raison_sociale'],
            'client' => $client,
            'structure' => ClientFolderService::structure($clientId),
            'documents' => GedRepository::forClient($clientId, $category),
            'allDocuments' => GedRepository::forClient($clientId),
            'category' => $category,
            'categories' => ClientFolderService::categoryLabels(),
            'view' => $_GET['view'] ?? 'list',
        ]);
    }

    public static function upload(int $clientId): void
    {
        Auth::requireAuth();
        if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
            View::flashT('error', 'flash.document_upload_error');
            View::redirect('/clients/' . $clientId . '/dossier');
        }
        $category = $_POST['category'] ?? 'divers';
        $id = GedRepository::storeInClientFolder($clientId, $_FILES['document'], $category, trim($_POST['title'] ?? '') ?: null);
        AuditLog::write('upload', 'documents', $id, ['category' => $category, 'client_id' => $clientId]);
        if (isset($_POST['process_now'])) {
            OcrService::processDocument($id);
        } else {
            DocumentController::processQueueOnce();
        }
        View::flashT('success', 'flash.document_added');
        View::redirect('/clients/' . $clientId . '/dossier?cat=' . urlencode($category));
    }

    public static function update(int $id): void
    {
        Auth::requireAuth();
        $doc = DocumentRepository::find($id);
        if (!$doc) {
            View::redirect('/ged');
        }
        GedRepository::updateMeta($id, [
            'title' => trim($_POST['title'] ?? $doc['original_name']),
            'category' => $_POST['category'] ?? 'divers',
            'notes' => trim($_POST['notes'] ?? ''),
            'tags' => trim($_POST['tags'] ?? ''),
            'ged_status' => $_POST['ged_status'] ?? 'a_traiter',
        ]);
        AuditLog::write('update', 'documents', $id);
        View::flashT('success', 'flash.document_updated');
        View::redirect($doc['client_id'] ? '/clients/' . $doc['client_id'] . '/dossier?cat=' . urlencode($_POST['category'] ?? $doc['category'] ?? '') : '/documents/' . $id);
    }

    public static function download(int $id): void
    {
        Auth::requireAuth();
        $doc = DocumentRepository::find($id);
        if (!$doc || !is_file($doc['file_path'])) {
            http_response_code(404);
            exit('Fichier introuvable');
        }
        header('Content-Type: ' . ($doc['mime'] ?: 'application/octet-stream'));
        header('Content-Disposition: inline; filename="' . basename($doc['original_name']) . '"');
        readfile($doc['file_path']);
        exit;
    }

    public static function destroy(int $id): void
    {
        Auth::requireAuth();
        $doc = DocumentRepository::find($id);
        if (!$doc) {
            View::flashT('error', 'flash.document_not_found');
            View::redirect('/ged');
        }
        $clientId = $doc['client_id'] ?? null;
        GedRepository::delete($id);
        AuditLog::write('delete', 'documents', $id, ['name' => $doc['original_name'] ?? '']);
        View::flashT('success', 'flash.document_deleted');
        View::redirect($clientId ? '/clients/' . $clientId . '/dossier' : '/ged');
    }

    public static function reassign(int $id): void
    {
        Auth::requireAuth();
        $doc = DocumentRepository::find($id);
        if (!$doc) {
            View::redirect('/ged');
        }
        $clientId = (int) ($_POST['client_id'] ?? 0);
        if ($clientId <= 0) {
            View::flashT('error', 'flash.document_client_required');
            View::redirect('/documents/' . $id);
        }
        try {
            GedRepository::reassignClient($id, $clientId, $_POST['category'] ?? null);
            View::flashT('success', 'flash.document_reassigned');
            View::redirect('/clients/' . $clientId . '/dossier?cat=' . urlencode($_POST['category'] ?? $doc['category'] ?? 'divers'));
        } catch (\InvalidArgumentException $e) {
            View::flash('error', $e->getMessage());
            View::redirect('/documents/' . $id);
        }
    }

    public static function bulk(int $clientId): void
    {
        Auth::requireAuth();
        $client = ClientRepository::find($clientId);
        if (!$client) {
            View::redirect('/ged');
        }

        $ids = array_map('intval', $_POST['ids'] ?? []);
        $action = $_POST['bulk_action'] ?? '';
        if (empty($ids) || $action === '') {
            View::flashT('error', 'flash.document_bulk_none');
            View::redirect('/clients/' . $clientId . '/dossier');
        }

        if ($action === 'delete' && !isset($_POST['confirm_delete'])) {
            View::flashT('error', 'flash.document_bulk_confirm_delete');
            View::redirect('/clients/' . $clientId . '/dossier');
        }

        $count = GedRepository::bulk($clientId, $ids, $action, [
            'ged_status' => $_POST['ged_status'] ?? 'archive',
            'category' => $_POST['bulk_category'] ?? 'divers',
        ]);

        $actionKeys = [
            'delete' => 'flash.document_bulk_deleted',
            'archive' => 'flash.document_bulk_archived',
            'status' => 'flash.document_bulk_updated',
            'move' => 'flash.document_bulk_moved',
        ];
        $actionKey = $actionKeys[$action] ?? 'flash.document_bulk_processed';
        View::flashT('success', 'flash.document_bulk_done', [
            'count' => $count,
            'action' => __($actionKey),
        ]);
        $cat = $_GET['cat'] ?? $_POST['return_cat'] ?? '';
        View::redirect('/clients/' . $clientId . '/dossier' . ($cat !== '' ? '?cat=' . urlencode($cat) : ''));
    }
}
