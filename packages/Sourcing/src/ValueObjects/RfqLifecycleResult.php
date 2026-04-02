<?php

declare(strict_types=1);

namespace Nexus\Sourcing\ValueObjects;

final readonly class RfqLifecycleResult
{
    public function __construct(
        public ?string $createdRfqId = null,
        public ?string $updatedRfqId = null,
        public int $affectedCount = 0,
        public int $copiedLineItemCount = 0,
        public int $copiedChildRecordCount = 0,
    ) {
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
