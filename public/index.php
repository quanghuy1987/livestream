<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment from .env if present
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        if (strpos($line, '=') !== false) {
            [$key, $value] = explode('=', $line, 2);
            putenv(trim($key) . '=' . trim($value));
        }
    }
}

/** @var \App\Http\Router $router */
$router  = require __DIR__ . '/../config/container.php';
$request = \App\Http\Request::fromGlobals();

$response = $router->dispatch($request);
$response->send();
