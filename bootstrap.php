<?php

declare(strict_types=1);

define('ROOT_PATH', __DIR__);

require_once ROOT_PATH . '/src/Core/Env.php';
\App\Core\Env::load(ROOT_PATH . '/.env');

$vendorAutoload = ROOT_PATH . '/vendor/autoload.php';
if (is_file($vendorAutoload)) {
    require_once $vendorAutoload;
}

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($prefix)));
    $file = ROOT_PATH . '/src/' . $relative . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

$config = require ROOT_PATH . '/config/app.php';
date_default_timezone_set($config['timezone']);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
