<?php

declare(strict_types=1);

namespace App\Http;

class Response
{
    protected $statusCode;
    protected $headers;
    protected $body;

    public function __construct(string $body = '', int $statusCode = 200, array $headers = [])
    {
        $this->body       = $body;
        $this->statusCode = $statusCode;
        $this->headers    = $headers;
    }

    public function getStatusCode(): int { return $this->statusCode; }
    public function getBody(): string    { return $this->body; }
    public function getHeaders(): array  { return $this->headers; }

    public function send(): void
    {
        http_response_code($this->statusCode);
        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }
        echo $this->body;
    }
}
