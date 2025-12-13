<?php

declare(strict_types=1);

namespace Nexus\ProcurementOperations\Coordinators;

use Nexus\Common\ValueObjects\Money;
use Nexus\ProcurementOperations\Contracts\DiscountCalculationServiceInterface;
use Nexus\ProcurementOperations\DTOs\Financial\DiscountOpportunityData;
use Nexus\ProcurementOperations\DTOs\Financial\EarlyPaymentDiscountData;
use Nexus\ProcurementOperations\DTOs\Financial\VolumeDiscountTierData;
use Nexus\ProcurementOperations\Events\Financial\DiscountOpportunitiesIdentifiedEvent;
use Nexus\ProcurementOperations\Events\Financial\DiscountOptimizationCompletedEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Coordinator for optimizing discount capture across early payment and volume discounts.
 *
 * This coordinator analyzes available discount opportunities, prioritizes them based on
 * ROI and cash flow impact, and coordinates discount capture across multiple vendors
 * and payment terms.
 *
 * Follows Advanced Orchestrator Pattern v1.1:
 * - Coordinates flow between discount services
 * - Does not contain business logic (delegates to services)
 * - Publishes events for audit trail
 */
final readonly class DiscountOptimizationCoordinator
{
    public function __construct(
        private DiscountCalculationServiceInterface $discountService,
        private EventDispatcherInterface $eventDispatcher,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * Analyze all available discount opportunities across the organization.
     *
     * @param string $tenantId Tenant identifier
     * @param array<string, mixed> $options Analysis options
     * @return array{
     *     summary: array{total_opportunities: int, total_potential_savings: Money, average_roi: float},
     *     opportunities: array<DiscountOpportunityData>,
     *     recommendations: array<string>,
     *     analysis_date: \DateTimeImmutable,
     * }
     */
    public function analyzeDiscountOpportunities(
        string $tenantId,
        array $options = [],
    ): array {
        $this->logger->info('Analyzing discount opportunities', [
            'tenant_id' => $tenantId,
            'options' => $options,
        ]);

        $startDate = new \DateTimeImmutable();
        $opportunities = [];
        $totalSavings = Money::zero($options['currency'] ?? 'USD');
        $totalRoi = 0.0;
        $recommendations = [];

        // Get early payment discounts eligible for capture
        $eligibleDiscounts = $options['eligible_discounts'] ?? [];
        $volumeSpendData = $options['volume_spend_data'] ?? [];
        $cashPosition = $options['cash_position'] ?? null;
        $minRoiThreshold = $options['min_roi_threshold'] ?? 10.0;

        // Process early payment discount opportunities
        foreach ($eligibleDiscounts as $discount) {
            $analysis = $this->analyzeEarlyPaymentOpportunity($discount, $minRoiThreshold);

            if ($analysis !== null) {
                $opportunities[] = $analysis;
                $totalSavings = $totalSavings->add($analysis->potentialSavings);
                $totalRoi += $analysis->annualizedRoi;
            }
        }

        // Process volume discount opportunities
        foreach ($volumeSpendData as $vendorId => $spendData) {
            $volumeOpportunities = $this->analyzeVolumeOpportunities($vendorId, $spendData, $minRoiThreshold);

            foreach ($volumeOpportunities as $opportunity) {
                $opportunities[] = $opportunity;
                $totalSavings = $totalSavings->add($opportunity->potentialSavings);
                $totalRoi += $opportunity->annualizedRoi;
            }
        }

        // Sort by ROI descending
        usort($opportunities, fn($a, $b) => $b->annualizedRoi <=> $a->annualizedRoi);

        // Generate recommendations
        $recommendations = $this->generateRecommendations(
            $opportunities,
            $cashPosition,
            $minRoiThreshold,
        );

        $averageRoi = count($opportunities) > 0 ? $totalRoi / count($opportunities) : 0.0;

        // Dispatch event
        $this->eventDispatcher->dispatch(
            new DiscountOpportunitiesIdentifiedEvent(
                tenantId: $tenantId,
                opportunityCount: count($opportunities),
                totalPotentialSavings: $totalSavings,
                averageRoi: $averageRoi,
                analysisDate: $startDate,
                options: $options,
            )
        );

        $this->logger->info('Discount analysis completed', [
            'tenant_id' => $tenantId,
            'opportunity_count' => count($opportunities),
            'total_savings' => $totalSavings->getAmount(),
            'average_roi' => $averageRoi,
        ]);

        return [
            'summary' => [
                'total_opportunities' => count($opportunities),
                'total_potential_savings' => $totalSavings,
                'average_roi' => round($averageRoi, 2),
            ],
            'opportunities' => $opportunities,
            'recommendations' => $recommendations,
            'analysis_date' => $startDate,
        ];
    }

    /**
     * Optimize discount capture based on cash flow constraints and ROI prioritization.
     *
     * @param string $tenantId Tenant identifier
     * @param Money $availableCash Available cash for early payments
     * @param array<DiscountOpportunityData> $opportunities Opportunities to consider
     * @param array<string, mixed> $constraints Optimization constraints
     * @return array{
     *     selected_opportunities: array<DiscountOpportunityData>,
     *     total_investment: Money,
     *     total_savings: Money,
     *     portfolio_roi: float,
     *     excluded_opportunities: array<DiscountOpportunityData>,
     *     exclusion_reasons: array<string, string>,
     * }
     */
    public function optimizeDiscountCapture(
        string $tenantId,
        Money $availableCash,
        array $opportunities,
        array $constraints = [],
    ): array {
        $this->logger->info('Optimizing discount capture', [
            'tenant_id' => $tenantId,
            'available_cash' => $availableCash->getAmount(),
            'opportunity_count' => count($opportunities),
        ]);

        $selected = [];
        $excluded = [];
        $exclusionReasons = [];
        $totalInvestment = Money::zero($availableCash->getCurrency());
        $totalSavings = Money::zero($availableCash->getCurrency());
        $remainingCash = $availableCash;

        // Extract constraints
        $maxVendorConcentration = $constraints['max_vendor_concentration'] ?? 0.30; // 30%
        $minRoi = $constraints['min_roi'] ?? 10.0;
        $excludedVendors = $constraints['excluded_vendors'] ?? [];
        $maxOpportunities = $constraints['max_opportunities'] ?? PHP_INT_MAX;

        // Sort by ROI descending (greedy algorithm)
        usort($opportunities, fn($a, $b) => $b->annualizedRoi <=> $a->annualizedRoi);

        // Track vendor concentration
        $vendorInvestment = [];

        foreach ($opportunities as $opportunity) {
            // Check if we've hit max opportunities
            if (count($selected) >= $maxOpportunities) {
                $excluded[] = $opportunity;
                $exclusionReasons[$opportunity->opportunityId] = 'Max opportunities limit reached';
                continue;
            }

            // Check vendor exclusion
            if (in_array($opportunity->vendorId, $excludedVendors, true)) {
                $excluded[] = $opportunity;
                $exclusionReasons[$opportunity->opportunityId] = 'Vendor is excluded';
                continue;
            }

            // Check minimum ROI
            if ($opportunity->annualizedRoi < $minRoi) {
                $excluded[] = $opportunity;
                $exclusionReasons[$opportunity->opportunityId] = "ROI below threshold ({$opportunity->annualizedRoi}% < {$minRoi}%)";
                continue;
            }

            // Check cash availability
            $investmentRequired = $opportunity->investmentRequired;
            if ($investmentRequired->greaterThan($remainingCash)) {
                $excluded[] = $opportunity;
                $exclusionReasons[$opportunity->opportunityId] = 'Insufficient cash';
                continue;
            }

            // Check vendor concentration
            $currentVendorInvestment = $vendorInvestment[$opportunity->vendorId] ?? Money::zero($availableCash->getCurrency());
            $projectedVendorInvestment = $currentVendorInvestment->add($investmentRequired);
            $projectedConcentration = $projectedVendorInvestment->getAmount() / max($totalInvestment->add($investmentRequired)->getAmount(), 0.01);

            if ($projectedConcentration > $maxVendorConcentration && $totalInvestment->getAmount() > 0) {
                $excluded[] = $opportunity;
                $exclusionReasons[$opportunity->opportunityId] = "Vendor concentration limit exceeded ({$projectedConcentration}% > {$maxVendorConcentration}%)";
                continue;
            }

            // Select this opportunity
            $selected[] = $opportunity;
            $totalInvestment = $totalInvestment->add($investmentRequired);
            $totalSavings = $totalSavings->add($opportunity->potentialSavings);
            $remainingCash = $remainingCash->subtract($investmentRequired);
            $vendorInvestment[$opportunity->vendorId] = $projectedVendorInvestment;
        }

        $portfolioRoi = $totalInvestment->getAmount() > 0
            ? ($totalSavings->getAmount() / $totalInvestment->getAmount()) * 100 * 12 // Annualized
            : 0.0;

        // Dispatch event
        $this->eventDispatcher->dispatch(
            new DiscountOptimizationCompletedEvent(
                tenantId: $tenantId,
                selectedCount: count($selected),
                excludedCount: count($excluded),
                totalInvestment: $totalInvestment,
                totalSavings: $totalSavings,
                portfolioRoi: $portfolioRoi,
                availableCash: $availableCash,
                constraints: $constraints,
            )
        );

        $this->logger->info('Discount optimization completed', [
            'tenant_id' => $tenantId,
            'selected_count' => count($selected),
            'excluded_count' => count($excluded),
            'total_investment' => $totalInvestment->getAmount(),
            'total_savings' => $totalSavings->getAmount(),
            'portfolio_roi' => round($portfolioRoi, 2),
        ]);

        return [
            'selected_opportunities' => $selected,
            'total_investment' => $totalInvestment,
            'total_savings' => $totalSavings,
            'portfolio_roi' => round($portfolioRoi, 2),
            'excluded_opportunities' => $excluded,
            'exclusion_reasons' => $exclusionReasons,
        ];
    }

    /**
     * Execute discount capture for selected opportunities.
     *
     * @param string $tenantId Tenant identifier
     * @param array<DiscountOpportunityData> $opportunities Opportunities to capture
     * @param string $executedBy User executing the capture
     * @return array{
     *     successful: array<string>,
     *     failed: array<string, string>,
     *     total_captured: Money,
     *     execution_date: \DateTimeImmutable,
     * }
     */
    public function executeDiscountCapture(
        string $tenantId,
        array $opportunities,
        string $executedBy,
    ): array {
        $this->logger->info('Executing discount capture', [
            'tenant_id' => $tenantId,
            'opportunity_count' => count($opportunities),
            'executed_by' => $executedBy,
        ]);

        $successful = [];
        $failed = [];
        $totalCaptured = Money::zero('USD');
        $executionDate = new \DateTimeImmutable();

        foreach ($opportunities as $opportunity) {
            try {
                // Validate opportunity is still valid
                if ($opportunity->expirationDate !== null && $opportunity->expirationDate < $executionDate) {
                    $failed[$opportunity->opportunityId] = 'Discount opportunity has expired';
                    continue;
                }

                // Capture the discount based on type
                $captureResult = $this->captureDiscount($opportunity, $executedBy);

                if ($captureResult['success']) {
                    $successful[] = $opportunity->opportunityId;
                    $totalCaptured = $totalCaptured->add($opportunity->potentialSavings);
                } else {
                    $failed[$opportunity->opportunityId] = $captureResult['error'];
                }
            } catch (\Throwable $e) {
                $this->logger->error('Discount capture failed', [
                    'opportunity_id' => $opportunity->opportunityId,
                    'error' => $e->getMessage(),
                ]);
                $failed[$opportunity->opportunityId] = $e->getMessage();
            }
        }

        $this->logger->info('Discount capture execution completed', [
            'tenant_id' => $tenantId,
            'successful_count' => count($successful),
            'failed_count' => count($failed),
            'total_captured' => $totalCaptured->getAmount(),
        ]);

        return [
            'successful' => $successful,
            'failed' => $failed,
            'total_captured' => $totalCaptured,
            'execution_date' => $executionDate,
        ];
    }

    /**
     * Generate discount capture forecast for budgeting purposes.
     *
     * @param string $tenantId Tenant identifier
     * @param \DateTimeImmutable $forecastStartDate Forecast start date
     * @param int $forecastMonths Number of months to forecast
     * @param array<string, mixed> $assumptions Forecast assumptions
     * @return array{
     *     monthly_forecast: array<string, array{expected_savings: Money, expected_opportunities: int}>,
     *     annual_projection: Money,
     *     confidence_level: float,
     *     assumptions: array<string, mixed>,
     * }
     */
    public function generateDiscountForecast(
        string $tenantId,
        \DateTimeImmutable $forecastStartDate,
        int $forecastMonths = 12,
        array $assumptions = [],
    ): array {
        $this->logger->info('Generating discount forecast', [
            'tenant_id' => $tenantId,
            'forecast_months' => $forecastMonths,
        ]);

        $currency = $assumptions['currency'] ?? 'USD';
        $historicalCaptureRate = $assumptions['historical_capture_rate'] ?? 0.65; // 65% capture rate
        $averageDiscountPercent = $assumptions['average_discount_percent'] ?? 2.0;
        $monthlyInvoiceVolume = $assumptions['monthly_invoice_volume'] ?? Money::zero($currency);
        $growthRate = $assumptions['monthly_growth_rate'] ?? 0.02; // 2% monthly growth

        $monthlyForecast = [];
        $totalProjection = Money::zero($currency);
        $currentVolume = $monthlyInvoiceVolume;

        for ($month = 0; $month < $forecastMonths; $month++) {
            $forecastDate = $forecastStartDate->modify("+{$month} months");
            $monthKey = $forecastDate->format('Y-m');

            // Calculate expected savings for the month
            $eligibleAmount = $currentVolume->multiply($historicalCaptureRate);
            $expectedSavings = $eligibleAmount->multiply($averageDiscountPercent / 100);
            $expectedOpportunities = (int) ceil($eligibleAmount->getAmount() / 10000); // Assume avg $10k per opportunity

            $monthlyForecast[$monthKey] = [
                'expected_savings' => $expectedSavings,
                'expected_opportunities' => $expectedOpportunities,
            ];

            $totalProjection = $totalProjection->add($expectedSavings);

            // Apply growth for next month
            $currentVolume = $currentVolume->multiply(1 + $growthRate);
        }

        // Calculate confidence level based on historical data availability
        $confidenceLevel = $this->calculateForecastConfidence($assumptions);

        $this->logger->info('Discount forecast generated', [
            'tenant_id' => $tenantId,
            'forecast_months' => $forecastMonths,
            'annual_projection' => $totalProjection->getAmount(),
            'confidence_level' => $confidenceLevel,
        ]);

        return [
            'monthly_forecast' => $monthlyForecast,
            'annual_projection' => $totalProjection,
            'confidence_level' => $confidenceLevel,
            'assumptions' => $assumptions,
        ];
    }

    /**
     * Get discount performance metrics for reporting.
     *
     * @param string $tenantId Tenant identifier
     * @param \DateTimeImmutable $periodStart Period start date
     * @param \DateTimeImmutable $periodEnd Period end date
     * @return array{
     *     discounts_captured: int,
     *     total_savings: Money,
     *     average_roi: float,
     *     capture_rate: float,
     *     missed_opportunities: int,
     *     missed_savings: Money,
     *     top_vendors: array<array{vendor_id: string, vendor_name: string, savings: Money}>,
     * }
     */
    public function getDiscountPerformanceMetrics(
        string $tenantId,
        \DateTimeImmutable $periodStart,
        \DateTimeImmutable $periodEnd,
    ): array {
        $this->logger->info('Generating discount performance metrics', [
            'tenant_id' => $tenantId,
            'period_start' => $periodStart->format('Y-m-d'),
            'period_end' => $periodEnd->format('Y-m-d'),
        ]);

        // Get captured and missed discounts from service
        $capturedDiscounts = $this->discountService->getCapturedDiscounts($tenantId, $periodStart, $periodEnd);
        $missedDiscounts = $this->discountService->getMissedDiscounts($tenantId, $periodStart, $periodEnd);

        $totalCaptured = count($capturedDiscounts);
        $totalMissed = count($missedDiscounts);
        $totalOpportunities = $totalCaptured + $totalMissed;

        $totalSavings = Money::zero('USD');
        $totalRoi = 0.0;
        $vendorSavings = [];

        foreach ($capturedDiscounts as $discount) {
            $totalSavings = $totalSavings->add($discount->discountAmount);
            $totalRoi += $discount->annualizedRoi ?? 0.0;

            $vendorId = $discount->vendorId;
            if (!isset($vendorSavings[$vendorId])) {
                $vendorSavings[$vendorId] = [
                    'vendor_id' => $vendorId,
                    'vendor_name' => $discount->vendorName ?? 'Unknown',
                    'savings' => Money::zero('USD'),
                ];
            }
            $vendorSavings[$vendorId]['savings'] = $vendorSavings[$vendorId]['savings']->add($discount->discountAmount);
        }

        $missedSavings = Money::zero('USD');
        foreach ($missedDiscounts as $discount) {
            $missedSavings = $missedSavings->add($discount->discountAmount);
        }

        // Sort vendors by savings descending and take top 5
        usort($vendorSavings, fn($a, $b) => $b['savings']->getAmount() <=> $a['savings']->getAmount());
        $topVendors = array_slice(array_values($vendorSavings), 0, 5);

        $averageRoi = $totalCaptured > 0 ? $totalRoi / $totalCaptured : 0.0;
        $captureRate = $totalOpportunities > 0 ? ($totalCaptured / $totalOpportunities) * 100 : 0.0;

        $this->logger->info('Discount performance metrics generated', [
            'tenant_id' => $tenantId,
            'discounts_captured' => $totalCaptured,
            'total_savings' => $totalSavings->getAmount(),
            'capture_rate' => $captureRate,
        ]);

        return [
            'discounts_captured' => $totalCaptured,
            'total_savings' => $totalSavings,
            'average_roi' => round($averageRoi, 2),
            'capture_rate' => round($captureRate, 2),
            'missed_opportunities' => $totalMissed,
            'missed_savings' => $missedSavings,
            'top_vendors' => $topVendors,
        ];
    }

    /**
     * Analyze an early payment discount opportunity.
     */
    private function analyzeEarlyPaymentOpportunity(
        EarlyPaymentDiscountData $discount,
        float $minRoiThreshold,
    ): ?DiscountOpportunityData {
        // Calculate ROI
        $roiResult = $this->discountService->calculateAnnualizedReturn($discount);

        if ($roiResult->annualizedRoi < $minRoiThreshold) {
            return null;
        }

        $daysRemaining = $discount->discountDueDate !== null
            ? max(0, (new \DateTimeImmutable())->diff($discount->discountDueDate)->days)
            : 0;

        return new DiscountOpportunityData(
            opportunityId: 'EPD-' . $discount->discountId,
            type: 'EARLY_PAYMENT',
            vendorId: $discount->vendorId,
            vendorName: $discount->vendorName ?? 'Unknown',
            invoiceId: $discount->invoiceId ?? null,
            invoiceAmount: $discount->invoiceAmount,
            discountPercent: $discount->discountPercent,
            potentialSavings: $discount->discountAmount,
            investmentRequired: $discount->invoiceAmount->subtract($discount->discountAmount),
            annualizedRoi: $roiResult->annualizedRoi,
            daysRemaining: $daysRemaining,
            expirationDate: $discount->discountDueDate,
            priority: $this->calculatePriority($roiResult->annualizedRoi, $daysRemaining),
        );
    }

    /**
     * Analyze volume discount opportunities for a vendor.
     *
     * @return array<DiscountOpportunityData>
     */
    private function analyzeVolumeOpportunities(
        string $vendorId,
        array $spendData,
        float $minRoiThreshold,
    ): array {
        $opportunities = [];

        $currentSpend = Money::of($spendData['current_spend'] ?? 0, $spendData['currency'] ?? 'USD');
        $tiers = $spendData['discount_tiers'] ?? [];
        $vendorName = $spendData['vendor_name'] ?? 'Unknown';

        // Find next tier opportunity
        foreach ($tiers as $tier) {
            if (!$tier instanceof VolumeDiscountTierData) {
                continue;
            }

            if ($tier->minimumSpend->greaterThan($currentSpend)) {
                $additionalSpendRequired = $tier->minimumSpend->subtract($currentSpend);
                $potentialSavings = $tier->minimumSpend->multiply($tier->discountPercent / 100);

                // Calculate simplified ROI (savings vs additional spend required)
                $roi = $additionalSpendRequired->getAmount() > 0
                    ? ($potentialSavings->getAmount() / $additionalSpendRequired->getAmount()) * 100
                    : 0.0;

                if ($roi >= $minRoiThreshold) {
                    $opportunities[] = new DiscountOpportunityData(
                        opportunityId: "VOL-{$vendorId}-{$tier->tierId}",
                        type: 'VOLUME_DISCOUNT',
                        vendorId: $vendorId,
                        vendorName: $vendorName,
                        invoiceId: null,
                        invoiceAmount: $tier->minimumSpend,
                        discountPercent: $tier->discountPercent,
                        potentialSavings: $potentialSavings,
                        investmentRequired: $additionalSpendRequired,
                        annualizedRoi: $roi,
                        daysRemaining: null,
                        expirationDate: $tier->effectiveEndDate,
                        priority: $this->calculatePriority($roi, 30),
                    );
                }

                // Only show next tier opportunity
                break;
            }
        }

        return $opportunities;
    }

    /**
     * Generate recommendations based on analysis.
     *
     * @return array<string>
     */
    private function generateRecommendations(
        array $opportunities,
        ?Money $cashPosition,
        float $minRoiThreshold,
    ): array {
        $recommendations = [];

        if (count($opportunities) === 0) {
            $recommendations[] = 'No discount opportunities currently meet the minimum ROI threshold of ' . $minRoiThreshold . '%';
            return $recommendations;
        }

        $highRoiCount = count(array_filter($opportunities, fn($o) => $o->annualizedRoi >= 30.0));
        if ($highRoiCount > 0) {
            $recommendations[] = "{$highRoiCount} opportunities with 30%+ annualized ROI - consider prioritizing these for immediate capture";
        }

        $expiringCount = count(array_filter($opportunities, fn($o) => $o->daysRemaining !== null && $o->daysRemaining <= 5));
        if ($expiringCount > 0) {
            $recommendations[] = "{$expiringCount} opportunities expiring within 5 days - urgent action required";
        }

        if ($cashPosition !== null) {
            $totalInvestmentRequired = array_reduce(
                $opportunities,
                fn($sum, $o) => $sum + $o->investmentRequired->getAmount(),
                0.0,
            );

            if ($totalInvestmentRequired > $cashPosition->getAmount()) {
                $recommendations[] = 'Available cash insufficient to capture all opportunities - prioritize by ROI';
            } else {
                $recommendations[] = 'Sufficient cash available to capture all identified opportunities';
            }
        }

        $vendorConcentration = [];
        foreach ($opportunities as $opportunity) {
            $vendorConcentration[$opportunity->vendorId] = ($vendorConcentration[$opportunity->vendorId] ?? 0) + 1;
        }

        arsort($vendorConcentration);
        $topVendor = array_key_first($vendorConcentration);
        if ($topVendor !== null && $vendorConcentration[$topVendor] >= 3) {
            $recommendations[] = "High opportunity concentration with vendor {$topVendor} - consider negotiating improved terms";
        }

        return $recommendations;
    }

    /**
     * Calculate priority score (1-5, 5 being highest).
     */
    private function calculatePriority(float $roi, ?int $daysRemaining): int
    {
        $roiScore = match (true) {
            $roi >= 50.0 => 2.5,
            $roi >= 30.0 => 2.0,
            $roi >= 20.0 => 1.5,
            $roi >= 10.0 => 1.0,
            default => 0.5,
        };

        $urgencyScore = match (true) {
            $daysRemaining === null => 1.0,
            $daysRemaining <= 2 => 2.5,
            $daysRemaining <= 5 => 2.0,
            $daysRemaining <= 10 => 1.5,
            default => 1.0,
        };

        return (int) min(5, max(1, round($roiScore + $urgencyScore)));
    }

    /**
     * Capture a discount opportunity.
     *
     * @return array{success: bool, error?: string}
     */
    private function captureDiscount(DiscountOpportunityData $opportunity, string $executedBy): array
    {
        // Delegate to discount service based on type
        try {
            if ($opportunity->type === 'EARLY_PAYMENT') {
                // Extract discount ID from opportunity ID
                $discountId = str_replace('EPD-', '', $opportunity->opportunityId);
                $this->discountService->captureDiscount($discountId, $executedBy);
            } else {
                // Volume discounts are typically captured automatically through spend
                // This is more of a tracking action
                $this->logger->info('Volume discount opportunity tracked', [
                    'opportunity_id' => $opportunity->opportunityId,
                    'executed_by' => $executedBy,
                ]);
            }

            return ['success' => true];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Calculate forecast confidence level.
     */
    private function calculateForecastConfidence(array $assumptions): float
    {
        $confidence = 0.5; // Base confidence

        // Higher confidence with more historical data points
        if (isset($assumptions['historical_months']) && $assumptions['historical_months'] >= 12) {
            $confidence += 0.2;
        } elseif (isset($assumptions['historical_months']) && $assumptions['historical_months'] >= 6) {
            $confidence += 0.1;
        }

        // Higher confidence with stable capture rate
        if (isset($assumptions['capture_rate_variance']) && $assumptions['capture_rate_variance'] < 0.1) {
            $confidence += 0.15;
        }

        // Lower confidence with high growth assumptions
        if (isset($assumptions['monthly_growth_rate']) && $assumptions['monthly_growth_rate'] > 0.05) {
            $confidence -= 0.1;
        }

        return min(0.95, max(0.3, $confidence));
    }
}
