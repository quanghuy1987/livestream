<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Entity\LivestreamViewer;

interface LivestreamViewerRepositoryInterface
{
    public function findActiveViewer(int $livestreamId, int $userId): ?LivestreamViewer;

    public function save(LivestreamViewer $viewer): LivestreamViewer;

    public function markLeft(int $livestreamId, int $userId): void;

    public function countActive(int $livestreamId): int;
}
