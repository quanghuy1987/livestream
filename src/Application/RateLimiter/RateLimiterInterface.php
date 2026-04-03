<?php

declare(strict_types=1);

namespace App\Application\RateLimiter;

interface RateLimiterInterface
{
    /**
     * Returns true if the request is within the allowed limit, false if exceeded.
     */
    public function check(string $action, int $userId, int $maxHits, int $windowSecs): bool;
}
