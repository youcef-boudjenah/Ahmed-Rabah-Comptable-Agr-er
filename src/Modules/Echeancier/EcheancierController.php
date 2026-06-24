<?php

declare(strict_types=1);

namespace App\Modules\Echeancier;

use App\Core\Auth;
use App\Core\View;
use App\Modules\Automation\DeadlineService;

final class EcheancierController
{
    public static function index(): void
    {
        Auth::requireAuth();
        $year = isset($_GET['year']) ? (int) $_GET['year'] : (int) date('Y');
        $upcoming = DeadlineService::cabinetUpcoming(Auth::cabinetId(), 90);
        $calendar = [];
        foreach ($upcoming as $item) {
            $key = $item['due_date']->format('Y-m-d');
            $calendar[$key][] = $item;
        }
        ksort($calendar);

        View::render('echeancier/index', [
            'title' => 'Échéancier fiscal & social',
            'year' => $year,
            'upcoming' => $upcoming,
            'calendar' => $calendar,
            'view' => $_GET['view'] ?? 'list',
        ]);
    }
}
