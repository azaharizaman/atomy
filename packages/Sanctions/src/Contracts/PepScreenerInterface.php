<?php

declare(strict_types=1);

namespace Nexus\Sanctions\Contracts;

use Nexus\Sanctions\ValueObjects\PepProfile;

/**
 * Interface for Politically Exposed Person (PEP) screening operations.
 * 
 * Provides contract for detecting PEPs and assessing risk levels according
 * to FATF (Financial Action Task Force) guidelines.
 * 
 * PEP Categories (FATF standards):
 * - Foreign PEPs: Senior officials from foreign countries
 * - Domestic PEPs: Senior officials from own country
 * - International Organization PEPs: Officials from UN, IMF, etc.
 * - Family Members: Spouse, children, parents
 * - Close Associates: Business partners, advisors
 * 
 * Implementing classes should provide:
 * - PEP detection with risk level classification
 * - Family member and close associate identification
 * - Former PEP identification (>12 months rule)
 * - Enhanced Due Diligence (EDD) requirement determination
 * - Risk score calculation with adjustments
 * 
 * @package Nexus\Sanctions\Contracts
 */
interface PepScreenerInterface
{
    /**
     * Screen a party for PEP status.
     *
     * Checks if party is a PEP, family member, or close associate.
     * Returns all matching PEP profiles with risk classifications.
     *
     * @param PartyInterface $party Party to screen
     * @param array<string, mixed> $options Screening options:
     *        - 'include_family' => bool (default: true)
     *        - 'include_associates' => bool (default: true)
     *        - 'include_former' => bool (default: true)
     *        - 'min_risk_level' => string (default: 'low')
     * @return array<PepProfile> Array of PEP profiles
     * @throws \Nexus\Sanctions\Exceptions\InvalidPartyException If party data invalid
     * @throws \Nexus\Sanctions\Exceptions\ScreeningFailedException If screening fails
     */
    public function screenForPep(
        PartyInterface $party,
        array $options = []
    ): array;

    /**
     * Check if party is related to known PEPs.
     *
     * Identifies family members and close associates of PEPs.
     * Useful for understanding indirect PEP exposure.
     *
     * @param PartyInterface $party Party to check
     * @return array<PepProfile> Related PEP profiles
     * @throws \Nexus\Sanctions\Exceptions\ScreeningFailedException If check fails
     */
    public function checkRelatedPersons(PartyInterface $party): array;

    /**
     * Assess PEP risk level for a party.
     *
     * Determines overall PEP risk considering:
     * - Direct PEP status vs family/associate
     * - Position level and influence
     * - Active vs former status
     * - Jurisdiction and corruption perception index
     *
     * @param PartyInterface $party Party to assess
     * @param array<PepProfile> $pepProfiles PEP profiles for party
     * @return \Nexus\Sanctions\Enums\PepLevel
     */
    public function assessRiskLevel(
        PartyInterface $party,
        array $pepProfiles
    ): \Nexus\Sanctions\Enums\PepLevel;

    /**
     * Check if Enhanced Due Diligence (EDD) is required.
     *
     * EDD is required for high and medium risk PEPs per FATF standards.
     * Considers party's PEP profiles and transaction patterns.
     *
     * @param PartyInterface $party Party to check
     * @param array<PepProfile> $pepProfiles PEP profiles for party
     * @return bool True if EDD required
     */
    public function requiresEdd(
        PartyInterface $party,
        array $pepProfiles
    ): bool;

    /**
     * Get recommended monitoring frequency for PEP.
     *
     * Based on PEP risk level and regulatory requirements.
     * Higher risk PEPs require more frequent monitoring.
     *
     * @param \Nexus\Sanctions\Enums\PepLevel $level PEP risk level
     * @return \Nexus\Sanctions\Enums\ScreeningFrequency
     */
    public function getMonitoringFrequency(
        \Nexus\Sanctions\Enums\PepLevel $level
    ): \Nexus\Sanctions\Enums\ScreeningFrequency;

    /**
     * Screen multiple parties for PEP status in batch.
     *
     * More efficient than individual screening for large volumes.
     * Processes parties concurrently when possible.
     *
     * @param array<PartyInterface> $parties Parties to screen
     * @param array<string, mixed> $options Screening options (same as screenForPep())
     * @return array<string, array<PepProfile>> PEP profiles keyed by party ID
     * @throws \Nexus\Sanctions\Exceptions\ScreeningFailedException If batch screening fails
     */
    public function screenMultiple(
        array $parties,
        array $options = []
    ): array;
}
