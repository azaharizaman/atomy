<?php

declare(strict_types=1);

namespace Nexus\AmlCompliance\Contracts;

use Nexus\AmlCompliance\Enums\RiskLevel;
use Nexus\AmlCompliance\ValueObjects\AmlRiskScore;

/**
 * AML Risk Assessor interface
 * 
 * Defines contract for comprehensive AML risk assessment services.
 * Implementations calculate risk scores based on multiple factors:
 * - Jurisdiction risk (30% weight)
 * - Business type risk (20% weight)
 * - Sanctions risk (25% weight)
 * - Transaction risk (25% weight)
 */
interface AmlRiskAssessorInterface
{
    /**
     * Perform comprehensive risk assessment for a party
     * 
     * @param PartyInterface $party Party to assess
     * @param SanctionsResultInterface|null $sanctionsResult Pre-fetched sanctions result
     * @param array<string, mixed> $transactionData Optional transaction history data
     * @return AmlRiskScore Complete risk assessment result
     * 
     * @throws \Nexus\AmlCompliance\Exceptions\RiskAssessmentFailedException
     */
    public function assess(
        PartyInterface $party,
        ?SanctionsResultInterface $sanctionsResult = null,
        array $transactionData = []
    ): AmlRiskScore;

    /**
     * Calculate jurisdiction risk score (0-100)
     * 
     * Considers:
     * - FATF grey list countries
     * - FATF black list countries
     * - EU high-risk third countries
     * - Tax haven jurisdictions
     * 
     * @param PartyInterface $party
     * @return int Risk score 0-100
     */
    public function assessJurisdictionRisk(PartyInterface $party): int;

    /**
     * Calculate business type risk score (0-100)
     * 
     * Considers:
     * - High-risk industries (FATF designated)
     * - Cash-intensive businesses
     * - Money service businesses
     * - Cryptocurrency businesses
     * 
     * @param PartyInterface $party
     * @return int Risk score 0-100
     */
    public function assessBusinessTypeRisk(PartyInterface $party): int;

    /**
     * Calculate sanctions-based risk score (0-100)
     * 
     * @param SanctionsResultInterface $sanctionsResult
     * @return int Risk score 0-100
     */
    public function assessSanctionsRisk(SanctionsResultInterface $sanctionsResult): int;

    /**
     * Calculate transaction-based risk score (0-100)
     * 
     * Considers:
     * - Transaction volume and frequency
     * - Geographic patterns
     * - Unusual patterns
     * - Structuring indicators
     * 
     * @param array<string, mixed> $transactionData
     * @return int Risk score 0-100
     */
    public function assessTransactionRisk(array $transactionData): int;

    /**
     * Get risk level for a given score
     */
    public function getRiskLevel(int $score): RiskLevel;

    /**
     * Check if party requires Enhanced Due Diligence (EDD)
     */
    public function requiresEdd(AmlRiskScore $riskScore): bool;

    /**
     * Generate recommendations based on risk assessment
     * 
     * @param AmlRiskScore $riskScore
     * @return array<string> List of recommended actions
     */
    public function generateRecommendations(AmlRiskScore $riskScore): array;

    /**
     * Calculate next review date based on risk level
     */
    public function calculateNextReviewDate(RiskLevel $riskLevel): \DateTimeImmutable;
}
