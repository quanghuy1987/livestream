<?php

declare(strict_types=1);

namespace App\Application\Action\Streamer;

use App\Application\DTO\GoLiveRequest;
use App\Domain\Entity\Livestream;
use App\Domain\Enum\AuditEvent;
use App\Domain\Exception\LivestreamNotFoundException;
use App\Domain\Repository\LivestreamRepositoryInterface;
use App\Domain\Service\AuditLoggerInterface;

final class GoLiveAction
{
    private $livestreamRepo;
    private $auditLogger;

    public function __construct(
        LivestreamRepositoryInterface $livestreamRepo,
        AuditLoggerInterface $auditLogger
    ) {
        $this->livestreamRepo = $livestreamRepo;
        $this->auditLogger    = $auditLogger;
    }

    public function execute(GoLiveRequest $request): Livestream
    {
        $livestream = $this->livestreamRepo->findActiveByStreamerIdForUpdate($request->streamerId);
        if ($livestream === null) {
            throw new LivestreamNotFoundException('No active room found for this streamer.');
        }

        $livestream->goLive();
        $saved = $this->livestreamRepo->save($livestream);

        $this->auditLogger->log(AuditEvent::STREAM_STARTED, $request->streamerId, $saved->getId());

        return $saved;
    }
}
