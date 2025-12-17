<?php

declare(strict_types=1);

namespace Nexus\KycVerification\Contracts;

use Nexus\KycVerification\Enums\RiskLevel;
use Nexus\KycVerification\ValueObjects\RiskAssessment;
use Nexus\KycVerification\ValueObjects\RiskFactor;

/**
 * Interface for risk assessment operations.
 */
interface RiskAssessorInterface
{
    /**
     * Perform risk assessment for a party
     * 
     * @param array<string, mixed> $partyData Additional party data
     */
    public function assess(string $partyId, array $partyData = []): RiskAssessment;

    /**
     * Get current risk assessment for a party
     */
    public function getCurrentAssessment(string $partyId): ?RiskAssessment;

    /**
     * Update risk assessment with new factors
     * 
     * @param array<RiskFactor> $newFactors
     */
    public function updateAssessment(
        string $partyId,
        array $newFactors,
        ?string $assessedBy = null
    ): RiskAssessment;

    /**
     * Override risk level (with senior approval)
     */
    public function overrideRiskLevel(
        string $partyId,
        RiskLevel $newRiskLevel,
        string $reason,
        string $approvedBy
    ): RiskAssessment;

    /**
     * Get risk level for a party
     */
    public function getRiskLevel(string $partyId): ?RiskLevel;

    /**
     * Calculate risk score from factors
     * 
     * @param array<RiskFactor> $factors
     */
    public function calculateRiskScore(array $factors): int;

    /**
     * Get risk factors for a party
     * 
     * @return array<RiskFactor>
     */
    public function getRiskFactors(string $partyId): array;

    /**
     * Check if party is high risk
     */
    public function isHighRisk(string $partyId): bool;

    /**
     * Check if party is blocked (prohibited risk level)
     */
    public function isBlocked(string $partyId): bool;

    /**
     * Get parties by risk level
     * 
     * @return array<string> Party IDs
     */
    public function getPartiesByRiskLevel(RiskLevel $riskLevel): array;

    /**
     * Get parties needing risk review (assessment expired)
     * 
     * @return array<string> Party IDs
     */
    public function getPartiesNeedingReview(): array;

    /**
     * Recalculate risk for all parties (batch operation)
     * 
     * @return array{processed: int, updated: int, errors: int}
     */
    public function recalculateAllRisks(): array;
}
