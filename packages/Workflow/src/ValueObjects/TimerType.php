<?php

declare(strict_types=1);

namespace Nexus\Workflow\ValueObjects;

/**
 * Timer type enum.
 */
enum TimerType: string
{
    case ESCALATION = 'escalation';
    case SLA_CHECK = 'sla_check';
    case REMINDER = 'reminder';
    case SCHEDULED_TASK = 'scheduled_task';
}
