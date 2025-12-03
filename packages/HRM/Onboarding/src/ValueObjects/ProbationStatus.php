<?php

declare(strict_types=1);

namespace Nexus\Onboarding\ValueObjects;

enum ProbationStatus: string
{
    case IN_PROGRESS = 'in_progress';
    case PASSED = 'passed';
    case EXTENDED = 'extended';
    case FAILED = 'failed';
}
