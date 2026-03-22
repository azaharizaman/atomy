<?php

declare(strict_types=1);

namespace Nexus\Outbox\Contracts;

use Psr\Clock\ClockInterface;

/**
 * Application clock for outbox transitions and lease checks (UTC in {@see SystemClock}).
 * Marker extending PSR-20 {@see ClockInterface} for interoperability.
 */
interface OutboxClockInterface extends ClockInterface
{
}
