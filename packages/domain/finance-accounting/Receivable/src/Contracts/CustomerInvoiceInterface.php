<?php

declare(strict_types=1);

namespace Nexus\Receivable\Contracts;

use DateTimeInterface;
use Nexus\Receivable\Enums\InvoiceStatus;
use Nexus\Receivable\Enums\CreditTerm;

/**
 * Customer Invoice Interface
 *
 * Represents a customer invoice (the legal demand for payment).
 * This is the primary A/R document.
 */
interface CustomerInvoiceInterface
{
    public function getId(): string;

    public function getTenantId(): string;

    public function getCustomerId(): string;

    public function getInvoiceNumber(): string;

    public function getInvoiceDate(): DateTimeInterface;

    public function getDueDate(): DateTimeInterface;

    public function getCurrency(): string;

    public function getExchangeRate(): float;

    public function getSubtotal(): float;

    public function getTaxAmount(): float;

    public function getTotalAmount(): float;

    public function getOutstandingBalance(): float;

    public function getStatus(): InvoiceStatus;

    public function getGlJournalId(): ?string;

    public function getSalesOrderId(): ?string;

    public function getCreditTerm(): CreditTerm;

    public function getDescription(): ?string;

    /**
     * @return CustomerInvoiceLineInterface[]
     */
    public function getLines(): array;

    public function getCreatedAt(): DateTimeInterface;

    public function getUpdatedAt(): DateTimeInterface;

    /**
     * Check if invoice is overdue
     */
    public function isOverdue(\DateTimeInterface $asOfDate): bool;

    /**
     * Get days past due (negative if not yet due)
     */
    public function getDaysPastDue(\DateTimeInterface $asOfDate): int;

    /**
     * Check if eligible for early payment discount
     */
    public function isEligibleForDiscount(\DateTimeInterface $paymentDate): bool;

    /**
     * Calculate early payment discount amount
     */
    public function calculateDiscount(\DateTimeInterface $paymentDate): float;
}
