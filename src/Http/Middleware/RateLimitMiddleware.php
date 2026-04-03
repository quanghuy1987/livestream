<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Application\RateLimiter\RateLimiterInterface;
use App\Http\JsonResponse;
use App\Http\Request;
use App\Http\Response;

final class RateLimitMiddleware implements MiddlewareInterface
{
    private $rateLimiter;
    private $action;
    private $maxHits;
    private $windowSecs;

    public function __construct(
        RateLimiterInterface $rateLimiter,
        string $action,
        int $maxHits,
        int $windowSecs
    ) {
        $this->rateLimiter = $rateLimiter;
        $this->action      = $action;
        $this->maxHits     = $maxHits;
        $this->windowSecs  = $windowSecs;
    }

    public function process(Request $request, callable $next): Response
    {
        $user    = $request->getAttribute('user');
        $allowed = $this->rateLimiter->check(
            $this->action,
            $user->getId(),
            $this->maxHits,
            $this->windowSecs
        );

        if (!$allowed) {
            return JsonResponse::tooManyRequests(
                "Rate limit exceeded for '{$this->action}'. Try again later."
            );
        }

        return $next($request);
    }
}
