#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// Load .env
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

$pdoFactory = require __DIR__ . '/../config/database.php';
$pdo        = $pdoFactory();

$livestreamRepo = new \App\Infrastructure\Persistence\MySQLLivestreamRepository($pdo);

$command = $argv[1] ?? null;

switch ($command) {
    case 'livestream:archive':
        $cmd = new \App\Cli\ArchiveLivestreamsCommand($livestreamRepo);
        $cmd->run($argv);
        break;

    default:
        echo "Usage: php bin/console.php <command>\n\n";
        echo "Available commands:\n";
        echo "  livestream:archive [olderThanMinutes=60] [batchSize=100]\n";
        echo "      Move ENDED streams older than N minutes to ARCHIVED status.\n";
        exit(1);
}
