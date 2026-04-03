<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\DTO\JoinLivestreamRequest;
use App\Application\DTO\LeaveLivestreamRequest;
use App\Application\DTO\ListLivestreamsRequest;
use App\Application\Action\Audience\GetLivestreamStatsAction;
use App\Application\Action\Audience\GetLivestreamAction;
use App\Application\Action\Audience\JoinLivestreamAction;
use App\Application\Action\Audience\LeaveLivestreamAction;
use App\Application\Action\Audience\ListLivestreamsAction;
use App\Domain\Entity\User;
use App\Http\ErrorHandler;
use App\Http\JsonResponse;
use App\Http\Request;
use App\Http\Response;
use App\Infrastructure\Persistence\TransactionManager;

final class AudienceController extends BaseController
{
    private $listLivestreams;
    private $getLivestream;
    private $joinLivestream;
    private $leaveLivestream;
    private $getStats;
    private $txManager;

    public function __construct(
        ErrorHandler $errorHandler,
        ListLivestreamsAction $listLivestreams,
        GetLivestreamAction $getLivestream,
        JoinLivestreamAction $joinLivestream,
        LeaveLivestreamAction $leaveLivestream,
        GetLivestreamStatsAction $getStats,
        TransactionManager $txManager
    ) {
        parent::__construct($errorHandler);
        $this->listLivestreams  = $listLivestreams;
        $this->getLivestream    = $getLivestream;
        $this->joinLivestream   = $joinLivestream;
        $this->leaveLivestream  = $leaveLivestream;
        $this->getStats         = $getStats;
        $this->txManager        = $txManager;
    }

    public function list(Request $request): Response
    {
        $self = $this;
        return $this->tryRun(function () use ($request, $self) {
            $dto    = new ListLivestreamsRequest(
                $request->getQueryParam('status'),
                $request->getQueryParam('sort', 'created_at'),
                $request->getQueryParam('dir', 'DESC'),
                (int) $request->getQueryParam('limit', 20),
                (int) $request->getQueryParam('page', 1)
            );
            $result = $self->listLivestreams->execute($dto);
            return JsonResponse::ok($result);
        });
    }

    public function show(Request $request): Response
    {
        $self = $this;
        return $this->tryRun(function () use ($request, $self) {
            $id         = (int) $request->getRouteParam('id');
            $livestream = $self->getLivestream->execute($id);
            return JsonResponse::ok($livestream->toArray());
        });
    }

    public function join(Request $request): Response
    {
        /** @var User $user */
        $user = $request->getAttribute('user');

        $self = $this;
        return $this->tryRun(function () use ($request, $user, $self) {
            $id     = (int) $request->getRouteParam('id');
            $dto    = new JoinLivestreamRequest($id, $user->getId());
            $result = $self->txManager->run(function () use ($self, $dto) {
                return $self->joinLivestream->execute($dto);
            });
            return JsonResponse::ok($result);
        });
    }

    public function leave(Request $request): Response
    {
        /** @var User $user */
        $user = $request->getAttribute('user');

        $self = $this;
        return $this->tryRun(function () use ($request, $user, $self) {
            $id     = (int) $request->getRouteParam('id');
            $dto    = new LeaveLivestreamRequest($id, $user->getId());
            $result = $self->txManager->run(function () use ($self, $dto) {
                return $self->leaveLivestream->execute($dto);
            });
            return JsonResponse::ok($result);
        });
    }

    public function stats(Request $request): Response
    {
        $self = $this;
        return $this->tryRun(function () use ($request, $self) {
            $id     = (int) $request->getRouteParam('id');
            $result = $self->getStats->execute($id);
            return JsonResponse::ok($result);
        });
    }
}
