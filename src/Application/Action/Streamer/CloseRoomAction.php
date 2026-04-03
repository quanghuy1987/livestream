<?php

declare(strict_types=1);

namespace App\Application\Action\Streamer;

use App\Application\DTO\CloseRoomRequest;
use App\Domain\Entity\Livestream;
use App\Domain\Enum\AuditEvent;
use App\Domain\Exception\LivestreamNotFoundException;
use App\Domain\Repository\LivestreamRepositoryInterface;
use App\Domain\Service\AuditLoggerInterface;

final class CloseRoomAction
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

    public function execute(CloseRoomRequest $request): Livestream
    {
        $livestream = $this->livestreamRepo->findActiveByStreamerIdForUpdate($request->streamerId);
        if ($livestream === null) {
            throw new LivestreamNotFoundException('No active room found for this streamer.');
        }

        $livestream->end();
        $saved = $this->livestreamRepo->save($livestream);

        $this->auditLogger->log(AuditEvent::STREAM_ENDED, $request->streamerId, $saved->getId());

        return $saved;
    }
}
