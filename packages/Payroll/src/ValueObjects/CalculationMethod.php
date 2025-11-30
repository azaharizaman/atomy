<?php

declare(strict_types=1);

namespace Nexus\Payroll\ValueObjects;

/**
 * Calculation method value object.
 */
enum CalculationMethod: string
{
    case FIXED_AMOUNT = 'fixed_amount';
    case PERCENTAGE_OF_BASIC = 'percentage_of_basic';
    case PERCENTAGE_OF_GROSS = 'percentage_of_gross';
    case PERCENTAGE_OF_COMPONENT = 'percentage_of_component';
    case FORMULA = 'formula';
    
    public function label(): string
    {
        return match($this) {
            self::FIXED_AMOUNT => 'Fixed Amount',
            self::PERCENTAGE_OF_BASIC => 'Percentage of Basic Salary',
            self::PERCENTAGE_OF_GROSS => 'Percentage of Gross Pay',
            self::PERCENTAGE_OF_COMPONENT => 'Percentage of Another Component',
            self::FORMULA => 'Custom Formula',
        };
    }
}
