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

    /**
     * Whether this quote is currently locked by an active comparison run.
     */
    public function isLocked(): bool;

    /**
     * Get the comparison run ID that holds the lock, if any.
     */
    public function getLockedByRunId(): ?string;

    /**
     * Get the user who initiated the lock, if any.
     */
    public function getLockedBy(): ?string;

    /**
     * Get the timestamp when the lock was acquired.
     */
    public function getLockedAt(): ?\DateTimeImmutable;

    /**
     * Get quote lines.
     *
     * @return array<array{item_code: string, description: string, quantity: float, unit: string, unit_price: float, lead_time_days?: int}>
     */
    public function getLines(): array;

    /**
     * Get payment terms.
     */
    public function getPaymentTerms(): ?string;
}
