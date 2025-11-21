<?php

declare(strict_types=1);

namespace Nexus\Assets\Services;

use Nexus\Assets\Contracts\AssetInterface;
use Nexus\Assets\Contracts\AssetRepositoryInterface;
use Nexus\Assets\Contracts\MaintenanceAnalyzerInterface;
use Psr\Log\LoggerInterface;

/**
 * Maintenance Analyzer - TCO and predictive maintenance analytics (Tier 2).
 *
 * Capabilities:
 * - Total Cost of Ownership (TCO) calculation
 * - Maintenance frequency analysis
 * - Cost trend prediction
 * - Preventive maintenance scheduling recommendations
 *
 * TCO Formula:
 * TCO = Acquisition Cost + Total Maintenance + Total Repairs + Total Downtime Cost - Salvage Value
 *
 * Use Cases:
 * - Budget forecasting for maintenance departments
 * - Asset replacement decision support
 * - Vendor performance analysis (warranty claim rates)
 */
final readonly class MaintenanceAnalyzer implements MaintenanceAnalyzerInterface
{
    public function __construct(
        private AssetRepositoryInterface $repository,
        private LoggerInterface $logger
    ) {}

    public function calculateTCO(string $assetId): array
    {
        $asset = $this->repository->findById($assetId);

        // Get all maintenance records for the asset
        $maintenanceRecords = $this->repository->getMaintenanceRecords($assetId);

        $totalMaintenance = 0.0;
        $totalRepairs = 0.0;
        $plannedMaintenance = 0;
        $unplannedMaintenance = 0;

        foreach ($maintenanceRecords as $record) {
            $cost = $record->getCost();

            if ($record->getType()->isPlanned()) {
                $totalMaintenance += $cost;
                $plannedMaintenance++;
            } else {
                $totalRepairs += $cost;
                $unplannedMaintenance++;
            }
        }

        // Calculate ownership duration
        $acquisitionDate = $asset->getAcquisitionDate();
        $now = new \DateTimeImmutable();
        $ownershipMonths = $this->calculateMonthsBetween($acquisitionDate, $now);

        // Calculate average monthly costs
        $avgMonthlyMaintenance = $ownershipMonths > 0 ? $totalMaintenance / $ownershipMonths : 0.0;
        $avgMonthlyRepairs = $ownershipMonths > 0 ? $totalRepairs / $ownershipMonths : 0.0;

        // Project TCO over remaining useful life
        $remainingMonths = max(0, $asset->getUsefulLifeMonths() - $ownershipMonths);
        $projectedMaintenanceCost = $avgMonthlyMaintenance * $remainingMonths;
        $projectedRepairCost = $avgMonthlyRepairs * $remainingMonths;

        // Calculate total TCO
        $historicalTCO = $asset->getCost() + $totalMaintenance + $totalRepairs;
        $projectedTCO = $historicalTCO + $projectedMaintenanceCost + $projectedRepairCost - $asset->getSalvageValue();

        return [
            'asset_id' => $assetId,
            'asset_tag' => $asset->getAssetTag(),
            'acquisition_cost' => $asset->getCost(),
            'salvage_value' => $asset->getSalvageValue(),
            'ownership_months' => $ownershipMonths,
            'remaining_months' => $remainingMonths,
            'historical' => [
                'total_maintenance' => $totalMaintenance,
                'total_repairs' => $totalRepairs,
                'total_cost' => $historicalTCO,
                'planned_count' => $plannedMaintenance,
                'unplanned_count' => $unplannedMaintenance,
            ],
            'projected' => [
                'maintenance_cost' => $projectedMaintenanceCost,
                'repair_cost' => $projectedRepairCost,
                'total_tco' => $projectedTCO,
            ],
            'averages' => [
                'monthly_maintenance' => $avgMonthlyMaintenance,
                'monthly_repairs' => $avgMonthlyRepairs,
                'cost_per_month' => $avgMonthlyMaintenance + $avgMonthlyRepairs,
            ],
        ];
    }

    public function analyzeMaintenancePattern(string $assetId): array
    {
        $asset = $this->repository->findById($assetId);
        $maintenanceRecords = $this->repository->getMaintenanceRecords($assetId);

        if (empty($maintenanceRecords)) {
            return [
                'asset_id' => $assetId,
                'pattern' => 'NO_DATA',
                'recommendation' => 'Establish baseline maintenance schedule',
            ];
        }

        // Calculate time between maintenance events
        $intervals = [];
        $previousDate = null;

        foreach ($maintenanceRecords as $record) {
            if ($previousDate !== null) {
                $days = $previousDate->diff($record->getCompletedAt())->days;
                $intervals[] = $days;
            }
            $previousDate = $record->getCompletedAt();
        }

        if (empty($intervals)) {
            return [
                'asset_id' => $assetId,
                'pattern' => 'INSUFFICIENT_DATA',
                'recommendation' => 'Need at least 2 maintenance records for pattern analysis',
            ];
        }

        // Calculate statistics
        $avgInterval = array_sum($intervals) / count($intervals);
        $minInterval = min($intervals);
        $maxInterval = max($intervals);

        // Determine pattern
        $pattern = $this->classifyMaintenancePattern($avgInterval, $intervals);

        // Generate recommendation
        $recommendation = $this->generateMaintenanceRecommendation($pattern, $avgInterval);

        return [
            'asset_id' => $assetId,
            'asset_tag' => $asset->getAssetTag(),
            'pattern' => $pattern,
            'statistics' => [
                'total_events' => count($maintenanceRecords),
                'avg_interval_days' => round($avgInterval, 1),
                'min_interval_days' => $minInterval,
                'max_interval_days' => $maxInterval,
            ],
            'recommendation' => $recommendation,
        ];
    }

    public function predictNextMaintenance(string $assetId): ?\DateTimeImmutable
    {
        $pattern = $this->analyzeMaintenancePattern($assetId);

        if (!isset($pattern['statistics']['avg_interval_days'])) {
            return null;
        }

        $maintenanceRecords = $this->repository->getMaintenanceRecords($assetId);
        $lastMaintenance = end($maintenanceRecords);

        if (!$lastMaintenance) {
            return null;
        }

        $avgInterval = (int) $pattern['statistics']['avg_interval_days'];
        
        return $lastMaintenance->getCompletedAt()->modify("+{$avgInterval} days");
    }

    /**
     * Classify maintenance pattern based on interval statistics.
     */
    private function classifyMaintenancePattern(float $avgInterval, array $intervals): string
    {
        $variance = $this->calculateVariance($intervals, $avgInterval);
        $stdDev = sqrt($variance);
        $coefficientOfVariation = $avgInterval > 0 ? ($stdDev / $avgInterval) : 0;

        if ($coefficientOfVariation < 0.2) {
            return 'REGULAR'; // Predictable pattern
        }

        if ($coefficientOfVariation < 0.5) {
            return 'MODERATE'; // Some variation
        }

        return 'IRREGULAR'; // High unpredictability
    }

    /**
     * Generate maintenance recommendation based on pattern.
     */
    private function generateMaintenanceRecommendation(string $pattern, float $avgInterval): string
    {
        return match ($pattern) {
            'REGULAR' => sprintf(
                'Asset shows predictable maintenance pattern. Schedule preventive maintenance every %.0f days.',
                $avgInterval
            ),
            'MODERATE' => sprintf(
                'Asset shows moderate variation. Consider preventive maintenance every %.0f days with ±15%% buffer.',
                $avgInterval
            ),
            'IRREGULAR' => 'Asset shows irregular maintenance pattern. Investigate root cause or consider replacement.',
            default => 'Insufficient data for recommendation.',
        };
    }

    /**
     * Calculate variance of intervals.
     */
    private function calculateVariance(array $values, float $mean): float
    {
        $squaredDiffs = array_map(fn($val) => ($val - $mean) ** 2, $values);
        
        return array_sum($squaredDiffs) / count($values);
    }

    /**
     * Calculate months between two dates.
     */
    private function calculateMonthsBetween(\DateTimeImmutable $start, \DateTimeImmutable $end): float
    {
        $interval = $start->diff($end);
        
        return ($interval->y * 12) + $interval->m + ($interval->d / 30);
    }
}
