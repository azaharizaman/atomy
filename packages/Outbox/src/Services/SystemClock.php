<?php

declare(strict_types=1);

namespace Nexus\Outbox\Services;

use DateTimeImmutable;
use DateTimeZone;
use Nexus\Outbox\Contracts\OutboxClockInterface;

final readonly class SystemClock implements OutboxClockInterface
{
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }
}
