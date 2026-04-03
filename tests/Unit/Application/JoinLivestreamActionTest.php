<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application;

use App\Application\DTO\JoinLivestreamRequest;
use App\Application\Action\Audience\JoinLivestreamAction;
use App\Domain\Entity\Livestream;
use App\Domain\Exception\LivestreamNotFoundException;
use App\Domain\Exception\LivestreamNotLiveException;
use App\Domain\Exception\ViewerAlreadyJoinedException;
use App\Domain\Repository\LivestreamRepositoryInterface;
use App\Domain\Repository\LivestreamViewerRepositoryInterface;
use App\Domain\Service\AuditLoggerInterface;
use PHPUnit\Framework\TestCase;

final class JoinLivestreamActionTest extends TestCase
{
    private function makeLiveStream(): Livestream
    {
        $stream = Livestream::create(99, 'Live Stream');
        $stream->goLive();
        return $stream;
    }

    public function test_joins_live_stream_successfully(): void
    {
        $stream = $this->makeLiveStream();

        $livestreamRepo = $this->createMock(LivestreamRepositoryInterface::class);
        $livestreamRepo->method('findByIdForUpdate')->willReturn($stream);
        $livestreamRepo->method('save')->willReturnArgument(0);

        $viewerRepo = $this->createMock(LivestreamViewerRepositoryInterface::class);
        $viewerRepo->method('findActiveViewer')->willReturn(null);
        $viewerRepo->method('save')->willReturnArgument(0);

        $useCase = new JoinLivestreamAction(
            $livestreamRepo,
            $viewerRepo,
            $this->createMock(AuditLoggerInterface::class)
        );

        $result = $useCase->execute(new JoinLivestreamRequest(1, 42));

        $this->assertArrayHasKey('livestream', $result);
        $this->assertArrayHasKey('joined_at', $result);
        $this->assertSame(1, $result['livestream']['viewer_count']);
    }

    public function test_throws_when_stream_not_found(): void
    {
        $this->expectException(LivestreamNotFoundException::class);

        $livestreamRepo = $this->createMock(LivestreamRepositoryInterface::class);
        $livestreamRepo->method('findByIdForUpdate')->willReturn(null);

        (new JoinLivestreamAction(
            $livestreamRepo,
            $this->createMock(LivestreamViewerRepositoryInterface::class),
            $this->createMock(AuditLoggerInterface::class)
        ))->execute(new JoinLivestreamRequest(999, 42));
    }

    public function test_throws_when_stream_not_live(): void
    {
        $this->expectException(LivestreamNotLiveException::class);

        $stream = Livestream::create(99, 'Not live');

        $livestreamRepo = $this->createMock(LivestreamRepositoryInterface::class);
        $livestreamRepo->method('findByIdForUpdate')->willReturn($stream);

        (new JoinLivestreamAction(
            $livestreamRepo,
            $this->createMock(LivestreamViewerRepositoryInterface::class),
            $this->createMock(AuditLoggerInterface::class)
        ))->execute(new JoinLivestreamRequest(1, 42));
    }

    public function test_throws_when_viewer_already_joined(): void
    {
        $this->expectException(ViewerAlreadyJoinedException::class);

        $stream = $this->makeLiveStream();
        $viewer = \App\Domain\Entity\LivestreamViewer::create(1, 42);

        $livestreamRepo = $this->createMock(LivestreamRepositoryInterface::class);
        $livestreamRepo->method('findByIdForUpdate')->willReturn($stream);

        $viewerRepo = $this->createMock(LivestreamViewerRepositoryInterface::class);
        $viewerRepo->method('findActiveViewer')->willReturn($viewer);

        (new JoinLivestreamAction(
            $livestreamRepo,
            $viewerRepo,
            $this->createMock(AuditLoggerInterface::class)
        ))->execute(new JoinLivestreamRequest(1, 42));
    }
}
