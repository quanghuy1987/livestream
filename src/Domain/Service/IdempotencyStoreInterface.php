<?php

declare(strict_types=1);

namespace App\Domain\Service;

interface IdempotencyStoreInterface
{
    /**
     * Returns ['response_status' => int, 'response_body' => string] or null if not found.
     */
    public function get(string $key, string $endpoint, int $userId): ?array;

    public function store(
        string $key,
        string $endpoint,
        int $userId,
        int $httpStatus,
        string $responseBody,
        \DateTimeImmutable $expiresAt
    ): void;
}
