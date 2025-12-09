<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs;

use Nexus\ProcurementOperations\Enums\VendorHoldReason;

/**
 * Request DTO for applying a hold to a vendor.
 */
final readonly class VendorHoldRequest
{
    /**
     * @param string $tenantId Tenant identifier
     * @param string $vendorId Vendor to apply hold to
     * @param VendorHoldReason $reason Hold reason
     * @param string $appliedBy User applying the hold
     * @param string|null $notes Additional notes
     * @param \DateTimeImmutable|null $effectiveUntil Optional expiration date
     * @param array<string, mixed> $metadata Additional metadata
     */
    public function __construct(
        public string $tenantId,
        public string $vendorId,
        public VendorHoldReason $reason,
        public string $appliedBy,
        public ?string $notes = null,
        public ?\DateTimeImmutable $effectiveUntil = null,
        public array $metadata = [],
    ) {}

    /**
     * Check if this hold has an expiration date.
     */
    public function hasExpiration(): bool
    {
        return $this->effectiveUntil !== null;
    }

    /**
     * Check if this is a hard block.
     */
    public function isHardBlock(): bool
    {
        return $this->reason->isHardBlock();
    }
}
