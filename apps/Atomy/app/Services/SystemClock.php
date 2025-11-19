<?php

declare(strict_types=1);

namespace App\Services;

use DateTimeImmutable;
use Nexus\Scheduler\Contracts\ClockInterface;

/**
 * System Clock
 *
 * Production implementation of ClockInterface using system time.
 */
final class SystemClock implements ClockInterface
{
    /**
     * Get the current time
     */
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }
}
