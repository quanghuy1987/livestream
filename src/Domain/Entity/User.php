<?php

declare(strict_types=1);

namespace App\Domain\Entity;

final class User
{
    private $id;
    private $token;
    private $username;
    private $role;
    private $createdAt;

    private function __construct() {}

    public static function mapToEntity(array $row): self
    {
        $user            = new self();
        $user->id        = (int) $row['id'];
        $user->token     = $row['token'];
        $user->username  = $row['username'];
        $user->role      = $row['role'];
        $user->createdAt = new \DateTimeImmutable($row['created_at']);
        return $user;
    }

    public function getId(): int       { return $this->id; }
    public function getToken(): string { return $this->token; }
    public function getUsername(): string { return $this->username; }
    public function getRole(): string  { return $this->role; }
    public function isStreamer(): bool { return $this->role === 'streamer'; }

    public function toArray(): array
    {
        return [
            'id'         => $this->id,
            'username'   => $this->username,
            'role'       => $this->role,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }
}
