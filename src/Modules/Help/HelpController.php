<?php

declare(strict_types=1);

namespace App\Modules\Help;

use App\Core\Auth;
use App\Core\View;

final class HelpController
{
    public static function index(): void
    {
        Auth::requireAuth();
        $section = $_GET['section'] ?? 'demarrage';
        $sections = UserGuide::sections();
        $ids = array_column($sections, 'id');
        if (!in_array($section, $ids, true)) {
            $section = 'demarrage';
        }

        View::render('help/index', [
            'title' => __('help.title'),
            'sections' => $sections,
            'activeSection' => $section,
            'faq' => UserGuide::faq(),
        ]);
    }
}
