<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\DTOs;

use Nexus\PaymentRails\Enums\AccountType;
use Nexus\PaymentRails\ValueObjects\RoutingNumber;

/**
 * Data transfer object for creating an ACH prenote batch.
 */
final readonly class AchPrenoteRequest
{
    public function __construct(
        public RoutingNumber $routingNumber,
        public string $accountNumber,
        public AccountType $accountType,
        public string $receiverName,
        public bool $isDebit = false,
        public ?string $companyName = null,
        public ?string $companyId = null,
    ) {
    }
}
