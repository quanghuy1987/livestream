<?php

declare(strict_types=1);

namespace App\Application\DTO;

final class StartRoomRequest
{
    public $streamerId;
    public $title;

    public function __construct(int $streamerId, string $title)
    {
        if (trim($title) === '') {
            throw new \InvalidArgumentException('Title is required.');
        }
        $this->streamerId = $streamerId;
        $this->title      = trim($title);
    }
}
