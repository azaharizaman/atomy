<?php

declare(strict_types=1);

namespace Nexus\Procurement\Contracts;

/**
 * Purchase order entity interface.
 *
 * This interface is compatible with Nexus\Payable requirements
 * for 3-way matching operations.
 */
interface PurchaseOrderInterface
{
    /**
     * Get purchase order ID.
     *
     * @return string ULID
     */
    public function getId(): string;

    /**
     * Get PO number.
     *
     * @return string e.g., "PO-2024-001"
     */
    public function getPoNumber(): string;

    /**
     * Get vendor ID.
     *
     * @return string Vendor ULID
     */
    public function getVendorId(): string;

    /**
     * Get requisition ID (null for direct POs).
     *
     * @return string|null Requisition ULID
     */
    public function getRequisitionId(): ?string;

    /**
     * Get PO status.
     *
     * @return string draft|released|partially_received|fully_received|closed
     */
    public function getStatus(): string;

    /**
     * Get total amount.
     *
     * @return float
     */
    public function getTotalAmount(): float;

    /**
     * Get currency code.
     *
     * @return string ISO 4217 code
     */
    public function getCurrency(): string;

    /**
     * Get PO lines.
     *
     * @return array<PurchaseOrderLineInterface>
     */
    public function getLines(): array;

    /**
     * Get created timestamp.
     *
     * @return \DateTimeImmutable
     */
    public function getCreatedAt(): \DateTimeImmutable;

    /**
     * Get released timestamp.
     *
     * @return \DateTimeImmutable|null
     */
    public function getReleasedAt(): ?\DateTimeImmutable;
}
