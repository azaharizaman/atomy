<?php

declare(strict_types=1);

namespace Nexus\KycVerification\Services;

use Nexus\KycVerification\Contracts\KycProfilePersistInterface;
use Nexus\KycVerification\Contracts\KycProfileQueryInterface;
use Nexus\KycVerification\Contracts\Providers\AuditLoggerProviderInterface;
use Nexus\KycVerification\Contracts\Providers\PartyProviderInterface;
use Nexus\KycVerification\Contracts\Providers\ScreeningProviderInterface;
use Nexus\KycVerification\Contracts\RiskAssessorInterface;
use Nexus\KycVerification\Enums\RiskLevel;
use Nexus\KycVerification\Exceptions\HighRiskPartyException;
use Nexus\KycVerification\Exceptions\KycVerificationException;
use Nexus\KycVerification\ValueObjects\RiskAssessment;
use Nexus\KycVerification\ValueObjects\RiskFactor;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Risk Assessor - evaluates and manages party risk levels.
 */
final readonly class RiskAssessor implements RiskAssessorInterface
{
    /**
     * Risk score thresholds for each level.
     */
    private const array RISK_THRESHOLDS = [
        'low' => 20,
        'medium' => 40,
        'high' => 60,
        'very_high' => 80,
        'prohibited' => 100,
    ];

    /**
     * Country risk scores (ISO 3166-1 alpha-2 codes).
     * Higher score = higher risk.
     * 
     * @var array<string, int>
     */
    private const array HIGH_RISK_COUNTRIES = [
        'KP' => 100, // North Korea
        'IR' => 95,  // Iran
        'SY' => 90,  // Syria
        'CU' => 85,  // Cuba
        'VE' => 70,  // Venezuela
        'MM' => 65,  // Myanmar
        'AF' => 60,  // Afghanistan
        'IQ' => 55,  // Iraq
        'LY' => 50,  // Libya
        'YE' => 50,  // Yemen
    ];

    /**
     * High-risk business categories.
     * 
     * @var array<string, int>
     */
    private const array HIGH_RISK_INDUSTRIES = [
        'gambling' => 40,
        'cryptocurrency' => 35,
        'money_services' => 35,
        'precious_metals' => 30,
        'arms_defense' => 45,
        'tobacco' => 25,
        'adult_entertainment' => 30,
        'cannabis' => 35,
    ];

    public function __construct(
        private KycProfileQueryInterface $profileQuery,
        private KycProfilePersistInterface $profilePersist,
        private PartyProviderInterface $partyProvider,
        private ScreeningProviderInterface $screeningProvider,
        private AuditLoggerProviderInterface $auditLogger,
        private LoggerInterface $logger = new NullLogger()
    ) {}

    public function assess(string $partyId, array $partyData = []): RiskAssessment
    {
        try {
            $factors = [];

            // Country risk
            $country = $this->partyProvider->getPartyCountry($partyId);
            if ($country !== null) {
                $countryRiskScore = self::HIGH_RISK_COUNTRIES[$country] ?? 0;
                if ($countryRiskScore > 0) {
                    $factors[] = RiskFactor::countryRisk(
                        $country,
                        $countryRiskScore
                    );
                }
            }

            // Industry risk
            $industry = $partyData['industry'] ?? null;
            if ($industry !== null && isset(self::HIGH_RISK_INDUSTRIES[$industry])) {
                $factors[] = RiskFactor::industryRisk(
                    $industry,
                    self::HIGH_RISK_INDUSTRIES[$industry]
                );
            }

            // Screening results
            $screeningResult = $this->screeningProvider->runComprehensiveScreening($partyId);
            
            // PEP status
            if ($screeningResult['pep']['isMatch'] ?? false) {
                $factors[] = RiskFactor::pepStatus(
                    $screeningResult['pep']['level'] ?? 'unknown',
                    $screeningResult['pep']['relationship'] ?? 'direct'
                );
            }

            // Sanctions
            if ($screeningResult['sanctions']['hasMatches'] ?? false) {
                $factors[] = RiskFactor::sanctionsMatch(
                    $screeningResult['sanctions']['lists'] ?? [],
                    $screeningResult['sanctions']['matchScore'] ?? 100
                );
            }

            // Adverse media
            if ($screeningResult['adverseMedia']['hasMatches'] ?? false) {
                $adverseMediaScore = min(
                    50,
                    ($screeningResult['adverseMedia']['matchCount'] ?? 0) * 10
                );
                $factors[] = new RiskFactor(
                    category: 'adverse_media',
                    name: 'Adverse Media',
                    description: 'Negative news coverage detected',
                    score: $adverseMediaScore,
                    source: 'screening_provider',
                    assessedAt: new \DateTimeImmutable(),
                    metadata: [
                        'match_count' => $screeningResult['adverseMedia']['matchCount'] ?? 0,
                    ]
                );
            }

            // Transaction patterns (if available)
            if (isset($partyData['transaction_volume'])) {
                $volumeScore = $this->assessTransactionVolume($partyData['transaction_volume']);
                if ($volumeScore > 0) {
                    $factors[] = new RiskFactor(
                        category: 'behavior',
                        name: 'High Transaction Volume',
                        description: 'Transaction volume exceeds typical thresholds',
                        score: $volumeScore,
                        source: 'transaction_analysis',
                        assessedAt: new \DateTimeImmutable(),
                        metadata: [
                            'volume' => $partyData['transaction_volume'],
                        ]
                    );
                }
            }

            // Calculate total score and determine risk level
            $totalScore = $this->calculateRiskScore($factors);
            $riskLevel = RiskLevel::fromScore($totalScore);

            $assessment = new RiskAssessment(
                riskLevel: $riskLevel,
                riskScore: $totalScore,
                factors: $factors,
                assessedAt: new \DateTimeImmutable(),
                validUntil: $this->calculateValidityPeriod($riskLevel),
                assessedBy: $partyData['assessed_by'] ?? null,
                overridden: false,
                overrideReason: null,
                previousLevel: null,
                metadata: [
                    'screening_timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
                ]
            );

            // Persist assessment
            $this->profilePersist->saveRiskAssessment($partyId, $assessment);

            // Log audit event
            $this->auditLogger->logRiskAssessment(
                partyId: $partyId,
                riskLevel: $riskLevel->value,
                riskScore: $totalScore,
                factors: array_map(fn(RiskFactor $f) => $f->toArray(), $factors),
                assessedBy: $partyData['assessed_by'] ?? null
            );

            $this->logger->info('Risk assessment completed', [
                'party_id' => $partyId,
                'risk_level' => $riskLevel->value,
                'risk_score' => $totalScore,
                'factor_count' => count($factors),
            ]);

            return $assessment;
        } catch (\Throwable $e) {
            $this->logger->error('Risk assessment failed', [
                'party_id' => $partyId,
                'error' => $e->getMessage(),
            ]);
            throw KycVerificationException::forParty(
                $partyId,
                'Risk assessment failed: ' . $e->getMessage(),
                $e
            );
        }
    }

    public function getCurrentAssessment(string $partyId): ?RiskAssessment
    {
        $profile = $this->profileQuery->findByPartyId($partyId);
        return $profile?->riskAssessment;
    }

    public function updateAssessment(
        string $partyId,
        array $newFactors,
        ?string $assessedBy = null
    ): RiskAssessment {
        $currentAssessment = $this->getCurrentAssessment($partyId);
        
        // Combine existing factors with new ones (override by category)
        $factors = $currentAssessment?->factors ?? [];
        $existingCategories = array_map(fn(RiskFactor $f) => $f->category, $factors);
        
        foreach ($newFactors as $newFactor) {
            $key = array_search($newFactor->category, $existingCategories);
            if ($key !== false) {
                $factors[$key] = $newFactor;
            } else {
                $factors[] = $newFactor;
            }
        }

        $totalScore = $this->calculateRiskScore($factors);
        $riskLevel = RiskLevel::fromScore($totalScore);

        $assessment = new RiskAssessment(
            riskLevel: $riskLevel,
            riskScore: $totalScore,
            factors: $factors,
            assessedAt: new \DateTimeImmutable(),
            validUntil: $this->calculateValidityPeriod($riskLevel),
            assessedBy: $assessedBy,
            overridden: false,
            overrideReason: null,
            previousLevel: $currentAssessment?->riskLevel,
            metadata: []
        );

        $this->profilePersist->saveRiskAssessment($partyId, $assessment);

        $this->auditLogger->logRiskAssessment(
            partyId: $partyId,
            riskLevel: $riskLevel->value,
            riskScore: $totalScore,
            factors: array_map(fn(RiskFactor $f) => $f->toArray(), $factors),
            assessedBy: $assessedBy
        );

        return $assessment;
    }

    public function overrideRiskLevel(
        string $partyId,
        RiskLevel $newRiskLevel,
        string $reason,
        string $approvedBy
    ): RiskAssessment {
        $currentAssessment = $this->getCurrentAssessment($partyId);
        if ($currentAssessment === null) {
            throw KycVerificationException::forParty(
                $partyId,
                'No existing risk assessment to override'
            );
        }

        $assessment = new RiskAssessment(
            riskLevel: $newRiskLevel,
            riskScore: $currentAssessment->riskScore,
            factors: $currentAssessment->factors,
            assessedAt: new \DateTimeImmutable(),
            validUntil: $this->calculateValidityPeriod($newRiskLevel),
            assessedBy: $approvedBy,
            overridden: true,
            overrideReason: $reason,
            previousLevel: $currentAssessment->riskLevel,
            metadata: [
                'override_approved_by' => $approvedBy,
                'override_timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            ]
        );

        $this->profilePersist->saveRiskAssessment($partyId, $assessment);

        $this->auditLogger->logRiskAssessment(
            partyId: $partyId,
            riskLevel: $newRiskLevel->value,
            riskScore: $currentAssessment->riskScore,
            factors: array_map(fn(RiskFactor $f) => $f->toArray(), $currentAssessment->factors),
            assessedBy: $approvedBy
        );

        $this->logger->warning('Risk level overridden', [
            'party_id' => $partyId,
            'previous_level' => $currentAssessment->riskLevel->value,
            'new_level' => $newRiskLevel->value,
            'reason' => $reason,
            'approved_by' => $approvedBy,
        ]);

        return $assessment;
    }

    public function getRiskLevel(string $partyId): ?RiskLevel
    {
        return $this->getCurrentAssessment($partyId)?->riskLevel;
    }

    public function calculateRiskScore(array $factors): int
    {
        if (empty($factors)) {
            return 0;
        }

        $totalScore = 0;
        foreach ($factors as $factor) {
            $totalScore += $factor->score;
        }

        // Cap at 100
        return min(100, $totalScore);
    }

    public function getRiskFactors(string $partyId): array
    {
        return $this->getCurrentAssessment($partyId)?->factors ?? [];
    }

    public function isHighRisk(string $partyId): bool
    {
        $riskLevel = $this->getRiskLevel($partyId);
        return $riskLevel !== null && in_array($riskLevel, [
            RiskLevel::HIGH,
            RiskLevel::VERY_HIGH,
            RiskLevel::PROHIBITED,
        ], true);
    }

    public function isBlocked(string $partyId): bool
    {
        return $this->getRiskLevel($partyId) === RiskLevel::PROHIBITED;
    }

    public function getPartiesByRiskLevel(RiskLevel $riskLevel): array
    {
        // Get all profiles and filter by risk level
        $profiles = $this->profileQuery->findHighRisk();
        
        return array_values(
            array_filter(
                array_map(
                    fn($p) => $p->riskAssessment?->riskLevel === $riskLevel ? $p->partyId : null,
                    $profiles
                )
            )
        );
    }

    public function getPartiesNeedingReview(): array
    {
        $profiles = $this->profileQuery->findNeedingReview();
        return array_map(fn($p) => $p->partyId, $profiles);
    }

    public function recalculateAllRisks(): array
    {
        $profiles = $this->profileQuery->search([]);
        
        $stats = [
            'processed' => 0,
            'updated' => 0,
            'errors' => 0,
        ];

        foreach ($profiles as $profile) {
            try {
                $oldLevel = $profile->riskAssessment?->riskLevel;
                $newAssessment = $this->assess($profile->partyId);
                
                $stats['processed']++;
                
                if ($oldLevel !== $newAssessment->riskLevel) {
                    $stats['updated']++;
                }
            } catch (\Throwable $e) {
                $this->logger->error('Failed to recalculate risk', [
                    'party_id' => $profile->partyId,
                    'error' => $e->getMessage(),
                ]);
                $stats['errors']++;
            }
        }

        return $stats;
    }

    /**
     * Calculate validity period based on risk level.
     */
    private function calculateValidityPeriod(RiskLevel $riskLevel): \DateTimeImmutable
    {
        $months = match ($riskLevel) {
            RiskLevel::LOW => 24,
            RiskLevel::MEDIUM => 12,
            RiskLevel::HIGH => 6,
            RiskLevel::VERY_HIGH => 3,
            RiskLevel::PROHIBITED => 1,
        };

        return (new \DateTimeImmutable())->modify("+{$months} months");
    }

    /**
     * Assess transaction volume risk.
     */
    private function assessTransactionVolume(float $volume): int
    {
        return match (true) {
            $volume > 10_000_000 => 30,
            $volume > 1_000_000 => 20,
            $volume > 500_000 => 10,
            $volume > 100_000 => 5,
            default => 0,
        };
    }
}
