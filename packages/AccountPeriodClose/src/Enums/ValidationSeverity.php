<?php

declare(strict_types=1);

namespace Nexus\AccountPeriodClose\Enums;

/**
 * Validation severity levels.
 */
enum ValidationSeverity: string
{
    case INFO = 'info';
    case WARNING = 'warning';
    case ERROR = 'error';
    case CRITICAL = 'critical';
}
