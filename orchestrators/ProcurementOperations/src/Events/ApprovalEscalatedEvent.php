<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Events;

use Nexus\ProcurementOperations\Enums\ApprovalLevel;

/**
 * Event dispatched when an approval is escalated due to timeout.
 */
final readonly class ApprovalEscalatedEvent
{
    /**
     * @param string $tenantId Tenant context
     * @param string $documentId Document ID (requisition or PO)
     * @param string $documentType Document type
     * @param ApprovalLevel $fromLevel Previous approval level
     * @param ApprovalLevel $toLevel Escalated approval level
     * @param string $originalApproverId Original approver who timed out
     * @param string $escalatedToApproverId New approver after escalation
     * @param int $timeoutHours Hours before escalation triggered
     * @param \DateTimeImmutable $escalatedAt Escalation timestamp
     */
    public function __construct(
        public string $tenantId,
        public string $documentId,
        public string $documentType,
        public ApprovalLevel $fromLevel,
        public ApprovalLevel $toLevel,
        public string $originalApproverId,
        public string $escalatedToApproverId,
        public int $timeoutHours,
        public \DateTimeImmutable $escalatedAt,
    ) {}
}
