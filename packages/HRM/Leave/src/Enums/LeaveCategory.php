<?php

declare(strict_types=1);

namespace Nexus\Leave\Enums;

enum LeaveCategory: string
{
    case ANNUAL = 'annual';
    case SICK = 'sick';
    case MATERNITY = 'maternity';
    case PATERNITY = 'paternity';
    case UNPAID = 'unpaid';
    case COMPASSIONATE = 'compassionate';
    case STUDY = 'study';
    case OTHER = 'other';
}
