<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\DTOs;

use Nexus\PaymentRails\Enums\RailType;

/**
 * Criteria for selecting the optimal payment rail.
 */
final readonly class RailSelectionCriteria
{
    /**
     * @param int $amountCents Amount in minor units (e.g., cents)
     * @param string $currency ISO-4217 currency code
     * @param string $destinationCountry ISO-3166-1 alpha-2 country code
     * @param string $urgency One of: standard, urgent, real-time
     * @param bool $preferLowCost Whether to prioritize cost over speed
     * @param bool $isInternational Whether the transfer is international
     * @param bool $requiresRecurring Whether recurring payments are required
     * @param string $beneficiaryType Beneficiary type (e.g., vendor, individual)
     * @param RailType|null $preferredRail Explicit preferred rail type
     */
    public function __construct(
        public int $amountCents,
        public string $currency,
        public string $destinationCountry,
        public string $urgency = 'standard',
        public bool $preferLowCost = true,
        public bool $isInternational = false,
        public bool $requiresRecurring = false,
        public string $beneficiaryType = 'individual',
        public ?RailType $preferredRail = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'amount_cents' => $this->amountCents,
            'currency' => $this->currency,
            'destination_country' => $this->destinationCountry,
            'urgency' => $this->urgency,
            'prefer_low_cost' => $this->preferLowCost,
            'is_international' => $this->isInternational,
            'requires_recurring' => $this->requiresRecurring,
            'beneficiary_type' => $this->beneficiaryType,
            'preferred_rail' => $this->preferredRail?->value,
        ];
    }
}
