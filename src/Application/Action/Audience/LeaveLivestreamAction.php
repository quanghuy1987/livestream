<?php

declare(strict_types=1);

namespace App\Application\Action\Audience;

use App\Application\DTO\LeaveLivestreamRequest;
use App\Domain\Enum\AuditEvent;
use App\Domain\Exception\LivestreamNotFoundException;
use App\Domain\Exception\ViewerNotJoinedException;
use App\Domain\Repository\LivestreamRepositoryInterface;
use App\Domain\Repository\LivestreamViewerRepositoryInterface;
use App\Domain\Service\AuditLoggerInterface;

final class LeaveLivestreamAction
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

    public function execute(LeaveLivestreamRequest $request): array
    {
        $livestream = $this->livestreamRepo->findByIdForUpdate($request->livestreamId);
        if ($livestream === null) {
            throw new LivestreamNotFoundException("Livestream #{$request->livestreamId} not found.");
        }

        $viewer = $this->viewerRepo->findActiveViewer($request->livestreamId, $request->userId);
        if ($viewer === null) {
            throw new ViewerNotJoinedException('You are not currently watching this livestream.');
        }

        $this->viewerRepo->markLeft($request->livestreamId, $request->userId);

        $livestream->decrementViewerCount();
        $saved = $this->livestreamRepo->save($livestream);

        $this->auditLogger->log(AuditEvent::USER_LEFT, $request->userId, $request->livestreamId);

        return ['livestream' => $saved->toArray()];
    }
}
