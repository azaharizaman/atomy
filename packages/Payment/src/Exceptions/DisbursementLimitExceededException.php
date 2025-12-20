<?php

declare(strict_types=1);

namespace Nexus\Payment\Exceptions;

use Nexus\Common\ValueObjects\Money;
use Nexus\Payment\Enums\LimitPeriod;

/**
 * Exception thrown when a disbursement limit is exceeded.
 *
 * Used for PAY-035: Disbursement limits and controls.
 */
final class DisbursementLimitExceededException extends PaymentException
{
    private function __construct(
        string $message,
        public readonly ?Money $amount = null,
        public readonly ?Money $limit = null,
        public readonly ?LimitPeriod $period = null,
        public readonly ?Money $currentUsage = null,
        public readonly ?int $currentCount = null,
        public readonly ?int $countLimit = null,
    ) {
        parent::__construct($message);
    }

    /**
     * Create exception for per-transaction limit exceeded.
     */
    public static function perTransactionLimitExceeded(Money $amount, Money $limit): self
    {
        return new self(
            message: sprintf(
                'Disbursement amount %s exceeds per-transaction limit of %s',
                $amount->format(),
                $limit->format(),
            ),
            amount: $amount,
            limit: $limit,
            period: LimitPeriod::PER_TRANSACTION,
        );
    }

    /**
     * Create exception for period limit exceeded.
     */
    public static function periodLimitExceeded(
        Money $amount,
        Money $currentUsage,
        Money $limit,
        LimitPeriod $period,
    ): self {
        $projectedTotal = $currentUsage->add($amount);

        return new self(
            message: sprintf(
                '%s disbursement limit exceeded. Current usage: %s, Attempted: %s, Would total: %s, Limit: %s',
                $period->label(),
                $currentUsage->format(),
                $amount->format(),
                $projectedTotal->format(),
                $limit->format(),
            ),
            amount: $amount,
            limit: $limit,
            period: $period,
            currentUsage: $currentUsage,
        );
    }

    /**
     * Create exception for count limit exceeded.
     */
    public static function countLimitExceeded(
        int $currentCount,
        int $limit,
        LimitPeriod $period,
    ): self {
        return new self(
            message: sprintf(
                '%s disbursement count limit exceeded. Current count: %d, Limit: %d',
                $period->label(),
                $currentCount,
                $limit,
            ),
            period: $period,
            currentCount: $currentCount,
            countLimit: $limit,
        );
    }

    /**
     * Create exception for daily limit exceeded.
     */
    public static function dailyLimitExceeded(Money $amount, Money $currentUsage, Money $limit): self
    {
        return self::periodLimitExceeded($amount, $currentUsage, $limit, LimitPeriod::DAILY);
    }

    /**
     * Create exception for weekly limit exceeded.
     */
    public static function weeklyLimitExceeded(Money $amount, Money $currentUsage, Money $limit): self
    {
        return self::periodLimitExceeded($amount, $currentUsage, $limit, LimitPeriod::WEEKLY);
    }

    /**
     * Create exception for monthly limit exceeded.
     */
    public static function monthlyLimitExceeded(Money $amount, Money $currentUsage, Money $limit): self
    {
        return self::periodLimitExceeded($amount, $currentUsage, $limit, LimitPeriod::MONTHLY);
    }

    /**
     * Check if this is a per-transaction limit violation.
     */
    public function isPerTransactionLimit(): bool
    {
        return $this->period === LimitPeriod::PER_TRANSACTION;
    }

    /**
     * Check if this is a count limit violation (vs amount limit).
     */
    public function isCountLimit(): bool
    {
        return $this->countLimit !== null;
    }
}
