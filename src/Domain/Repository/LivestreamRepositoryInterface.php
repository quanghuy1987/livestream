<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\Livestream;

interface LivestreamRepositoryInterface
{
    public function findById(int $id): ?Livestream;

    public function findByIdForUpdate(int $id): ?Livestream;

    public function findActiveByStreamerId(int $streamerId): ?Livestream;

    public function findActiveByStreamerIdForUpdate(int $streamerId): ?Livestream;

    public function save(Livestream $livestream): Livestream;

    /**
     * @return Livestream[]
     */
    public function findPaginated(
        ?string $status,
        string $sort,
        string $dir,
        int $limit,
        int $offset
    ): array;

    public function countFiltered(?string $status): int;

    public function archiveEnded(\DateTimeImmutable $olderThan, int $batchSize): int;
}
