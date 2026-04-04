<?php

declare(strict_types=1);

namespace Nexus\Sourcing\ValueObjects;

use Nexus\Sourcing\Exceptions\RfqLifecycleException;

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
            throw RfqLifecycleException::validationFailed('Action cannot be empty.');
        }

        if (trim($status) === '') {
            throw RfqLifecycleException::validationFailed('Status cannot be empty.');
        }

        if ($rfqId !== null && trim($rfqId) === '') {
            throw RfqLifecycleException::validationFailed('RFQ id cannot be empty when provided.');
        }

        if ($sourceRfqId !== null && trim($sourceRfqId) === '') {
            throw RfqLifecycleException::validationFailed('Source RFQ id cannot be empty when provided.');
        }

        if ($invitationId !== null && trim($invitationId) === '') {
            throw RfqLifecycleException::validationFailed('Invitation id cannot be empty when provided.');
        }

        if ($affectedCount < 0) {
            throw RfqLifecycleException::validationFailed('Affected count cannot be negative.');
        }

        if ($copiedLineItemCount < 0) {
            throw RfqLifecycleException::validationFailed('Copied line item count cannot be negative.');
        }

        if ($copiedChildRecordCount < 0) {
            throw RfqLifecycleException::validationFailed('Copied child record count cannot be negative.');
        }
    }
}
