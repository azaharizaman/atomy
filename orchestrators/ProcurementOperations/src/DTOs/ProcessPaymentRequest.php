<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs;

/**
 * Request DTO for payment processing.
 */
final readonly class ProcessPaymentRequest
{
    /**
     * @param string $tenantId Tenant context
     * @param array<string> $vendorBillIds Vendor bills to pay
     * @param string $paymentMethod Payment method (bank_transfer, cheque, etc.)
     * @param string $bankAccountId Bank account to pay from
     * @param string $processedBy User ID processing the payment
     * @param \DateTimeImmutable|null $scheduledDate Payment date (null = immediate)
     * @param bool $takeEarlyPaymentDiscount Whether to take early payment discount
     * @param string|null $paymentReference Custom payment reference
     * @param string|null $notes Payment notes
     * @param array<string, mixed> $metadata Additional metadata
     */
    public function __construct(
        public string $tenantId,
        public array $vendorBillIds,
        public string $paymentMethod,
        public string $bankAccountId,
        public string $processedBy,
        public ?\DateTimeImmutable $scheduledDate = null,
        public bool $takeEarlyPaymentDiscount = true,
        public ?string $paymentReference = null,
        public ?string $notes = null,
        public array $metadata = [],
    ) {}
}
