<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application;

use App\Application\DTO\LeaveLivestreamRequest;
use App\Application\Action\Audience\LeaveLivestreamAction;
use App\Domain\Entity\Livestream;
use App\Domain\Entity\LivestreamViewer;
use App\Domain\Exception\LivestreamNotFoundException;
use App\Domain\Exception\ViewerNotJoinedException;
use App\Domain\Repository\LivestreamRepositoryInterface;
use App\Domain\Repository\LivestreamViewerRepositoryInterface;
use App\Domain\Service\AuditLoggerInterface;
use PHPUnit\Framework\TestCase;

final class LeaveLivestreamActionTest extends TestCase
{
    public function test_leaves_stream_and_decrements_count(): void
    {
        $stream = Livestream::create(99, 'Test');
        $stream->goLive();
        $stream->incrementViewerCount();

        $viewer = LivestreamViewer::create(1, 42);

        $livestreamRepo = $this->createMock(LivestreamRepositoryInterface::class);
        $livestreamRepo->method('findByIdForUpdate')->willReturn($stream);
        $livestreamRepo->method('save')->willReturnArgument(0);

        $viewerRepo = $this->createMock(LivestreamViewerRepositoryInterface::class);
        $viewerRepo->method('findActiveViewer')->willReturn($viewer);

        $useCase = new LeaveLivestreamAction(
            $livestreamRepo,
            $viewerRepo,
            $this->createMock(AuditLoggerInterface::class)
        );

        $result = $useCase->execute(new LeaveLivestreamRequest(1, 42));

        $this->assertSame(0, $result['livestream']['viewer_count']);
    }

    public function test_throws_when_stream_not_found(): void
    {
        $this->expectException(LivestreamNotFoundException::class);

        $livestreamRepo = $this->createMock(LivestreamRepositoryInterface::class);
        $livestreamRepo->method('findByIdForUpdate')->willReturn(null);

        (new LeaveLivestreamAction(
            $livestreamRepo,
            $this->createMock(LivestreamViewerRepositoryInterface::class),
            $this->createMock(AuditLoggerInterface::class)
        ))->execute(new LeaveLivestreamRequest(999, 42));
    }

    public function test_throws_when_viewer_not_joined(): void
    {
        $this->expectException(ViewerNotJoinedException::class);

        $stream = Livestream::create(99, 'Test');
        $stream->goLive();

        $livestreamRepo = $this->createMock(LivestreamRepositoryInterface::class);
        $livestreamRepo->method('findByIdForUpdate')->willReturn($stream);

        $viewerRepo = $this->createMock(LivestreamViewerRepositoryInterface::class);
        $viewerRepo->method('findActiveViewer')->willReturn(null);

        (new LeaveLivestreamAction(
            $livestreamRepo,
            $viewerRepo,
            $this->createMock(AuditLoggerInterface::class)
        ))->execute(new LeaveLivestreamRequest(1, 42));
    }
}
