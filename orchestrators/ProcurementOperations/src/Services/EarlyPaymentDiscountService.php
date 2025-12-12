<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Services;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\Contracts\DiscountCalculationServiceInterface;
use Nexus\ProcurementOperations\DTOs\Financial\EarlyPaymentDiscountData;
use Nexus\ProcurementOperations\DTOs\Financial\VolumeDiscountResult;
use Nexus\ProcurementOperations\DTOs\Financial\VolumeDiscountTierData;
use Nexus\ProcurementOperations\Events\Payment\EarlyPaymentDiscountCapturedEvent;
use Nexus\ProcurementOperations\Events\Payment\EarlyPaymentDiscountMissedEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Service for calculating early payment and volume-based discounts.
 *
 * This service handles:
 * - Early payment discount evaluation (2/10 Net 30, etc.)
 * - Annualized return rate calculation for discount decisions
 * - Volume-based discount tier calculation
 * - Discount capture and miss tracking with events
 *
 * @package Nexus\ProcurementOperations\Services
 */
final readonly class EarlyPaymentDiscountService implements DiscountCalculationServiceInterface
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * {@inheritDoc}
     */
    public function getEarlyPaymentDiscount(
        string $tenantId,
        string $invoiceId,
        ?string $vendorId = null,
    ): ?EarlyPaymentDiscountData {
        // This would typically query a repository for invoice payment terms
        // For now, we return null to indicate lookup should be done by caller
        $this->logger->debug('Looking up early payment discount', [
            'tenant_id' => $tenantId,
            'invoice_id' => $invoiceId,
            'vendor_id' => $vendorId,
        ]);

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function calculateEarlyPaymentDiscountAmount(
        Money $invoiceAmount,
        EarlyPaymentDiscountData $discountTerms,
        \DateTimeImmutable $paymentDate,
    ): Money {
        if (!$discountTerms->isDiscountAvailable($paymentDate)) {
            return Money::of(0, $invoiceAmount->getCurrency());
        }

        $discountAmount = $invoiceAmount->getAmount() * ($discountTerms->discountPercentage / 100);

        return Money::of($discountAmount, $invoiceAmount->getCurrency());
    }

    /**
     * {@inheritDoc}
     */
    public function isEarlyPaymentDiscountAvailable(
        EarlyPaymentDiscountData $discountTerms,
        \DateTimeImmutable $paymentDate,
    ): bool {
        return $discountTerms->isDiscountAvailable($paymentDate);
    }

    /**
     * {@inheritDoc}
     */
    public function calculateAnnualizedReturnRate(
        EarlyPaymentDiscountData $discountTerms,
    ): float {
        return $discountTerms->getAnnualizedReturnRate();
    }

    /**
     * {@inheritDoc}
     */
    public function getVolumeDiscountTiers(
        string $tenantId,
        string $vendorId,
        ?\DateTimeImmutable $effectiveDate = null,
        ?string $productCategoryId = null,
    ): array {
        // This would typically query a repository for vendor discount tiers
        // Returning empty array to indicate lookup should be done by caller
        $this->logger->debug('Looking up volume discount tiers', [
            'tenant_id' => $tenantId,
            'vendor_id' => $vendorId,
            'effective_date' => $effectiveDate?->format('Y-m-d'),
            'product_category_id' => $productCategoryId,
        ]);

        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function calculateVolumeDiscount(
        Money $purchaseAmount,
        array $applicableTiers,
        ?\DateTimeImmutable $effectiveDate = null,
    ): VolumeDiscountResult {
        $effectiveDate ??= new \DateTimeImmutable();

        // Filter tiers by effectiveness
        $effectiveTiers = array_filter(
            $applicableTiers,
            fn(VolumeDiscountTierData $tier) => $tier->isEffective($effectiveDate)
        );

        if (empty($effectiveTiers)) {
            return VolumeDiscountResult::noDiscount(
                purchaseAmount: $purchaseAmount,
                reason: 'No effective discount tiers available',
            );
        }

        // Sort tiers by minimum amount descending to find the best applicable tier
        usort(
            $effectiveTiers,
            fn(VolumeDiscountTierData $a, VolumeDiscountTierData $b) =>
                $b->minAmount->getAmount() <=> $a->minAmount->getAmount()
        );

        // Find the highest tier that applies
        $applicableTier = null;
        foreach ($effectiveTiers as $tier) {
            if ($tier->appliesTo($purchaseAmount)) {
                $applicableTier = $tier;
                break;
            }
        }

        if ($applicableTier === null) {
            // Check if any tier threshold was not met
            $lowestTier = end($effectiveTiers);
            $shortfall = $lowestTier->minAmount->getAmount() - $purchaseAmount->getAmount();

            return VolumeDiscountResult::noDiscount(
                purchaseAmount: $purchaseAmount,
                reason: sprintf(
                    'Purchase amount below minimum tier threshold. Need additional %s to qualify for %s tier.',
                    Money::of($shortfall, $purchaseAmount->getCurrency())->format(),
                    $lowestTier->tierName
                ),
            );
        }

        $discountAmount = $applicableTier->calculateDiscount($purchaseAmount);

        $this->logger->info('Volume discount calculated', [
            'tier_name' => $applicableTier->tierName,
            'purchase_amount' => $purchaseAmount->getAmount(),
            'discount_amount' => $discountAmount->getAmount(),
            'effective_percentage' => ($discountAmount->getAmount() / $purchaseAmount->getAmount()) * 100,
        ]);

        return VolumeDiscountResult::withSingleTier(
            purchaseAmount: $purchaseAmount,
            discountAmount: $discountAmount,
            appliedTier: $applicableTier,
        );
    }

    /**
     * {@inheritDoc}
     */
    public function estimatePotentialDiscountSavings(
        string $tenantId,
        string $vendorId,
        Money $projectedSpend,
        string $timePeriod = 'annual',
    ): array {
        $tiers = $this->getVolumeDiscountTiers($tenantId, $vendorId);

        if (empty($tiers)) {
            return [
                'current_tier' => null,
                'potential_tier' => null,
                'current_savings' => Money::of(0, $projectedSpend->getCurrency()),
                'potential_savings' => Money::of(0, $projectedSpend->getCurrency()),
                'additional_spend_required' => Money::of(0, $projectedSpend->getCurrency()),
                'roi_recommendation' => 'No volume discount program available with this vendor',
            ];
        }

        $result = $this->calculateVolumeDiscount($projectedSpend, $tiers);

        // Find next tier up
        $sortedTiers = $tiers;
        usort(
            $sortedTiers,
            fn(VolumeDiscountTierData $a, VolumeDiscountTierData $b) =>
                $a->minAmount->getAmount() <=> $b->minAmount->getAmount()
        );

        $nextTier = null;
        foreach ($sortedTiers as $tier) {
            if ($tier->minAmount->getAmount() > $projectedSpend->getAmount()) {
                $nextTier = $tier;
                break;
            }
        }

        $additionalSpendRequired = Money::of(0, $projectedSpend->getCurrency());
        $potentialSavings = $result->discountAmount;

        if ($nextTier !== null) {
            $additionalSpendRequired = Money::of(
                $nextTier->minAmount->getAmount() - $projectedSpend->getAmount(),
                $projectedSpend->getCurrency()
            );
            $potentialSavings = $nextTier->calculateDiscount($nextTier->minAmount);
        }

        return [
            'current_tier' => $result->appliedTier?->tierName,
            'potential_tier' => $nextTier?->tierName,
            'current_savings' => $result->discountAmount,
            'potential_savings' => $potentialSavings,
            'additional_spend_required' => $additionalSpendRequired,
            'roi_recommendation' => $this->generateRoiRecommendation(
                $result,
                $nextTier,
                $additionalSpendRequired,
                $potentialSavings
            ),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function recordCapturedDiscount(
        string $tenantId,
        string $invoiceId,
        string $invoiceNumber,
        string $vendorId,
        string $vendorName,
        Money $originalAmount,
        Money $discountAmount,
        EarlyPaymentDiscountData $discountTerms,
        string $capturedBy,
    ): void {
        $daysEarly = $discountTerms->getDaysUntilDiscountDeadline(new \DateTimeImmutable());

        $event = new EarlyPaymentDiscountCapturedEvent(
            tenantId: $tenantId,
            invoiceId: $invoiceId,
            invoiceNumber: $invoiceNumber,
            vendorId: $vendorId,
            vendorName: $vendorName,
            originalAmount: $originalAmount,
            discountAmount: $discountAmount,
            netPaymentAmount: Money::of(
                $originalAmount->getAmount() - $discountAmount->getAmount(),
                $originalAmount->getCurrency()
            ),
            discountTerms: $discountTerms->getFormattedTerms(),
            discountPercentage: $discountTerms->discountPercentage,
            daysEarly: max(0, $daysEarly),
            annualizedReturnRate: $discountTerms->getAnnualizedReturnRate(),
            capturedBy: $capturedBy,
            occurredAt: new \DateTimeImmutable(),
        );

        $this->eventDispatcher->dispatch($event);

        $this->logger->info('Early payment discount captured', [
            'tenant_id' => $tenantId,
            'invoice_id' => $invoiceId,
            'discount_amount' => $discountAmount->getAmount(),
            'annualized_return' => $discountTerms->getAnnualizedReturnRate(),
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function recordMissedDiscount(
        string $tenantId,
        string $invoiceId,
        string $invoiceNumber,
        string $vendorId,
        string $vendorName,
        Money $originalAmount,
        Money $missedDiscountAmount,
        EarlyPaymentDiscountData $discountTerms,
        string $missedReason,
    ): void {
        $event = new EarlyPaymentDiscountMissedEvent(
            tenantId: $tenantId,
            invoiceId: $invoiceId,
            invoiceNumber: $invoiceNumber,
            vendorId: $vendorId,
            vendorName: $vendorName,
            originalAmount: $originalAmount,
            missedDiscountAmount: $missedDiscountAmount,
            discountTerms: $discountTerms->getFormattedTerms(),
            discountPercentage: $discountTerms->discountPercentage,
            discountDeadline: $discountTerms->getDiscountDeadline(),
            missedReason: $missedReason,
            occurredAt: new \DateTimeImmutable(),
        );

        $this->eventDispatcher->dispatch($event);

        $this->logger->warning('Early payment discount missed', [
            'tenant_id' => $tenantId,
            'invoice_id' => $invoiceId,
            'missed_amount' => $missedDiscountAmount->getAmount(),
            'reason' => $missedReason,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function getDiscountPerformanceMetrics(
        string $tenantId,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
        ?string $vendorId = null,
    ): array {
        // This would typically aggregate from a repository
        // Returning structure for now
        return [
            'period_start' => $startDate->format('Y-m-d'),
            'period_end' => $endDate->format('Y-m-d'),
            'total_eligible_discounts' => 0,
            'discounts_captured' => 0,
            'discounts_missed' => 0,
            'capture_rate_percentage' => 0.0,
            'total_savings_captured' => Money::of(0, 'USD'),
            'total_savings_missed' => Money::of(0, 'USD'),
            'average_annualized_return_captured' => 0.0,
            'average_days_early' => 0,
            'top_performing_vendors' => [],
            'improvement_opportunities' => [],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function prioritizeInvoicesForDiscountCapture(
        string $tenantId,
        Money $availableCash,
        ?\DateTimeImmutable $paymentDate = null,
    ): array {
        // This would typically query invoices with early payment terms
        // and prioritize by annualized return rate
        $paymentDate ??= new \DateTimeImmutable();

        return [
            'prioritized_invoices' => [],
            'total_discount_capturable' => Money::of(0, $availableCash->getCurrency()),
            'total_payment_amount' => Money::of(0, $availableCash->getCurrency()),
            'remaining_cash' => $availableCash,
            'optimization_strategy' => 'maximize_annualized_return',
        ];
    }

    /**
     * Generate ROI recommendation based on tier analysis.
     */
    private function generateRoiRecommendation(
        VolumeDiscountResult $currentResult,
        ?VolumeDiscountTierData $nextTier,
        Money $additionalSpendRequired,
        Money $potentialSavings,
    ): string {
        if ($nextTier === null) {
            if ($currentResult->hasDiscount) {
                return sprintf(
                    'You are at the highest discount tier (%s). Continue current purchasing volume.',
                    $currentResult->appliedTier->tierName
                );
            }
            return 'No volume discount tiers available.';
        }

        $additionalSavings = $potentialSavings->getAmount() - $currentResult->discountAmount->getAmount();

        if ($additionalSpendRequired->getAmount() <= 0) {
            return sprintf(
                'Already qualified for %s tier.',
                $nextTier->tierName
            );
        }

        $savingsRatio = $additionalSavings / $additionalSpendRequired->getAmount();

        if ($savingsRatio > 0.10) { // More than 10% savings on additional spend
            return sprintf(
                'HIGHLY RECOMMENDED: Increase spend by %s to reach %s tier. ' .
                'Additional savings of %s (%.1f%% return on incremental spend).',
                $additionalSpendRequired->format(),
                $nextTier->tierName,
                Money::of($additionalSavings, $potentialSavings->getCurrency())->format(),
                $savingsRatio * 100
            );
        }

        if ($savingsRatio > 0.05) { // More than 5% savings
            return sprintf(
                'RECOMMENDED: Consider increasing spend by %s to reach %s tier for %s additional savings.',
                $additionalSpendRequired->format(),
                $nextTier->tierName,
                Money::of($additionalSavings, $potentialSavings->getCurrency())->format()
            );
        }

        return sprintf(
            'Optional: %s additional spend needed for %s tier. Marginal benefit of %s.',
            $additionalSpendRequired->format(),
            $nextTier->tierName,
            Money::of($additionalSavings, $potentialSavings->getCurrency())->format()
        );
    }
}
