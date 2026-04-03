<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application;

use App\Application\DTO\GoLiveRequest;
use App\Application\Action\Streamer\GoLiveAction;
use App\Domain\Entity\Livestream;
use App\Domain\Enum\LivestreamStatus;
use App\Domain\Exception\LivestreamAlreadyActiveException;
use App\Domain\Exception\LivestreamNotFoundException;
use App\Domain\Repository\LivestreamRepositoryInterface;
use App\Domain\Service\AuditLoggerInterface;
use PHPUnit\Framework\TestCase;

final class GoLiveActionTest extends TestCase
{
    public function test_transitions_created_stream_to_live(): void
    {
        $stream = Livestream::create(1, 'Test');

        $repo = $this->createMock(LivestreamRepositoryInterface::class);
        $repo->method('findActiveByStreamerIdForUpdate')->willReturn($stream);
        $repo->method('save')->willReturnArgument(0);

        $useCase = new GoLiveAction($repo, $this->createMock(AuditLoggerInterface::class));
        $result  = $useCase->execute(new GoLiveRequest(1));

        $this->assertSame(LivestreamStatus::LIVE, $result->getStatus());
        $this->assertNotNull($result->getStartedAt());
    }

    public function test_throws_when_no_active_room(): void
    {
        $this->expectException(LivestreamNotFoundException::class);

        $repo = $this->createMock(LivestreamRepositoryInterface::class);
        $repo->method('findActiveByStreamerIdForUpdate')->willReturn(null);

        (new GoLiveAction($repo, $this->createMock(AuditLoggerInterface::class)))
            ->execute(new GoLiveRequest(1));
    }

    public function test_throws_when_already_live(): void
    {
        $this->expectException(LivestreamAlreadyActiveException::class);

        $stream = Livestream::create(1, 'Test');
        $stream->goLive(); // already LIVE

        $repo = $this->createMock(LivestreamRepositoryInterface::class);
        $repo->method('findActiveByStreamerIdForUpdate')->willReturn($stream);
        $repo->method('save')->willReturnArgument(0);

        (new GoLiveAction($repo, $this->createMock(AuditLoggerInterface::class)))
            ->execute(new GoLiveRequest(1));
    }
}
