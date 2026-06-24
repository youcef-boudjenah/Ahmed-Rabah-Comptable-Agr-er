<?php

declare(strict_types=1);

namespace App\Modules\Search;

use App\Core\Auth;
use App\Core\View;

final class SearchController
{
    public static function index(): void
    {
        Auth::requireAuth();
        $q = trim($_GET['q'] ?? '');
        View::render('search/index', [
            'title' => 'Recherche',
            'query' => $q,
            'results' => $q !== '' ? SearchService::global($q) : null,
        ]);
    }
}
