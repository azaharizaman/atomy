<?php

declare(strict_types=1);

namespace Nexus\LeaveManagement\Enums;

enum LeaveDuration: string
{
    case FULL_DAY = 'full_day';
    case HALF_DAY_AM = 'half_day_am';
    case HALF_DAY_PM = 'half_day_pm';
    case HOURS = 'hours';
}
