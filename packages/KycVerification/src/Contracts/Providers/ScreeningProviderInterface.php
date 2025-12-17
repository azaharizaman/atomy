<?php

declare(strict_types=1);

namespace Nexus\KycVerification\Contracts\Providers;

use Nexus\KycVerification\ValueObjects\RiskFactor;

/**
 * Interface for screening services (sanctions, PEP, adverse media).
 * 
 * This interface abstracts external screening services.
 * The orchestrator layer must implement this using compliance/screening providers.
 */
interface ScreeningProviderInterface
{
    /**
     * Screen party against sanctions lists
     * 
     * @param array<string, mixed> $partyData Party information for screening
     * @return array{matched: bool, matches: array<array{list: string, score: float, details: array<string, mixed>}>}
     */
    public function screenSanctions(
        string $partyId,
        array $partyData
    ): array;

    /**
     * Screen party for PEP (Politically Exposed Person) status
     * 
     * @param array<string, mixed> $partyData Party information for screening
     * @return array{is_pep: bool, tier: ?int, position: ?string, details: ?array<string, mixed>}
     */
    public function screenPep(
        string $partyId,
        array $partyData
    ): array;

    /**
     * Screen for adverse media
     * 
     * @param array<string, mixed> $partyData Party information for screening
     * @return array{found: bool, articles: array<array{headline: string, source: string, date: string, severity: string}>}
     */
    public function screenAdverseMedia(
        string $partyId,
        array $partyData
    ): array;

    /**
     * Get country risk level
     * 
     * @return array{risk_level: string, risk_score: int, factors: array<string>}
     */
    public function getCountryRisk(string $countryCode): array;

    /**
     * Get industry risk level
     * 
     * @return array{risk_level: string, risk_score: int, factors: array<string>}
     */
    public function getIndustryRisk(string $industryCode): array;

    /**
     * Run comprehensive screening and return risk factors
     * 
     * @param array<string, mixed> $partyData Party information for screening
     * @return array<RiskFactor>
     */
    public function runComprehensiveScreening(
        string $partyId,
        array $partyData
    ): array;

    /**
     * Check if screening results are stale (need refresh)
     */
    public function isScreeningStale(
        string $partyId,
        \DateTimeImmutable $lastScreenedAt
    ): bool;
}
