<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Events;

/**
 * Event dispatched when an approval task is delegated to another user.
 */
final readonly class ApprovalDelegatedEvent
{
    /**
     * @param string $tenantId Tenant context
     * @param string $documentId Document ID (requisition or PO)
     * @param string $documentType Document type
     * @param string $originalApproverId Original approver user ID
     * @param string $delegatedToId Delegate user ID
     * @param string $delegationId Delegation record ID
     * @param \DateTimeImmutable $delegatedAt Delegation timestamp
     * @param \DateTimeImmutable $delegationEndsAt When delegation expires
     */
    public function __construct(
        public string $tenantId,
        public string $documentId,
        public string $documentType,
        public string $originalApproverId,
        public string $delegatedToId,
        public string $delegationId,
        public \DateTimeImmutable $delegatedAt,
        public \DateTimeImmutable $delegationEndsAt,
    ) {}
}
