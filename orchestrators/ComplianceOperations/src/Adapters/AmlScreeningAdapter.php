<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\Adapters;

use Nexus\AmlCompliance\Contracts\AmlRiskAssessorInterface;
use Nexus\AmlCompliance\Contracts\TransactionMonitorInterface;
use Nexus\AmlCompliance\Enums\RiskLevel;
use Nexus\AmlCompliance\ValueObjects\AmlRiskScore;
use Nexus\ComplianceOperations\Contracts\AmlScreeningAdapterInterface;

/**
 * Adapter for AML screening package interface.
 *
 * Adapts the AmlCompliance package to the ComplianceOperations orchestrator's
 * interface requirements. This adapter implements the orchestrator's own contract
 * and delegates to the atomic package's interfaces.
 *
 * Following the Interface Segregation principle from ARCHITECTURE.md:
 * Orchestrators define their own interfaces and adapters implement them using
 * atomic package interfaces.
 */
final readonly class AmlScreeningAdapter implements AmlScreeningAdapterInterface
{
    public function __construct(
        private AmlRiskAssessorInterface $riskAssessor,
        private TransactionMonitorInterface $transactionMonitor,
    ) {}

    /**
     * Perform AML risk assessment for a party.
     *
     * @param string $partyId Party identifier
     * @param array<string, mixed> $partyData Party data for assessment
     * @return array<string, mixed> Risk assessment result
     */
    public function assessRisk(string $partyId, array $partyData): array
    {
        $party = $this->buildPartyAdapter($partyId, $partyData);
        $riskScore = $this->riskAssessor->assess($party);

        return $this->buildRiskScoreData($riskScore);
    }

    /**
     * Get current risk assessment for a party.
     *
     * @param string $partyId Party identifier
     * @return array<string, mixed>|null Risk assessment data or null
     *
     * @throws \RuntimeException This operation is not yet supported by the underlying AML package
     */
    public function getCurrentAssessment(string $partyId): ?array
    {
        throw new \RuntimeException(
            sprintf(
                'Operation getCurrentAssessment() is not yet supported. ' .
                'The underlying AML package (AmlCompliance) does not expose a method to retrieve stored assessments. ' .
                'Party ID: %s',
                $partyId
            )
        );
    }

    /**
     * Get risk level for a party.
     *
     * @param string $partyId Party identifier
     * @return string|null Risk level value or null if not assessed
     *
     * @throws \RuntimeException This operation is not yet supported by the underlying AML package
     */
    public function getRiskLevel(string $partyId): ?string
    {
        throw new \RuntimeException(
            sprintf(
                'Operation getRiskLevel() is not yet supported. ' .
                'The underlying AML package (AmlCompliance) does not expose a method to retrieve stored risk levels. ' .
                'Party ID: %s',
                $partyId
            )
        );
    }

    /**
     * Check if party requires enhanced due diligence.
     *
     * @param string $partyId Party identifier
     *
     * @throws \RuntimeException This operation is not yet supported by the underlying AML package
     */
    public function requiresEdd(string $partyId): bool
    {
        throw new \RuntimeException(
            sprintf(
                'Operation requiresEdd() is not yet supported. ' .
                'The underlying AML package (AmlCompliance) does not expose a method to check EDD requirements for stored assessments. ' .
                'Party ID: %s',
                $partyId
            )
        );
    }

    /**
     * Monitor transactions for a party.
     *
     * @param string $partyId Party identifier
     * @param array<int, array<string, mixed>> $transactions Transaction data
     * @param \DateTimeImmutable $periodStart Period start
     * @param \DateTimeImmutable $periodEnd Period end
     * @return array<string, mixed> Monitoring result
     */
    public function monitorTransactions(
        string $partyId,
        array $transactions,
        \DateTimeImmutable $periodStart,
        \DateTimeImmutable $periodEnd
    ): array {
        $result = $this->transactionMonitor->monitor(
            $partyId,
            $transactions,
            $periodStart,
            $periodEnd
        );

        return [
            'partyId' => $result->partyId,
            'riskScore' => $result->riskScore,
            'alertCount' => count($result->alerts),
            'requiresReview' => $result->requiresReview(),
            'sarRecommended' => $result->shouldConsiderSar(),
            'periodStart' => $result->periodStart?->format('Y-m-d'),
            'periodEnd' => $result->periodEnd?->format('Y-m-d'),
        ];
    }

    /**
     * Check if party is high risk.
     *
     * @param string $partyId Party identifier
     *
     * @throws \RuntimeException This operation is not yet supported by the underlying AML package
     */
    public function isHighRisk(string $partyId): bool
    {
        throw new \RuntimeException(
            sprintf(
                'Operation isHighRisk() is not yet supported. ' .
                'The underlying AML package (AmlCompliance) does not expose a method to check risk status for stored assessments. ' .
                'Party ID: %s',
                $partyId
            )
        );
    }

    /**
     * Get recommendations for a party.
     *
     * @param string $partyId Party identifier
     * @return array<string> Recommendations
     *
     * @throws \RuntimeException This operation is not yet supported by the underlying AML package
     */
    public function getRecommendations(string $partyId): array
    {
        throw new \RuntimeException(
            sprintf(
                'Operation getRecommendations() is not yet supported. ' .
                'The underlying AML package (AmlCompliance) does not expose a method to retrieve recommendations for stored assessments. ' .
                'Party ID: %s',
                $partyId
            )
        );
    }

    /**
     * Calculate next review date based on risk level.
     *
     * @param string $riskLevel Risk level
     * @return \DateTimeImmutable Next review date
     */
    public function calculateNextReviewDate(string $riskLevel): \DateTimeImmutable
    {
        $level = RiskLevel::tryFrom($riskLevel) ?? RiskLevel::MEDIUM;
        return $this->riskAssessor->calculateNextReviewDate($level);
    }

    /**
     * Build party adapter for AML interfaces.
     */
    private function buildPartyAdapter(string $partyId, array $partyData): object
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
     * Build risk score data array.
     *
     * @return array<string, mixed>
     */
    private function buildRiskScoreData(AmlRiskScore $riskScore): array
    {
        return [
            'partyId' => $riskScore->partyId,
            'overallScore' => $riskScore->overallScore,
            'riskLevel' => $riskScore->riskLevel->value,
            'jurisdictionScore' => $riskScore->factors->jurisdictionScore,
            'businessTypeScore' => $riskScore->factors->businessTypeScore,
            'sanctionsScore' => $riskScore->factors->sanctionsScore,
            'transactionScore' => $riskScore->factors->transactionScore,
            'assessedAt' => $riskScore->assessedAt->format('Y-m-d H:i:s'),
            'nextReviewDate' => $riskScore->nextReviewDate?->format('Y-m-d'),
            'recommendations' => $riskScore->recommendations,
            'requiresEdd' => $riskScore->requiresEdd(),
        ];
    }
}
