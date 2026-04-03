<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Enum\LivestreamStatus;
use App\Domain\Exception\LivestreamAlreadyActiveException;
use App\Domain\Exception\LivestreamNotLiveException;

final class Livestream
{
    private $id;
    private $streamerId;
    private $title;
    private $status;
    private $viewerCount;
    private $startedAt;
    private $endedAt;
    private $archivedAt;
    private $createdAt;
    private $updatedAt;

    private function __construct() {}

    public static function create(int $streamerId, string $title): self
    {
        $ls              = new self();
        $ls->id          = null;
        $ls->streamerId  = $streamerId;
        $ls->title       = trim($title);
        $ls->status      = LivestreamStatus::CREATED;
        $ls->viewerCount = 0;
        $ls->startedAt   = null;
        $ls->endedAt     = null;
        $ls->archivedAt  = null;
        $ls->createdAt   = new \DateTimeImmutable();
        $ls->updatedAt   = new \DateTimeImmutable();
        return $ls;
    }

    public static function mapToEntity(array $row): self
    {
        $ls              = new self();
        $ls->id          = (int) $row['id'];
        $ls->streamerId  = (int) $row['streamer_id'];
        $ls->title       = $row['title'];
        $ls->status      = $row['status'];
        $ls->viewerCount = (int) $row['viewer_count'];
        $ls->startedAt   = $row['started_at']  ? new \DateTimeImmutable($row['started_at'])  : null;
        $ls->endedAt     = $row['ended_at']    ? new \DateTimeImmutable($row['ended_at'])    : null;
        $ls->archivedAt  = $row['archived_at'] ? new \DateTimeImmutable($row['archived_at']) : null;
        $ls->createdAt   = new \DateTimeImmutable($row['created_at']);
        $ls->updatedAt   = new \DateTimeImmutable($row['updated_at']);
        return $ls;
    }

    public function goLive(): void
    {
        if ($this->status !== LivestreamStatus::CREATED) {
            throw new LivestreamAlreadyActiveException(
                "Cannot go live from status '{$this->status}'. Stream must be in CREATED state."
            );
        }
        $this->status    = LivestreamStatus::LIVE;
        $this->startedAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function end(): void
    {
        if ($this->status !== LivestreamStatus::LIVE) {
            throw new LivestreamNotLiveException(
                "Cannot end a stream with status '{$this->status}'. Stream must be LIVE."
            );
        }
        $this->status    = LivestreamStatus::ENDED;
        $this->endedAt   = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function archive(): void
    {
        if ($this->status !== LivestreamStatus::ENDED) {
            return;
        }
        $this->status     = LivestreamStatus::ARCHIVED;
        $this->archivedAt = new \DateTimeImmutable();
        $this->updatedAt  = new \DateTimeImmutable();
    }

    public function incrementViewerCount(): void
    {
        $this->viewerCount++;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function decrementViewerCount(): void
    {
        $this->viewerCount = max(0, $this->viewerCount - 1);
        $this->updatedAt   = new \DateTimeImmutable();
    }

    public function isActive(): bool
    {
        return in_array($this->status, LivestreamStatus::active(), true);
    }

    public function getId(): ?int                         { return $this->id; }
    public function getStreamerId(): int                  { return $this->streamerId; }
    public function getTitle(): string                    { return $this->title; }
    public function getStatus(): string                   { return $this->status; }
    public function getViewerCount(): int                 { return $this->viewerCount; }
    public function getStartedAt(): ?\DateTimeImmutable   { return $this->startedAt; }
    public function getEndedAt(): ?\DateTimeImmutable     { return $this->endedAt; }
    public function getArchivedAt(): ?\DateTimeImmutable  { return $this->archivedAt; }
    public function getCreatedAt(): \DateTimeImmutable    { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable    { return $this->updatedAt; }

    public function toArray(): array
    {
        return [
            'id'           => $this->id,
            'streamer_id'  => $this->streamerId,
            'title'        => $this->title,
            'status'       => $this->status,
            'viewer_count' => $this->viewerCount,
            'started_at'   => $this->startedAt  ? $this->startedAt->format('Y-m-d H:i:s')  : null,
            'ended_at'     => $this->endedAt    ? $this->endedAt->format('Y-m-d H:i:s')    : null,
            'archived_at'  => $this->archivedAt ? $this->archivedAt->format('Y-m-d H:i:s') : null,
            'created_at'   => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at'   => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
