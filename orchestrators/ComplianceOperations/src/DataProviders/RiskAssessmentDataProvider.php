<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\DataProviders;

use Nexus\KycVerification\Contracts\RiskAssessorInterface as KycRiskAssessorInterface;
use Nexus\AmlCompliance\Contracts\AmlRiskAssessorInterface;
use Nexus\ComplianceOperations\DTOs\Risk\RiskAssessmentContext;
use Nexus\ComplianceOperations\DTOs\Risk\RiskSummaryData;
use Nexus\ComplianceOperations\Exceptions\RiskAssessmentException;
use Psr\Log\LoggerInterface;

/**
 * DataProvider for risk scoring and assessment data aggregation.
 *
 * Aggregates risk data from multiple sources to provide
 * comprehensive context for compliance workflows including:
 * - Combined risk scores from KYC and AML
 * - Risk factor analysis
 * - Risk trend tracking
 * - EDD requirement determination
 *
 * Following Advanced Orchestrator Pattern v1.1:
 * DataProviders abstract data fetching from Coordinators.
 */
final readonly class RiskAssessmentDataProvider
{
    public function __construct(
        private KycRiskAssessorInterface $kycRiskAssessor,
        private AmlRiskAssessorInterface $amlRiskAssessor,
        private LoggerInterface $logger,
    ) {}

    /**
     * Get comprehensive risk assessment context for a party.
     *
     * @param string $tenantId Tenant context
     * @param string $partyId Party identifier
     * @param array<string, mixed> $partyData Additional party data for assessment
     * @throws RiskAssessmentException If data cannot be retrieved
     */
    public function getRiskContext(
        string $tenantId,
        string $partyId,
        array $partyData = []
    ): RiskAssessmentContext {
        $this->logger->info('Fetching risk assessment context', [
            'tenant_id' => $tenantId,
            'party_id' => $partyId,
        ]);

        // Get KYC risk assessment
        $kycAssessment = $this->kycRiskAssessor->getCurrentAssessment($partyId);

        // Get AML risk assessment
        $amlParty = $this->buildAmlPartyAdapter($partyId, $partyData);
        $amlAssessment = $this->amlRiskAssessor->assess($amlParty);

        // Calculate combined risk score
        $combinedScore = $this->calculateCombinedScore(
            $kycAssessment?->riskScore ?? 0,
            $amlAssessment->overallScore
        );

        // Determine if EDD is required
        $requiresEdd = ($kycAssessment !== null && $this->kycRiskAssessor->isHighRisk($partyId))
            || $this->amlRiskAssessor->requiresEdd($amlAssessment);

        // Get risk factors from both sources
        $kycFactors = $kycAssessment !== null ? $this->buildKycFactorsData($kycAssessment) : [];
        $amlFactors = $this->buildAmlFactorsData($amlAssessment);

        return new RiskAssessmentContext(
            tenantId: $tenantId,
            partyId: $partyId,
            combinedRiskScore: $combinedScore,
            kycRiskScore: $kycAssessment?->riskScore ?? null,
            amlRiskScore: $amlAssessment->overallScore,
            riskLevel: $this->determineRiskLevel($combinedScore),
            requiresEdd: $requiresEdd,
            kycRiskFactors: $kycFactors,
            amlRiskFactors: $amlFactors,
            recommendations: $this->mergeRecommendations(
                $kycAssessment !== null ? [] : [],
                $this->amlRiskAssessor->generateRecommendations($amlAssessment)
            ),
            nextReviewDate: $this->amlRiskAssessor->calculateNextReviewDate($amlAssessment->riskLevel),
            assessedAt: new \DateTimeImmutable(),
        );
    }

    /**
     * Get risk summary for dashboard display.
     *
     * @param string $tenantId Tenant context
     */
    public function getRiskSummary(string $tenantId): RiskSummaryData
    {
        $this->logger->info('Fetching risk summary', [
            'tenant_id' => $tenantId,
        ]);

        // Get parties by risk level from KYC
        $kycHighRisk = $this->kycRiskAssessor->getPartiesNeedingReview();
        $amlHighRisk = $this->amlRiskAssessor->getPartiesByRiskLevel(
            \Nexus\AmlCompliance\Enums\RiskLevel::HIGH
        );

        return new RiskSummaryData(
            tenantId: $tenantId,
            totalAssessed: count($kycHighRisk) + count($amlHighRisk),
            highRiskCount: count($kycHighRisk),
            mediumRiskCount: 0,
            lowRiskCount: 0,
            requiresEddCount: count($kycHighRisk),
            pendingReviewCount: count($kycHighRisk),
            generatedAt: new \DateTimeImmutable(),
        );
    }

    /**
     * Get parties requiring enhanced due diligence.
     *
     * @param string $tenantId Tenant context
     * @return array<string> Party IDs requiring EDD
     */
    public function getPartiesRequiringEdd(string $tenantId): array
    {
        $this->logger->info('Fetching parties requiring EDD', [
            'tenant_id' => $tenantId,
        ]);

        $kycParties = $this->kycRiskAssessor->getPartiesNeedingReview();
        $amlParties = $this->amlRiskAssessor->getPartiesByRiskLevel(
            \Nexus\AmlCompliance\Enums\RiskLevel::HIGH
        );

        return array_unique(array_merge($kycParties, $amlParties));
    }

    /**
     * Get parties needing risk review.
     *
     * @param string $tenantId Tenant context
     * @return array<string> Party IDs needing review
     */
    public function getPartiesNeedingReview(string $tenantId): array
    {
        $this->logger->info('Fetching parties needing risk review', [
            'tenant_id' => $tenantId,
        ]);

        return $this->kycRiskAssessor->getPartiesNeedingReview();
    }

    /**
     * Check if party is high risk.
     *
     * @param string $partyId Party identifier
     */
    public function isHighRisk(string $partyId): bool
    {
        return $this->kycRiskAssessor->isHighRisk($partyId)
            || $this->kycRiskAssessor->isBlocked($partyId);
    }

    /**
     * Check if party requires enhanced due diligence.
     *
     * @param string $partyId Party identifier
     */
    public function requiresEdd(string $partyId): bool
    {
        $kycLevel = $this->kycRiskAssessor->getRiskLevel($partyId);
        
        if ($kycLevel !== null && in_array($kycLevel->value, ['high', 'very_high', 'prohibited'], true)) {
            return true;
        }

        return false;
    }

    /**
     * Calculate combined risk score from KYC and AML scores.
     */
    private function calculateCombinedScore(int $kycScore, int $amlScore): int
    {
        // Weighted average: KYC 40%, AML 60%
        $weighted = ($kycScore * 0.4) + ($amlScore * 0.6);
        return (int) min(100, max(0, $weighted));
    }

    /**
     * Determine risk level from combined score.
     */
    private function determineRiskLevel(int $score): string
    {
        return match (true) {
            $score >= 80 => 'prohibited',
            $score >= 60 => 'very_high',
            $score >= 40 => 'high',
            $score >= 20 => 'medium',
            default => 'low',
        };
    }

    /**
     * Build AML party adapter.
     */
    private function buildAmlPartyAdapter(string $partyId, array $partyData): object
    {
        return new class($partyId, $partyData) implements \Nexus\AmlCompliance\Contracts\PartyInterface {
            public function __construct(
                private string $partyId,
                private array $data,
            ) {}

            public function getId(): string { return $this->partyId; }
            public function getName(): string { return $this->data['name'] ?? ''; }
            public function getType(): string { return $this->data['type'] ?? 'individual'; }
            public function getCountryCode(): string { return $this->data['countryCode'] ?? 'MY'; }
            public function getAssociatedCountryCodes(): array { return $this->data['associatedCountries'] ?? []; }
            public function getIndustryCode(): ?string { return $this->data['industryCode'] ?? null; }
            public function isPep(): bool { return $this->data['isPep'] ?? false; }
            public function getPepLevel(): ?int { return $this->data['pepLevel'] ?? null; }
            public function getCreatedAt(): \DateTimeImmutable { return $this->data['createdAt'] ?? new \DateTimeImmutable(); }
            public function getDateOfBirthOrIncorporation(): ?\DateTimeImmutable { return $this->data['dateOfBirth'] ?? null; }
            public function getBeneficialOwners(): array { return $this->data['beneficialOwners'] ?? []; }
            public function getIdentifiers(): array { return $this->data['identifiers'] ?? []; }
            public function getMetadata(): array { return $this->data['metadata'] ?? []; }
            public function isActive(): bool { return $this->data['isActive'] ?? true; }
            public function getLastActivityDate(): ?\DateTimeImmutable { return $this->data['lastActivityDate'] ?? null; }
        };
    }

    /**
     * Build KYC risk factors data array.
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildKycFactorsData(object $assessment): array
    {
        $factors = [];
        foreach ($assessment->riskFactors as $factor) {
            $factors[] = [
                'source' => 'kyc',
                'code' => $factor->code,
                'category' => $factor->category,
                'score' => $factor->score,
                'description' => $factor->description,
            ];
        }
        return $factors;
    }

    /**
     * Build AML risk factors data array.
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildAmlFactorsData(object $assessment): array
    {
        $factors = $assessment->factors;
        return [
            [
                'source' => 'aml',
                'category' => 'jurisdiction',
                'score' => $factors->jurisdictionScore,
                'description' => 'Jurisdiction risk score',
            ],
            [
                'source' => 'aml',
                'category' => 'business_type',
                'score' => $factors->businessTypeScore,
                'description' => 'Business type risk score',
            ],
            [
                'source' => 'aml',
                'category' => 'sanctions',
                'score' => $factors->sanctionsScore,
                'description' => 'Sanctions risk score',
            ],
            [
                'source' => 'aml',
                'category' => 'transaction',
                'score' => $factors->transactionScore,
                'description' => 'Transaction risk score',
            ],
        ];
    }

    /**
     * Merge recommendations from multiple sources.
     *
     * @param array<string> $kycRecommendations
     * @param array<string> $amlRecommendations
     * @return array<string>
     */
    private function mergeRecommendations(array $kycRecommendations, array $amlRecommendations): array
    {
        return array_unique(array_merge($kycRecommendations, $amlRecommendations));
    }
}
