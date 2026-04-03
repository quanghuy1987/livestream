<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application;

use App\Application\DTO\StartRoomRequest;
use App\Application\Action\Streamer\StartRoomAction;
use App\Domain\Entity\Livestream;
use App\Domain\Enum\LivestreamStatus;
use App\Domain\Exception\LivestreamAlreadyActiveException;
use App\Domain\Repository\LivestreamRepositoryInterface;
use App\Domain\Service\AuditLoggerInterface;
use PHPUnit\Framework\TestCase;

final class StartRoomActionTest extends TestCase
{
    private function makeAction(
        ?Livestream $existingStream,
        ?Livestream $savedStream = null
    ): StartRoomAction {
        $repo   = $this->createMock(LivestreamRepositoryInterface::class);
        $logger = $this->createMock(AuditLoggerInterface::class);

        $repo->method('findActiveByStreamerIdForUpdate')->willReturn($existingStream);

        if ($savedStream !== null) {
            $repo->method('save')->willReturn($savedStream);
        } else {
            $repo->method('save')->willReturnArgument(0);
        }

        return new StartRoomAction($repo, $logger);
    }

    public function test_creates_room_when_no_active_stream(): void
    {
        $useCase  = $this->makeAction(null);
        $request  = new StartRoomRequest(1, 'My Stream');
        $result   = $useCase->execute($request);

        $this->assertSame(LivestreamStatus::CREATED, $result->getStatus());
        $this->assertSame(1, $result->getStreamerId());
        $this->assertSame('My Stream', $result->getTitle());
    }

    public function test_throws_when_active_stream_exists(): void
    {
        $this->expectException(LivestreamAlreadyActiveException::class);

        $existing = Livestream::create(1, 'Existing');
        $useCase  = $this->makeAction($existing);
        $useCase->execute(new StartRoomRequest(1, 'New Stream'));
    }

    public function test_audit_log_is_called_on_success(): void
    {
        $repo   = $this->createMock(LivestreamRepositoryInterface::class);
        $logger = $this->createMock(AuditLoggerInterface::class);

        $repo->method('findActiveByStreamerIdForUpdate')->willReturn(null);
        $repo->method('save')->willReturnArgument(0);

        $logger->expects($this->once())->method('log');

        $useCase = new StartRoomAction($repo, $logger);
        $useCase->execute(new StartRoomRequest(1, 'My Stream'));
    }

    public function test_throws_on_empty_title(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new StartRoomRequest(1, '');
    }
}
