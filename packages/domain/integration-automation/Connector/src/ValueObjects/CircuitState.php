<?php

declare(strict_types=1);

namespace Nexus\Connector\ValueObjects;

/**
 * Circuit breaker states.
 */
enum CircuitState: string
{
    case CLOSED = 'closed';      // Normal operation
    case OPEN = 'open';          // Failing, reject requests
    case HALF_OPEN = 'half_open'; // Testing if service recovered
}
