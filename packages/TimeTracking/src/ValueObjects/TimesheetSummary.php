<?php

declare(strict_types=1);

namespace Nexus\TimeTracking\ValueObjects;

use DateTimeImmutable;
use Nexus\TimeTracking\Enums\TimesheetStatus;

/**
 * Immutable value object for a single timesheet entry (FUN-PRO-0254, FUN-PRO-0566).
 * Work item (e.g. task) and user are identified by external IDs.
 */
final readonly class TimesheetSummary
{
    public function __construct(
        public string $id,
        public string $userId,
        /** Work item id (e.g. task id) */
        public string $workItemId,
        public DateTimeImmutable $date,
        /** Hours worked; must be in [0, 24] (BUS-PRO-0056) */
        public float $hours,
        public string $description,
        public TimesheetStatus $status,
        /** Billing rate (optional; BUS-PRO-0101 default from allocation) */
        public ?string $billingRate = null,
    ) {
        if ($hours < 0 || $hours > 24) {
            throw new \InvalidArgumentException(sprintf(
                'Hours must be between 0 and 24, got %s.',
                $hours
            ));
        }
        if ($workItemId === '') {
            throw new \InvalidArgumentException('Work item id cannot be empty.');
        }
        if ($userId === '') {
            throw new \InvalidArgumentException('User id cannot be empty.');
        }
    }

    public function isApproved(): bool
    {
        return $this->status->isImmutable();
    }
}
