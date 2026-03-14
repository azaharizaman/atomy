<?php

declare(strict_types=1);

namespace Nexus\Milestone\ValueObjects;

use DateTimeImmutable;
use Nexus\Milestone\Enums\MilestoneStatus;

/**
 * Milestone entity (FUN-PRO-0569). BUS-PRO-0077: billing amount cannot exceed remaining budget.
 */
final readonly class MilestoneSummary
{
    public function __construct(
        public string $id,
        /** Context (e.g. project) id for budget check */
        public string $contextId,
        public string $title,
        public ?DateTimeImmutable $dueDate,
        /** Billing amount in smallest currency unit (e.g. cents) or string representation */
        public string $billingAmount,
        public string $currency,
        public MilestoneStatus $status,
        public string $description = '',
    ) {
        if ($title === '') {
            throw new \InvalidArgumentException('Milestone title cannot be empty.');
        }
        if ($contextId === '') {
            throw new \InvalidArgumentException('Context id cannot be empty.');
        }
    }
}
