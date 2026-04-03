<?php

declare(strict_types=1);

namespace App\Http;

final class JsonResponse extends Response
{
    public function __construct($data, int $statusCode = 200)
    {
        parent::__construct(
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            $statusCode,
            ['Content-Type' => 'application/json']
        );
    }

    public static function ok($data): self
    {
        return new self(['success' => true, 'data' => $data], 200);
    }

    public static function created($data): self
    {
        return new self(['success' => true, 'data' => $data], 201);
    }

    public static function notFound(string $message = 'Not found.'): self
    {
        return new self(['success' => false, 'message' => $message], 404);
    }

    public static function unauthorized(string $message = 'Unauthorized.'): self
    {
        return new self(['success' => false, 'message' => $message], 401);
    }

    public static function forbidden(string $message = 'Forbidden.'): self
    {
        return new self(['success' => false, 'message' => $message], 403);
    }

    public static function conflict(string $message): self
    {
        return new self(['success' => false, 'message' => $message], 409);
    }

    public static function unprocessable(string $message): self
    {
        return new self(['success' => false, 'message' => $message], 422);
    }

    public static function tooManyRequests(string $message = 'Too many requests.'): self
    {
        return new self(['success' => false, 'message' => $message], 429);
    }

    public static function badRequest(string $message): self
    {
        return new self(['success' => false, 'message' => $message], 400);
    }

    public static function serverError(string $message = 'Internal server error.'): self
    {
        return new self(['success' => false, 'message' => $message], 500);
    }
}
