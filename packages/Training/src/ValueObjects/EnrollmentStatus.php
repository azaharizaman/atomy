<?php

declare(strict_types=1);

namespace Nexus\Training\ValueObjects;

enum EnrollmentStatus: string
{
    case PENDING = 'pending';
    case ENROLLED = 'enrolled';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}
