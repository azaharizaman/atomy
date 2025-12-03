<?php

declare(strict_types=1);

namespace Nexus\Onboarding\ValueObjects;

enum TaskStatus: string
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case SKIPPED = 'skipped';
}
