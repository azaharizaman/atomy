<?php

declare(strict_types=1);

namespace Nexus\CostAccounting\Services;

use Nexus\CostAccounting\Contracts\CostVarianceCalculatorInterface;
use Nexus\CostAccounting\Contracts\ProductCostQueryInterface;
use Nexus\CostAccounting\Events\CostVarianceDetectedEvent;
use Nexus\CostAccounting\ValueObjects\CostVarianceBreakdown;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

/**
 * Cost Variance Calculator Service
 * 
 * Calculates and analyzes variances between actual and standard costs.
 */
final readonly class CostVarianceCalculator implements CostVarianceCalculatorInterface
{
    public function __construct(
        private ProductCostQueryInterface $productCostQuery,
        private EventDispatcherInterface $eventDispatcher,
        private LoggerInterface $logger
    ) {}

    /**
     * Calculate variances for a product
     */
    public function calculate(string $productId, string $periodId): CostVarianceBreakdown
    {
        $this->logger->info('Calculating cost variances', [
            'product_id' => $productId,
            'period_id' => $periodId,
        ]);

        // Get standard cost
        $standardCost = $this->productCostQuery->findStandardCost($productId, $periodId);
        
        // Get actual cost
        $actualCost = $this->productCostQuery->findActualCost($productId, $periodId);

        if ($standardCost === null && $actualCost === null) {
            throw new \RuntimeException(
                sprintf(
                    'No cost data found for product %s in period %s',
                    $productId,
                    $periodId
                )
            );
        }

        // Default to zero if not available
        $standardMaterial = $standardCost?->getMaterialCost() ?? 0.0;
        $standardLabor = $standardCost?->getLaborCost() ?? 0.0;
        $standardOverhead = $standardCost?->getOverheadCost() ?? 0.0;
        
        $actualMaterial = $actualCost?->getMaterialCost() ?? 0.0;
        $actualLabor = $actualCost?->getLaborCost() ?? 0.0;
        $actualOverhead = $actualCost?->getOverheadCost() ?? 0.0;

        // Calculate variances
        $materialVariance = $actualMaterial - $standardMaterial;
        $laborVariance = $actualLabor - $standardLabor;
        $overheadVariance = $actualOverhead - $standardOverhead;
        
        // Total variance
        $totalVariance = $materialVariance + $laborVariance + $overheadVariance;

        // Calculate price, rate, and efficiency variances
        // These require additional data like quantity and price differences
        $priceVariance = $this->calculatePriceVariance($productId, $periodId);
        $rateVariance = $this->calculateRateVariance($productId, $periodId);
        $efficiencyVariance = $this->calculateEfficiencyVariance(
            $materialVariance,
            $laborVariance,
            $overheadVariance,
            $priceVariance,
            $rateVariance
        );

        $isFavorable = $totalVariance < 0;

        // Calculate variance percentage (variance relative to standard cost)
        $standardTotal = $standardMaterial + $standardLabor + $standardOverhead;
        $variancePercentage = $standardTotal > 0 
            ? ($totalVariance / $standardTotal) * 100 
            : 0.0;

        // Create variance breakdown
        $varianceBreakdown = new CostVarianceBreakdown(
            productId: $productId,
            periodId: $periodId,
            priceVariance: $priceVariance,
            rateVariance: $rateVariance,
            efficiencyVariance: $efficiencyVariance,
            totalVariance: $totalVariance,
            variancePercentage: $variancePercentage,
            materialVariance: $materialVariance,
            laborVariance: $laborVariance,
            overheadVariance: $overheadVariance,
            baselineCost: $standardTotal
        );

        // Dispatch event
        $this->eventDispatcher->dispatch(new CostVarianceDetectedEvent(
            productId: $productId,
            periodId: $periodId,
            priceVariance: $priceVariance,
            rateVariance: $rateVariance,
            efficiencyVariance: $efficiencyVariance,
            totalVariance: $totalVariance,
            variancePercentage: $variancePercentage,
            isFavorable: $isFavorable,
            tenantId: $actualCost?->getTenantId() ?? $standardCost?->getTenantId() ?? 'unknown',
            occurredAt: new \DateTimeImmutable()
        ));

        $this->logger->info('Variance calculation completed', [
            'product_id' => $productId,
            'total_variance' => $totalVariance,
            'is_favorable' => $isFavorable,
        ]);

        return $varianceBreakdown;
    }

    /**
     * Calculate price variance
     * Price Variance = (Actual Price - Standard Price) × Actual Quantity
     */
    private function calculatePriceVariance(string $productId, string $periodId): float
    {
        // Simplified calculation - actual implementation would need
        // detailed transaction data
        $standardCost = $this->productCostQuery->findStandardCost($productId, $periodId);
        $actualCost = $this->productCostQuery->findActualCost($productId, $periodId);

        if ($standardCost === null || $actualCost === null) {
            return 0.0;
        }

        // Price variance is typically a portion of material variance
        // This is a simplified heuristic
        $materialVariance = $actualCost->getMaterialCost() - $standardCost->getMaterialCost();
        
        return $materialVariance * 0.6; // Assume 60% of material variance is price variance
    }

    /**
     * Calculate rate variance
     * Rate Variance = (Actual Rate - Standard Rate) × Actual Hours
     */
    private function calculateRateVariance(string $productId, string $periodId): float
    {
        $standardCost = $this->productCostQuery->findStandardCost($productId, $periodId);
        $actualCost = $this->productCostQuery->findActualCost($productId, $periodId);

        if ($standardCost === null || $actualCost === null) {
            return 0.0;
        }

        // Rate variance is typically a portion of labor variance
        $laborVariance = $actualCost->getLaborCost() - $standardCost->getLaborCost();
        
        return $laborVariance * 0.7; // Assume 70% of labor variance is rate variance
    }

    /**
     * Calculate efficiency variance
     * Efficiency Variance = Total Variance - Price Variance - Rate Variance
     */
    private function calculateEfficiencyVariance(
        float $materialVariance,
        float $laborVariance,
        float $overheadVariance,
        float $priceVariance,
        float $rateVariance
    ): float {
        $totalVariance = $materialVariance + $laborVariance + $overheadVariance;
        
        // Efficiency variance is what remains after price and rate variances
        return $totalVariance - $priceVariance - $rateVariance;
    }

    /**
     * Check if variance exceeds investigation threshold
     */
    public function exceedsThreshold(CostVarianceBreakdown $variance, float $thresholdPercentage): bool
    {
        $baseline = $variance->getBaselineCost();
        
        if ($baseline <= 0) {
            return false;
        }
        
        $thresholdAmount = abs($baseline) * ($thresholdPercentage / 100);
        
        return abs($variance->getTotalVariance()) > $thresholdAmount;
    }
}
