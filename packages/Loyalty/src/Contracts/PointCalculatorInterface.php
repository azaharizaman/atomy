<?php

declare(strict_types=1);

namespace Nexus\Loyalty\Contracts;

use Nexus\Common\ValueObjects\Money;

/**
 * Interface for calculating point accrual based on transaction data.
 */
interface PointCalculatorInterface
{
    /**
     * Calculate points to accrue for a given transaction amount.
     *
     * @param Money $amount The transaction amount.
     * @param string $currency The transaction currency.
     * @param array<string, float> $multipliers Key-value pairs of multipliers (e.g., ['tier' => 1.5, 'promo' => 2.0]).
     * @return int The number of points to accrue.
     */
    public function calculateAccrual(Money $amount, string $currency, array $multipliers = []): int;

    /**
     * Calculate points for non-transactional "Experiential Rewards".
     *
     * @param string $eventType The type of experiential event (e.g., 'social_share', 'eco_action').
     * @param array<string, mixed> $context Additional data for calculation.
     * @return int The number of points to award.
     */
    public function calculateExperientialReward(string $eventType, array $context = []): int;
}
