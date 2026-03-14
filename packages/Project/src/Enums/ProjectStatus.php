<?php

declare(strict_types=1);

namespace Nexus\Project\Enums;

/**
 * Project lifecycle status. BUS-PRO-0096: cannot be completed if incomplete tasks exist.
 */
enum ProjectStatus: string
{
    case Planning = 'planning';
    case Active = 'active';
    case OnHold = 'on_hold';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function isTerminal(): bool
    {
        return $this === self::Completed || $this === self::Cancelled;
    }

    public function allowsLessonsLearned(): bool
    {
        return $this === self::Completed || $this === self::Cancelled;
    }
}
