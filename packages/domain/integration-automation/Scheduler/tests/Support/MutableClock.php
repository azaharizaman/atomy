<?php

declare(strict_types=1);

namespace Nexus\Scheduler\Tests\Support;

use DateTimeImmutable;
use Nexus\Scheduler\Contracts\ClockInterface;

final class MutableClock implements ClockInterface
{
    public function __construct(private DateTimeImmutable $now)
    {
    }

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }

    public function set(DateTimeImmutable $moment): void
    {
        $this->now = $moment;
    }

    public function advance(string $modifier): void
    {
        $this->now = $this->now->modify($modifier);
    }
}
