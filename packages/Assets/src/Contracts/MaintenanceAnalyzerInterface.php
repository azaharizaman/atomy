<?php

declare(strict_types=1);

namespace Nexus\Assets\Contracts;

/**
 * Maintenance Analyzer Interface (Tier 2+)
 *
 * Analyzes Total Cost of Ownership and provides replacement recommendations.
 */
interface MaintenanceAnalyzerInterface
{
    /**
     * Calculate Total Cost of Ownership
     *
     * Formula: Acquisition Cost + Total Maintenance Cost - Current Net Book Value
     *
     * @param string $assetId Asset identifier
     * @return float TCO amount
     * @throws \Nexus\Assets\Exceptions\AssetNotFoundException
     */
    public function calculateTotalCostOfOwnership(string $assetId): float;

    /**
     * Recommend asset replacement
     *
     * Returns true if maintenance cost in last 12 months > 50% of current net book value
     *
     * @param string $assetId Asset identifier
     * @return bool True if replacement recommended
     * @throws \Nexus\Assets\Exceptions\AssetNotFoundException
     */
    public function recommendReplacement(string $assetId): bool;

    /**
     * Get maintenance cost for period
     *
     * @param string $assetId Asset identifier
     * @param \DateTimeInterface $startDate Period start
     * @param \DateTimeInterface $endDate Period end
     * @return float Total maintenance cost
     * @throws \Nexus\Assets\Exceptions\AssetNotFoundException
     */
    public function getMaintenanceCostForPeriod(
        string $assetId,
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate
    ): float;

    /**
     * Get total downtime hours for period
     *
     * @param string $assetId Asset identifier
     * @param \DateTimeInterface $startDate Period start
     * @param \DateTimeInterface $endDate Period end
     * @return float Total downtime hours
     * @throws \Nexus\Assets\Exceptions\AssetNotFoundException
     */
    public function getTotalDowntimeForPeriod(
        string $assetId,
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate
    ): float;

    /**
     * Get average maintenance cost per year
     *
     * @param string $assetId Asset identifier
     * @return float Average annual maintenance cost
     * @throws \Nexus\Assets\Exceptions\AssetNotFoundException
     */
    public function getAverageAnnualMaintenanceCost(string $assetId): float;
}
