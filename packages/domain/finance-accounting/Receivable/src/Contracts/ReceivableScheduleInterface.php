<?php

declare(strict_types=1);

namespace Nexus\Receivable\Contracts;

use DateTimeInterface;

/**
 * Receivable Schedule Interface
 *
 * Represents a scheduled payment due date for an invoice.
 * Supports installment payments and early payment discounts.
 */
interface ReceivableScheduleInterface
{
    public function getId(): string;

    public function getTenantId(): string;

    public function getInvoiceId(): string;

    public function getCustomerId(): string;

    public function getScheduledAmount(): float;

    public function getDueDate(): DateTimeInterface;

    public function getEarlyPaymentDiscountPercent(): float;

    public function getEarlyPaymentDiscountDate(): ?DateTimeInterface;

    public function getStatus(): string;

    public function getReceiptId(): ?string;

    public function getCurrency(): string;

    /**
     * Check if eligible for early payment discount
     */
    public function isEligibleForDiscount(DateTimeInterface $paymentDate): bool;

    /**
     * Calculate early payment discount amount
     */
    public function calculateDiscount(DateTimeInterface $paymentDate): float;
}
