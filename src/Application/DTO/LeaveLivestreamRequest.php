<?php

declare(strict_types=1);

namespace App\Application\DTO;

final class LeaveLivestreamRequest
{
    public $livestreamId;
    public $userId;

    public function __construct(int $livestreamId, int $userId)
    {
        $this->livestreamId = $livestreamId;
        $this->userId       = $userId;
    }
}
