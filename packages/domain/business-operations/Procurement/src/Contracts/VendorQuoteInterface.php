<?php

declare(strict_types=1);

namespace Nexus\Procurement\Contracts;

/**
 * Vendor quote entity interface.
 */
interface VendorQuoteInterface
{
    /**
     * Get quote ID.
     *
     * @return string ULID
     */
    public function getId(): string;

    /**
     * Get RFQ number.
     *
     * @return string e.g., "RFQ-2024-001"
     */
    public function getRfqNumber(): string;

    /**
     * Get vendor ID.
     *
     * @return string Vendor ULID
     */
    public function getVendorId(): string;

    /**
     * Get submission date.
     *
     * @return \DateTimeImmutable
     */
    public function getSubmissionDate(): \DateTimeImmutable;

    /**
     * Get quote validity period (days).
     *
     * @return int
     */
    public function getValidityPeriod(): int;

    /**
     * Get total quoted amount.
     *
     * @return float
     */
    public function getTotalQuotedAmount(): float;

    /**
     * Get quote status.
     *
     * @return string pending|accepted|rejected|expired
     */
    public function getStatus(): string;

    /**
     * Check if quote is valid (within validity period).
     *
     * @return bool
     */
    public function isValid(): bool;
}
