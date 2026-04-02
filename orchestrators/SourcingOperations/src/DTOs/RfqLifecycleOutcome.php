<?php

declare(strict_types=1);

namespace Nexus\SourcingOperations\DTOs;

final readonly class RfqLifecycleOutcome
{
    public string $action;
    public string $tenantId;
    public string $status;
    public ?string $rfqId;
    public ?string $sourceRfqId;
    public int $affectedCount;
    public ?string $invitationId;
    public int $copiedLineItemCount;
    public int $copiedChildRecordCount;

    public function __construct(
        string $action,
        string $tenantId,
        string $status,
        ?string $rfqId = null,
        ?string $sourceRfqId = null,
        int $affectedCount = 0,
        ?string $invitationId = null,
        int $copiedLineItemCount = 0,
        int $copiedChildRecordCount = 0,
    ) {
        if (trim($action) === '') {
            throw new \InvalidArgumentException('Action cannot be empty.');
        }

        if (trim($tenantId) === '') {
            throw new \InvalidArgumentException('Tenant id cannot be empty.');
        }

        if (trim($status) === '') {
            throw new \InvalidArgumentException('Status cannot be empty.');
        }

        if ($rfqId !== null && trim($rfqId) === '') {
            throw new \InvalidArgumentException('RFQ id cannot be empty when provided.');
        }

        if ($sourceRfqId !== null && trim($sourceRfqId) === '') {
            throw new \InvalidArgumentException('Source RFQ id cannot be empty when provided.');
        }

        if ($invitationId !== null && trim($invitationId) === '') {
            throw new \InvalidArgumentException('Invitation id cannot be empty when provided.');
        }

        if ($affectedCount < 0) {
            throw new \InvalidArgumentException('Affected count cannot be negative.');
        }

        if ($copiedLineItemCount < 0) {
            throw new \InvalidArgumentException('Copied line item count cannot be negative.');
        }

        if ($copiedChildRecordCount < 0) {
            throw new \InvalidArgumentException('Copied child record count cannot be negative.');
        }

        $this->action = trim($action);
        $this->tenantId = trim($tenantId);
        $this->status = trim($status);
        $this->rfqId = $rfqId !== null ? trim($rfqId) : null;
        $this->sourceRfqId = $sourceRfqId !== null ? trim($sourceRfqId) : null;
        $this->affectedCount = $affectedCount;
        $this->invitationId = $invitationId !== null ? trim($invitationId) : null;
        $this->copiedLineItemCount = $copiedLineItemCount;
        $this->copiedChildRecordCount = $copiedChildRecordCount;
    }
}
