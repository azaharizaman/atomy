<?php

declare(strict_types=1);

namespace Nexus\Payment\ValueObjects;

use Nexus\Common\ValueObjects\Money;
use Nexus\Payment\Enums\LimitPeriod;
use Nexus\Payment\Exceptions\DisbursementLimitExceededException;

/**
 * Value object representing disbursement limits and controls.
 *
 * Used for PAY-035: Disbursement limits and controls.
 * Supports per-transaction, daily, weekly, monthly, and custom period limits.
 */
final class DisbursementLimits
{
    /**
     * @param Money|null $perTransactionLimit Maximum amount per single disbursement
     * @param Money|null $dailyLimit Maximum total amount per day
     * @param Money|null $weeklyLimit Maximum total amount per week
     * @param Money|null $monthlyLimit Maximum total amount per month
     * @param int|null $dailyCountLimit Maximum number of disbursements per day
     * @param int|null $weeklyCountLimit Maximum number of disbursements per week
     * @param int|null $monthlyCountLimit Maximum number of disbursements per month
     * @param bool $requiresApprovalAboveLimit Whether amounts above limits require manual approval
     */
    private function __construct(
        private readonly ?Money $perTransactionLimit,
        private readonly ?Money $dailyLimit,
        private readonly ?Money $weeklyLimit,
        private readonly ?Money $monthlyLimit,
        private readonly ?int $dailyCountLimit,
        private readonly ?int $weeklyCountLimit,
        private readonly ?int $monthlyCountLimit,
        private readonly bool $requiresApprovalAboveLimit = false,
    ) {}

    /**
     * Create limits with no restrictions.
     */
    public static function none(): self
    {
        return new self(
            perTransactionLimit: null,
            dailyLimit: null,
            weeklyLimit: null,
            monthlyLimit: null,
            dailyCountLimit: null,
            weeklyCountLimit: null,
            monthlyCountLimit: null,
            requiresApprovalAboveLimit: false,
        );
    }

    /**
     * Create limits from configuration array.
     *
     * @param array{
     *     per_transaction?: array{amount: int|float, currency: string},
     *     daily?: array{amount: int|float, currency: string},
     *     weekly?: array{amount: int|float, currency: string},
     *     monthly?: array{amount: int|float, currency: string},
     *     daily_count?: int,
     *     weekly_count?: int,
     *     monthly_count?: int,
     *     requires_approval_above_limit?: bool,
     * } $config
     */
    public static function fromConfig(array $config): self
    {
        $createMoney = static fn(?array $data): ?Money =>
            $data !== null ? Money::of($data['amount'], $data['currency']) : null;

        return new self(
            perTransactionLimit: $createMoney($config['per_transaction'] ?? null),
            dailyLimit: $createMoney($config['daily'] ?? null),
            weeklyLimit: $createMoney($config['weekly'] ?? null),
            monthlyLimit: $createMoney($config['monthly'] ?? null),
            dailyCountLimit: $config['daily_count'] ?? null,
            weeklyCountLimit: $config['weekly_count'] ?? null,
            monthlyCountLimit: $config['monthly_count'] ?? null,
            requiresApprovalAboveLimit: $config['requires_approval_above_limit'] ?? false,
        );
    }

    /**
     * Create limits from array (deserialization).
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return self::fromConfig($data);
    }

    /**
     * Validate a disbursement amount against per-transaction limit.
     *
     * @throws DisbursementLimitExceededException If limit is exceeded
     */
    public function validateAmount(Money $amount): void
    {
        if ($this->perTransactionLimit === null) {
            return;
        }

        if ($amount->greaterThan($this->perTransactionLimit)) {
            throw DisbursementLimitExceededException::perTransactionLimitExceeded(
                amount: $amount,
                limit: $this->perTransactionLimit,
            );
        }
    }

    /**
     * Validate against period limit with current usage.
     *
     * @param Money $amount Amount to validate
     * @param Money $currentUsage Current period usage (excluding this transaction)
     * @param LimitPeriod $period The period to check
     *
     * @throws DisbursementLimitExceededException If limit would be exceeded
     */
    public function validatePeriodAmount(Money $amount, Money $currentUsage, LimitPeriod $period): void
    {
        $limit = $this->getLimitForPeriod($period);

        if ($limit === null) {
            return;
        }

        $projectedTotal = $currentUsage->add($amount);

        if ($projectedTotal->greaterThan($limit)) {
            throw DisbursementLimitExceededException::periodLimitExceeded(
                amount: $amount,
                currentUsage: $currentUsage,
                limit: $limit,
                period: $period,
            );
        }
    }

    /**
     * Validate against period count limit.
     *
     * @param int $currentCount Current period transaction count (excluding this transaction)
     * @param LimitPeriod $period The period to check
     *
     * @throws DisbursementLimitExceededException If limit would be exceeded
     */
    public function validatePeriodCount(int $currentCount, LimitPeriod $period): void
    {
        $limit = $this->getCountLimitForPeriod($period);

        if ($limit === null) {
            return;
        }

        if ($currentCount >= $limit) {
            throw DisbursementLimitExceededException::countLimitExceeded(
                currentCount: $currentCount,
                limit: $limit,
                period: $period,
            );
        }
    }

    /**
     * Check if amount exceeds per-transaction limit (without throwing).
     */
    public function exceedsPerTransactionLimit(Money $amount): bool
    {
        if ($this->perTransactionLimit === null) {
            return false;
        }

        return $amount->greaterThan($this->perTransactionLimit);
    }

    /**
     * Check if amount would require approval based on limits.
     */
    public function requiresApproval(Money $amount): bool
    {
        if (!$this->requiresApprovalAboveLimit) {
            return false;
        }

        return $this->exceedsPerTransactionLimit($amount);
    }

    /**
     * Get the limit for a specific period.
     */
    public function getLimitForPeriod(LimitPeriod $period): ?Money
    {
        return match ($period) {
            LimitPeriod::PER_TRANSACTION => $this->perTransactionLimit,
            LimitPeriod::DAILY => $this->dailyLimit,
            LimitPeriod::WEEKLY => $this->weeklyLimit,
            LimitPeriod::MONTHLY => $this->monthlyLimit,
            LimitPeriod::QUARTERLY, LimitPeriod::ANNUALLY => null,
        };
    }

    /**
     * Get the count limit for a specific period.
     */
    public function getCountLimitForPeriod(LimitPeriod $period): ?int
    {
        return match ($period) {
            LimitPeriod::DAILY => $this->dailyCountLimit,
            LimitPeriod::WEEKLY => $this->weeklyCountLimit,
            LimitPeriod::MONTHLY => $this->monthlyCountLimit,
            default => null,
        };
    }

    /**
     * Check if any limits are configured.
     */
    public function hasLimits(): bool
    {
        return $this->perTransactionLimit !== null
            || $this->dailyLimit !== null
            || $this->weeklyLimit !== null
            || $this->monthlyLimit !== null
            || $this->dailyCountLimit !== null
            || $this->weeklyCountLimit !== null
            || $this->monthlyCountLimit !== null;
    }

    /**
     * Get per-transaction limit.
     */
    public function getPerTransactionLimit(): ?Money
    {
        return $this->perTransactionLimit;
    }

    /**
     * Get daily limit.
     */
    public function getDailyLimit(): ?Money
    {
        return $this->dailyLimit;
    }

    /**
     * Get weekly limit.
     */
    public function getWeeklyLimit(): ?Money
    {
        return $this->weeklyLimit;
    }

    /**
     * Get monthly limit.
     */
    public function getMonthlyLimit(): ?Money
    {
        return $this->monthlyLimit;
    }

    /**
     * Create a new instance with updated per-transaction limit.
     */
    public function withPerTransactionLimit(Money $limit): self
    {
        return new self(
            perTransactionLimit: $limit,
            dailyLimit: $this->dailyLimit,
            weeklyLimit: $this->weeklyLimit,
            monthlyLimit: $this->monthlyLimit,
            dailyCountLimit: $this->dailyCountLimit,
            weeklyCountLimit: $this->weeklyCountLimit,
            monthlyCountLimit: $this->monthlyCountLimit,
            requiresApprovalAboveLimit: $this->requiresApprovalAboveLimit,
        );
    }

    /**
     * Create a new instance with updated daily limit.
     */
    public function withDailyLimit(Money $limit): self
    {
        return new self(
            perTransactionLimit: $this->perTransactionLimit,
            dailyLimit: $limit,
            weeklyLimit: $this->weeklyLimit,
            monthlyLimit: $this->monthlyLimit,
            dailyCountLimit: $this->dailyCountLimit,
            weeklyCountLimit: $this->weeklyCountLimit,
            monthlyCountLimit: $this->monthlyCountLimit,
            requiresApprovalAboveLimit: $this->requiresApprovalAboveLimit,
        );
    }

    /**
     * Create a new instance with updated weekly limit.
     */
    public function withWeeklyLimit(Money $limit): self
    {
        return new self(
            perTransactionLimit: $this->perTransactionLimit,
            dailyLimit: $this->dailyLimit,
            weeklyLimit: $limit,
            monthlyLimit: $this->monthlyLimit,
            dailyCountLimit: $this->dailyCountLimit,
            weeklyCountLimit: $this->weeklyCountLimit,
            monthlyCountLimit: $this->monthlyCountLimit,
            requiresApprovalAboveLimit: $this->requiresApprovalAboveLimit,
        );
    }

    /**
     * Create a new instance with updated monthly limit.
     */
    public function withMonthlyLimit(Money $limit): self
    {
        return new self(
            perTransactionLimit: $this->perTransactionLimit,
            dailyLimit: $this->dailyLimit,
            weeklyLimit: $this->weeklyLimit,
            monthlyLimit: $limit,
            dailyCountLimit: $this->dailyCountLimit,
            weeklyCountLimit: $this->weeklyCountLimit,
            monthlyCountLimit: $this->monthlyCountLimit,
            requiresApprovalAboveLimit: $this->requiresApprovalAboveLimit,
        );
    }

    /**
     * Create a new instance with updated daily count limit.
     */
    public function withDailyCountLimit(int $limit): self
    {
        return new self(
            perTransactionLimit: $this->perTransactionLimit,
            dailyLimit: $this->dailyLimit,
            weeklyLimit: $this->weeklyLimit,
            monthlyLimit: $this->monthlyLimit,
            dailyCountLimit: $limit,
            weeklyCountLimit: $this->weeklyCountLimit,
            monthlyCountLimit: $this->monthlyCountLimit,
            requiresApprovalAboveLimit: $this->requiresApprovalAboveLimit,
        );
    }

    /**
     * Create a new instance with updated weekly count limit.
     */
    public function withWeeklyCountLimit(int $limit): self
    {
        return new self(
            perTransactionLimit: $this->perTransactionLimit,
            dailyLimit: $this->dailyLimit,
            weeklyLimit: $this->weeklyLimit,
            monthlyLimit: $this->monthlyLimit,
            dailyCountLimit: $this->dailyCountLimit,
            weeklyCountLimit: $limit,
            monthlyCountLimit: $this->monthlyCountLimit,
            requiresApprovalAboveLimit: $this->requiresApprovalAboveLimit,
        );
    }

    /**
     * Create a new instance with updated monthly count limit.
     */
    public function withMonthlyCountLimit(int $limit): self
    {
        return new self(
            perTransactionLimit: $this->perTransactionLimit,
            dailyLimit: $this->dailyLimit,
            weeklyLimit: $this->weeklyLimit,
            monthlyLimit: $this->monthlyLimit,
            dailyCountLimit: $this->dailyCountLimit,
            weeklyCountLimit: $this->weeklyCountLimit,
            monthlyCountLimit: $limit,
            requiresApprovalAboveLimit: $this->requiresApprovalAboveLimit,
        );
    }

    /**
     * Create a new instance with approval requirement enabled.
     */
    public function withApprovalRequired(bool $required = true): self
    {
        return new self(
            perTransactionLimit: $this->perTransactionLimit,
            dailyLimit: $this->dailyLimit,
            weeklyLimit: $this->weeklyLimit,
            monthlyLimit: $this->monthlyLimit,
            dailyCountLimit: $this->dailyCountLimit,
            weeklyCountLimit: $this->weeklyCountLimit,
            monthlyCountLimit: $this->monthlyCountLimit,
            requiresApprovalAboveLimit: $required,
        );
    }

    /**
     * Convert to array for serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'per_transaction' => $this->perTransactionLimit?->toArray(),
            'daily' => $this->dailyLimit?->toArray(),
            'weekly' => $this->weeklyLimit?->toArray(),
            'monthly' => $this->monthlyLimit?->toArray(),
            'daily_count' => $this->dailyCountLimit,
            'weekly_count' => $this->weeklyCountLimit,
            'monthly_count' => $this->monthlyCountLimit,
            'requires_approval_above_limit' => $this->requiresApprovalAboveLimit,
        ];
    }
}
