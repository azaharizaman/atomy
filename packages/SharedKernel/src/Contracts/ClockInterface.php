<?php

declare(strict_types=1);

namespace Nexus\SharedKernel\Contracts;

use DateTimeImmutable;

/**
 * Clock Interface
 *
 * Provides current time for testability.
 * Allows mocking time in tests without relying on system clock.
 *
 * Consuming applications should provide implementation using their
 * preferred clock mechanism (system clock, frozen clock for tests, etc.).
 */
interface ClockInterface
{
    /**
     * Get the current time
     *
     * @return DateTimeImmutable Current time
     */
    public function now(): DateTimeImmutable;
}
