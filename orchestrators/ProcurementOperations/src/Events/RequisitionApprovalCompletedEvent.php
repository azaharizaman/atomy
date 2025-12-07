<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Events;

/**
 * Event dispatched when requisition approval workflow completes successfully.
 */
final readonly class RequisitionApprovalCompletedEvent
{
    /**
     * @param array<string> $approvedBy
     */
    public function __construct(
        public string $tenantId,
        public ?string $requisitionId,
        public array $approvedBy,
        public \DateTimeImmutable $approvedAt,
    ) {}
}
