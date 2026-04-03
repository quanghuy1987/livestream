<?php

declare(strict_types=1);

namespace App\Domain\Service;

interface AuditLoggerInterface
{
    public function log(string $event, ?int $userId, ?int $livestreamId, array $payload = []): void;
}
