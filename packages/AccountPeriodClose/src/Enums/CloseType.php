<?php

declare(strict_types=1);

namespace Nexus\AccountPeriodClose\Enums;

/**
 * Type of period close.
 */
enum CloseType: string
{
    case MONTHLY = 'monthly';
    case QUARTERLY = 'quarterly';
    case YEARLY = 'yearly';
    case INTERIM = 'interim';
}
