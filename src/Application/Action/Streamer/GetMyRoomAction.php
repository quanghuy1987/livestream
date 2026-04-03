<?php

declare(strict_types=1);

namespace App\Application\Action\Streamer;

use App\Domain\Entity\Livestream;
use App\Domain\Exception\LivestreamNotFoundException;
use App\Domain\Repository\LivestreamRepositoryInterface;

final class GetMyRoomAction
{
    private $livestreamRepo;

    public function __construct(LivestreamRepositoryInterface $livestreamRepo)
    {
        $this->livestreamRepo = $livestreamRepo;
    }

    public function execute(int $streamerId): Livestream
    {
        $livestream = $this->livestreamRepo->findActiveByStreamerId($streamerId);
        if ($livestream === null) {
            throw new LivestreamNotFoundException('No active room found for this streamer.');
        }
        return $livestream;
    }
}
