<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\Coordinators;

use Nexus\ComplianceOperations\Contracts\AmlScreeningAdapterInterface;
use Nexus\ComplianceOperations\Contracts\KycVerificationAdapterInterface;
use Nexus\ComplianceOperations\Contracts\SanctionsCheckAdapterInterface;
use Nexus\ComplianceOperations\DataProviders\RiskAssessmentDataProvider;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Coordinates risk scoring and assessment operations.
 *
 * This coordinator manages risk assessment processes including
 * risk scoring, risk level determination, and risk-based recommendations.
 *
 * Following the Advanced Orchestrator Pattern:
 * - Coordinators direct flow, they do not execute business logic
 * - Delegates to data providers for data aggregation
 * - Uses adapters for external service integration
 *
 * @see ARCHITECTURE.md Section 3 for coordinator patterns
 */
final readonly class RiskAssessmentCoordinator
{
    public function __construct(
        private AmlScreeningAdapterInterface $amlAdapter,
        private KycVerificationAdapterInterface $kycAdapter,
        private SanctionsCheckAdapterInterface $sanctionsAdapter,
        private ?RiskAssessmentDataProvider $riskDataProvider = null,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * Perform comprehensive risk assessment for a party.
     *
     * @param string $tenantId Tenant identifier
     * @param string $partyId Party identifier
     * @param array<string, mixed> $partyData Additional party data for assessment
     * @return array<string, mixed> Risk assessment result
     */
    public function assessRisk(
        string $tenantId,
        string $partyId,
        array $partyData = []
    ): array {
        $this->logger->info('Performing risk assessment', [
            'tenant_id' => $tenantId,
            'party_id' => $partyId,
        ]);

        try {
            $riskFactors = [];
            $totalScore = 0;
            $maxScore = 100;

            // 1. AML Risk Assessment
            $amlAssessment = $this->amlAdapter->getCurrentAssessment($partyId);
            $amlRiskLevel = $this->amlAdapter->getRiskLevel($partyId);

            if ($amlRiskLevel === 'high') {
                $riskFactors[] = [
                    'category' => 'aml',
                    'factor' => 'high_aml_risk',
                    'score' => 30,
                    'description' => 'Party has high AML risk classification',
                ];
                $totalScore += 30;
            } elseif ($amlRiskLevel === 'medium') {
                $riskFactors[] = [
                    'category' => 'aml',
                    'factor' => 'medium_aml_risk',
                    'score' => 15,
                    'description' => 'Party has medium AML risk classification',
                ];
                $totalScore += 15;
            }

            // 2. Sanctions Risk
            if ($this->sanctionsAdapter->hasMatches($partyId)) {
                $riskFactors[] = [
                    'category' => 'sanctions',
                    'factor' => 'sanctions_match',
                    'score' => 40,
                    'description' => 'Party has sanctions list matches',
                ];
                $totalScore += 40;
            }

            // 3. PEP Status
            if ($this->sanctionsAdapter->isPep($partyId)) {
                $riskFactors[] = [
                    'category' => 'pep',
                    'factor' => 'pep_status',
                    'score' => 20,
                    'description' => 'Party is a Politically Exposed Person',
                ];
                $totalScore += 20;
            }

            // 4. KYC Verification Status
            $kycStatus = $this->kycAdapter->getStatus($partyId);
            if ($kycStatus === 'rejected' || $kycStatus === 'expired') {
                $riskFactors[] = [
                    'category' => 'kyc',
                    'factor' => 'kyc_' . $kycStatus,
                    'score' => 25,
                    'description' => "KYC verification is {$kycStatus}",
                ];
                $totalScore += 25;
            }

            // 5. EDD Requirement
            if ($this->amlAdapter->requiresEdd($partyId)) {
                $riskFactors[] = [
                    'category' => 'edd',
                    'factor' => 'requires_edd',
                    'score' => 10,
                    'description' => 'Party requires Enhanced Due Diligence',
                ];
                $totalScore += 10;
            }

            // 6. Country Risk (if provided)
            if (isset($partyData['country_code'])) {
                $countryRisk = $this->assessCountryRisk($partyData['country_code']);
                if ($countryRisk > 0) {
                    $riskFactors[] = [
                        'category' => 'geographic',
                        'factor' => 'high_risk_country',
                        'score' => $countryRisk,
                        'description' => 'Party is associated with high-risk jurisdiction',
                    ];
                    $totalScore += $countryRisk;
                }
            }

            // Cap the score at max
            $totalScore = min($totalScore, $maxScore);

            // Determine overall risk level
            $riskLevel = $this->determineRiskLevel($totalScore);

            // Generate recommendations
            $recommendations = $this->generateRecommendations($riskLevel, $riskFactors);

            return [
                'success' => true,
                'tenant_id' => $tenantId,
                'party_id' => $partyId,
                'risk_score' => $totalScore,
                'risk_level' => $riskLevel,
                'risk_factors' => $riskFactors,
                'requires_edd' => $this->amlAdapter->requiresEdd($partyId),
                'recommendations' => $recommendations,
                'aml_assessment' => $amlAssessment,
                'kyc_status' => $kycStatus,
                'sanctions_status' => [
                    'has_matches' => $this->sanctionsAdapter->hasMatches($partyId),
                    'is_pep' => $this->sanctionsAdapter->isPep($partyId),
                ],
                'assessed_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
                'next_review_date' => $this->amlAdapter->calculateNextReviewDate($riskLevel)->format(\DateTimeInterface::ATOM),
            ];
        } catch (\Throwable $e) {
            $this->logger->error('Failed to perform risk assessment', [
                'party_id' => $partyId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'party_id' => $partyId,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get risk summary for multiple parties.
     *
     * @param string $tenantId Tenant identifier
     * @param array<string> $partyIds Party identifiers
     * @return array<string, mixed> Risk summary
     */
    public function getRiskSummary(
        string $tenantId,
        array $partyIds
    ): array {
        $this->logger->info('Getting risk summary', [
            'tenant_id' => $tenantId,
            'party_count' => count($partyIds),
        ]);

        $summaries = [];
        $riskDistribution = [
            'high' => 0,
            'medium' => 0,
            'low' => 0,
        ];

        foreach ($partyIds as $partyId) {
            $assessment = $this->assessRisk($tenantId, $partyId);

            if ($assessment['success']) {
                $summaries[] = [
                    'party_id' => $partyId,
                    'risk_score' => $assessment['risk_score'],
                    'risk_level' => $assessment['risk_level'],
                    'requires_edd' => $assessment['requires_edd'],
                ];

                $riskDistribution[$assessment['risk_level']]++;
            }
        }

        return [
            'tenant_id' => $tenantId,
            'total_parties' => count($partyIds),
            'assessed_count' => count($summaries),
            'risk_distribution' => $riskDistribution,
            'summaries' => $summaries,
            'generated_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * Get parties by risk level.
     *
     * @param string $tenantId Tenant identifier
     * @param string $riskLevel Risk level (high, medium, low)
     * @param int $limit Maximum number of parties to return
     * @return array<string, mixed> Parties by risk level
     */
    public function getPartiesByRiskLevel(
        string $tenantId,
        string $riskLevel,
        int $limit = 100
    ): array {
        $this->logger->info('Getting parties by risk level', [
            'tenant_id' => $tenantId,
            'risk_level' => $riskLevel,
        ]);

        // This would typically query a data provider
        return [
            'tenant_id' => $tenantId,
            'risk_level' => $riskLevel,
            'limit' => $limit,
            'parties' => [],
            'total_count' => 0,
            'generated_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * Calculate risk score for a transaction.
     *
     * @param string $tenantId Tenant identifier
     * @param string $partyId Party identifier
     * @param int $amountCents Transaction amount in cents
     * @param string $currency Transaction currency
     * @param array<string, mixed> $transactionData Additional transaction data
     * @return array<string, mixed> Transaction risk score
     */
    public function calculateTransactionRisk(
        string $tenantId,
        string $partyId,
        int $amountCents,
        string $currency,
        array $transactionData = []
    ): array {
        $this->logger->info('Calculating transaction risk', [
            'tenant_id' => $tenantId,
            'party_id' => $partyId,
            'amount_cents' => $amountCents,
        ]);

        // Get base party risk
        $partyRisk = $this->assessRisk($tenantId, $partyId);

        $transactionRiskScore = $partyRisk['risk_score'] ?? 0;
        $riskFactors = $partyRisk['risk_factors'] ?? [];

        // Add transaction-specific risk factors
        $amountThresholds = $this->getAmountThresholds($currency);

        if ($amountCents >= $amountThresholds['high']) {
            $riskFactors[] = [
                'category' => 'transaction',
                'factor' => 'high_value',
                'score' => 20,
                'description' => 'High value transaction',
            ];
            $transactionRiskScore += 20;
        } elseif ($amountCents >= $amountThresholds['medium']) {
            $riskFactors[] = [
                'category' => 'transaction',
                'factor' => 'medium_value',
                'score' => 10,
                'description' => 'Medium value transaction',
            ];
            $transactionRiskScore += 10;
        }

        // Check for cross-border transaction
        if (isset($transactionData['cross_border']) && $transactionData['cross_border']) {
            $riskFactors[] = [
                'category' => 'transaction',
                'factor' => 'cross_border',
                'score' => 15,
                'description' => 'Cross-border transaction',
            ];
            $transactionRiskScore += 15;
        }

        // Cap at 100
        $transactionRiskScore = min($transactionRiskScore, 100);

        return [
            'success' => true,
            'tenant_id' => $tenantId,
            'party_id' => $partyId,
            'transaction_risk_score' => $transactionRiskScore,
            'party_risk_score' => $partyRisk['risk_score'] ?? 0,
            'risk_level' => $this->determineRiskLevel($transactionRiskScore),
            'risk_factors' => $riskFactors,
            'requires_review' => $transactionRiskScore >= 50,
            'requires_manual_approval' => $transactionRiskScore >= 75,
            'calculated_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * Assess country risk.
     *
     * @param string $countryCode ISO 3166-1 alpha-2 country code
     * @return int Country risk score
     */
    private function assessCountryRisk(string $countryCode): int
    {
        // High-risk jurisdictions (simplified list)
        $highRiskCountries = ['IR', 'KP', 'SY', 'CU', 'SD', 'MM', 'BY', 'RU', 'AF'];
        $mediumRiskCountries = ['PK', 'YE', 'VE', 'ZW', 'NG', 'KE'];

        $countryCode = strtoupper($countryCode);

        if (in_array($countryCode, $highRiskCountries, true)) {
            return 25;
        }

        if (in_array($countryCode, $mediumRiskCountries, true)) {
            return 15;
        }

        return 0;
    }

    /**
     * Determine risk level from score.
     *
     * @param int $score Risk score
     * @return string Risk level
     */
    private function determineRiskLevel(int $score): string
    {
        if ($score >= 50) {
            return 'high';
        }

        if ($score >= 25) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Generate recommendations based on risk assessment.
     *
     * @param string $riskLevel Risk level
     * @param array<int, array<string, mixed>> $riskFactors Risk factors
     * @return array<string> Recommendations
     */
    private function generateRecommendations(string $riskLevel, array $riskFactors): array
    {
        $recommendations = [];

        // Add AML recommendations
        foreach ($riskFactors as $factor) {
            if ($factor['category'] === 'aml' && $factor['factor'] === 'high_aml_risk') {
                $recommendations[] = 'Perform enhanced due diligence';
                $recommendations[] = 'Obtain senior management approval for high-risk relationship';
            }

            if ($factor['category'] === 'sanctions') {
                $recommendations[] = 'Review sanctions matches before proceeding';
                $recommendations[] = 'Consider filing Suspicious Activity Report if warranted';
            }

            if ($factor['category'] === 'pep') {
                $recommendations[] = 'Apply additional PEP screening measures';
                $recommendations[] = 'Obtain source of funds documentation';
            }
        }

        // Add level-based recommendations
        if ($riskLevel === 'high') {
            $recommendations[] = 'Implement ongoing monitoring';
            $recommendations[] = 'Schedule more frequent reviews';
        }

        return array_unique($recommendations);
    }

    /**
     * Get amount thresholds for currency.
     *
     * @param string $currency Currency code
     * @return array<string, int> Thresholds in cents
     */
    private function getAmountThresholds(string $currency): array
    {
        // Simplified thresholds - in production these would be configurable
        return match (strtoupper($currency)) {
            'USD', 'EUR', 'GBP' => [
                'high' => 10_000_00,    // $10,000
                'medium' => 3_000_00,   // $3,000
            ],
            'JPY' => [
                'high' => 1_000_000_00,  // ¥1,000,000
                'medium' => 300_000_00,  // ¥300,000
            ],
            default => [
                'high' => 10_000_00,
                'medium' => 3_000_00,
            ],
        };
    }
}
