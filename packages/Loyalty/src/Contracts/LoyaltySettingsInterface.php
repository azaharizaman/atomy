<?php

declare(strict_types=1);

namespace Nexus\Loyalty\Contracts;

/**
 * Interface for Loyalty package settings.
 * This allows the package to remain decoupled from the Setting package
 * while still being configurable at runtime via orchestrators.
 */
interface LoyaltySettingsInterface
{
    /**
     * Get the base rate of points per currency unit.
     */
    public function getBaseRate(): float;

    /**
     * Get the map of experiential event types to point values.
     * 
     * @return array<string, int>
     */
    public function getExperientialRewards(): array;

    /**
     * Get the tier configuration.
     * 
     * @return array<string, array{threshold: int, name: string, benefits: array<string>}>
     */
    public function getTierConfig(): array;

    /**
     * Get the number of months a member retains a tier.
     */
    public function getDefaultRetentionMonths(): int;

    /**
     * Get the sliding window for qualifying points in days.
     */
    public function getEvaluationWindowDays(): int;

    /**
     * Get the minimum balance required to perform a redemption.
     */
    public function getMinBalanceThreshold(): int;

    /**
     * Get the incremental step for redemptions.
     */
    public function getIncrementalStep(): int;

    /**
     * Get the default number of months before points expire.
     */
    public function getDefaultExpiryMonths(): int;
}
