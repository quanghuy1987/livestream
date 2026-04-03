<?php

declare(strict_types=1);

namespace App\Application\DTO;

final class ListLivestreamsRequest
{
    public $status;
    public $sort;
    public $dir;
    public $limit;
    public $page;

    public function __construct(?string $status, string $sort, string $dir, int $limit, int $page)
    {
        $this->status = $status;
        $this->sort   = $sort;
        $this->dir    = $dir;
        $this->limit  = $limit;
        $this->page   = $page;
    }
}
