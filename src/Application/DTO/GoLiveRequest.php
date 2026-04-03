<?php

declare(strict_types=1);

namespace App\Application\DTO;

final class GoLiveRequest
{
    public $streamerId;

    public function __construct(int $streamerId)
    {
        $this->streamerId = $streamerId;
    }
}
