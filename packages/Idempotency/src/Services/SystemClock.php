<?php

declare(strict_types=1);

namespace Nexus\Idempotency\Services;

use DateTimeImmutable;
use DateTimeZone;
use Nexus\Idempotency\Contracts\IdempotencyClockInterface;

final readonly class SystemClock implements IdempotencyClockInterface
{
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }
}
