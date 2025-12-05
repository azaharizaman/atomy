<?php

declare(strict_types=1);

namespace Nexus\Payable\Events;

/**
 * Dispatched when a payment is successfully executed to a vendor.
 *
 * Consuming applications or orchestrators may listen to this event
 * to trigger side-effects such as:
 * - Post payment journal entry (DR AP, CR Bank)
 * - Update vendor ledger
 * - Mark invoices as paid
 * - Notify vendor of payment
 * - Update bank reconciliation
 * - Audit logging
 */
final readonly class PaymentExecutedEvent
{
    /**
     * @param string $paymentId Unique identifier of the payment
     * @param string $tenantId Tenant context
     * @param string $paymentReference Payment reference number
     * @param string $vendorId Vendor party ID
     * @param string $vendorName Vendor display name
     * @param array<string> $paidInvoiceIds List of invoice IDs paid
     * @param array<string> $paidInvoiceNumbers List of invoice numbers paid
     * @param int $totalAmountCents Total payment amount in cents
     * @param int $discountTakenCents Early payment discount taken in cents
     * @param int $netAmountCents Net amount paid (total - discount) in cents
     * @param string $currency Currency code (ISO 4217)
     * @param string $paymentMethod Payment method used
     * @param string $bankAccountId Bank account used for payment
     * @param string|null $chequeNumber Cheque number (if cheque payment)
     * @param string|null $bankReferenceNumber Bank transaction reference
     * @param string|null $vendorBankAccountId Vendor's bank account paid to
     * @param string $executedBy User ID who executed the payment
     * @param \DateTimeImmutable $executedAt Timestamp of payment execution
     */
    public function __construct(
        public string $paymentId,
        public string $tenantId,
        public string $paymentReference,
        public string $vendorId,
        public string $vendorName,
        public array $paidInvoiceIds,
        public array $paidInvoiceNumbers,
        public int $totalAmountCents,
        public int $discountTakenCents,
        public int $netAmountCents,
        public string $currency,
        public string $paymentMethod,
        public string $bankAccountId,
        public ?string $chequeNumber,
        public ?string $bankReferenceNumber,
        public ?string $vendorBankAccountId,
        public string $executedBy,
        public \DateTimeImmutable $executedAt,
    ) {}
}
