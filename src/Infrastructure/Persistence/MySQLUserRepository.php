<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Entity\User;
use App\Domain\Repository\UserRepositoryInterface;

final class MySQLUserRepository implements UserRepositoryInterface
{
    private $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findByToken(string $token): ?User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE token = :token LIMIT 1');
        $stmt->execute([':token' => $token]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ? User::mapToEntity($row) : null;
    }
}
