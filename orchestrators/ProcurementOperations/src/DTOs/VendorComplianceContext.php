<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs;

use Nexus\ProcurementOperations\Enums\VendorHoldReason;

/**
 * Context DTO for vendor compliance data.
 *
 * Aggregates all vendor compliance information for rule validation.
 */
final readonly class VendorComplianceContext
{
    /**
     * @param string $vendorId Vendor identifier
     * @param string $vendorName Vendor name for display
     * @param bool $isActive Whether vendor is active
     * @param bool $isBlocked Whether vendor has any active holds
     * @param array<VendorHoldReason> $activeHoldReasons Active hold reasons
     * @param bool $isCompliant Whether vendor meets all compliance requirements
     * @param array<string, bool> $complianceChecks Individual compliance checks
     * @param \DateTimeImmutable|null $lastComplianceReview Last compliance review date
     * @param float|null $performanceScore Vendor performance score (0-100)
     * @param array<string, mixed> $metadata Additional compliance data
     */
    public function __construct(
        public string $vendorId,
        public string $vendorName,
        public bool $isActive,
        public bool $isBlocked,
        public array $activeHoldReasons,
        public bool $isCompliant,
        public array $complianceChecks,
        public ?\DateTimeImmutable $lastComplianceReview = null,
        public ?float $performanceScore = null,
        public array $metadata = [],
    ) {}

    /**
     * Check if vendor has a specific hold reason.
     */
    public function hasHoldReason(VendorHoldReason $reason): bool
    {
        return in_array($reason, $this->activeHoldReasons, true);
    }

    /**
     * Check if vendor has any hard blocks.
     */
    public function hasHardBlock(): bool
    {
        foreach ($this->activeHoldReasons as $reason) {
            if ($reason->isHardBlock()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if vendor has only soft blocks.
     */
    public function hasOnlySoftBlocks(): bool
    {
        if (empty($this->activeHoldReasons)) {
            return false;
        }

        foreach ($this->activeHoldReasons as $reason) {
            if ($reason->isHardBlock()) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check if vendor can receive new POs.
     *
     * Vendors with hard blocks cannot receive any new POs.
     */
    public function canReceiveNewPurchaseOrders(): bool
    {
        return $this->isActive && !$this->hasHardBlock();
    }

    /**
     * Check if vendor can receive payments.
     *
     * Vendors with hard blocks cannot receive any payments.
     * Vendors with only soft blocks can receive payments for existing orders.
     */
    public function canReceivePayments(): bool
    {
        return $this->isActive && !$this->hasHardBlock();
    }

    /**
     * Get a specific compliance check result.
     */
    public function getComplianceCheck(string $checkName): ?bool
    {
        return $this->complianceChecks[$checkName] ?? null;
    }

    /**
     * Get count of active holds.
     */
    public function getActiveHoldCount(): int
    {
        return count($this->activeHoldReasons);
    }

    /**
     * Get highest severity level among active holds.
     */
    public function getHighestSeverityLevel(): int
    {
        if (empty($this->activeHoldReasons)) {
            return 0;
        }

        return max(array_map(
            fn (VendorHoldReason $reason) => $reason->severityLevel(),
            $this->activeHoldReasons
        ));
    }
}
