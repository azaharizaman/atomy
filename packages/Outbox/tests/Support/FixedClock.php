<?php

declare(strict_types=1);

namespace Nexus\Outbox\Tests\Support;

use DateTimeImmutable;
use Nexus\Outbox\Contracts\OutboxClockInterface;

/**
 * Mutable clock for unit tests (advance "now" without sleeping).
 */
final class FixedClock implements OutboxClockInterface
{
    public function __construct(
        private DateTimeImmutable $now,
    ) {
    }

    public function setNow(DateTimeImmutable $now): void
    {
        $this->now = $now;
    }

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }
}
