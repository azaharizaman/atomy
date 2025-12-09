<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Events;

use Nexus\ProcurementOperations\Enums\VendorHoldReason;

/**
 * Event published when a vendor is blocked (hold applied).
 */
final readonly class VendorBlockedEvent
{
    public function __construct(
        public string $tenantId,
        public string $vendorId,
        public VendorHoldReason $reason,
        public string $blockedBy,
        public \DateTimeImmutable $blockedAt,
        public ?string $notes = null,
        public ?\DateTimeImmutable $effectiveUntil = null,
    ) {}

    /**
     * Check if this is a hard block.
     */
    public function isHardBlock(): bool
    {
        return $this->reason->isHardBlock();
    }
}
