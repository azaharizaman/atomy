<?php

declare(strict_types=1);

namespace Nexus\Connector\ValueObjects;

/**
 * Integration request/response status.
 */
enum IntegrationStatus: string
{
    case SUCCESS = 'success';
    case FAILED = 'failed';
    case TIMEOUT = 'timeout';
    case RATE_LIMITED = 'rate_limited';
    case CIRCUIT_OPEN = 'circuit_open';
}
