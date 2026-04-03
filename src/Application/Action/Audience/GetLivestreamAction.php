<?php

declare(strict_types=1);

namespace App\Application\Action\Audience;

use App\Domain\Entity\Livestream;
use App\Domain\Exception\LivestreamNotFoundException;
use App\Domain\Repository\LivestreamRepositoryInterface;

final class GetLivestreamAction
{
    private $livestreamRepo;

    public function __construct(LivestreamRepositoryInterface $livestreamRepo)
    {
        $this->livestreamRepo = $livestreamRepo;
    }

    public function execute(int $livestreamId): Livestream
    {
        $livestream = $this->livestreamRepo->findById($livestreamId);
        if ($livestream === null) {
            throw new LivestreamNotFoundException("Livestream #{$livestreamId} not found.");
        }
        return $livestream;
    }
}
