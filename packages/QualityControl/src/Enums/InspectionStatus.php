<?php

declare(strict_types=1);

namespace Nexus\QualityControl\Enums;

enum InspectionStatus: string
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}
