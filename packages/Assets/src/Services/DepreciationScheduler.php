<?php

declare(strict_types=1);

namespace Nexus\Assets\Services;

use Nexus\Assets\Contracts\AssetRepositoryInterface;
use Nexus\Assets\Contracts\DepreciationEngineInterface;
use Nexus\Assets\Core\Engine\DoubleDecliningBalanceDepreciation;
use Nexus\Assets\Core\Engine\StraightLineDepreciation;
use Nexus\Assets\Core\Engine\UnitsOfProductionDepreciation;
use Nexus\Assets\Enums\AssetStatus;
use Nexus\Assets\Enums\DepreciationMethod;
use Nexus\Assets\Events\AssetDepreciatedEvent;
use Nexus\Period\Services\PeriodManager;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * Depreciation Scheduler - Batch depreciation processing engine.
 *
 * Designed for monthly automation via Nexus\Scheduler integration.
 *
 * Workflow:
 * 1. Retrieve all depreciable assets (status = ACTIVE)
 * 2. Determine depreciation method for each asset
 * 3. Calculate depreciation for the period
 * 4. Record depreciation via AssetManager
 * 5. Dispatch batch event for audit logging
 *
 * Integration Points:
 * - Nexus\Period: Validates period is open
 * - Nexus\Scheduler: JobHandlerInterface implementation
 * - Nexus\Finance: GL posting via event listener (Tier 3)
 */
final readonly class DepreciationScheduler
{
    public function __construct(
        private AssetRepositoryInterface $repository,
        private AssetManager $assetManager,
        private PeriodManager $periodManager,
        private EventDispatcherInterface $eventDispatcher,
        private LoggerInterface $logger
    ) {}

    /**
     * Process depreciation for all active assets in the given period.
     *
     * @param \DateTimeImmutable $periodStart Start of depreciation period
     * @param \DateTimeImmutable $periodEnd End of depreciation period
     * @param array<string, mixed> $options Processing options (e.g., 'asset_ids', 'category_ids')
     * @return array{processed: int, total_amount: float, failures: array}
     */
    public function processDepreciation(
        \DateTimeImmutable $periodStart,
        \DateTimeImmutable $periodEnd,
        array $options = []
    ): array {
        // Validate period is open (integration with Nexus\Period)
        $this->validatePeriodIsOpen($periodStart, $periodEnd);

        // Retrieve depreciable assets
        $assets = $this->getDepreciableAssets($options);

        $processed = 0;
        $totalAmount = 0.0;
        $failures = [];

        $this->logger->info('Starting depreciation batch', [
            'period_start' => $periodStart->format('Y-m-d'),
            'period_end' => $periodEnd->format('Y-m-d'),
            'asset_count' => count($assets),
        ]);

        foreach ($assets as $asset) {
            try {
                // Get appropriate depreciation engine
                $engine = $this->getDepreciationEngine($asset->getDepreciationMethod());

                // Determine units consumed (if applicable)
                $unitsConsumed = $this->getUnitsConsumed($asset, $periodStart, $periodEnd, $options);

                // Record depreciation
                $amount = $this->assetManager->recordDepreciation(
                    assetId: $asset->getId(),
                    periodStart: $periodStart,
                    periodEnd: $periodEnd,
                    engine: $engine,
                    unitsConsumed: $unitsConsumed
                );

                $processed++;
                $totalAmount += $amount;

                $this->logger->debug('Asset depreciated', [
                    'asset_id' => $asset->getId(),
                    'asset_tag' => $asset->getAssetTag(),
                    'amount' => $amount,
                ]);
            } catch (\Throwable $e) {
                $failures[] = [
                    'asset_id' => $asset->getId(),
                    'asset_tag' => $asset->getAssetTag(),
                    'error' => $e->getMessage(),
                ];

                $this->logger->error('Depreciation failed for asset', [
                    'asset_id' => $asset->getId(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Dispatch batch completion event
        $this->eventDispatcher->dispatch(
            new AssetDepreciatedEvent(
                periodStart: $periodStart,
                periodEnd: $periodEnd,
                assetsProcessed: $processed,
                totalDepreciation: $totalAmount,
                failures: count($failures)
            )
        );

        $this->logger->info('Depreciation batch completed', [
            'processed' => $processed,
            'total_amount' => $totalAmount,
            'failures' => count($failures),
        ]);

        return [
            'processed' => $processed,
            'total_amount' => $totalAmount,
            'failures' => $failures,
        ];
    }

    /**
     * Retrieve all assets eligible for depreciation.
     */
    private function getDepreciableAssets(array $options): array
    {
        $filters = [
            'status' => AssetStatus::ACTIVE,
        ];

        // Optional filters
        if (isset($options['asset_ids'])) {
            $filters['ids'] = $options['asset_ids'];
        }

        if (isset($options['category_ids'])) {
            $filters['category_ids'] = $options['category_ids'];
        }

        if (isset($options['location_ids'])) {
            $filters['location_ids'] = $options['location_ids'];
        }

        return $this->repository->findAll($filters);
    }

    /**
     * Get depreciation engine based on method.
     */
    private function getDepreciationEngine(DepreciationMethod $method): DepreciationEngineInterface
    {
        return match ($method) {
            DepreciationMethod::STRAIGHT_LINE => new StraightLineDepreciation(),
            DepreciationMethod::DOUBLE_DECLINING_BALANCE => new DoubleDecliningBalanceDepreciation(),
            DepreciationMethod::UNITS_OF_PRODUCTION => new UnitsOfProductionDepreciation(),
        };
    }

    /**
     * Get units consumed for the period (Tier 3 - Units of Production).
     *
     * In real implementation, this would query:
     * - Manufacturing\ProductionRecords
     * - Inventory\StockMovements
     * - Custom usage tracking tables
     */
    private function getUnitsConsumed(
        object $asset,
        \DateTimeImmutable $periodStart,
        \DateTimeImmutable $periodEnd,
        array $options
    ): ?float {
        // Check if units provided explicitly in options
        if (isset($options['units_consumed'][$asset->getId()])) {
            return $options['units_consumed'][$asset->getId()];
        }

        // For Units of Production method, units must be provided
        if ($asset->getDepreciationMethod() === DepreciationMethod::UNITS_OF_PRODUCTION) {
            // In real implementation, query usage tracking system
            // For now, return null to trigger exception in calculateUnits()
            return null;
        }

        return null;
    }

    /**
     * Validate that the period is open for posting (integration with Nexus\Period).
     */
    private function validatePeriodIsOpen(
        \DateTimeImmutable $periodStart,
        \DateTimeImmutable $periodEnd
    ): void {
        // In real implementation, call PeriodManager to validate period is open
        // For now, this is a placeholder
        $this->logger->debug('Period validation', [
            'period_start' => $periodStart->format('Y-m-d'),
            'period_end' => $periodEnd->format('Y-m-d'),
        ]);
    }
}
