<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Repository\UserRepositoryInterface;
use App\Http\JsonResponse;
use App\Http\Request;
use App\Http\Response;

final class AuthMiddleware implements MiddlewareInterface
{
    private $userRepo;

    public function __construct(UserRepositoryInterface $userRepo)
    {
        $this->userRepo = $userRepo;
    }

    public function process(Request $request, callable $next): Response
    {
        $header = $request->getHeader('Authorization') ?? '';
        if (!preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return JsonResponse::unauthorized('Missing or invalid Authorization header.');
        }

        $user = $this->userRepo->findByToken(trim($matches[1]));
        if ($user === null) {
            return JsonResponse::unauthorized('Invalid token.');
        }

        return $next($request->withAttribute('user', $user));
    }
}
