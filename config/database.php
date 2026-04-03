<?php

declare(strict_types=1);

return function (): \PDO {
    $host     = getenv('DB_HOST')     ?: 'db';
    $port     = getenv('DB_PORT')     ?: '3306';
    $dbname   = getenv('DB_DATABASE') ?: 'livestream';
    $username = getenv('DB_USERNAME') ?: 'livestream';
    $password = getenv('DB_PASSWORD') ?: 'secret';

    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

    $pdo = new \PDO($dsn, $username, $password, [
        \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        \PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    return $pdo;
};
