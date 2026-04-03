<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Entity;

use App\Domain\Entity\Livestream;
use App\Domain\Enum\LivestreamStatus;
use App\Domain\Exception\LivestreamAlreadyActiveException;
use App\Domain\Exception\LivestreamNotLiveException;
use PHPUnit\Framework\TestCase;

final class LivestreamTest extends TestCase
{
    public function test_create_returns_created_status(): void
    {
        $ls = Livestream::create(1, 'My Stream');

        $this->assertNull($ls->getId());
        $this->assertSame(1, $ls->getStreamerId());
        $this->assertSame('My Stream', $ls->getTitle());
        $this->assertSame(LivestreamStatus::CREATED, $ls->getStatus());
        $this->assertSame(0, $ls->getViewerCount());
        $this->assertTrue($ls->isActive());
    }

    public function test_go_live_transitions_to_live(): void
    {
        $ls = Livestream::create(1, 'Test');
        $ls->goLive();

        $this->assertSame(LivestreamStatus::LIVE, $ls->getStatus());
        $this->assertNotNull($ls->getStartedAt());
        $this->assertTrue($ls->isActive());
    }

    public function test_end_transitions_from_live_to_ended(): void
    {
        $ls = Livestream::create(1, 'Test');
        $ls->goLive();
        $ls->end();

        $this->assertSame(LivestreamStatus::ENDED, $ls->getStatus());
        $this->assertNotNull($ls->getEndedAt());
        $this->assertFalse($ls->isActive());
    }

    public function test_archive_transitions_from_ended_to_archived(): void
    {
        $ls = Livestream::create(1, 'Test');
        $ls->goLive();
        $ls->end();
        $ls->archive();

        $this->assertSame(LivestreamStatus::ARCHIVED, $ls->getStatus());
        $this->assertNotNull($ls->getArchivedAt());
    }

    public function test_cannot_go_live_from_non_created_state(): void
    {
        $this->expectException(LivestreamAlreadyActiveException::class);

        $ls = Livestream::create(1, 'Test');
        $ls->goLive();
        $ls->goLive(); // should throw
    }

    public function test_cannot_end_from_created_state(): void
    {
        $this->expectException(LivestreamNotLiveException::class);

        $ls = Livestream::create(1, 'Test');
        $ls->end(); // should throw — not LIVE
    }

    public function test_cannot_end_from_ended_state(): void
    {
        $this->expectException(LivestreamNotLiveException::class);

        $ls = Livestream::create(1, 'Test');
        $ls->goLive();
        $ls->end();
        $ls->end(); // should throw
    }

    public function test_archive_is_no_op_if_not_ended(): void
    {
        $ls = Livestream::create(1, 'Test');
        $ls->goLive();
        $ls->archive(); // should not throw, just be ignored

        $this->assertSame(LivestreamStatus::LIVE, $ls->getStatus());
    }

    public function test_increment_viewer_count(): void
    {
        $ls = Livestream::create(1, 'Test');
        $ls->incrementViewerCount();
        $ls->incrementViewerCount();

        $this->assertSame(2, $ls->getViewerCount());
    }

    public function test_decrement_viewer_count_does_not_go_below_zero(): void
    {
        $ls = Livestream::create(1, 'Test');
        $ls->decrementViewerCount();

        $this->assertSame(0, $ls->getViewerCount());
    }

    public function test_to_array_returns_correct_structure(): void
    {
        $ls   = Livestream::create(1, 'Test Stream');
        $data = $ls->toArray();

        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('streamer_id', $data);
        $this->assertArrayHasKey('title', $data);
        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('viewer_count', $data);
        $this->assertSame('Test Stream', $data['title']);
        $this->assertSame('CREATED', $data['status']);
    }
}
