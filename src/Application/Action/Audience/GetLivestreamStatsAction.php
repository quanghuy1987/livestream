<?php

declare(strict_types=1);

namespace App\Application\Action\Audience;

use App\Domain\Exception\LivestreamNotFoundException;
use App\Domain\Repository\LivestreamRepositoryInterface;
use App\Domain\Repository\LivestreamViewerRepositoryInterface;

final class GetLivestreamStatsAction
{
    private $livestreamRepo;
    private $viewerRepo;

    public function __construct(
        LivestreamRepositoryInterface $livestreamRepo,
        LivestreamViewerRepositoryInterface $viewerRepo
    ) {
        $this->livestreamRepo = $livestreamRepo;
        $this->viewerRepo     = $viewerRepo;
    }

    public function execute(int $livestreamId): array
    {
        $livestream = $this->livestreamRepo->findById($livestreamId);
        if ($livestream === null) {
            throw new LivestreamNotFoundException("Livestream #{$livestreamId} not found.");
        }

        $activeViewers = $this->viewerRepo->countActive($livestreamId);

        return [
            'livestream_id'  => $livestream->getId(),
            'title'          => $livestream->getTitle(),
            'status'         => $livestream->getStatus(),
            'viewer_count'   => $livestream->getViewerCount(),
            'active_viewers' => $activeViewers,
            'started_at'     => $livestream->getStartedAt()
                ? $livestream->getStartedAt()->format('Y-m-d H:i:s')
                : null,
            'ended_at'       => $livestream->getEndedAt()
                ? $livestream->getEndedAt()->format('Y-m-d H:i:s')
                : null,
        ];
    }
}
