<?php

declare(strict_types=1);

namespace App\Application\Action\Audience;

use App\Application\DTO\ListLivestreamsRequest;
use App\Domain\Enum\LivestreamStatus;
use App\Domain\Repository\LivestreamRepositoryInterface;

final class ListLivestreamsAction
{
    private const ALLOWED_SORTS = ['viewer_count', 'created_at', 'started_at'];

    private $livestreamRepo;

    public function __construct(LivestreamRepositoryInterface $livestreamRepo)
    {
        $this->livestreamRepo = $livestreamRepo;
    }

    public function execute(ListLivestreamsRequest $request): array
    {
        $status = $request->status !== null && LivestreamStatus::isValid($request->status)
            ? $request->status
            : null;

        $sort   = in_array($request->sort, self::ALLOWED_SORTS, true) ? $request->sort : 'created_at';
        $dir    = strtoupper($request->dir) === 'ASC' ? 'ASC' : 'DESC';
        $limit  = min(max($request->limit, 1), 100);
        $page   = max($request->page, 1);
        $offset = ($page - 1) * $limit;

        $items = $this->livestreamRepo->findPaginated($status, $sort, $dir, $limit, $offset);
        $total = $this->livestreamRepo->countFiltered($status);

        $data = [];
        foreach ($items as $ls) {
            $data[] = $ls->toArray();
        }

        return [
            'data' => $data,
            'meta' => [
                'total'       => $total,
                'page'        => $page,
                'limit'       => $limit,
                'total_pages' => (int) ceil($total / $limit),
            ],
        ];
    }
}
