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
            'title' => 'Clients',
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
        View::flash('success', 'Client créé.');
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
        View::flash('success', 'Client mis à jour.');
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
            View::flash('success', 'Note ajoutée.');
        }
        View::redirect('/clients/' . $id);
    }

    public static function deleteNote(int $id, int $noteId): void
    {
        Auth::requireAuth();
        ClientNoteRepository::delete($noteId, $id);
        View::redirect('/clients/' . $id);
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
