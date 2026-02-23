<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\DTOs\FiscalPeriod;

/**
 * Enum representing fiscal period types.
 */
enum PeriodType: string
{
    case MONTHLY = 'monthly';
    case QUARTERLY = 'quarterly';
    case ANNUALLY = 'annually';
}
