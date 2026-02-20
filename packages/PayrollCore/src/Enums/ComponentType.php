<?php

declare(strict_types=1);

namespace Nexus\PayrollCore\Enums;

enum ComponentType: string
{
    case EARNING = 'earning';
    case DEDUCTION = 'deduction';
    case CONTRIBUTION = 'contribution';
}
