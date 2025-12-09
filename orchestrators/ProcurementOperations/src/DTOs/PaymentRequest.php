<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\DTOs;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\Enums\PaymentMethod;

/**
 * Request DTO for payment execution.
 */
final readonly class PaymentRequest
{
    /**
     * @param string $vendorId Vendor being paid
     * @param Money $amount Payment amount
     * @param PaymentMethod $preferredMethod Preferred payment method
     * @param array<string> $invoiceIds Invoices being paid
     * @param string|null $bankAccountId Vendor bank account ID (for ACH/Wire)
     * @param string|null $mailingAddressId Mailing address ID (for Check)
     * @param bool $urgent Urgent payment flag
     * @param bool $international International payment flag
     * @param string|null $memo Payment memo/reference
     * @param \DateTimeImmutable|null $scheduledDate Scheduled payment date
     * @param array<string, mixed> $metadata Additional metadata
     */
    public function __construct(
        public string $vendorId,
        public Money $amount,
        public PaymentMethod $preferredMethod,
        public array $invoiceIds = [],
        public ?string $bankAccountId = null,
        public ?string $mailingAddressId = null,
        public bool $urgent = false,
        public bool $international = false,
        public ?string $memo = null,
        public ?\DateTimeImmutable $scheduledDate = null,
        public array $metadata = [],
    ) {}

    /**
     * Check if bank account details are provided.
     */
    public function hasBankAccount(): bool
    {
        return $this->bankAccountId !== null;
    }

    /**
     * Check if mailing address is provided.
     */
    public function hasMailingAddress(): bool
    {
        return $this->mailingAddressId !== null;
    }

    /**
     * Check if payment is scheduled for future.
     */
    public function isScheduled(): bool
    {
        if ($this->scheduledDate === null) {
            return false;
        }

        return $this->scheduledDate > new \DateTimeImmutable();
    }
}
