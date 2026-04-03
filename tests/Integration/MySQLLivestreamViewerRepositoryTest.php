<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Domain\Entity\Livestream;
use App\Domain\Entity\LivestreamViewer;
use App\Infrastructure\Persistence\MySQLLivestreamRepository;
use App\Infrastructure\Persistence\MySQLLivestreamViewerRepository;

final class MySQLLivestreamViewerRepositoryTest extends IntegrationTestCase
{
    private $streamRepo;
    private $viewerRepo;
    private $livestreamId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->streamRepo = new MySQLLivestreamRepository($this->pdo);
        $this->viewerRepo = new MySQLLivestreamViewerRepository($this->pdo);

        $this->seedUser(1, 'streamer');
        $this->seedUser(2, 'audience');
        $this->seedUser(3, 'audience');

        $ls = $this->streamRepo->save(Livestream::create(1, 'Test Stream'));
        $ls->goLive();
        $ls = $this->streamRepo->save($ls);
        $this->livestreamId = $ls->getId();
    }

    public function test_save_inserts_viewer_and_returns_with_id(): void
    {
        $viewer = LivestreamViewer::create($this->livestreamId, 2);
        $saved  = $this->viewerRepo->save($viewer);

        $this->assertNotNull($saved->getId());
        $this->assertSame($this->livestreamId, $saved->getLivestreamId());
        $this->assertSame(2, $saved->getUserId());
        $this->assertTrue($saved->isActive());
        $this->assertNotNull($saved->getJoinedAt());
        $this->assertNull($saved->getLeftAt());
    }

    public function test_find_active_viewer_returns_joined_viewer(): void
    {
        $this->viewerRepo->save(LivestreamViewer::create($this->livestreamId, 2));

        $found = $this->viewerRepo->findActiveViewer($this->livestreamId, 2);
        $this->assertNotNull($found);
        $this->assertSame(2, $found->getUserId());
        $this->assertTrue($found->isActive());
    }

    public function test_find_active_viewer_returns_null_when_not_joined(): void
    {
        $found = $this->viewerRepo->findActiveViewer($this->livestreamId, 2);
        $this->assertNull($found);
    }

    public function test_find_active_viewer_returns_null_after_leaving(): void
    {
        $this->viewerRepo->save(LivestreamViewer::create($this->livestreamId, 2));
        $this->viewerRepo->markLeft($this->livestreamId, 2);

        $found = $this->viewerRepo->findActiveViewer($this->livestreamId, 2);
        $this->assertNull($found);
    }

    public function test_mark_left_sets_is_active_zero_and_left_at(): void
    {
        $this->viewerRepo->save(LivestreamViewer::create($this->livestreamId, 2));
        $this->viewerRepo->markLeft($this->livestreamId, 2);

        $row = $this->pdo->query(
            "SELECT * FROM livestream_viewers
             WHERE livestream_id = {$this->livestreamId} AND user_id = 2"
        )->fetch(\PDO::FETCH_ASSOC);

        $this->assertSame(0, (int) $row['is_active']);
        $this->assertNotNull($row['left_at']);
    }

    public function test_viewer_can_join_leave_and_rejoin_multiple_times(): void
    {
        // join → leave → join → leave → should not throw
        $this->viewerRepo->save(LivestreamViewer::create($this->livestreamId, 2));
        $this->viewerRepo->markLeft($this->livestreamId, 2);

        $this->viewerRepo->save(LivestreamViewer::create($this->livestreamId, 2));
        $this->viewerRepo->markLeft($this->livestreamId, 2);

        $found = $this->viewerRepo->findActiveViewer($this->livestreamId, 2);
        $this->assertNull($found);

        $rows = $this->pdo->query(
            "SELECT COUNT(*) FROM livestream_viewers
             WHERE livestream_id = {$this->livestreamId} AND user_id = 2"
        )->fetchColumn();
        $this->assertSame(2, (int) $rows);
    }

    public function test_viewer_can_rejoin_after_leaving(): void
    {
        $this->viewerRepo->save(LivestreamViewer::create($this->livestreamId, 2));
        $this->viewerRepo->markLeft($this->livestreamId, 2);
        $this->viewerRepo->save(LivestreamViewer::create($this->livestreamId, 2));

        $found = $this->viewerRepo->findActiveViewer($this->livestreamId, 2);
        $this->assertNotNull($found);
        $this->assertTrue($found->isActive());
    }

    public function test_count_active_returns_correct_number(): void
    {
        $this->viewerRepo->save(LivestreamViewer::create($this->livestreamId, 2));
        $this->viewerRepo->save(LivestreamViewer::create($this->livestreamId, 3));

        $this->assertSame(2, $this->viewerRepo->countActive($this->livestreamId));
    }

    public function test_count_active_decreases_after_leaving(): void
    {
        $this->viewerRepo->save(LivestreamViewer::create($this->livestreamId, 2));
        $this->viewerRepo->save(LivestreamViewer::create($this->livestreamId, 3));
        $this->viewerRepo->markLeft($this->livestreamId, 2);

        $this->assertSame(1, $this->viewerRepo->countActive($this->livestreamId));
    }

}
