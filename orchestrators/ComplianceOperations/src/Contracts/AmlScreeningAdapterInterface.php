<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\Contracts;

/**
 * Interface for AML screening adapter.
 *
 * This interface defines the contract for the orchestrator's AML screening
 * needs. Adapters implement this interface using the AmlCompliance package.
 *
 * Following Interface Segregation from ARCHITECTURE.md:
 * Orchestrators define their own interfaces, not depending on atomic package interfaces.
 */
interface AmlScreeningAdapterInterface
{
    /**
     * Perform AML risk assessment for a party.
     *
     * @param string $partyId Party identifier
     * @param array<string, mixed> $partyData Party data for assessment
     * @return array<string, mixed> Risk assessment result
     */
    public function assessRisk(string $partyId, array $partyData): array;

    /**
     * Get current risk assessment for a party.
     *
     * @param string $partyId Party identifier
     * @return array<string, mixed>|null Risk assessment data or null
     */
    public function getCurrentAssessment(string $partyId): ?array;

    /**
     * Get risk level for a party.
     *
     * @param string $partyId Party identifier
     * @return string|null Risk level value or null if not assessed
     */
    public function getRiskLevel(string $partyId): ?string;

    /**
     * Check if party requires enhanced due diligence.
     *
     * @param string $partyId Party identifier
     */
    public function requiresEdd(string $partyId): bool;

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
    ): array;

    /**
     * Check if party is high risk.
     *
     * @param string $partyId Party identifier
     */
    public function isHighRisk(string $partyId): bool;

    /**
     * Get recommendations for a party.
     *
     * @param string $partyId Party identifier
     * @return array<string> Recommendations
     */
    public function getRecommendations(string $partyId): array;

    /**
     * Calculate next review date based on risk level.
     *
     * @param string $riskLevel Risk level
     * @return \DateTimeImmutable Next review date
     */
    public function calculateNextReviewDate(string $riskLevel): \DateTimeImmutable;
}
