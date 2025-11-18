<?php

declare(strict_types=1);

namespace Nexus\Period\Enums;

/**
 * Period Type Enum
 * 
 * Defines the independent period types supported by the system.
 * Each type operates independently with its own open/closed periods.
 */
enum PeriodType: string
{
    case Accounting = 'accounting';
    case Inventory = 'inventory';
    case Payroll = 'payroll';
    case Manufacturing = 'manufacturing';

    public function label(): string
    {
        return match($this) {
            self::Accounting => 'Accounting Period',
            self::Inventory => 'Inventory Period',
            self::Payroll => 'Payroll Period',
            self::Manufacturing => 'Manufacturing Period',
        };
    }
}
