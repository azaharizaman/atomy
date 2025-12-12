<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Services;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\DTOs\Financial\VolumeDiscountResult;
use Nexus\ProcurementOperations\DTOs\Financial\VolumeDiscountTierData;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Service for calculating volume-based vendor discounts.
 *
 * This service handles:
 * - Volume discount tier evaluation
 * - Cumulative spend tracking
 * - Tier progression calculation
 * - Discount optimization recommendations
 *
 * @package Nexus\ProcurementOperations\Services
 */
final readonly class VolumeDiscountService
{
    public function __construct(
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * Calculate the best applicable volume discount for a purchase.
     *
     * @param Money $purchaseAmount The purchase amount
     * @param array<VolumeDiscountTierData> $availableTiers Available discount tiers
     * @param \DateTimeImmutable|null $effectiveDate Date to check tier effectiveness
     * @return VolumeDiscountResult The discount calculation result
     */
    public function calculateDiscount(
        Money $purchaseAmount,
        array $availableTiers,
        ?\DateTimeImmutable $effectiveDate = null,
    ): VolumeDiscountResult {
        $effectiveDate ??= new \DateTimeImmutable();

        // Filter and sort tiers by minimum amount (descending)
        $effectiveTiers = array_filter(
            $availableTiers,
            fn(VolumeDiscountTierData $tier) => $tier->isEffective($effectiveDate)
        );

        if (empty($effectiveTiers)) {
            return VolumeDiscountResult::noDiscount(
                purchaseAmount: $purchaseAmount,
                reason: 'No effective discount tiers available',
            );
        }

        usort(
            $effectiveTiers,
            fn(VolumeDiscountTierData $a, VolumeDiscountTierData $b) =>
                $b->minAmount->getAmount() <=> $a->minAmount->getAmount()
        );

        // Find the highest applicable tier
        foreach ($effectiveTiers as $tier) {
            if ($tier->appliesTo($purchaseAmount)) {
                $discountAmount = $tier->calculateDiscount($purchaseAmount);

                $this->logger->info('Volume discount applied', [
                    'tier' => $tier->tierName,
                    'purchase_amount' => $purchaseAmount->getAmount(),
                    'discount_amount' => $discountAmount->getAmount(),
                ]);

                return VolumeDiscountResult::withSingleTier(
                    purchaseAmount: $purchaseAmount,
                    discountAmount: $discountAmount,
                    appliedTier: $tier,
                );
            }
        }

        // No tier met
        $lowestTier = end($effectiveTiers);
        $shortfall = $lowestTier->minAmount->getAmount() - $purchaseAmount->getAmount();

        return VolumeDiscountResult::noDiscount(
            purchaseAmount: $purchaseAmount,
            reason: sprintf(
                'Purchase amount below minimum tier threshold. Need %s more to qualify.',
                Money::of($shortfall, $purchaseAmount->getCurrency())->format()
            ),
        );
    }

    /**
     * Calculate cumulative discount based on period spending.
     *
     * @param string $tenantId Tenant identifier
     * @param string $vendorId Vendor identifier
     * @param Money $periodSpend Total spend in the period
     * @param Money $newPurchaseAmount New purchase amount
     * @param array<VolumeDiscountTierData> $tiers Available discount tiers
     * @param \DateTimeImmutable|null $effectiveDate Date for tier effectiveness
     * @return VolumeDiscountResult The discount calculation result
     */
    public function calculateCumulativeDiscount(
        string $tenantId,
        string $vendorId,
        Money $periodSpend,
        Money $newPurchaseAmount,
        array $tiers,
        ?\DateTimeImmutable $effectiveDate = null,
    ): VolumeDiscountResult {
        $effectiveDate ??= new \DateTimeImmutable();

        // Calculate total amount including new purchase
        $totalAmount = Money::of(
            $periodSpend->getAmount() + $newPurchaseAmount->getAmount(),
            $periodSpend->getCurrency()
        );

        // Find applicable tier based on total cumulative spend
        $result = $this->calculateDiscount($totalAmount, $tiers, $effectiveDate);

        if (!$result->hasDiscount) {
            // Check if previous spend already qualified for a tier
            $previousResult = $this->calculateDiscount($periodSpend, $tiers, $effectiveDate);
            
            if ($previousResult->hasDiscount) {
                // Apply the existing tier discount to new purchase
                $discountAmount = $previousResult->appliedTier->calculateDiscount($newPurchaseAmount);
                
                return VolumeDiscountResult::withSingleTier(
                    purchaseAmount: $newPurchaseAmount,
                    discountAmount: $discountAmount,
                    appliedTier: $previousResult->appliedTier,
                );
            }

            return VolumeDiscountResult::noDiscount(
                purchaseAmount: $newPurchaseAmount,
                reason: $result->reason,
            );
        }

        // Calculate discount for new purchase only (not total spend)
        $discountAmount = $result->appliedTier->calculateDiscount($newPurchaseAmount);

        $this->logger->info('Cumulative volume discount calculated', [
            'tenant_id' => $tenantId,
            'vendor_id' => $vendorId,
            'period_spend' => $periodSpend->getAmount(),
            'new_purchase' => $newPurchaseAmount->getAmount(),
            'tier' => $result->appliedTier->tierName,
            'discount' => $discountAmount->getAmount(),
        ]);

        return VolumeDiscountResult::withSingleTier(
            purchaseAmount: $newPurchaseAmount,
            discountAmount: $discountAmount,
            appliedTier: $result->appliedTier,
        );
    }

    /**
     * Analyze tier progression opportunities.
     *
     * @param Money $currentSpend Current period spend
     * @param array<VolumeDiscountTierData> $tiers Available discount tiers
     * @param string $remainingPeriod Human-readable remaining period description
     * @return array<string, mixed> Analysis result
     */
    public function analyzeTierProgression(
        Money $currentSpend,
        array $tiers,
        string $remainingPeriod = '3 months',
    ): array {
        // Sort tiers by minimum amount (ascending)
        $sortedTiers = $tiers;
        usort(
            $sortedTiers,
            fn(VolumeDiscountTierData $a, VolumeDiscountTierData $b) =>
                $a->minAmount->getAmount() <=> $b->minAmount->getAmount()
        );

        $currentTier = null;
        $nextTier = null;

        foreach ($sortedTiers as $tier) {
            if ($tier->appliesTo($currentSpend)) {
                $currentTier = $tier;
            } elseif ($currentTier !== null && $nextTier === null) {
                $nextTier = $tier;
                break;
            } elseif ($currentTier === null) {
                $nextTier = $tier;
                break;
            }
        }

        $spendToNextTier = null;
        $potentialAdditionalSavings = null;

        if ($nextTier !== null) {
            $spendToNextTier = Money::of(
                $nextTier->minAmount->getAmount() - $currentSpend->getAmount(),
                $currentSpend->getCurrency()
            );

            // Estimate additional savings based on reaching next tier
            // Assumes additional spend equal to spend-to-next-tier
            $additionalSpendWithDiscount = $nextTier->calculateDiscount($spendToNextTier);
            $currentTierDiscount = $currentTier?->calculateDiscount($spendToNextTier)
                ?? Money::of(0, $currentSpend->getCurrency());

            $potentialAdditionalSavings = Money::of(
                $additionalSpendWithDiscount->getAmount() - $currentTierDiscount->getAmount(),
                $currentSpend->getCurrency()
            );
        }

        return [
            'current_spend' => $currentSpend,
            'current_tier' => $currentTier ? [
                'name' => $currentTier->tierName,
                'discount_percentage' => $currentTier->isPercentageDiscount
                    ? $currentTier->discountPercentage
                    : null,
                'discount_fixed' => !$currentTier->isPercentageDiscount
                    ? $currentTier->fixedDiscountAmount?->getAmount()
                    : null,
            ] : null,
            'next_tier' => $nextTier ? [
                'name' => $nextTier->tierName,
                'minimum_amount' => $nextTier->minAmount,
                'discount_percentage' => $nextTier->isPercentageDiscount
                    ? $nextTier->discountPercentage
                    : null,
            ] : null,
            'spend_to_next_tier' => $spendToNextTier,
            'potential_additional_savings' => $potentialAdditionalSavings,
            'recommendation' => $this->generateProgressionRecommendation(
                $currentTier,
                $nextTier,
                $spendToNextTier,
                $potentialAdditionalSavings,
                $remainingPeriod
            ),
        ];
    }

    /**
     * Find optimal tier for a projected spend amount.
     *
     * @param Money $projectedSpend Projected spend amount
     * @param array<VolumeDiscountTierData> $tiers Available discount tiers
     * @return VolumeDiscountTierData|null The optimal tier or null if none applies
     */
    public function findOptimalTier(
        Money $projectedSpend,
        array $tiers,
    ): ?VolumeDiscountTierData {
        $bestTier = null;
        $bestEffectiveRate = 0;

        foreach ($tiers as $tier) {
            if (!$tier->appliesTo($projectedSpend)) {
                continue;
            }

            $discount = $tier->calculateDiscount($projectedSpend);
            $effectiveRate = $discount->getAmount() / $projectedSpend->getAmount();

            if ($effectiveRate > $bestEffectiveRate) {
                $bestEffectiveRate = $effectiveRate;
                $bestTier = $tier;
            }
        }

        return $bestTier;
    }

    /**
     * Create standard tier structure for a vendor.
     *
     * @param string $vendorId Vendor identifier
     * @param string $currency Currency code
     * @param array<array{name: string, min: float, max: float|null, percentage: float}> $tierConfig Tier configuration
     * @return array<VolumeDiscountTierData> Created tiers
     */
    public function createStandardTiers(
        string $vendorId,
        string $currency,
        array $tierConfig,
    ): array {
        $tiers = [];
        $effectiveFrom = new \DateTimeImmutable('first day of January this year');

        foreach ($tierConfig as $index => $config) {
            $tiers[] = VolumeDiscountTierData::percentageTier(
                tierId: "tier-{$vendorId}-" . ($index + 1),
                tierName: $config['name'],
                vendorId: $vendorId,
                minAmount: Money::of($config['min'], $currency),
                maxAmount: isset($config['max']) ? Money::of($config['max'], $currency) : null,
                discountPercentage: $config['percentage'],
                effectiveFrom: $effectiveFrom,
            );
        }

        return $tiers;
    }

    /**
     * Generate progression recommendation text.
     */
    private function generateProgressionRecommendation(
        ?VolumeDiscountTierData $currentTier,
        ?VolumeDiscountTierData $nextTier,
        ?Money $spendToNextTier,
        ?Money $additionalSavings,
        string $remainingPeriod,
    ): string {
        if ($nextTier === null) {
            if ($currentTier !== null) {
                return sprintf(
                    'Already at highest tier (%s). Maintain current spend level.',
                    $currentTier->tierName
                );
            }
            return 'No volume discount tiers configured for this vendor.';
        }

        if ($spendToNextTier === null) {
            return 'Unable to calculate progression path.';
        }

        if ($currentTier === null) {
            return sprintf(
                'Increase spend by %s to reach %s tier and start earning discounts.',
                $spendToNextTier->format(),
                $nextTier->tierName
            );
        }

        $monthlySpendNeeded = $spendToNextTier->getAmount() / 3; // Assuming 3 months

        return sprintf(
            'Currently at %s tier. Spend additional %s (%s/month over %s) ' .
            'to reach %s tier. Potential additional savings: %s',
            $currentTier->tierName,
            $spendToNextTier->format(),
            Money::of($monthlySpendNeeded, $spendToNextTier->getCurrency())->format(),
            $remainingPeriod,
            $nextTier->tierName,
            $additionalSavings?->format() ?? 'calculating...'
        );
    }
}
