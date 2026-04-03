<?php

declare(strict_types=1);

namespace App\Application\Action\Audience;

use App\Application\DTO\JoinLivestreamRequest;
use App\Domain\Entity\LivestreamViewer;
use App\Domain\Enum\AuditEvent;
use App\Domain\Enum\LivestreamStatus;
use App\Domain\Exception\LivestreamNotFoundException;
use App\Domain\Exception\LivestreamNotLiveException;
use App\Domain\Exception\ViewerAlreadyJoinedException;
use App\Domain\Repository\LivestreamRepositoryInterface;
use App\Domain\Repository\LivestreamViewerRepositoryInterface;
use App\Domain\Service\AuditLoggerInterface;

final class JoinLivestreamAction
{
    private $livestreamRepo;
    private $viewerRepo;
    private $auditLogger;

    public function __construct(
        LivestreamRepositoryInterface $livestreamRepo,
        LivestreamViewerRepositoryInterface $viewerRepo,
        AuditLoggerInterface $auditLogger
    ) {
        $this->livestreamRepo = $livestreamRepo;
        $this->viewerRepo     = $viewerRepo;
        $this->auditLogger    = $auditLogger;
    }

    public function execute(JoinLivestreamRequest $request): array
    {
        $livestream = $this->livestreamRepo->findByIdForUpdate($request->livestreamId);
        if ($livestream === null) {
            throw new LivestreamNotFoundException("Livestream #{$request->livestreamId} not found.");
        }

        if ($livestream->getStatus() !== LivestreamStatus::LIVE) {
            throw new LivestreamNotLiveException('You can only join a LIVE stream.');
        }

        $existing = $this->viewerRepo->findActiveViewer($request->livestreamId, $request->userId);
        if ($existing !== null) {
            throw new ViewerAlreadyJoinedException('You have already joined this livestream.');
        }

        $viewer = LivestreamViewer::create($request->livestreamId, $request->userId);
        $this->viewerRepo->save($viewer);

        $livestream->incrementViewerCount();
        $saved = $this->livestreamRepo->save($livestream);

        $this->auditLogger->log(AuditEvent::USER_JOINED, $request->userId, $request->livestreamId);

        return [
            'livestream' => $saved->toArray(),
            'joined_at'  => $viewer->getJoinedAt()->format('Y-m-d H:i:s'),
        ];
    }
}
