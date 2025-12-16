<?php

declare(strict_types=1);

namespace Nexus\AmlCompliance\Services;

use Nexus\AmlCompliance\Contracts\AmlRiskAssessorInterface;
use Nexus\AmlCompliance\Contracts\PartyInterface;
use Nexus\AmlCompliance\Contracts\SanctionsResultInterface;
use Nexus\AmlCompliance\Enums\BusinessTypeRisk;
use Nexus\AmlCompliance\Enums\JurisdictionRisk;
use Nexus\AmlCompliance\Enums\RiskLevel;
use Nexus\AmlCompliance\Exceptions\RiskAssessmentFailedException;
use Nexus\AmlCompliance\ValueObjects\AmlRiskScore;
use Nexus\AmlCompliance\ValueObjects\RiskFactors;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * AML Risk Assessor Service
 * 
 * Production-ready implementation of comprehensive AML risk assessment.
 * Calculates risk scores based on FATF guidelines using weighted factors:
 * - Jurisdiction Risk: 30%
 * - Business Type Risk: 20%
 * - Sanctions Risk: 25%
 * - Transaction Risk: 25%
 */
final readonly class AmlRiskAssessor implements AmlRiskAssessorInterface
{
    /**
     * Review frequency in days by risk level
     */
    private const REVIEW_FREQUENCY = [
        RiskLevel::LOW->value => 365,      // Annual
        RiskLevel::MEDIUM->value => 180,   // Semi-annual
        RiskLevel::HIGH->value => 90,      // Quarterly
    ];

    /**
     * PEP risk multipliers by level
     */
    private const PEP_MULTIPLIERS = [
        1 => 2.0,  // Head of state
        2 => 1.8,  // Senior government official
        3 => 1.5,  // Regional political figure
        4 => 1.3,  // Family member of PEP
        5 => 1.2,  // Close associate of PEP
    ];

    public function __construct(
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * {@inheritdoc}
     */
    public function assess(
        PartyInterface $party,
        ?SanctionsResultInterface $sanctionsResult = null,
        array $transactionData = []
    ): AmlRiskScore {
        $this->logger->debug('Starting AML risk assessment', [
            'party_id' => $party->getId(),
            'has_sanctions_result' => $sanctionsResult !== null,
            'has_transaction_data' => !empty($transactionData),
        ]);

        try {
            // Calculate individual risk scores
            $jurisdictionScore = $this->assessJurisdictionRisk($party);
            $businessTypeScore = $this->assessBusinessTypeRisk($party);
            $sanctionsScore = $sanctionsResult !== null
                ? $this->assessSanctionsRisk($sanctionsResult)
                : 0;
            $transactionScore = !empty($transactionData)
                ? $this->assessTransactionRisk($transactionData)
                : 0;

            // Apply PEP multiplier if applicable
            $pepMultiplier = $this->getPepMultiplier($party);

            // Create risk factors
            $factors = new RiskFactors(
                jurisdictionScore: (int) min(100, $jurisdictionScore * $pepMultiplier),
                businessTypeScore: (int) min(100, $businessTypeScore * $pepMultiplier),
                sanctionsScore: $sanctionsScore,
                transactionScore: $transactionScore,
                metadata: [
                    'pep_multiplier' => $pepMultiplier,
                    'pep_level' => $party->getPepLevel(),
                ]
            );

            // Generate risk score
            $riskScore = AmlRiskScore::fromFactors(
                partyId: $party->getId(),
                factors: $factors,
                assessedBy: 'system'
            );

            $this->logger->info('AML risk assessment completed', [
                'party_id' => $party->getId(),
                'overall_score' => $riskScore->overallScore,
                'risk_level' => $riskScore->riskLevel->value,
                'requires_edd' => $this->requiresEdd($riskScore),
            ]);

            return $riskScore;

        } catch (\Throwable $e) {
            $this->logger->error('AML risk assessment failed', [
                'party_id' => $party->getId(),
                'error' => $e->getMessage(),
            ]);

            if ($e instanceof RiskAssessmentFailedException) {
                throw $e;
            }

            throw RiskAssessmentFailedException::calculationError(
                $party->getId(),
                'overall',
                $e->getMessage()
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function assessJurisdictionRisk(PartyInterface $party): int
    {
        $countryCode = strtoupper($party->getCountryCode());
        $associatedCountries = array_map('strtoupper', $party->getAssociatedCountryCodes());

        // Get primary country risk
        $primaryRisk = JurisdictionRisk::fromCountryCode($countryCode);

        // Check for prohibited jurisdictions (FATF blacklist)
        if ($primaryRisk->isProhibited()) {
            throw RiskAssessmentFailedException::prohibitedJurisdiction(
                $party->getId(),
                $countryCode
            );
        }

        // Calculate weighted jurisdiction score using getScoreContribution() for 0-100 scale
        $primaryScore = $primaryRisk->getScoreContribution();
        $maxAssociatedScore = 0;

        foreach ($associatedCountries as $associatedCountry) {
            if ($associatedCountry === $countryCode) {
                continue;
            }

            $associatedRisk = JurisdictionRisk::fromCountryCode($associatedCountry);

            if ($associatedRisk->isProhibited()) {
                // Associated with prohibited jurisdiction - very high risk
                $maxAssociatedScore = max($maxAssociatedScore, 95);
            } else {
                $maxAssociatedScore = max($maxAssociatedScore, $associatedRisk->getScoreContribution());
            }
        }

        // Primary jurisdiction contributes 70%, associated 30%
        $finalScore = (int) round(($primaryScore * 0.7) + ($maxAssociatedScore * 0.3));

        // Check beneficial owners for organizations
        $beneficialOwners = $party->getBeneficialOwners();
        if (!empty($beneficialOwners)) {
            $boRiskScore = $this->assessBeneficialOwnerJurisdictionRisk($beneficialOwners);
            // BO risk adds up to 20 points
            $finalScore = min(100, $finalScore + (int) round($boRiskScore * 0.2));
        }

        $this->logger->debug('Jurisdiction risk assessed', [
            'party_id' => $party->getId(),
            'country_code' => $countryCode,
            'primary_risk' => $primaryRisk->value,
            'final_score' => $finalScore,
        ]);

        return $finalScore;
    }

    /**
     * {@inheritdoc}
     */
    public function assessBusinessTypeRisk(PartyInterface $party): int
    {
        $industryCode = $party->getIndustryCode();

        if ($industryCode === null) {
            // No industry code - assume medium risk
            return 50;
        }

        // Use fromNaicsCode for numeric codes (NAICS), fromIndustryCode for string codes
        if (is_numeric($industryCode)) {
            $businessRisk = BusinessTypeRisk::fromNaicsCode($industryCode);
        } else {
            $businessRisk = BusinessTypeRisk::fromIndustryCode($industryCode);
        }
        $baseScore = $businessRisk->getScoreContribution();

        // Additional risk factors
        $additionalRisk = 0;

        // Cash-intensive indicator from metadata
        $metadata = $party->getMetadata();
        if ($metadata['is_cash_intensive'] ?? false) {
            $additionalRisk += 15;
        }

        // High-value transactions indicator
        if ($metadata['high_value_transactions'] ?? false) {
            $additionalRisk += 10;
        }

        // International exposure
        if (count($party->getAssociatedCountryCodes()) > 3) {
            $additionalRisk += 10;
        }

        $finalScore = min(100, $baseScore + $additionalRisk);

        $this->logger->debug('Business type risk assessed', [
            'party_id' => $party->getId(),
            'industry_code' => $industryCode,
            'business_risk' => $businessRisk->value,
            'final_score' => $finalScore,
        ]);

        return $finalScore;
    }

    /**
     * {@inheritdoc}
     */
    public function assessSanctionsRisk(SanctionsResultInterface $sanctionsResult): int
    {
        if ($sanctionsResult->isBlocked()) {
            // Confirmed sanctions hit - maximum risk
            return 100;
        }

        if (!$sanctionsResult->hasMatches()) {
            // No matches - zero sanctions risk
            return 0;
        }

        // Calculate risk based on match quality
        $matchCount = $sanctionsResult->getMatchCount();
        $highestScore = $sanctionsResult->getHighestMatchScore();
        $hasConfirmed = $sanctionsResult->hasConfirmedMatch();

        if ($hasConfirmed) {
            // Confirmed match but not blocked - very high risk
            return 90;
        }

        // Risk scales with match score and count
        $baseRisk = $highestScore; // 0-100 from sanctions result
        $countMultiplier = min(1.5, 1.0 + ($matchCount * 0.1)); // Up to 1.5x for multiple matches

        $finalScore = min(100, (int) round($baseRisk * $countMultiplier));

        $this->logger->debug('Sanctions risk assessed', [
            'party_id' => $sanctionsResult->getPartyId(),
            'match_count' => $matchCount,
            'highest_score' => $highestScore,
            'final_score' => $finalScore,
        ]);

        return $finalScore;
    }

    /**
     * {@inheritdoc}
     */
    public function assessTransactionRisk(array $transactionData): int
    {
        if (empty($transactionData)) {
            return 0;
        }

        $riskScore = 0;

        // Volume risk (high transaction volumes)
        $totalVolume = $transactionData['total_volume'] ?? 0.0;
        $volumeThreshold = $transactionData['volume_threshold'] ?? 1000000.0;
        if ($totalVolume > $volumeThreshold) {
            $volumeRatio = min(2.0, $totalVolume / $volumeThreshold);
            $riskScore += (int) round(30 * ($volumeRatio - 1));
        }

        // Frequency risk (high transaction frequency)
        $transactionCount = $transactionData['transaction_count'] ?? 0;
        $avgFrequency = $transactionData['average_frequency'] ?? $transactionCount;
        if ($transactionCount > 0 && $avgFrequency > 0) {
            $frequencyRatio = $transactionCount / $avgFrequency;
            if ($frequencyRatio > 2.0) {
                $riskScore += (int) round(min(20, ($frequencyRatio - 2) * 10));
            }
        }

        // Geographic diversity risk
        $countriesCount = $transactionData['unique_countries'] ?? 1;
        if ($countriesCount > 5) {
            $riskScore += min(15, ($countriesCount - 5) * 3);
        }

        // High-risk country transactions
        $highRiskCountryTransactions = $transactionData['high_risk_country_transactions'] ?? 0;
        if ($highRiskCountryTransactions > 0) {
            $hrRatio = $highRiskCountryTransactions / max(1, $transactionCount);
            $riskScore += (int) round(25 * $hrRatio);
        }

        // Structuring indicators
        if ($transactionData['structuring_detected'] ?? false) {
            $riskScore += 30;
        }

        // Round amount patterns
        $roundAmountPercentage = $transactionData['round_amount_percentage'] ?? 0.0;
        if ($roundAmountPercentage > 0.6) {
            $riskScore += (int) round(($roundAmountPercentage - 0.6) * 50);
        }

        return min(100, $riskScore);
    }

    /**
     * {@inheritdoc}
     */
    public function getRiskLevel(int $score): RiskLevel
    {
        return RiskLevel::fromScore($score);
    }

    /**
     * {@inheritdoc}
     */
    public function requiresEdd(AmlRiskScore $riskScore): bool
    {
        return $riskScore->requiresEdd();
    }

    /**
     * {@inheritdoc}
     */
    public function generateRecommendations(AmlRiskScore $riskScore): array
    {
        $recommendations = [];
        $factors = $riskScore->factors;

        // High jurisdiction risk
        if ($factors->jurisdictionScore >= 70) {
            $recommendations[] = 'Enhanced Due Diligence required due to high-risk jurisdiction';
            $recommendations[] = 'Verify source of funds documentation';
            $recommendations[] = 'Obtain additional beneficial ownership information';
        } elseif ($factors->jurisdictionScore >= 40) {
            $recommendations[] = 'Consider additional jurisdiction verification';
        }

        // High business type risk
        if ($factors->businessTypeScore >= 70) {
            $recommendations[] = 'Enhanced monitoring for high-risk business type';
            $recommendations[] = 'Verify business license and registration';
            $recommendations[] = 'Implement enhanced transaction monitoring';
        }

        // High sanctions risk
        if ($factors->sanctionsScore >= 70) {
            $recommendations[] = 'URGENT: Review sanctions matches immediately';
            $recommendations[] = 'Escalate to compliance officer';
            $recommendations[] = 'Consider relationship termination if match confirmed';
        } elseif ($factors->sanctionsScore >= 40) {
            $recommendations[] = 'Review potential sanctions matches';
            $recommendations[] = 'Document false positive analysis if applicable';
        }

        // High transaction risk
        if ($factors->transactionScore >= 70) {
            $recommendations[] = 'Review transaction patterns for suspicious activity';
            $recommendations[] = 'Consider filing SAR if patterns persist';
        } elseif ($factors->transactionScore >= 40) {
            $recommendations[] = 'Monitor transaction patterns closely';
        }

        // Overall high risk
        if ($riskScore->riskLevel === RiskLevel::HIGH) {
            $recommendations[] = 'Quarterly risk review required';
            $recommendations[] = 'Senior management approval required for continued relationship';
        }

        return array_unique($recommendations);
    }

    /**
     * {@inheritdoc}
     */
    public function calculateNextReviewDate(RiskLevel $riskLevel): \DateTimeImmutable
    {
        $days = self::REVIEW_FREQUENCY[$riskLevel->value] ?? 365;
        return new \DateTimeImmutable("+{$days} days");
    }

    /**
     * Get PEP multiplier for party
     */
    private function getPepMultiplier(PartyInterface $party): float
    {
        if (!$party->isPep()) {
            return 1.0;
        }

        $pepLevel = $party->getPepLevel() ?? 5;
        return self::PEP_MULTIPLIERS[$pepLevel] ?? 1.2;
    }

    /**
     * Assess jurisdiction risk for beneficial owners
     * 
     * @param array<array{name: string, ownership_percentage: float, country_code: string}> $beneficialOwners
     */
    private function assessBeneficialOwnerJurisdictionRisk(array $beneficialOwners): int
    {
        if (empty($beneficialOwners)) {
            return 0;
        }

        $totalRisk = 0.0;
        $totalWeight = 0.0;

        foreach ($beneficialOwners as $owner) {
            $ownershipPercentage = $owner['ownership_percentage'] ?? 0.0;
            $countryCode = strtoupper($owner['country_code'] ?? 'US');

            $jurisdictionRisk = JurisdictionRisk::fromCountryCode($countryCode);
            $riskWeight = $jurisdictionRisk->getWeight();

            // Weight by ownership percentage
            $totalRisk += $riskWeight * ($ownershipPercentage / 100);
            $totalWeight += $ownershipPercentage / 100;
        }

        if ($totalWeight === 0.0) {
            return 0;
        }

        return (int) round($totalRisk / $totalWeight);
    }
}
