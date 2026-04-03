<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\DTO\CloseRoomRequest;
use App\Application\DTO\GoLiveRequest;
use App\Application\DTO\StartRoomRequest;
use App\Application\Action\Streamer\CloseRoomAction;
use App\Application\Action\Streamer\GetMyRoomAction;
use App\Application\Action\Streamer\GoLiveAction;
use App\Application\Action\Streamer\StartRoomAction;
use App\Domain\Entity\User;
use App\Http\ErrorHandler;
use App\Http\JsonResponse;
use App\Http\Request;
use App\Http\Response;
use App\Infrastructure\Persistence\TransactionManager;

final class StreamerController extends BaseController
{
    private $startRoom;
    private $goLive;
    private $closeRoom;
    private $getMyRoom;
    private $txManager;

    public function __construct(
        ErrorHandler $errorHandler,
        StartRoomAction $startRoom,
        GoLiveAction $goLive,
        CloseRoomAction $closeRoom,
        GetMyRoomAction $getMyRoom,
        TransactionManager $txManager
    ) {
        parent::__construct($errorHandler);
        $this->startRoom = $startRoom;
        $this->goLive    = $goLive;
        $this->closeRoom = $closeRoom;
        $this->getMyRoom = $getMyRoom;
        $this->txManager = $txManager;
    }

    public function startRoom(Request $request): Response
    {
        /** @var User $user */
        $user = $request->getAttribute('user');

        $self = $this;
        return $this->tryRun(function () use ($request, $user, $self) {
            if (!$user->isStreamer()) {
                return JsonResponse::forbidden('Only streamers can create rooms.');
            }

            $body = $request->getParsedBody();
            $dto  = new StartRoomRequest($user->getId(), $body['title'] ?? '');

            $livestream = $self->txManager->run(function () use ($self, $dto) {
                return $self->startRoom->execute($dto);
            });

            return JsonResponse::created($livestream->toArray());
        });
    }

    public function goLive(Request $request): Response
    {
        /** @var User $user */
        $user = $request->getAttribute('user');

        $self = $this;
        return $this->tryRun(function () use ($user, $self) {
            if (!$user->isStreamer()) {
                return JsonResponse::forbidden('Only streamers can go live.');
            }

            $dto        = new GoLiveRequest($user->getId());
            $livestream = $self->txManager->run(function () use ($self, $dto) {
                return $self->goLive->execute($dto);
            });

            return JsonResponse::ok($livestream->toArray());
        });
    }

    public function closeRoom(Request $request): Response
    {
        /** @var User $user */
        $user = $request->getAttribute('user');

        $self = $this;
        return $this->tryRun(function () use ($user, $self) {
            if (!$user->isStreamer()) {
                return JsonResponse::forbidden('Only streamers can close rooms.');
            }

            $dto        = new CloseRoomRequest($user->getId());
            $livestream = $self->txManager->run(function () use ($self, $dto) {
                return $self->closeRoom->execute($dto);
            });

            return JsonResponse::ok($livestream->toArray());
        });
    }

    public function myRoom(Request $request): Response
    {
        /** @var User $user */
        $user = $request->getAttribute('user');

        $self = $this;
        return $this->tryRun(function () use ($user, $self) {
            if (!$user->isStreamer()) {
                return JsonResponse::forbidden('Only streamers can view their room.');
            }

            $livestream = $self->getMyRoom->execute($user->getId());
            return JsonResponse::ok($livestream->toArray());
        });
    }
}
