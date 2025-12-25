<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Contracts;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentRails\DTOs\RailSelectionCriteria;

/**
 * Contract for selecting the appropriate payment rail.
 *
 * The rail selector evaluates available rails and recommends
 * the best rail based on eligibility and scoring.
 */
interface RailSelectorInterface
{
    /**
     * Select the best payment rail for given selection criteria.
     */
    public function select(RailSelectionCriteria $criteria): PaymentRailInterface;

    /**
     * Get all eligible rails for given criteria.
     *
     * @return array<PaymentRailInterface>
     */
    public function getEligibleRails(RailSelectionCriteria $criteria): array;

    /**
     * Get optimal rail for domestic transfers.
     */
    public function getOptimalDomesticRail(Money $amount, string $currency): PaymentRailInterface;

    /**
     * Get optimal rail for international transfers.
     */
    public function getOptimalInternationalRail(
        Money $amount,
        string $sourceCurrency,
        string $destinationCountry,
    ): PaymentRailInterface;

    /**
     * Get the fastest available rail (urgent selection).
     */
    public function getFastestRail(RailSelectionCriteria $criteria): PaymentRailInterface;

    /**
     * Get the cheapest available rail.
     */
    public function getCheapestRail(RailSelectionCriteria $criteria): PaymentRailInterface;
}
