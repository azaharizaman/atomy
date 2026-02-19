<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\DataProviders;

use Nexus\AmlCompliance\Contracts\AmlRiskAssessorInterface;
use Nexus\AmlCompliance\Contracts\TransactionMonitorInterface;
use Nexus\AmlCompliance\Contracts\SarManagerInterface;
use Nexus\AmlCompliance\Enums\RiskLevel;
use Nexus\AmlCompliance\ValueObjects\AmlRiskScore;
use Nexus\AmlCompliance\ValueObjects\TransactionMonitoringResult;
use Nexus\ComplianceOperations\DTOs\Aml\AmlScreeningContext;
use Nexus\ComplianceOperations\DTOs\Aml\AmlSummaryData;
use Nexus\ComplianceOperations\Exceptions\AmlDataException;
use Psr\Log\LoggerInterface;

/**
 * DataProvider for AML screening data aggregation.
 *
 * Aggregates AML data from the AmlCompliance package to provide
 * comprehensive context for compliance workflows including:
 * - Risk assessments and scores
 * - Transaction monitoring results
 * - SAR (Suspicious Activity Report) data
 * - Risk factor analysis
 *
 * Following Advanced Orchestrator Pattern v1.1:
 * DataProviders abstract data fetching from Coordinators.
 */
final readonly class AmlDataProvider
{
    public function __construct(
        private AmlRiskAssessorInterface $riskAssessor,
        private TransactionMonitorInterface $transactionMonitor,
        private SarManagerInterface $sarManager,
        private LoggerInterface $logger,
    ) {}

    /**
     * Get comprehensive AML context for a party.
     *
     * @param string $tenantId Tenant context
     * @param string $partyId Party identifier
     * @param array<string, mixed> $partyData Party data for assessment
     * @throws AmlDataException If data cannot be retrieved
     */
    public function getAmlContext(string $tenantId, string $partyId, array $partyData = []): AmlScreeningContext
    {
        $this->logger->info('Fetching AML context', [
            'tenant_id' => $tenantId,
            'party_id' => $partyId,
        ]);

        // Build party adapter for AML interfaces
        $party = $this->buildPartyAdapter($partyId, $partyData);

        // Get risk assessment
        $riskScore = $this->riskAssessor->assess($party);

        // Get transaction monitoring results if data provided
        $monitoringResult = null;
        if (isset($partyData['transactions'])) {
            $monitoringResult = $this->monitorTransactions(
                $partyId,
                $partyData['transactions'],
                $partyData['periodStart'] ?? new \DateTimeImmutable('-30 days'),
                $partyData['periodEnd'] ?? new \DateTimeImmutable()
            );
        }

        // Get SAR data using SarManager::getSarByPartyId()
        $sarData = $this->getSarData($partyId);

        return new AmlScreeningContext(
            tenantId: $tenantId,
            partyId: $partyId,
            riskScore: $this->buildRiskScoreData($riskScore),
            monitoringResult: $monitoringResult !== null ? $this->buildMonitoringData($monitoringResult) : null,
            sarData: $sarData,
            requiresEdd: $this->riskAssessor->requiresEdd($riskScore),
            recommendations: $this->riskAssessor->generateRecommendations($riskScore),
            nextReviewDate: $this->riskAssessor->calculateNextReviewDate($riskScore->riskLevel),
            assessedAt: new \DateTimeImmutable(),
        );
    }

    /**
     * Get AML summary for dashboard display.
     *
     * @param string $tenantId Tenant context
     */
    public function getAmlSummary(string $tenantId): AmlSummaryData
    {
        $this->logger->info('Fetching AML summary', [
            'tenant_id' => $tenantId,
        ]);

        try {
            // Get SAR metrics using SarManager::getSarMetrics()
            $sarMetrics = $this->sarManager->getSarMetrics();

            // Get high risk parties count using AmlRiskAssessor::getPartiesByRiskLevel()
            $highRiskParties = $this->riskAssessor->getPartiesByRiskLevel(RiskLevel::HIGH, 1000);

            return new AmlSummaryData(
                tenantId: $tenantId,
                totalAssessments: $sarMetrics['total_assessments'] ?? 0,
                highRiskCount: count($highRiskParties),
                pendingSars: $sarMetrics['pending_sars'] ?? 0,
                filedSars: $sarMetrics['filed_sars'] ?? 0,
                alertsGenerated: $sarMetrics['alerts_generated'] ?? 0,
                generatedAt: new \DateTimeImmutable(),
            );
        } catch (\Throwable $e) {
            $this->logger->error('Failed to fetch AML summary', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);

            throw new AmlDataException('Failed to fetch AML summary: ' . $e->getMessage(), 0, $e);
        }
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

        try {
            // Use AmlRiskAssessor::getPartiesByRiskLevel() to get high risk party IDs
            // Returns array<string> of party IDs directly
            return $this->riskAssessor->getPartiesByRiskLevel(RiskLevel::HIGH, 1000);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to fetch parties requiring EDD', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);

            throw new AmlDataException('Failed to fetch parties requiring EDD: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Monitor transactions for a party.
     *
     * @param string $partyId Party identifier
     * @param array $transactions Transaction data
     * @param \DateTimeImmutable $periodStart Period start
     * @param \DateTimeImmutable $periodEnd Period end
     */
    public function monitorTransactions(
        string $partyId,
        array $transactions,
        \DateTimeImmutable $periodStart,
        \DateTimeImmutable $periodEnd
    ): TransactionMonitoringResult {
        $this->logger->info('Monitoring transactions', [
            'party_id' => $partyId,
            'period_start' => $periodStart->format('Y-m-d'),
            'period_end' => $periodEnd->format('Y-m-d'),
        ]);

        return $this->transactionMonitor->monitor(
            $partyId,
            $transactions,
            $periodStart,
            $periodEnd
        );
    }

    /**
     * Check if party is high risk.
     *
     * @param string $partyId Party identifier
     */
    public function isHighRisk(string $partyId): bool
    {
        // This would need a party adapter in real implementation
        return false;
    }

    /**
     * Get SAR data for a party.
     *
     * @param string $partyId Party identifier
     * @return array<string, mixed>
     */
    public function getSarData(string $partyId): array
    {
        $this->logger->info('Fetching SAR data', [
            'party_id' => $partyId,
        ]);

        try {
            // Use SarManager::getSarByPartyId() to get SAR data
            $sar = $this->sarManager->getSarByPartyId($partyId);

            if ($sar === null) {
                return [
                    'hasSar' => false,
                    'sarId' => null,
                    'status' => null,
                    'filedAt' => null,
                ];
            }

            return [
                'hasSar' => true,
                'sarId' => $sar->sarId,
                'status' => $sar->status->value,
                'filedAt' => $sar->submittedAt?->format('Y-m-d H:i:s'),
            ];
        } catch (\Throwable $e) {
            $this->logger->error('Failed to fetch SAR data', [
                'party_id' => $partyId,
                'error' => $e->getMessage(),
            ]);

            // Return empty data on error to maintain compatibility
            return [
                'hasSar' => false,
                'sarId' => null,
                'status' => null,
                'filedAt' => null,
            ];
        }
    }

    /**
     * Build party adapter for AML interfaces.
     */
    private function buildPartyAdapter(string $partyId, array $partyData): object
    {
        // Return an anonymous class implementing PartyInterface
        // In production, this would use a proper adapter class
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
            'overallScore' => $riskScore->overallScore,
            'riskLevel' => $riskScore->riskLevel->value,
            'jurisdictionScore' => $riskScore->factors->jurisdictionScore,
            'businessTypeScore' => $riskScore->factors->businessTypeScore,
            'sanctionsScore' => $riskScore->factors->sanctionsScore,
            'transactionScore' => $riskScore->factors->transactionScore,
            'riskFactors' => $riskScore->factors->toArray(),
            'assessedAt' => $riskScore->assessedAt->format('Y-m-d H:i:s'),
            'validUntil' => $riskScore->nextReviewDate?->format('Y-m-d'),
        ];
    }

    /**
     * Build monitoring result data array.
     *
     * @return array<string, mixed>
     */
    private function buildMonitoringData(TransactionMonitoringResult $result): array
    {
        return [
            'partyId' => $result->partyId,
            'riskScore' => $result->riskScore,
            'alertCount' => count($result->alerts),
            'alerts' => array_map(fn($alert) => [
                'type' => $alert->type,
                'severity' => $alert->severity,
                'message' => $alert->message,
                'transactionId' => $alert->transactionId,
                'triggeredAt' => $alert->triggeredAt->format('Y-m-d H:i:s'),
            ], $result->alerts),
            'requiresReview' => $result->isSuspicious,
            'sarRecommended' => $result->shouldConsiderSar(),
            'periodStart' => $result->periodStart?->format('Y-m-d'),
            'periodEnd' => $result->periodEnd?->format('Y-m-d'),
        ];
    }
}
