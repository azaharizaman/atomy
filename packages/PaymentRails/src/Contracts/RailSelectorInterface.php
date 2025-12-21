<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Contracts;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentRails\Enums\RailType;
use Nexus\PaymentRails\ValueObjects\RailCapabilities;

/**
 * Contract for selecting the appropriate payment rail.
 *
 * The rail selector evaluates available rails and recommends
 * the best rail based on cost, speed, and capabilities.
 */
interface RailSelectorInterface
{
    /**
     * Get all available payment rails.
     *
     * @return array<PaymentRailInterface>
     */
    public function getAvailableRails(): array;

    /**
     * Get a specific rail by type.
     */
    public function getRail(RailType $railType): ?PaymentRailInterface;

    /**
     * Check if a specific rail type is available.
     */
    public function isRailAvailable(RailType $railType): bool;

    /**
     * Select the best rail for a payment.
     *
     * @param Money $amount Payment amount
     * @param string $currency Currency code
     * @param bool $isUrgent Whether the payment is urgent
     * @param bool $isDomestic Whether the payment is domestic
     * @param array<string, mixed> $preferences Additional preferences
     */
    public function selectBestRail(
        Money $amount,
        string $currency,
        bool $isUrgent = false,
        bool $isDomestic = true,
        array $preferences = [],
    ): PaymentRailInterface;

    /**
     * Get rails that support a specific amount and currency.
     *
     * @return array<PaymentRailInterface>
     */
    public function getRailsForPayment(Money $amount, string $currency): array;

    /**
     * Get the cheapest rail for a payment.
     */
    public function getCheapestRail(Money $amount, string $currency): ?PaymentRailInterface;

    /**
     * Get the fastest rail for a payment.
     */
    public function getFastestRail(Money $amount, string $currency): ?PaymentRailInterface;

    /**
     * Get rail capabilities comparison.
     *
     * @return array<RailType, RailCapabilities>
     */
    public function compareCapabilities(): array;

    /**
     * Get estimated cost for using a specific rail.
     *
     * @param RailType $railType
     * @param Money $amount
     * @return Money|null Estimated cost or null if unavailable
     */
    public function getEstimatedCost(RailType $railType, Money $amount): ?Money;
}
