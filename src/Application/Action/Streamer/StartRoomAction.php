<?php

declare(strict_types=1);

namespace App\Application\Action\Streamer;

use App\Application\DTO\StartRoomRequest;
use App\Domain\Entity\Livestream;
use App\Domain\Enum\AuditEvent;
use App\Domain\Exception\LivestreamAlreadyActiveException;
use App\Domain\Repository\LivestreamRepositoryInterface;
use App\Domain\Service\AuditLoggerInterface;

final class StartRoomAction
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

    public function execute(StartRoomRequest $request): Livestream
    {
        $existing = $this->livestreamRepo->findActiveByStreamerIdForUpdate($request->streamerId);
        if ($existing !== null) {
            throw new LivestreamAlreadyActiveException(
                'You already have an active livestream session.'
            );
        }

        $livestream = Livestream::create($request->streamerId, $request->title);
        $saved      = $this->livestreamRepo->save($livestream);

        $this->auditLogger->log(AuditEvent::STREAM_CREATED, $request->streamerId, $saved->getId());

        return $saved;
    }
}
