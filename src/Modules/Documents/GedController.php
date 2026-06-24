<?php

declare(strict_types=1);

namespace App\Modules\Documents;

use App\Core\Auth;
use App\Core\View;
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
            'query' => $q,
            'categories' => ClientFolderService::CATEGORIES,
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
            'categories' => ClientFolderService::CATEGORIES,
            'view' => $_GET['view'] ?? 'list',
        ]);
    }

    public static function upload(int $clientId): void
    {
        Auth::requireAuth();
        if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
            View::flash('error', 'Erreur upload.');
            View::redirect('/clients/' . $clientId . '/dossier');
        }
        $category = $_POST['category'] ?? 'divers';
        $id = GedRepository::storeInClientFolder($clientId, $_FILES['document'], $category, trim($_POST['title'] ?? '') ?: null);
        if (isset($_POST['process_now'])) {
            OcrService::processDocument($id);
        } else {
            DocumentController::processQueueOnce();
        }
        View::flash('success', 'Document ajouté au dossier client.');
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
        View::flash('success', 'Document mis à jour.');
        View::redirect($doc['client_id'] ? '/clients/' . $doc['client_id'] . '/dossier' : '/ged');
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
}
