<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Entity\Livestream;
use App\Domain\Repository\LivestreamRepositoryInterface;

final class MySQLLivestreamRepository implements LivestreamRepositoryInterface
{
    private $pdo;

    private const ALLOWED_SORTS = ['viewer_count', 'created_at', 'started_at'];

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function findById(int $id): ?Livestream
    {
        $stmt = $this->pdo->prepare('SELECT * FROM livestreams WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ? Livestream::mapToEntity($row) : null;
    }

    public function findByIdForUpdate(int $id): ?Livestream
    {
        $stmt = $this->pdo->prepare('SELECT * FROM livestreams WHERE id = :id LIMIT 1 FOR UPDATE');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ? Livestream::mapToEntity($row) : null;
    }

    public function findActiveByStreamerId(int $streamerId): ?Livestream
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM livestreams
             WHERE streamer_id = :sid AND status IN ('CREATED','LIVE')
             LIMIT 1"
        );
        $stmt->execute([':sid' => $streamerId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ? Livestream::mapToEntity($row) : null;
    }

    public function findActiveByStreamerIdForUpdate(int $streamerId): ?Livestream
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM livestreams
             WHERE streamer_id = :sid AND status IN ('CREATED','LIVE')
             LIMIT 1 FOR UPDATE"
        );
        $stmt->execute([':sid' => $streamerId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ? Livestream::mapToEntity($row) : null;
    }

    public function save(Livestream $livestream): Livestream
    {
        if ($livestream->getId() === null) {
            return $this->insert($livestream);
        }
        return $this->update($livestream);
    }

    private function insert(Livestream $ls): Livestream
    {
        $sql = "INSERT INTO livestreams
                    (streamer_id, title, status, viewer_count, started_at, ended_at, archived_at, created_at, updated_at)
                VALUES
                    (:streamer_id, :title, :status, :viewer_count, :started_at, :ended_at, :archived_at, :created_at, :updated_at)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->bindParams($ls));

        return $this->findById((int) $this->pdo->lastInsertId());
    }

    private function update(Livestream $ls): Livestream
    {
        $sql = "UPDATE livestreams
                SET status        = :status,
                    viewer_count  = :viewer_count,
                    started_at    = :started_at,
                    ended_at      = :ended_at,
                    archived_at   = :archived_at,
                    updated_at    = :updated_at
                WHERE id = :id";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':status'       => $ls->getStatus(),
            ':viewer_count' => $ls->getViewerCount(),
            ':started_at'   => $ls->getStartedAt()  ? $ls->getStartedAt()->format('Y-m-d H:i:s')  : null,
            ':ended_at'     => $ls->getEndedAt()    ? $ls->getEndedAt()->format('Y-m-d H:i:s')    : null,
            ':archived_at'  => $ls->getArchivedAt() ? $ls->getArchivedAt()->format('Y-m-d H:i:s') : null,
            ':updated_at'   => $ls->getUpdatedAt()->format('Y-m-d H:i:s'),
            ':id'           => $ls->getId(),
        ]);

        return $this->findById($ls->getId());
    }

    private function bindParams(Livestream $ls): array
    {
        return [
            ':streamer_id'  => $ls->getStreamerId(),
            ':title'        => $ls->getTitle(),
            ':status'       => $ls->getStatus(),
            ':viewer_count' => $ls->getViewerCount(),
            ':started_at'   => $ls->getStartedAt()  ? $ls->getStartedAt()->format('Y-m-d H:i:s')  : null,
            ':ended_at'     => $ls->getEndedAt()    ? $ls->getEndedAt()->format('Y-m-d H:i:s')    : null,
            ':archived_at'  => $ls->getArchivedAt() ? $ls->getArchivedAt()->format('Y-m-d H:i:s') : null,
            ':created_at'   => $ls->getCreatedAt()->format('Y-m-d H:i:s'),
            ':updated_at'   => $ls->getUpdatedAt()->format('Y-m-d H:i:s'),
        ];
    }

    public function findPaginated(?string $status, string $sort, string $dir, int $limit, int $offset): array
    {
        $sort = in_array($sort, self::ALLOWED_SORTS, true) ? $sort : 'created_at';
        $dir  = strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC';

        $where  = '';
        $params = [];
        if ($status !== null) {
            $where             = 'WHERE status = :status';
            $params[':status'] = $status;
        }

        $params[':limit']  = $limit;
        $params[':offset'] = $offset;

        $sql  = "SELECT * FROM livestreams {$where} ORDER BY {$sort} {$dir} LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $key => $value) {
            $type = is_int($value) ? \PDO::PARAM_INT : \PDO::PARAM_STR;
            $stmt->bindValue($key, $value, $type);
        }
        $stmt->execute();

        $rows   = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $result = [];
        foreach ($rows as $row) {
            $result[] = Livestream::mapToEntity($row);
        }
        return $result;
    }

    public function countFiltered(?string $status): int
    {
        $where  = '';
        $params = [];
        if ($status !== null) {
            $where             = 'WHERE status = :status';
            $params[':status'] = $status;
        }

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM livestreams {$where}");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public function archiveEnded(\DateTimeImmutable $olderThan, int $batchSize): int
    {
        $sql = "UPDATE livestreams
                SET status = 'ARCHIVED', archived_at = NOW(), updated_at = NOW()
                WHERE status = 'ENDED' AND ended_at <= :older_than
                LIMIT :batch";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':older_than', $olderThan->format('Y-m-d H:i:s'), \PDO::PARAM_STR);
        $stmt->bindValue(':batch', $batchSize, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount();
    }
}
