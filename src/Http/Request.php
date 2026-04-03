<?php

declare(strict_types=1);

namespace App\Http;

final class Request
{
    private $method;
    private $path;
    private $queryParams;
    private $parsedBody;
    private $headers;
    private $routeParams;
    private $attributes;

    private function __construct() {}

    public static function fromGlobals(): self
    {
        $req              = new self();
        $req->method      = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $req->path        = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
        $req->queryParams = $_GET;
        $req->routeParams = [];
        $req->attributes  = [];
        $req->headers = [];
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $name                = str_replace('_', '-', substr($key, 5));
                $req->headers[$name] = $value;
            }
        }
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $req->headers['CONTENT-TYPE'] = $_SERVER['CONTENT_TYPE'];
        }

        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'application/json') !== false) {
            $raw             = file_get_contents('php://input');
            $req->parsedBody = json_decode($raw, true) ?? [];
        } else {
            $req->parsedBody = $_POST;
        }

        return $req;
    }

    public function withRouteParams(array $params): self
    {
        $clone              = clone $this;
        $clone->routeParams = $params;
        return $clone;
    }

    public function withAttribute(string $key, $value): self
    {
        $clone                   = clone $this;
        $clone->attributes[$key] = $value;
        return $clone;
    }

    public function getMethod(): string     { return $this->method; }
    public function getPath(): string       { return $this->path; }
    public function getQueryParams(): array { return $this->queryParams; }
    public function getParsedBody(): array  { return $this->parsedBody; }
    public function getRouteParams(): array { return $this->routeParams; }

    public function getQueryParam(string $key, $default = null)
    {
        return $this->queryParams[$key] ?? $default;
    }

    public function getRouteParam(string $key, $default = null)
    {
        return $this->routeParams[$key] ?? $default;
    }

    public function getHeader(string $name): ?string
    {
        $normalized = strtoupper(str_replace('-', '_', $name));
        return $this->headers[str_replace('_', '-', $normalized)]
            ?? $this->headers[$normalized]
            ?? null;
    }

    public function getAttribute(string $key, $default = null)
    {
        return $this->attributes[$key] ?? $default;
    }
}
