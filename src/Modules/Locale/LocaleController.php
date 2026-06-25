<?php

declare(strict_types=1);

namespace App\Modules\Locale;

use App\Core\Lang;
use App\Core\View;

final class LocaleController
{
    public static function switch(string $locale): void
    {
        Lang::setLocale($locale);

        $redirect = $_SERVER['HTTP_REFERER'] ?? '/';
        $path = parse_url($redirect, PHP_URL_PATH);
        if (!is_string($path) || $path === '' || str_starts_with($path, '/locale/')) {
            $path = '/';
        }

        View::redirect($path);
    }
}
