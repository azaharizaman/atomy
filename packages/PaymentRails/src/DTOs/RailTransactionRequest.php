<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\DTOs;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentRails\ValueObjects\BankAccount;
use Nexus\PaymentRails\ValueObjects\RoutingNumber;

/**
 * Data transfer object for validating a payment rail transaction.
 */
final class RailTransactionRequest
{
    /**
     * @param array<string, mixed>|null $metadata
     */
    public function __construct(
        public readonly string $beneficiaryName,
        public readonly Money $amount,
        public readonly ?BankAccount $beneficiaryAccount = null,
        public readonly ?RoutingNumber $routingNumber = null,
        public readonly ?string $beneficiaryCountry = null,
        public readonly ?string $beneficiaryAddress = null,
        public readonly bool $isInternational = false,
        public readonly ?string $purposeOfPayment = null,
        public readonly ?string $memo = null,
        public readonly ?array $metadata = null,
    ) {
    }
}
