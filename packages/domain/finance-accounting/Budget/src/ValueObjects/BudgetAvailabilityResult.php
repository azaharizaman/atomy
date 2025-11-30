<?php

declare(strict_types=1);

namespace Nexus\Budget\ValueObjects;

use Nexus\Finance\ValueObjects\Money;

/**
 * Budget Availability Result value object
 * 
 * Immutable result of budget availability check.
 */
final readonly class BudgetAvailabilityResult
{
    public function __construct(
        private bool $isAvailable,
        private Money $requestedAmount,
        private Money $availableAmount,
        private string $reason
    ) {}

    public function isAvailable(): bool
    {
        return $this->isAvailable;
    }

    public function getRequestedAmount(): Money
    {
        return $this->requestedAmount;
    }

    public function getAvailableAmount(): Money
    {
        return $this->availableAmount;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    /**
     * Get shortfall amount (if any)
     */
    public function getShortfall(): ?Money
    {
        if ($this->isAvailable) {
            return null;
        }

        return $this->requestedAmount->subtract($this->availableAmount);
    }

    /**
     * Get availability message
     */
    public function getMessage(): string
    {
        if ($this->isAvailable) {
            return sprintf(
                'Budget available: %s of %s requested',
                $this->availableAmount->format(),
                $this->requestedAmount->format()
            );
        }

        $shortfall = $this->getShortfall();
        return sprintf(
            'Insufficient budget: %s available, %s requested (shortfall: %s). Reason: %s',
            $this->availableAmount->format(),
            $this->requestedAmount->format(),
            $shortfall?->format() ?? 'N/A',
            $this->reason
        );
    }
}
