<?php

declare(strict_types=1);

namespace App\Domain\Enum;

final class AuditEvent
{
    const STREAM_CREATED = 'STREAM_CREATED';
    const STREAM_STARTED = 'STREAM_STARTED';
    const STREAM_ENDED   = 'STREAM_ENDED';
    const USER_JOINED    = 'USER_JOINED';
    const USER_LEFT      = 'USER_LEFT';
}
