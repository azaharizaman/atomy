<?php

declare(strict_types=1);

namespace Nexus\Scheduler\Contracts;

use DateTimeImmutable;

/**
 * Clock Interface
 *
 * Provides current time for testability.
 * Allows mocking time in tests without relying on system clock.
 */
interface ClockInterface
{
    /**
     * Get the current time
     */
    public function now(): DateTimeImmutable;
}
