<?php

declare(strict_types=1);

namespace Nexus\QualityControl\Enums;

enum InspectionDecision: string
{
    case ACCEPT = 'accept';
    case REJECT = 'reject';
    case CONDITIONAL_ACCEPT = 'conditional_accept';
    case REWORK = 'rework';
}
