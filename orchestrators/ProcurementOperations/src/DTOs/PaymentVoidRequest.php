<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs;

/**
 * Request DTO for voiding a payment.
 */
final readonly class PaymentVoidRequest
{
    /**
     * @param string $tenantId Tenant context
     * @param string $paymentId ID of the payment to void
     * @param string $voidedBy User ID performing the void
     * @param string $reason Reason for the void
     * @param bool $reverseAccounting Whether to automatically reverse GL entries
     */
    public function __construct(
        public string $tenantId,
        public string $paymentId,
        public string $voidedBy,
        public string $reason,
        public bool $reverseAccounting = true
    ) {}
}
