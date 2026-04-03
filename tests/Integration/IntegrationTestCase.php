<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use PHPUnit\Framework\TestCase;

abstract class IntegrationTestCase extends TestCase
{
    protected $pdo;

    protected function setUp(): void
    {
        $host     = getenv('DB_HOST')     ?: 'db';
        $port     = getenv('DB_PORT')     ?: '3306';
        $dbname   = getenv('DB_DATABASE') ?: 'livestream_test';
        $username = getenv('DB_USERNAME') ?: 'livestream';
        $password = getenv('DB_PASSWORD') ?: 'secret';

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

        $this->pdo = new \PDO($dsn, $username, $password, [
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES   => false,
        ]);

        $this->pdo->beginTransaction();
    }

    protected function tearDown(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    protected function seedUser(int $id, string $role = 'streamer'): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO users (id, token, username, role) VALUES (:id, :token, :username, :role)"
        );
        $stmt->execute([
            ':id'       => $id,
            ':token'    => 'token_' . $id,
            ':username' => 'user_' . $id,
            ':role'     => $role,
        ]);
    }
}
