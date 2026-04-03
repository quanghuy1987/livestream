<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Entity\LivestreamViewer;
use App\Domain\Repository\LivestreamViewerRepositoryInterface;

final class MySQLLivestreamViewerRepository implements LivestreamViewerRepositoryInterface
{
    private $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findActiveViewer(int $livestreamId, int $userId): ?LivestreamViewer
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM livestream_viewers
             WHERE livestream_id = :lid AND user_id = :uid AND is_active = 1
             LIMIT 1'
        );
        $stmt->execute([':lid' => $livestreamId, ':uid' => $userId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ? LivestreamViewer::mapToEntity($row) : null;
    }

    public function save(LivestreamViewer $viewer): LivestreamViewer
    {
        $sql = "INSERT INTO livestream_viewers (livestream_id, user_id, joined_at, left_at, is_active)
                VALUES (:lid, :uid, :joined_at, :left_at, :is_active)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':lid'       => $viewer->getLivestreamId(),
            ':uid'       => $viewer->getUserId(),
            ':joined_at' => $viewer->getJoinedAt()->format('Y-m-d H:i:s'),
            ':left_at'   => $viewer->getLeftAt() ? $viewer->getLeftAt()->format('Y-m-d H:i:s') : null,
            ':is_active' => $viewer->isActive() ? 1 : 0,
        ]);

        $id  = (int) $this->pdo->lastInsertId();
        $row = $this->pdo->query("SELECT * FROM livestream_viewers WHERE id = {$id}")->fetch(\PDO::FETCH_ASSOC);
        return LivestreamViewer::mapToEntity($row);
    }

    public function markLeft(int $livestreamId, int $userId): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE livestream_viewers
             SET is_active = 0, left_at = NOW()
             WHERE livestream_id = :lid AND user_id = :uid AND is_active = 1'
        );
        $stmt->execute([':lid' => $livestreamId, ':uid' => $userId]);
    }

    public function countActive(int $livestreamId): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM livestream_viewers WHERE livestream_id = :lid AND is_active = 1'
        );
        $stmt->execute([':lid' => $livestreamId]);
        return (int) $stmt->fetchColumn();
    }
}
