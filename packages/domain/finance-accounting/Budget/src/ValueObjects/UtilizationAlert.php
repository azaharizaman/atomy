<?php

declare(strict_types=1);

namespace Nexus\Budget\ValueObjects;

use Nexus\Budget\Enums\AlertSeverity;
use Nexus\Finance\ValueObjects\Money;

/**
 * Utilization Alert value object
 * 
 * Immutable representation of a budget utilization alert.
 */
final readonly class UtilizationAlert
{
    public function __construct(
        public string $budgetId,
        public string $budgetName,
        public string $periodId,
        public float $utilizationPercentage,
        public Money $allocatedAmount,
        public Money $actualAmount,
        public Money $committedAmount,
        public AlertSeverity $severity,
        public \DateTimeImmutable $triggeredAt
    ) {}

    public function getBudgetId(): string
    {
        return $this->budgetId;
    }

    public function getBudgetName(): string
    {
        return $this->budgetName;
    }

    public function getPeriodId(): string
    {
        return $this->periodId;
    }

    public function getUtilizationPercentage(): float
    {
        return $this->utilizationPercentage;
    }

    public function getAllocatedAmount(): Money
    {
        return $this->allocatedAmount;
    }

    public function getActualAmount(): Money
    {
        return $this->actualAmount;
    }

    public function getCommittedAmount(): Money
    {
        return $this->committedAmount;
    }

    public function getSeverity(): AlertSeverity
    {
        return $this->severity;
    }

    public function getTriggeredAt(): \DateTimeImmutable
    {
        return $this->triggeredAt;
    }

    /**
     * Get available amount (allocated - actual - committed)
     */
    public function getAvailableAmount(): Money
    {
        return $this->allocatedAmount
            ->subtract($this->actualAmount)
            ->subtract($this->committedAmount);
    }

    /**
     * Check if budget is exceeded
     */
    public function isBudgetExceeded(): bool
    {
        return $this->utilizationPercentage >= 100.0;
    }

    /**
     * Get formatted alert message
     */
    public function getFormattedMessage(): string
    {
        return sprintf(
            '[%s] Budget "%s" utilization at %.2f%% - Allocated: %s, Actual: %s, Committed: %s',
            $this->severity->label(),
            $this->budgetName,
            $this->utilizationPercentage,
            (string) $this->allocatedAmount,
            (string) $this->actualAmount,
            (string) $this->committedAmount
        );
    }
}
