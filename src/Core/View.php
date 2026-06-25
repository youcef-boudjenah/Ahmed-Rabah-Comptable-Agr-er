<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    public static function render(string $template, array $vars = []): void
    {
        extract($vars, EXTR_SKIP);
        $config = require ROOT_PATH . '/config/app.php';
        $user = Auth::user();
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        $path = ROOT_PATH . '/templates/' . $template . '.php';
        if (!is_file($path)) {
            throw new \RuntimeException("Template not found: $template");
        }
        require ROOT_PATH . '/templates/layout.php';
    }

    public static function redirect(string $path): never
    {
        header('Location: ' . $path);
        exit;
    }

    public static function flash(string $type, string $message): void
    {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }

    /** @param array<string, string|int|float> $replace */
    public static function flashT(string $type, string $key, array $replace = []): void
    {
        self::flash($type, __($key, $replace));
    }
}
