<?php

declare(strict_types=1);

namespace Nexus\ShiftManagement\Enums;

enum ShiftType: string
{
    case MORNING = 'morning';
    case AFTERNOON = 'afternoon';
    case NIGHT = 'night';
    case ROTATING = 'rotating';
}
