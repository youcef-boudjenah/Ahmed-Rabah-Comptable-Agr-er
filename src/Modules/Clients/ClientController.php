<?php

declare(strict_types=1);

namespace App\Modules\Clients;

use App\Core\Auth;
use App\Core\View;

final class ClientController
{
    public static function index(): void
    {
        Auth::requireAuth();
        $filters = [
            'q' => trim($_GET['q'] ?? ''),
            'secteur' => $_GET['secteur'] ?? '',
            'wilaya' => trim($_GET['wilaya'] ?? ''),
            'status' => $_GET['status'] ?? '',
            'sort' => $_GET['sort'] ?? 'name',
            'page' => (int) ($_GET['page'] ?? 1),
            'per_page' => (int) ($_GET['per_page'] ?? 50),
        ];
        $result = ClientListService::paginated($filters);

        View::render('clients/index', [
            'title' => __('nav.clients'),
            'clients' => $result['items'],
            'pagination' => $result,
            'filters' => $filters,
            'stats' => ClientListService::cabinetStats(),
            'perPageOptions' => ClientListService::perPageOptions(),
        ]);
    }

    public static function searchApi(): void
    {
        Auth::requireAuth();
        header('Content-Type: application/json; charset=utf-8');
        $q = trim($_GET['q'] ?? '');
        $limit = (int) ($_GET['limit'] ?? 20);
        echo json_encode(['results' => ClientListService::search($q, $limit)], JSON_UNESCAPED_UNICODE);
    }

    public static function createForm(): void
    {
        Auth::requireAuth();
        View::render('clients/form', ['title' => 'Nouveau client', 'client' => null]);
    }

    public static function store(): void
    {
        Auth::requireAuth();
        ClientRepository::create(self::input());
        View::flashT('success', 'flash.client_created');
        View::redirect('/clients');
    }

    public static function editForm(int $id): void
    {
        Auth::requireAuth();
        $client = ClientRepository::find($id);
        if (!$client) {
            View::redirect('/clients');
        }
        View::render('clients/form', ['title' => 'Modifier client', 'client' => $client]);
    }

    public static function update(int $id): void
    {
        Auth::requireAuth();
        ClientRepository::update($id, self::input());
        View::flashT('success', 'flash.client_updated');
        View::redirect('/clients/' . $id);
    }

    public static function show(int $id): void
    {
        Auth::requireAuth();
        $client = ClientRepository::find($id);
        if (!$client) {
            View::redirect('/clients');
        }
        View::render('clients/show', [
            'title' => $client['raison_sociale'],
            'client' => $client,
            'profile' => ClientService::profile($id),
        ]);
    }

    public static function addNote(int $id): void
    {
        Auth::requireAuth();
        $content = trim($_POST['content'] ?? '');
        if ($content !== '' && ClientRepository::find($id)) {
            ClientNoteRepository::add($id, $content, isset($_POST['pin']));
            View::flashT('success', 'flash.client_note_added');
        }
        View::redirect('/clients/' . $id);
    }

    public static function deleteNote(int $id, int $noteId): void
    {
        ClientNoteRepository::delete($noteId, $id);
        View::redirect('/clients/' . $id);
    }

    public static function updateNote(int $id, int $noteId): void
    {
        Auth::requireAuth();
        $content = trim($_POST['content'] ?? '');
        if ($content !== '' && ClientRepository::find($id)) {
            ClientNoteRepository::update($noteId, $id, $content, isset($_POST['pin']));
            View::flashT('success', 'flash.client_note_updated');
        }
        View::redirect('/clients/' . $id);
    }

    public static function archive(int $id): void
    {
        Auth::requireAuth();
        if (!ClientRepository::find($id)) {
            View::redirect('/clients');
        }
        ClientRepository::archive($id);
        View::flashT('success', 'flash.client_archived');
        View::redirect('/clients');
    }

    public static function restore(int $id): void
    {
        Auth::requireAuth();
        if (!ClientRepository::find($id)) {
            View::redirect('/clients');
        }
        ClientRepository::restore($id);
        View::flashT('success', 'flash.client_restored');
        View::redirect('/clients/' . $id);
    }

    public static function duplicate(int $id): void
    {
        Auth::requireAuth();
        try {
            $newId = ClientRepository::duplicate($id);
            View::flashT('success', 'flash.client_duplicated');
            View::redirect('/clients/' . $newId . '/edit');
        } catch (\InvalidArgumentException) {
            View::redirect('/clients');
        }
    }

    public static function bulk(): void
    {
        Auth::requireAuth();
        $ids = array_map('intval', $_POST['ids'] ?? []);
        $action = $_POST['bulk_action'] ?? '';
        if (empty($ids) || $action === '') {
            View::flashT('error', 'flash.client_bulk_none');
            View::redirect('/clients' . (isset($_POST['return_query']) ? '?' . $_POST['return_query'] : ''));
        }

        $count = match ($action) {
            'archive' => ClientRepository::bulkArchive($ids),
            'restore' => ClientRepository::bulkRestore($ids),
            default => 0,
        };

        $msg = match ($action) {
            'archive' => __('flash.client_bulk_archived', ['count' => $count]),
            'restore' => __('flash.client_bulk_restored', ['count' => $count]),
            default => __('flash.client_bulk_unknown'),
        };
        View::flash($count > 0 ? 'success' : 'error', $msg);
        View::redirect('/clients' . (isset($_POST['return_query']) ? '?' . $_POST['return_query'] : ''));
    }

    private static function input(): array
    {
        return [
            'raison_sociale' => trim($_POST['raison_sociale'] ?? ''),
            'nif' => trim($_POST['nif'] ?? ''),
            'nin' => trim($_POST['nin'] ?? ''),
            'numero_cotisant' => trim($_POST['numero_cotisant'] ?? '') ?: null,
            'secteur' => $_POST['secteur'] ?? 'SERVICES',
            'regime_fiscal' => $_POST['regime_fiscal'] ?? 'MENSUEL',
            'cnas_regime' => $_POST['cnas_regime'] ?? 'MENSUEL',
            'wilaya' => trim($_POST['wilaya'] ?? ''),
            'adresse' => trim($_POST['adresse'] ?? ''),
            'activite' => trim($_POST['activite'] ?? ''),
            'contact_email' => trim($_POST['contact_email'] ?? '') ?: null,
            'contact_phone' => trim($_POST['contact_phone'] ?? '') ?: null,
            'contact_name' => trim($_POST['contact_name'] ?? '') ?: null,
        ];
    }
}
