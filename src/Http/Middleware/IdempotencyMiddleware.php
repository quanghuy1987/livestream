<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Service\IdempotencyStoreInterface;
use App\Http\JsonResponse;
use App\Http\Request;
use App\Http\Response;

final class IdempotencyMiddleware implements MiddlewareInterface
{
    private $store;
    private $endpoint;

    public function __construct(IdempotencyStoreInterface $store, string $endpoint)
    {
        $this->store    = $store;
        $this->endpoint = $endpoint;
    }

    public function process(Request $request, callable $next): Response
    {
        $key = $request->getHeader('Idempotency-Key');

        if ($key === null) {
            return $next($request);
        }

        $user   = $request->getAttribute('user');
        $cached = $this->store->get($key, $this->endpoint, $user->getId());

        if ($cached !== null) {
            $data = json_decode($cached['response_body'], true);
            return new JsonResponse($data, (int) $cached['response_status']);
        }

        $response = $next($request);

        if ($response->getStatusCode() < 500) {
            $this->store->store(
                $key,
                $this->endpoint,
                $user->getId(),
                $response->getStatusCode(),
                $response->getBody(),
                new \DateTimeImmutable('+24 hours')
            );
        }

        return $response;
    }
}
