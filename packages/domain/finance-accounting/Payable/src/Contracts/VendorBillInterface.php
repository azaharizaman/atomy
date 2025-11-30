<?php

declare(strict_types=1);

namespace Nexus\Payable\Contracts;

/**
 * Vendor bill entity interface.
 */
interface VendorBillInterface
{
    public function getId(): string;
    public function getTenantId(): string;
    public function getVendorId(): string;
    public function getBillNumber(): string;
    public function getBillDate(): \DateTimeInterface;
    public function getDueDate(): \DateTimeInterface;
    public function getCurrency(): string;
    public function getExchangeRate(): float;
    public function getSubtotal(): float;
    public function getTaxAmount(): float;
    public function getTotalAmount(): float;
    public function getStatus(): string;
    public function getMatchingStatus(): string;
    public function getGlJournalId(): ?string;
    public function getDescription(): ?string;
    
    /**
     * Get bill lines.
     *
     * @return array<VendorBillLineInterface>
     */
    public function getLines(): array;
    
    public function getCreatedAt(): \DateTimeInterface;
    public function getUpdatedAt(): \DateTimeInterface;
}
