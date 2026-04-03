<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Domain\Entity\Livestream;
use App\Domain\Enum\LivestreamStatus;
use App\Infrastructure\Persistence\MySQLLivestreamRepository;

final class MySQLLivestreamRepositoryTest extends IntegrationTestCase
{
    private $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new MySQLLivestreamRepository($this->pdo);
        $this->seedUser(1);
        $this->seedUser(2);
    }

    // -------------------------------------------------------------------------
    // insert + findById
    // -------------------------------------------------------------------------

    public function test_save_inserts_new_livestream_and_returns_with_id(): void
    {
        $ls     = Livestream::create(1, 'My Stream');
        $saved  = $this->repo->save($ls);

        $this->assertNotNull($saved->getId());
        $this->assertSame(1, $saved->getStreamerId());
        $this->assertSame('My Stream', $saved->getTitle());
        $this->assertSame(LivestreamStatus::CREATED, $saved->getStatus());
        $this->assertSame(0, $saved->getViewerCount());
        $this->assertNull($saved->getStartedAt());
    }

    public function test_find_by_id_returns_null_for_missing_stream(): void
    {
        $this->assertNull($this->repo->findById(99999));
    }

    // -------------------------------------------------------------------------
    // update via save
    // -------------------------------------------------------------------------

    public function test_save_updates_status_when_going_live(): void
    {
        $ls    = $this->repo->save(Livestream::create(1, 'Stream'));
        $ls->goLive();
        $updated = $this->repo->save($ls);

        $this->assertSame(LivestreamStatus::LIVE, $updated->getStatus());
        $this->assertNotNull($updated->getStartedAt());
        $this->assertNull($updated->getEndedAt());
    }

    public function test_save_updates_status_when_ending(): void
    {
        $ls = $this->repo->save(Livestream::create(1, 'Stream'));
        $ls->goLive();
        $ls = $this->repo->save($ls);
        $ls->end();
        $updated = $this->repo->save($ls);

        $this->assertSame(LivestreamStatus::ENDED, $updated->getStatus());
        $this->assertNotNull($updated->getEndedAt());
    }

    public function test_save_updates_viewer_count(): void
    {
        $ls = $this->repo->save(Livestream::create(1, 'Stream'));
        $ls->goLive();
        $ls = $this->repo->save($ls);
        $ls->incrementViewerCount();
        $ls->incrementViewerCount();
        $updated = $this->repo->save($ls);

        $this->assertSame(2, $updated->getViewerCount());
    }

    public function test_decrement_viewer_count_does_not_go_below_zero(): void
    {
        $ls = $this->repo->save(Livestream::create(1, 'Stream'));
        $ls->goLive();
        $ls = $this->repo->save($ls);
        $ls->decrementViewerCount();
        $updated = $this->repo->save($ls);

        $this->assertSame(0, $updated->getViewerCount());
    }

    // -------------------------------------------------------------------------
    // findActiveByStreamerId
    // -------------------------------------------------------------------------

    public function test_find_active_returns_created_stream(): void
    {
        $this->repo->save(Livestream::create(1, 'Stream'));
        $found = $this->repo->findActiveByStreamerId(1);

        $this->assertNotNull($found);
        $this->assertSame(LivestreamStatus::CREATED, $found->getStatus());
    }

    public function test_find_active_returns_live_stream(): void
    {
        $ls = $this->repo->save(Livestream::create(1, 'Stream'));
        $ls->goLive();
        $this->repo->save($ls);

        $found = $this->repo->findActiveByStreamerId(1);
        $this->assertNotNull($found);
        $this->assertSame(LivestreamStatus::LIVE, $found->getStatus());
    }

    public function test_find_active_returns_null_after_stream_ended(): void
    {
        $ls = $this->repo->save(Livestream::create(1, 'Stream'));
        $ls->goLive();
        $ls = $this->repo->save($ls);
        $ls->end();
        $this->repo->save($ls);

        $this->assertNull($this->repo->findActiveByStreamerId(1));
    }

    public function test_find_active_only_returns_own_stream(): void
    {
        $this->repo->save(Livestream::create(1, 'Streamer 1'));
        $this->assertNull($this->repo->findActiveByStreamerId(2));
    }

    // -------------------------------------------------------------------------
    // findPaginated + countFiltered
    // -------------------------------------------------------------------------

    public function test_find_paginated_returns_all_streams(): void
    {
        $this->repo->save(Livestream::create(1, 'Stream A'));
        $this->repo->save(Livestream::create(2, 'Stream B'));

        $results = $this->repo->findPaginated(null, 'created_at', 'ASC', 10, 0);
        $this->assertCount(2, $results);
    }

    public function test_find_paginated_filters_by_status(): void
    {
        $ls = $this->repo->save(Livestream::create(1, 'Stream A'));
        $ls->goLive();
        $this->repo->save($ls);
        $this->repo->save(Livestream::create(2, 'Stream B'));

        $live    = $this->repo->findPaginated(LivestreamStatus::LIVE, 'created_at', 'DESC', 10, 0);
        $created = $this->repo->findPaginated(LivestreamStatus::CREATED, 'created_at', 'DESC', 10, 0);

        $this->assertCount(1, $live);
        $this->assertSame(LivestreamStatus::LIVE, $live[0]->getStatus());
        $this->assertCount(1, $created);
    }

    public function test_find_paginated_respects_limit_and_offset(): void
    {
        $this->repo->save(Livestream::create(1, 'Stream A'));
        $this->repo->save(Livestream::create(2, 'Stream B'));

        $page1 = $this->repo->findPaginated(null, 'created_at', 'ASC', 1, 0);
        $page2 = $this->repo->findPaginated(null, 'created_at', 'ASC', 1, 1);

        $this->assertCount(1, $page1);
        $this->assertCount(1, $page2);
        $this->assertNotSame($page1[0]->getId(), $page2[0]->getId());
    }

    public function test_count_filtered_returns_correct_total(): void
    {
        $ls = $this->repo->save(Livestream::create(1, 'Stream A'));
        $ls->goLive();
        $this->repo->save($ls);
        $this->repo->save(Livestream::create(2, 'Stream B'));

        $this->assertSame(2, $this->repo->countFiltered(null));
        $this->assertSame(1, $this->repo->countFiltered(LivestreamStatus::LIVE));
        $this->assertSame(1, $this->repo->countFiltered(LivestreamStatus::CREATED));
        $this->assertSame(0, $this->repo->countFiltered(LivestreamStatus::ENDED));
    }

    // -------------------------------------------------------------------------
    // archiveEnded
    // -------------------------------------------------------------------------

    public function test_archive_ended_moves_old_ended_streams(): void
    {
        $ls = $this->repo->save(Livestream::create(1, 'Stream'));
        $ls->goLive();
        $ls = $this->repo->save($ls);
        $ls->end();
        $this->repo->save($ls);

        // Set ended_at to the past so the archive threshold catches it
        $this->pdo->exec(
            "UPDATE livestreams SET ended_at = DATE_SUB(NOW(), INTERVAL 2 HOUR) WHERE status = 'ENDED'"
        );

        $count = $this->repo->archiveEnded(new \DateTimeImmutable('-1 hour'), 100);

        $this->assertSame(1, $count);

        $archived = $this->repo->findById($ls->getId());
        $this->assertSame(LivestreamStatus::ARCHIVED, $archived->getStatus());
        $this->assertNotNull($archived->getArchivedAt());
    }

    public function test_archive_ended_does_not_touch_recent_ended_streams(): void
    {
        $ls = $this->repo->save(Livestream::create(1, 'Stream'));
        $ls->goLive();
        $ls = $this->repo->save($ls);
        $ls->end();
        $this->repo->save($ls);

        // ended_at is just now, threshold is 1 hour ago — should NOT be archived
        $count = $this->repo->archiveEnded(new \DateTimeImmutable('-1 hour'), 100);

        $this->assertSame(0, $count);
    }

    public function test_archive_ended_respects_batch_size(): void
    {
        for ($i = 1; $i <= 3; $i++) {
            $this->seedUser(10 + $i);
            $ls = $this->repo->save(Livestream::create(10 + $i, "Stream {$i}"));
            $ls->goLive();
            $ls = $this->repo->save($ls);
            $ls->end();
            $this->repo->save($ls);
        }

        $this->pdo->exec(
            "UPDATE livestreams SET ended_at = DATE_SUB(NOW(), INTERVAL 2 HOUR) WHERE status = 'ENDED'"
        );

        $count = $this->repo->archiveEnded(new \DateTimeImmutable('-1 hour'), 2);

        $this->assertSame(2, $count);
    }
}
