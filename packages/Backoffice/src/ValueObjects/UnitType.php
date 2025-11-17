<?php

declare(strict_types=1);

namespace Nexus\Backoffice\ValueObjects;

enum UnitType: string
{
    case PROJECT_TEAM = 'project_team';
    case COMMITTEE = 'committee';
    case TASK_FORCE = 'task_force';
    case WORKING_GROUP = 'working_group';
    case CENTER_OF_EXCELLENCE = 'center_of_excellence';

    public function isTemporaryByNature(): bool
    {
        return match ($this) {
            self::PROJECT_TEAM, self::TASK_FORCE, self::WORKING_GROUP => true,
            self::COMMITTEE, self::CENTER_OF_EXCELLENCE => false,
        };
    }
}
