<?php

declare(strict_types=1);

namespace Nexus\Sourcing\ValueObjects;

final readonly class RfqLifecycleResult
{
    public function __construct(
        public string $action,
        public string $status,
        public ?string $rfqId = null,
        public ?string $sourceRfqId = null,
        public int $affectedCount = 0,
        public ?string $invitationId = null,
        public int $copiedLineItemCount = 0,
        public int $copiedChildRecordCount = 0,
    ) {
        if (trim($action) === '') {
            throw new \InvalidArgumentException('Action cannot be empty.');
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
    }
}
