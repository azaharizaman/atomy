<?php

declare(strict_types=1);

namespace Nexus\InsightOperations\DTOs;

enum MetricStatus: string
{
    case AVAILABLE = 'available';
    case NOT_AVAILABLE = 'not_available';
    case ERROR = 'error';
}
