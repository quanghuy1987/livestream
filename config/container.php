<?php

declare(strict_types=1);

use App\Application\Action\Audience\GetLivestreamStatsAction;
use App\Application\Action\Audience\GetLivestreamAction;
use App\Application\Action\Audience\JoinLivestreamAction;
use App\Application\Action\Audience\LeaveLivestreamAction;
use App\Application\Action\Audience\ListLivestreamsAction;
use App\Application\Action\Streamer\CloseRoomAction;
use App\Application\Action\Streamer\GetMyRoomAction;
use App\Application\Action\Streamer\GoLiveAction;
use App\Application\Action\Streamer\StartRoomAction;
use App\Http\Controller\AudienceController;
use App\Http\Controller\StreamerController;
use App\Http\ErrorHandler;
use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\IdempotencyMiddleware;
use App\Http\Middleware\MiddlewarePipeline;
use App\Http\Middleware\RateLimitMiddleware;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Router;
use App\Infrastructure\Idempotency\MySQLIdempotencyStore;
use App\Infrastructure\Logging\MySQLAuditLogger;
use App\Infrastructure\Persistence\MySQLLivestreamRepository;
use App\Infrastructure\Persistence\MySQLLivestreamViewerRepository;
use App\Infrastructure\Persistence\MySQLUserRepository;
use App\Infrastructure\Persistence\TransactionManager;
use App\Infrastructure\RateLimiter\MySQLRateLimiter;

// ── Database ─────────────────────────────────────────────────────────────────
$pdoFactory = require __DIR__ . '/database.php';
$pdo        = $pdoFactory();

// ── Infrastructure ────────────────────────────────────────────────────────────
$userRepo       = new MySQLUserRepository($pdo);
$livestreamRepo = new MySQLLivestreamRepository($pdo);
$viewerRepo     = new MySQLLivestreamViewerRepository($pdo);
$auditLogger    = new MySQLAuditLogger($pdo);
$rateLimiter    = new MySQLRateLimiter($pdo);
$idemStore      = new MySQLIdempotencyStore($pdo);
$txManager      = new TransactionManager($pdo);

// ── Use Cases ─────────────────────────────────────────────────────────────────
$startRoomAction  = new StartRoomAction($livestreamRepo, $auditLogger);
$goLiveAction     = new GoLiveAction($livestreamRepo, $auditLogger);
$closeRoomAction  = new CloseRoomAction($livestreamRepo, $auditLogger);
$getMyRoomAction  = new GetMyRoomAction($livestreamRepo);

$listLivestreamsAction = new ListLivestreamsAction($livestreamRepo);
$getLivestreamAction   = new GetLivestreamAction($livestreamRepo);
$joinLivestreamAction  = new JoinLivestreamAction($livestreamRepo, $viewerRepo, $auditLogger);
$leaveLivestreamAction = new LeaveLivestreamAction($livestreamRepo, $viewerRepo, $auditLogger);
$getStatsAction        = new GetLivestreamStatsAction($livestreamRepo, $viewerRepo);

// ── HTTP ──────────────────────────────────────────────────────────────────────
$errorHandler = new ErrorHandler();

$streamerController = new StreamerController(
    $errorHandler,
    $startRoomAction,
    $goLiveAction,
    $closeRoomAction,
    $getMyRoomAction,
    $txManager
);

$audienceController = new AudienceController(
    $errorHandler,
    $listLivestreamsAction,
    $getLivestreamAction,
    $joinLivestreamAction,
    $leaveLivestreamAction,
    $getStatsAction,
    $txManager
);

// ── Middleware ────────────────────────────────────────────────────────────────
$authMiddleware    = new AuthMiddleware($userRepo);
$streamerOnly      = new RoleMiddleware('streamer');
$audienceOnly      = new RoleMiddleware('audience');

// ── Router ────────────────────────────────────────────────────────────────────
$router = new Router();

// Helper: build a pipeline for a handler with optional extra middlewares
$route = function (callable $handler, array $extra = []) use ($authMiddleware, $errorHandler): callable {
    return function ($request) use ($handler, $authMiddleware, $extra, $errorHandler) {
        $pipeline = new MiddlewarePipeline();
        $pipeline->pipe($authMiddleware);
        foreach ($extra as $mw) {
            $pipeline->pipe($mw);
        }
        try {
            return $pipeline->run($request, $handler);
        } catch (\Throwable $e) {
            return $errorHandler->handle($e);
        }
    };
};

// Streamer routes
$router->add('POST', '/streamer/start_room', $route(
    [$streamerController, 'startRoom'],
    [
        $streamerOnly,
        new RateLimitMiddleware($rateLimiter, 'start_room', 5, 60),
        new IdempotencyMiddleware($idemStore, 'start_room'),
    ]
));

$router->add('POST', '/streamer/go_live', $route(
    [$streamerController, 'goLive'],
    [$streamerOnly]
));

$router->add('POST', '/streamer/close_room', $route(
    [$streamerController, 'closeRoom'],
    [
        $streamerOnly,
        new IdempotencyMiddleware($idemStore, 'close_room'),
    ]
));

$router->add('GET', '/streamer/my_room', $route(
    [$streamerController, 'myRoom'],
    [$streamerOnly]
));

// Audience routes
$router->add('GET', '/audience/livestreams', $route(
    [$audienceController, 'list'],
    [$audienceOnly]
));

$router->add('GET', '/audience/livestreams/{id}', $route(
    [$audienceController, 'show'],
    [$audienceOnly]
));

$router->add('POST', '/audience/livestreams/{id}/join', $route(
    [$audienceController, 'join'],
    [
        $audienceOnly,
        new RateLimitMiddleware($rateLimiter, 'join', 20, 1),
    ]
));

$router->add('POST', '/audience/livestreams/{id}/leave', $route(
    [$audienceController, 'leave'],
    [$audienceOnly]
));

$router->add('GET', '/audience/livestreams/{id}/stats', $route(
    [$audienceController, 'stats'],
    [$audienceOnly]
));

return $router;
