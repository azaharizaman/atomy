<?php

declare(strict_types=1);

namespace Nexus\Receivable\Contracts;

use DateTimeInterface;
use Nexus\Receivable\Enums\PaymentReceiptStatus;
use Nexus\Receivable\Enums\PaymentMethod;

/**
 * Payment Receipt Interface
 *
 * Represents a payment received from a customer.
 */
interface PaymentReceiptInterface
{
    public function getId(): string;

    public function getTenantId(): string;

    public function getCustomerId(): string;

    public function getReceiptNumber(): string;

    public function getReceiptDate(): DateTimeInterface;

    public function getAmount(): float;

    public function getCurrency(): string;

    public function getExchangeRate(): float;

    /**
     * Get amount converted to invoice currency (for multi-currency payments)
     */
    public function getAmountInInvoiceCurrency(): ?float;

    public function getPaymentMethod(): PaymentMethod;

    public function getBankAccount(): ?string;

    public function getReference(): ?string;

    public function getStatus(): PaymentReceiptStatus;

    public function getGlJournalId(): ?string;

    /**
     * Get allocations as array of ['invoice_id' => amount]
     */
    public function getAllocations(): array;

    /**
     * Get total amount already allocated
     */
    public function getAllocatedAmount(): float;

    /**
     * Get unallocated (unapplied) amount
     */
    public function getUnallocatedAmount(): float;

    public function getCreatedAt(): DateTimeInterface;

    public function getUpdatedAt(): DateTimeInterface;
}
