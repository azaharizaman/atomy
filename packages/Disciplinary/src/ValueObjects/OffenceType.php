<?php

declare(strict_types=1);

namespace Nexus\Disciplinary\ValueObjects;

enum OffenceType: string
{
    case CODE_OF_CONDUCT = 'code_of_conduct';
    case FRAUD = 'fraud';
    case WORKPLACE_HARASSMENT = 'workplace_harassment';
    case SAFETY_VIOLATION = 'safety_violation';
    case ATTENDANCE = 'attendance';
    case PERFORMANCE = 'performance';
    case INSUBORDINATION = 'insubordination';
    case THEFT = 'theft';
    case OTHER = 'other';
}
