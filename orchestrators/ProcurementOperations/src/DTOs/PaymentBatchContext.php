<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs;

/**
 * Context DTO for payment batch operations.
 *
 * Aggregates data for payment processing.
 */
final readonly class PaymentBatchContext
{
    /**
     * @param string $tenantId Tenant context
     * @param string $paymentBatchId Batch ID
     * @param string $paymentMethod Payment method
     * @param string $bankAccountId Bank account ID
     * @param int $totalAmountCents Total batch amount in cents
     * @param int $totalDiscountCents Total early payment discount in cents
     * @param int $netAmountCents Net amount to pay in cents
     * @param string $currency Currency code
     * @param array<int, array{
     *     vendorBillId: string,
     *     vendorBillNumber: string,
     *     vendorId: string,
     *     vendorName: string,
     *     amountCents: int,
     *     discountCents: int,
     *     netAmountCents: int,
     *     dueDate: \DateTimeImmutable,
     *     discountDate: ?\DateTimeImmutable
     * }> $invoices Invoices in the batch
     * @param array{
     *     bankAccountId: string,
     *     bankAccountNumber: string,
     *     bankName: string,
     *     currency: string,
     *     availableBalanceCents: int
     * }|null $bankAccountInfo Bank account information
     * @param array<string, array{
     *     vendorId: string,
     *     vendorName: string,
     *     totalAmountCents: int,
     *     invoiceCount: int
     * }>|null $vendorSummary Summary by vendor
     * @param \DateTimeImmutable|null $scheduledDate Scheduled payment date
     * @param string|null $status Batch status
     */
    public function __construct(
        public string $tenantId,
        public string $paymentBatchId,
        public string $paymentMethod,
        public string $bankAccountId,
        public int $totalAmountCents,
        public int $totalDiscountCents,
        public int $netAmountCents,
        public string $currency,
        public array $invoices,
        public ?array $bankAccountInfo = null,
        public ?array $vendorSummary = null,
        public ?\DateTimeImmutable $scheduledDate = null,
        public ?string $status = null,
    ) {}

    /**
     * Get invoice count in batch.
     */
    public function getInvoiceCount(): int
    {
        return count($this->invoices);
    }

    /**
     * Get unique vendor count in batch.
     */
    public function getVendorCount(): int
    {
        $vendorIds = array_unique(array_column($this->invoices, 'vendorId'));
        return count($vendorIds);
    }

    /**
     * Check if bank account has sufficient funds.
     */
    public function hasSufficientFunds(): bool
    {
        if ($this->bankAccountInfo === null) {
            return false;
        }
        return $this->bankAccountInfo['availableBalanceCents'] >= $this->netAmountCents;
    }

    /**
     * Get invoices eligible for early payment discount.
     *
     * @return array<array{
     *     vendorBillId: string,
     *     vendorBillNumber: string,
     *     discountCents: int
     * }>
     */
    public function getDiscountEligibleInvoices(): array
    {
        return array_filter(
            $this->invoices,
            fn(array $invoice) => $invoice['discountCents'] > 0
        );
    }
}
