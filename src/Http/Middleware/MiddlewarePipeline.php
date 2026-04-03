<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Request;
use App\Http\Response;

final class MiddlewarePipeline
{
    private $middlewares = [];

    public function pipe(MiddlewareInterface $middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    public function run(Request $request, callable $handler): Response
    {
        $stack = $handler;
        foreach (array_reverse($this->middlewares) as $middleware) {
            $next  = $stack;
            $stack = function (Request $req) use ($middleware, $next): Response {
                return $middleware->process($req, $next);
            };
        }
        return $stack($request);
    }
}
