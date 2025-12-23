<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\DTOs;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentRails\ValueObjects\BankAccount;
use Nexus\PaymentRails\ValueObjects\RoutingNumber;

/**
 * Data transfer object for validating a payment rail transaction.
 */
final readonly class RailTransactionRequest
{
    /**
     * @param array<string, mixed>|null $metadata
     */
    public function __construct(
        public string $beneficiaryName,
        public Money $amount,
        public ?BankAccount $beneficiaryAccount = null,
        public ?RoutingNumber $routingNumber = null,
        public ?string $beneficiaryCountry = null,
        public ?string $beneficiaryAddress = null,
        public bool $isInternational = false,
        public ?string $purposeOfPayment = null,
        public ?string $memo = null,
        public ?array $metadata = null,
    ) {
    }
}
