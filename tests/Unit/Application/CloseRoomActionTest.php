<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application;

use App\Application\DTO\CloseRoomRequest;
use App\Application\Action\Streamer\CloseRoomAction;
use App\Domain\Entity\Livestream;
use App\Domain\Enum\LivestreamStatus;
use App\Domain\Exception\LivestreamNotFoundException;
use App\Domain\Exception\LivestreamNotLiveException;
use App\Domain\Repository\LivestreamRepositoryInterface;
use App\Domain\Service\AuditLoggerInterface;
use PHPUnit\Framework\TestCase;

final class CloseRoomActionTest extends TestCase
{
    public function test_ends_live_stream(): void
    {
        $stream = Livestream::create(1, 'Test');
        $stream->goLive();

        $repo = $this->createMock(LivestreamRepositoryInterface::class);
        $repo->method('findActiveByStreamerIdForUpdate')->willReturn($stream);
        $repo->method('save')->willReturnArgument(0);

        $useCase = new CloseRoomAction($repo, $this->createMock(AuditLoggerInterface::class));
        $result  = $useCase->execute(new CloseRoomRequest(1));

        $this->assertSame(LivestreamStatus::ENDED, $result->getStatus());
        $this->assertNotNull($result->getEndedAt());
    }

    public function test_throws_when_no_active_room(): void
    {
        $this->expectException(LivestreamNotFoundException::class);

        $repo = $this->createMock(LivestreamRepositoryInterface::class);
        $repo->method('findActiveByStreamerIdForUpdate')->willReturn(null);

        (new CloseRoomAction($repo, $this->createMock(AuditLoggerInterface::class)))
            ->execute(new CloseRoomRequest(1));
    }

    public function test_throws_when_stream_is_only_created(): void
    {
        $this->expectException(LivestreamNotLiveException::class);

        $stream = Livestream::create(1, 'Test'); // still CREATED, not LIVE

        $repo = $this->createMock(LivestreamRepositoryInterface::class);
        $repo->method('findActiveByStreamerIdForUpdate')->willReturn($stream);
        $repo->method('save')->willReturnArgument(0);

        (new CloseRoomAction($repo, $this->createMock(AuditLoggerInterface::class)))
            ->execute(new CloseRoomRequest(1));
    }
}
