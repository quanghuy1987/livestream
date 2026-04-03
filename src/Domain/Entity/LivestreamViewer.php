<?php

declare(strict_types=1);

namespace App\Domain\Entity;

final class LivestreamViewer
{
    private $id;
    private $livestreamId;
    private $userId;
    private $joinedAt;
    private $leftAt;
    private $isActive;

    private function __construct() {}

    public static function create(int $livestreamId, int $userId): self
    {
        $v               = new self();
        $v->id           = null;
        $v->livestreamId = $livestreamId;
        $v->userId       = $userId;
        $v->joinedAt     = new \DateTimeImmutable();
        $v->leftAt       = null;
        $v->isActive     = true;
        return $v;
    }

    public static function mapToEntity(array $row): self
    {
        $v               = new self();
        $v->id           = (int) $row['id'];
        $v->livestreamId = (int) $row['livestream_id'];
        $v->userId       = (int) $row['user_id'];
        $v->joinedAt     = new \DateTimeImmutable($row['joined_at']);
        $v->leftAt       = $row['left_at'] ? new \DateTimeImmutable($row['left_at']) : null;
        $v->isActive     = (bool) $row['is_active'];
        return $v;
    }

    public function leave(): void
    {
        $this->isActive = false;
        $this->leftAt   = new \DateTimeImmutable();
    }

    public function getId(): ?int                       { return $this->id; }
    public function getLivestreamId(): int              { return $this->livestreamId; }
    public function getUserId(): int                    { return $this->userId; }
    public function getJoinedAt(): \DateTimeImmutable   { return $this->joinedAt; }
    public function getLeftAt(): ?\DateTimeImmutable    { return $this->leftAt; }
    public function isActive(): bool                    { return $this->isActive; }

    public function toArray(): array
    {
        return [
            'id'            => $this->id,
            'livestream_id' => $this->livestreamId,
            'user_id'       => $this->userId,
            'joined_at'     => $this->joinedAt->format('Y-m-d H:i:s'),
            'left_at'       => $this->leftAt ? $this->leftAt->format('Y-m-d H:i:s') : null,
            'is_active'     => $this->isActive,
        ];
    }
}
