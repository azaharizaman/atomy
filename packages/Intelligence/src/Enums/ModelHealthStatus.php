<?php

declare(strict_types=1);

namespace Nexus\Intelligence\Enums;

/**
 * Model health status
 */
enum ModelHealthStatus: string
{
    case HEALTHY = 'healthy';
    case WARNING = 'warning';
    case DEGRADED = 'degraded';
    case CRITICAL = 'critical';
    case OFFLINE = 'offline';
}
