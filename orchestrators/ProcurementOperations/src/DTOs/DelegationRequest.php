<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs;

/**
 * Request DTO for delegation operations.
 *
 * Contains information needed to set up or query delegation.
 */
final readonly class DelegationRequest
{
    /**
     * @param string $tenantId Tenant context
     * @param string $delegatorId User ID who is delegating
     * @param string $delegateeId User ID who receives delegation
     * @param \DateTimeImmutable $startsAt Delegation start date
     * @param \DateTimeImmutable $endsAt Delegation end date
     * @param array<string> $taskTypes Task types to delegate (e.g., ['requisition_approval', 'po_approval'])
     * @param string|null $reason Reason for delegation
     */
    public function __construct(
        public string $tenantId,
        public string $delegatorId,
        public string $delegateeId,
        public \DateTimeImmutable $startsAt,
        public \DateTimeImmutable $endsAt,
        public array $taskTypes = [],
        public ?string $reason = null,
    ) {}

    /**
     * Check if the delegation is currently active.
     */
    public function isActive(): bool
    {
        $now = new \DateTimeImmutable();
        return $now >= $this->startsAt && $now <= $this->endsAt;
    }

    /**
     * Check if a specific task type is included.
     */
    public function includesTaskType(string $taskType): bool
    {
        return empty($this->taskTypes) || in_array($taskType, $this->taskTypes, true);
    }
}
