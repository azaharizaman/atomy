<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Events;

/**
 * Event dispatched when requisition approval is rejected.
 */
final readonly class RequisitionApprovalRejectedEvent
{
    public function __construct(
        public string $tenantId,
        public ?string $requisitionId,
        public ?string $rejectedBy,
        public ?string $reason,
        public \DateTimeImmutable $rejectedAt = new \DateTimeImmutable(),
    ) {}
}
