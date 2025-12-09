<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Events;

use Nexus\ProcurementOperations\Enums\VendorHoldReason;

/**
 * Event published when a vendor is unblocked (hold removed).
 */
final readonly class VendorUnblockedEvent
{
    public function __construct(
        public string $tenantId,
        public string $vendorId,
        public VendorHoldReason $reason,
        public string $unblockedBy,
        public \DateTimeImmutable $unblockedAt,
        public ?string $notes = null,
    ) {}
}
