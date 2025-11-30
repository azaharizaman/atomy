<?php

declare(strict_types=1);

namespace Nexus\Assets\Contracts;

use DateTimeInterface;

/**
 * Warranty Record Interface (Tier 2+)
 *
 * Tracks vendor warranty coverage with expiry alerts.
 */
interface WarrantyRecordInterface
{
    /**
     * Get unique identifier
     */
    public function getId(): string;

    /**
     * Get asset identifier
     */
    public function getAssetId(): string;

    /**
     * Get tenant identifier
     */
    public function getTenantId(): string;

    /**
     * Get vendor identifier
     */
    public function getVendorId(): string;

    /**
     * Get vendor name
     */
    public function getVendorName(): ?string;

    /**
     * Get warranty start date
     */
    public function getStartDate(): DateTimeInterface;

    /**
     * Get warranty expiry date
     */
    public function getExpiryDate(): DateTimeInterface;

    /**
     * Get coverage details
     */
    public function getCoverageDetails(): ?string;

    /**
     * Get warranty terms
     */
    public function getTerms(): ?string;

    /**
     * Get claim history (array of claim records)
     */
    public function getClaimHistory(): array;

    /**
     * Get warranty policy number
     */
    public function getPolicyNumber(): ?string;

    /**
     * Get coverage amount
     */
    public function getCoverageAmount(): ?float;

    /**
     * Get created timestamp
     */
    public function getCreatedAt(): DateTimeInterface;

    /**
     * Get updated timestamp
     */
    public function getUpdatedAt(): DateTimeInterface;

    /**
     * Check if warranty is currently active
     */
    public function isActive(): bool;

    /**
     * Get days until expiry
     */
    public function getDaysUntilExpiry(): int;

    /**
     * Check if warranty is expiring soon (within 30 days)
     */
    public function isExpiringSoon(): bool;
}
