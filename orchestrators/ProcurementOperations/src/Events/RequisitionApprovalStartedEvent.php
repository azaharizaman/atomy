<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Events;

/**
 * Event dispatched when requisition approval workflow starts.
 */
final readonly class RequisitionApprovalStartedEvent
{
    public function __construct(
        public string $tenantId,
        public ?string $requisitionId,
        public int $amountCents,
        public string $requestorId,
        public \DateTimeImmutable $startedAt,
    ) {}
}
