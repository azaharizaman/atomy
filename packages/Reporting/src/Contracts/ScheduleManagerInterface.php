<?php

declare(strict_types=1);

namespace Nexus\Reporting\Contracts;

use Nexus\Reporting\ValueObjects\ScheduleDefinition;
use Nexus\Reporting\ValueObjects\ScheduledJob;

interface ScheduleManagerInterface
{
    public function schedule(ScheduleDefinition $definition): ScheduledJob;
}
