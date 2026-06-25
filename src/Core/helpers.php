<?php

declare(strict_types=1);

use App\Core\Lang;

if (!function_exists('__')) {
    function __(string $key, array $replace = []): string
    {
        return Lang::get($key, $replace);
    }
}
