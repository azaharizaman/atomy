<?php

declare(strict_types=1);

namespace Nexus\TimeTracking\ValueObjects;

use DateTimeImmutable;
use Nexus\TimeTracking\Enums\TimesheetStatus;

/**
 * Immutable timesheet line (FUN-PRO-0254, FUN-PRO-0566).
 * workItemId references external entity (e.g. task); no persistence in this package.
 */
final readonly class TimesheetEntry
{
    public function __construct(
        public string $id,
        public string $userId,
        /** External work item id (e.g. task id) */
        public string $workItemId,
        public DateTimeImmutable $date,
        /** Hours worked; must be >= 0 and part of day total <= 24 (BUS-PRO-0056) */
        public float $hours,
        public TimesheetStatus $status,
        /** Optional billing rate (BUS-PRO-0101: defaults to resource allocation rate from context) */
        public ?string $billingRate = null,
        public string $description = '',
    ) {
        if ($hours < 0) {
            throw new \InvalidArgumentException('Timesheet hours cannot be negative.');
        }
    }
}
