<?php

declare(strict_types=1);

namespace Nexus\PaymentRails\Services;

use Nexus\Common\ValueObjects\Money;
use Nexus\PaymentRails\Contracts\AchRailInterface;
use Nexus\PaymentRails\Contracts\CheckRailInterface;
use Nexus\PaymentRails\Contracts\PaymentRailInterface;
use Nexus\PaymentRails\Contracts\RailSelectorInterface;
use Nexus\PaymentRails\Contracts\RtgsRailInterface;
use Nexus\PaymentRails\Contracts\VirtualCardRailInterface;
use Nexus\PaymentRails\Contracts\WireRailInterface;
use Nexus\PaymentRails\DTOs\RailSelectionCriteria;
use Nexus\PaymentRails\Enums\RailType;
use Nexus\PaymentRails\Enums\WireType;
use Nexus\PaymentRails\Events\RailSelected;
use Nexus\PaymentRails\Exceptions\NoEligibleRailException;
use Nexus\PaymentRails\ValueObjects\RailCapabilities;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Payment rail selector service.
 *
 * Selects the optimal payment rail based on:
 * - Amount and currency
 * - Speed requirements
 * - Cost optimization
 * - Destination country
 * - Business rules and preferences
 */
final readonly class RailSelector implements RailSelectorInterface
{
    /**
     * Amount thresholds for rail selection (in cents).
     */
    private const HIGH_VALUE_THRESHOLD_CENTS = 10000000; // $100,000

    private const MEDIUM_VALUE_THRESHOLD_CENTS = 1000000; // $10,000

    /**
     * @param array<RailType, PaymentRailInterface> $rails
     */
    public function __construct(
        private array $rails,
        private ?EventDispatcherInterface $eventDispatcher = null,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * Select the best payment rail for given criteria.
     */
    public function select(RailSelectionCriteria $criteria): PaymentRailInterface
    {
        $eligibleRails = $this->getEligibleRails($criteria);

        if (empty($eligibleRails)) {
            throw NoEligibleRailException::forCriteria($criteria);
        }

        // Score and rank rails
        $scoredRails = $this->scoreRails($eligibleRails, $criteria);

        // Sort by score descending
        usort($scoredRails, fn($a, $b) => $b['score'] <=> $a['score']);

        $selectedRail = $scoredRails[0]['rail'];
        $score = $scoredRails[0]['score'];

        $this->logger->info('Payment rail selected', [
            'rail_type' => $selectedRail->getRailType()->value,
            'score' => $score,
            'amount_cents' => $criteria->amountCents,
            'currency' => $criteria->currency,
        ]);

        // Dispatch selection event
        $this->eventDispatcher?->dispatch(new RailSelected(
            railType: $selectedRail->getRailType(),
            criteria: $criteria,
            score: $score,
        ));

        return $selectedRail;
    }

    /**
     * Get all eligible rails for criteria.
     *
     * @return array<PaymentRailInterface>
     */
    public function getEligibleRails(RailSelectionCriteria $criteria): array
    {
        $eligible = [];

        foreach ($this->rails as $rail) {
            if ($this->isEligible($rail, $criteria)) {
                $eligible[] = $rail;
            }
        }

        return $eligible;
    }

    /**
     * Get optimal rail for domestic transfers.
     */
    public function getOptimalDomesticRail(Money $amount, string $currency): PaymentRailInterface
    {
        $criteria = new RailSelectionCriteria(
            amountCents: $amount->getAmountInMinorUnits(),
            currency: $currency,
            destinationCountry: $this->getCountryForCurrency($currency),
            urgency: 'standard',
            preferLowCost: true,
        );

        return $this->select($criteria);
    }

    /**
     * Get optimal rail for international transfers.
     */
    public function getOptimalInternationalRail(
        Money $amount,
        string $sourceCurrency,
        string $destinationCountry,
    ): PaymentRailInterface {
        $criteria = new RailSelectionCriteria(
            amountCents: $amount->getAmountInMinorUnits(),
            currency: $sourceCurrency,
            destinationCountry: $destinationCountry,
            urgency: 'standard',
            isInternational: true,
            preferLowCost: false, // Speed over cost for international
        );

        return $this->select($criteria);
    }

    /**
     * Get fastest available rail.
     */
    public function getFastestRail(RailSelectionCriteria $criteria): PaymentRailInterface
    {
        $fastCriteria = new RailSelectionCriteria(
            amountCents: $criteria->amountCents,
            currency: $criteria->currency,
            destinationCountry: $criteria->destinationCountry,
            urgency: 'urgent',
            preferLowCost: false,
            isInternational: $criteria->isInternational,
            beneficiaryType: $criteria->beneficiaryType,
        );

        return $this->select($fastCriteria);
    }

    /**
     * Get cheapest available rail.
     */
    public function getCheapestRail(RailSelectionCriteria $criteria): PaymentRailInterface
    {
        $cheapCriteria = new RailSelectionCriteria(
            amountCents: $criteria->amountCents,
            currency: $criteria->currency,
            destinationCountry: $criteria->destinationCountry,
            urgency: 'standard',
            preferLowCost: true,
            isInternational: $criteria->isInternational,
            beneficiaryType: $criteria->beneficiaryType,
        );

        return $this->select($cheapCriteria);
    }

    /**
     * Check if rail is eligible for criteria.
     */
    private function isEligible(PaymentRailInterface $rail, RailSelectionCriteria $criteria): bool
    {
        // Must be available
        if (!$rail->isAvailable()) {
            return false;
        }

        $capabilities = $rail->getCapabilities();
        $amount = $this->criteriaAmountAsMoney($criteria);

        // Check currency support
        if (!$capabilities->supportsCurrency($criteria->currency)) {
            return false;
        }

        // Check amount bounds
        if (!$capabilities->isAmountWithinLimits($amount)) {
            return false;
        }

        // Check urgency requirements
        if ($criteria->urgency === 'urgent' && $capabilities->typicalSettlementDays > 1) {
            return false;
        }

        if ($criteria->urgency === 'real-time' && !$capabilities->isRealTime()) {
            return false;
        }

        // Check recurring requirement
        if ($criteria->requiresRecurring && !$capabilities->supportsRecurring) {
            return false;
        }

        // Rail-specific eligibility
        return $this->checkRailSpecificEligibility($rail, $criteria);
    }

    /**
     * Check rail-specific eligibility rules.
     */
    private function checkRailSpecificEligibility(
        PaymentRailInterface $rail,
        RailSelectionCriteria $criteria,
    ): bool {
        return match ($rail->getRailType()) {
            RailType::ACH => $this->isAchEligible($criteria),
            RailType::WIRE => $this->isWireEligible($criteria),
            RailType::CHECK => $this->isCheckEligible($criteria),
            RailType::RTGS => $this->isRtgsEligible($criteria),
            RailType::VIRTUAL_CARD => $this->isVirtualCardEligible($criteria),
        };
    }

    /**
     * Check ACH eligibility.
     */
    private function isAchEligible(RailSelectionCriteria $criteria): bool
    {
        // ACH is domestic USD only
        if ($criteria->currency !== 'USD') {
            return false;
        }

        if ($criteria->isInternational) {
            return false;
        }

        return true;
    }

    /**
     * Check wire eligibility.
     */
    private function isWireEligible(RailSelectionCriteria $criteria): bool
    {
        // Wire supports international and high-value domestic
        if ($criteria->isInternational) {
            return true;
        }

        // Domestic: prefer for high values
        return $criteria->amountCents >= self::MEDIUM_VALUE_THRESHOLD_CENTS;
    }

    /**
     * Check check eligibility.
     */
    private function isCheckEligible(RailSelectionCriteria $criteria): bool
    {
        // Checks not suitable for urgent or international
        if ($criteria->urgency === 'urgent' || $criteria->urgency === 'real-time') {
            return false;
        }

        if ($criteria->isInternational) {
            return false;
        }

        // Checks not suitable for very high values
        return $criteria->amountCents <= self::HIGH_VALUE_THRESHOLD_CENTS;
    }

    /**
     * Check RTGS eligibility.
     */
    private function isRtgsEligible(RailSelectionCriteria $criteria): bool
    {
        // RTGS is for high-value transfers only
        if ($criteria->amountCents < self::MEDIUM_VALUE_THRESHOLD_CENTS) {
            return false;
        }

        // Not international (RTGS is per-country)
        if ($criteria->isInternational) {
            return false;
        }

        return true;
    }

    /**
     * Check virtual card eligibility.
     */
    private function isVirtualCardEligible(RailSelectionCriteria $criteria): bool
    {
        // Virtual cards for vendor payments
        if ($criteria->beneficiaryType !== 'vendor') {
            return false;
        }

        // Not for real-time or urgent
        if ($criteria->urgency === 'real-time') {
            return false;
        }

        return true;
    }

    /**
     * Score rails based on criteria.
     *
     * @param array<PaymentRailInterface> $rails
     * @return array<array{rail: PaymentRailInterface, score: float}>
     */
    private function scoreRails(array $rails, RailSelectionCriteria $criteria): array
    {
        $scored = [];

        foreach ($rails as $rail) {
            $score = $this->calculateScore($rail, $criteria);
            $scored[] = ['rail' => $rail, 'score' => $score];
        }

        return $scored;
    }

    /**
     * Calculate score for a rail.
     */
    private function calculateScore(PaymentRailInterface $rail, RailSelectionCriteria $criteria): float
    {
        $score = 50.0; // Base score

        $capabilities = $rail->getCapabilities();

        // Speed scoring (0-25 points)
        $speedScore = $this->calculateSpeedScore($capabilities, $criteria);
        $score += $speedScore;

        // Cost scoring (0-25 points)
        $costScore = $this->calculateCostScore($rail, $criteria);
        $score += $costScore;

        // Capability fit (0-25 points)
        $fitScore = $this->calculateFitScore($capabilities, $criteria);
        $score += $fitScore;

        // Preference bonuses (0-25 points)
        $preferenceScore = $this->calculatePreferenceScore($rail, $criteria);
        $score += $preferenceScore;

        return min(100.0, max(0.0, $score));
    }

    /**
     * Calculate speed score.
     */
    private function calculateSpeedScore(RailCapabilities $capabilities, RailSelectionCriteria $criteria): float
    {
        $maxPoints = 25.0;

        $isRealTime = $capabilities->isRealTime();
        $settlementDays = $capabilities->typicalSettlementDays;

        if ($criteria->urgency === 'real-time') {
            return $isRealTime ? $maxPoints : 0.0;
        }

        if ($criteria->urgency === 'urgent') {
            if ($isRealTime) {
                return $maxPoints;
            }
            if ($settlementDays === 1) {
                return 15.0;
            }
            return 5.0;
        }

        // Standard: prefer faster but don't penalize slow
        if ($isRealTime) {
            return 20.0;
        }
        if ($settlementDays <= 1) {
            return 15.0;
        }
        if ($settlementDays <= 2) {
            return 12.0;
        }
        
        return 10.0;
    }

    /**
     * Calculate cost score.
     */
    private function calculateCostScore(PaymentRailInterface $rail, RailSelectionCriteria $criteria): float
    {
        $maxPoints = 25.0;

        // Estimated relative costs (lower is better)
        $costRanking = match ($rail->getRailType()) {
            RailType::ACH => 1, // Cheapest
            RailType::CHECK => 2,
            RailType::VIRTUAL_CARD => 3,
            RailType::WIRE => 4,
            RailType::RTGS => 5, // Most expensive
        };

        if ($criteria->preferLowCost) {
            // Heavily favor low cost
            return $maxPoints - ($costRanking - 1) * 6;
        }

        // Standard: slight preference for lower cost
        return $maxPoints - ($costRanking - 1) * 3;
    }

    /**
     * Calculate capability fit score.
     */
    private function calculateFitScore(RailCapabilities $capabilities, RailSelectionCriteria $criteria): float
    {
        $score = 10.0;

        // Bonus for supporting recurring if needed
        if ($criteria->requiresRecurring && $capabilities->supportsRecurring) {
            $score += 5.0;
        }

        // Bonus for refund support
        if ($this->supportsRefunds($capabilities)) {
            $score += 3.0;
        }

        // Penalty for being close to limits
        if ($capabilities->maximumAmount !== null) {
            $maxMinorUnits = $capabilities->maximumAmount->getAmountInMinorUnits();
            if ($maxMinorUnits > 0) {
                $utilizationRatio = $criteria->amountCents / $maxMinorUnits;
            if ($utilizationRatio > 0.9) {
                $score -= 5.0; // Close to limit
            }
            }
        }

        return max(0.0, min(25.0, $score));
    }

    /**
     * Calculate preference score.
     */
    private function calculatePreferenceScore(
        PaymentRailInterface $rail,
        RailSelectionCriteria $criteria,
    ): float {
        $score = 0.0;

        // Preferred rail bonus
        if ($criteria->preferredRail === $rail->getRailType()) {
            $score += 15.0;
        }

        // Amount-based preferences
        $railType = $rail->getRailType();

        if ($criteria->amountCents >= self::HIGH_VALUE_THRESHOLD_CENTS) {
            // High value: prefer RTGS or Wire
            if (in_array($railType, [RailType::RTGS, RailType::WIRE], true)) {
                $score += 10.0;
            }
        } elseif ($criteria->amountCents <= 100000) { // $1,000
            // Low value: prefer ACH or Check
            if (in_array($railType, [RailType::ACH, RailType::CHECK], true)) {
                $score += 5.0;
            }
        }

        return min(25.0, $score);
    }

    /**
     * Get country for currency (simplified mapping).
     */
    private function getCountryForCurrency(string $currency): string
    {
        return match ($currency) {
            'USD' => 'US',
            'EUR' => 'DE', // Default to Germany for EUR
            'GBP' => 'GB',
            'CAD' => 'CA',
            'AUD' => 'AU',
            'MYR' => 'MY',
            'SGD' => 'SG',
            'INR' => 'IN',
            default => 'US',
        };
    }

    private function criteriaAmountAsMoney(RailSelectionCriteria $criteria): Money
    {
        return new Money($criteria->amountCents, $criteria->currency);
    }

    private function supportsRefunds(RailCapabilities $capabilities): bool
    {
        return (bool) $capabilities->getCapability('supports_refunds', false);
    }
}
