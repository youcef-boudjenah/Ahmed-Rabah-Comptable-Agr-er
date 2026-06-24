<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

/** @var \App\Core\Router $router */
$router = require dirname(__DIR__) . '/routes.php';
$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'] ?? '/');
