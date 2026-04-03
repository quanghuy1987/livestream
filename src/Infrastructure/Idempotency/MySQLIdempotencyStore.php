<?php

declare(strict_types=1);

namespace App\Infrastructure\Idempotency;

use App\Domain\Service\IdempotencyStoreInterface;

final class MySQLIdempotencyStore implements IdempotencyStoreInterface
{
    private $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function get(string $key, string $endpoint, int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT response_status, response_body
             FROM idempotency_keys
             WHERE idempotency_key = :key AND endpoint = :ep AND user_id = :uid AND expires_at > NOW()
             LIMIT 1'
        );
        $stmt->execute([':key' => $key, ':ep' => $endpoint, ':uid' => $userId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function store(
        string $key,
        string $endpoint,
        int $userId,
        int $httpStatus,
        string $responseBody,
        \DateTimeImmutable $expiresAt
    ): void {
        $sql = "INSERT IGNORE INTO idempotency_keys
                    (idempotency_key, endpoint, user_id, response_status, response_body, created_at, expires_at)
                VALUES (:key, :ep, :uid, :status, :body, NOW(), :expires_at)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':key'        => $key,
            ':ep'         => $endpoint,
            ':uid'        => $userId,
            ':status'     => $httpStatus,
            ':body'       => $responseBody,
            ':expires_at' => $expiresAt->format('Y-m-d H:i:s'),
        ]);
    }
}
