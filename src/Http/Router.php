<?php

declare(strict_types=1);

namespace App\Http;

final class Router
{
    private $routes = [];

    public function add(string $method, string $pattern, callable $handler): void
    {
        $this->routes[] = [
            'method'  => strtoupper($method),
            'regex'   => $this->patternToRegex($pattern),
            'handler' => $handler,
        ];
    }

    public function dispatch(Request $request): Response
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->getMethod()) {
                continue;
            }
            if (preg_match($route['regex'], $request->getPath(), $matches)) {
                $params  = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $request = $request->withRouteParams($params);
                return ($route['handler'])($request);
            }
        }
        return JsonResponse::notFound('Route not found.');
    }

    private function patternToRegex(string $pattern): string
    {
        $escaped = preg_quote($pattern, '#');
        $regex   = preg_replace('/\\\{(\w+)\\\}/', '(?P<$1>[^/]+)', $escaped);
        return '#^' . $regex . '$#';
    }
}
