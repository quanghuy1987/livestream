<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\JsonResponse;
use App\Http\Request;
use App\Http\Response;

final class RoleMiddleware implements MiddlewareInterface
{
    private $requiredRole;

    public function __construct(string $requiredRole)
    {
        $this->requiredRole = $requiredRole;
    }

    public function process(Request $request, callable $next): Response
    {
        $user = $request->getAttribute('user');

        if ($user === null || $user->getRole() !== $this->requiredRole) {
            return JsonResponse::forbidden('Access denied. This action requires the ' . $this->requiredRole . ' role.');
        }

        return $next($request);
    }
}
