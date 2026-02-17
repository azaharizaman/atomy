<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs;

/**
 * Request DTO for initiating a batch payment run.
 */
final readonly class PaymentRunRequest
{
    /**
     * @param string $tenantId Tenant context
     * @param string $bankAccountId Bank account to pay from
     * @param \DateTimeImmutable $paymentDate Scheduled date for payments
     * @param array<string, mixed> $filters Filters for selecting bills (vendorId, dueDate, etc.)
     * @param string|null $paymentMethod Preferred payment method
     * @param string $initiatedBy User ID initiating the run
     */
    public function __construct(
        public string $tenantId,
        public string $bankAccountId,
        public \DateTimeImmutable $paymentDate,
        public array $filters = [],
        public ?string $paymentMethod = null,
        public string $initiatedBy
    ) {}
}
