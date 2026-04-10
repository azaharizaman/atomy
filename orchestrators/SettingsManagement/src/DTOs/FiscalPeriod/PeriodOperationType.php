<?php

declare(strict_types=1);

namespace Nexus\SettingsManagement\DTOs\FiscalPeriod;

/**
 * Operation types for period validation.
 */
enum PeriodOperationType: string
{
    case TRANSACTION = 'transaction';
    case ADJUSTMENT = 'adjustment';
    case CLOSING = 'closing';
    case REPORTING = 'reporting';
}
