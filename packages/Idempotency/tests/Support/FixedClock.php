<?php

declare(strict_types=1);

namespace Nexus\Idempotency\Tests\Support;

use DateTimeImmutable;
use Nexus\Idempotency\Contracts\IdempotencyClockInterface;

final class FixedClock implements IdempotencyClockInterface
{
    public function __construct(
        private DateTimeImmutable $time,
    ) {
    }

    public function setTime(DateTimeImmutable $time): void
    {
        $this->time = $time;
    }

    public function now(): DateTimeImmutable
    {
        return $this->time;
    }
}
