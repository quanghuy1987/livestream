<?php

declare(strict_types=1);

namespace App\Infrastructure\Logging;

use App\Domain\Service\AuditLoggerInterface;

final class MySQLAuditLogger implements AuditLoggerInterface
{
    private $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function log(string $event, ?int $userId, ?int $livestreamId, array $payload = []): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO audit_logs (event, user_id, livestream_id, payload, created_at)
             VALUES (:event, :user_id, :livestream_id, :payload, NOW())'
        );
        $stmt->execute([
            ':event'         => $event,
            ':user_id'       => $userId,
            ':livestream_id' => $livestreamId,
            ':payload'       => empty($payload) ? null : json_encode($payload),
        ]);
    }
}
