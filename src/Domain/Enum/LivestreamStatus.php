<?php

declare(strict_types=1);

namespace App\Domain\Enum;

final class LivestreamStatus
{
    const CREATED  = 'CREATED';
    const LIVE     = 'LIVE';
    const ENDED    = 'ENDED';
    const ARCHIVED = 'ARCHIVED';

    public static function active(): array
    {
        return [self::CREATED, self::LIVE];
    }

    public static function all(): array
    {
        return [self::CREATED, self::LIVE, self::ENDED, self::ARCHIVED];
    }

    public static function isValid(string $status): bool
    {
        return in_array($status, self::all(), true);
    }
}
