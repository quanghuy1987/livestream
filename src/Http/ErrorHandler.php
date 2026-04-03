<?php

declare(strict_types=1);

namespace App\Http;

use App\Domain\Exception\LivestreamAlreadyActiveException;
use App\Domain\Exception\LivestreamNotFoundException;
use App\Domain\Exception\LivestreamNotLiveException;
use App\Domain\Exception\RateLimitExceededException;
use App\Domain\Exception\ViewerAlreadyJoinedException;
use App\Domain\Exception\ViewerNotJoinedException;

final class ErrorHandler
{
    public function handle(\Throwable $e): Response
    {
        if ($e instanceof LivestreamNotFoundException) {
            return JsonResponse::notFound($e->getMessage());
        }

        if ($e instanceof LivestreamAlreadyActiveException || $e instanceof ViewerAlreadyJoinedException) {
            return JsonResponse::conflict($e->getMessage());
        }

        if ($e instanceof LivestreamNotLiveException || $e instanceof ViewerNotJoinedException) {
            return JsonResponse::unprocessable($e->getMessage());
        }

        if ($e instanceof RateLimitExceededException) {
            return JsonResponse::tooManyRequests($e->getMessage());
        }

        if ($e instanceof \InvalidArgumentException) {
            return JsonResponse::badRequest($e->getMessage());
        }

        // Log unexpected errors (in production use proper logger)
        error_log((string) $e);

        return JsonResponse::serverError('An unexpected error occurred.');
    }
}
