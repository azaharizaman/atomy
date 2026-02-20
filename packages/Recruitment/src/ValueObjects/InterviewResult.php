<?php

declare(strict_types=1);

namespace Nexus\Recruitment\ValueObjects;

enum InterviewResult: string
{
    case EXCELLENT = 'excellent';
    case GOOD = 'good';
    case AVERAGE = 'average';
    case POOR = 'poor';
    case NOT_SUITABLE = 'not_suitable';
}
