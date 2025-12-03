<?php

declare(strict_types=1);

namespace Nexus\AttendanceManagement\Enums;

enum CheckType: string
{
    case CHECK_IN = 'check_in';
    case CHECK_OUT = 'check_out';
}
