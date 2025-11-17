<?php

declare(strict_types=1);

namespace Nexus\Payroll\ValueObjects;

/**
 * Component type value object.
 */
enum ComponentType: string
{
    case EARNING = 'earning';
    case DEDUCTION = 'deduction';
    case CONTRIBUTION = 'contribution';
    
    public function label(): string
    {
        return match($this) {
            self::EARNING => 'Earning',
            self::DEDUCTION => 'Deduction',
            self::CONTRIBUTION => 'Employer Contribution',
        };
    }
}
