<?php

declare(strict_types=1);

namespace Nexus\Idempotency\Enums;

enum BeginOutcome: string
{
    case FirstExecution = 'first_execution';
    case Replay = 'replay';
    case InProgress = 'in_progress';
}
