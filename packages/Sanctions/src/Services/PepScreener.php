<?php

declare(strict_types=1);

namespace Nexus\Sanctions\Services;

use Nexus\Sanctions\Contracts\PartyInterface;
use Nexus\Sanctions\Contracts\PepScreenerInterface;
use Nexus\Sanctions\Contracts\SanctionsRepositoryInterface;
use Nexus\Sanctions\Enums\PepLevel;
use Nexus\Sanctions\Enums\ScreeningFrequency;
use Nexus\Sanctions\Exceptions\InvalidPartyException;
use Nexus\Sanctions\Exceptions\ScreeningFailedException;
use Nexus\Sanctions\ValueObjects\PepProfile;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Production-ready PEP screening service.
 * 
 * Implements FATF (Financial Action Task Force) guidelines for
 * Politically Exposed Person (PEP) detection and risk assessment.
 * 
 * PEP Categories:
 * - Foreign PEPs: Senior officials from foreign countries
 * - Domestic PEPs: Senior officials from own country
 * - International Organization PEPs: UN, IMF, World Bank officials
 * - Family Members: Immediate family (spouse, children, parents)
 * - Close Associates: Business partners with known close ties
 * 
 * Risk Assessment Factors:
 * - Position level and influence (High: heads of state, Medium: mid-level, Low: former)
 * - Jurisdiction corruption perception index
 * - Active vs former status (>12 months rule per FATF)
 * - Transaction patterns and volumes
 * 
 * @package Nexus\Sanctions\Services
 */
final readonly class PepScreener implements PepScreenerInterface
{
    private const DEFAULT_THRESHOLD = 0.85;
    private const FORMER_PEP_MONTHS = 12;

    public function __construct(
        private SanctionsRepositoryInterface $repository,
        private LoggerInterface $logger = new NullLogger()
    ) {}

    /**
     * {@inheritDoc}
     */
    public function screenForPep(
        PartyInterface $party,
        array $options = []
    ): array {
        try {
            // Validate party
            $this->validateParty($party);

            // Extract options
            $includeFamily = $options['include_family'] ?? true;
            $includeAssociates = $options['include_associates'] ?? true;
            $includeFormer = $options['include_former'] ?? true;
            $minRiskLevel = $options['min_risk_level'] ?? 'low';
            $threshold = $options['similarity_threshold'] ?? self::DEFAULT_THRESHOLD;

            $this->logger->info('Starting PEP screening', [
                'party_id' => $party->getId(),
                'party_name' => $party->getName(),
                'include_family' => $includeFamily,
                'include_associates' => $includeAssociates,
            ]);

            // Search PEP database
            $pepData = $this->repository->findPepByName(
                $party->getName(),
                $threshold
            );

            $pepProfiles = [];
            foreach ($pepData as $data) {
                $profile = $this->createPepProfile($data);

                // Filter by risk level
                if (!$this->meetsMinRiskLevel($profile, $minRiskLevel)) {
                    continue;
                }

                // Filter by former status
                if (!$includeFormer && $profile->isFormer()) {
                    continue;
                }

                $pepProfiles[] = $profile;
            }

            // Check for related persons if enabled
            if (($includeFamily || $includeAssociates) && count($pepProfiles) > 0) {
                foreach ($pepProfiles as $profile) {
                    $relatedData = $this->repository->getRelatedPersons($profile->pepId);
                    
                    foreach ($relatedData as $related) {
                        $relatedProfile = $this->createPepProfile($related);
                        
                        // Add if meets criteria
                        if ($this->meetsMinRiskLevel($relatedProfile, $minRiskLevel)) {
                            $pepProfiles[] = $relatedProfile;
                        }
                    }
                }
            }

            // Remove duplicates
            $pepProfiles = $this->removeDuplicateProfiles($pepProfiles);

            $this->logger->info('PEP screening completed', [
                'party_id' => $party->getId(),
                'pep_profiles_found' => count($pepProfiles),
            ]);

            return $pepProfiles;

        } catch (InvalidPartyException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->logger->error('PEP screening failed', [
                'party_id' => $party->getId(),
                'error' => $e->getMessage(),
            ]);
            throw ScreeningFailedException::screeningFailed(
                $party->getId(),
                $e->getMessage(),
                $e
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function checkRelatedPersons(PartyInterface $party): array
    {
        // First find if party is a PEP
        $pepProfiles = $this->screenForPep($party, ['include_family' => false, 'include_associates' => false]);

        if (count($pepProfiles) === 0) {
            return [];
        }

        // Get related persons for all matched PEP profiles
        $allRelated = [];
        foreach ($pepProfiles as $profile) {
            $relatedData = $this->repository->getRelatedPersons($profile->pepId);
            
            foreach ($relatedData as $related) {
                $allRelated[] = $this->createPepProfile($related);
            }
        }

        return $this->removeDuplicateProfiles($allRelated);
    }

    /**
     * {@inheritDoc}
     */
    public function assessRiskLevel(
        PartyInterface $party,
        array $pepProfiles
    ): PepLevel {
        if (count($pepProfiles) === 0) {
            return PepLevel::NONE;
        }

        // Find highest risk level
        $highestLevel = PepLevel::NONE;
        $levels = [PepLevel::NONE, PepLevel::LOW, PepLevel::MEDIUM, PepLevel::HIGH];

        foreach ($pepProfiles as $profile) {
            $currentIndex = array_search($highestLevel, $levels);
            $profileIndex = array_search($profile->level, $levels);

            if ($profileIndex > $currentIndex) {
                $highestLevel = $profile->level;
            }
        }

        // Apply modifiers based on party characteristics
        $highestLevel = $this->applyRiskModifiers($party, $highestLevel, $pepProfiles);

        return $highestLevel;
    }

    /**
     * {@inheritDoc}
     */
    public function requiresEdd(
        PartyInterface $party,
        array $pepProfiles
    ): bool {
        if (count($pepProfiles) === 0) {
            return false;
        }

        $riskLevel = $this->assessRiskLevel($party, $pepProfiles);
        return $riskLevel->requiresEdd();
    }

    /**
     * {@inheritDoc}
     */
    public function getMonitoringFrequency(PepLevel $level): ScreeningFrequency
    {
        return ScreeningFrequency::fromPepLevel($level);
    }

    /**
     * {@inheritDoc}
     */
    public function screenMultiple(
        array $parties,
        array $options = []
    ): array {
        $results = [];

        foreach ($parties as $party) {
            try {
                $results[$party->getId()] = $this->screenForPep($party, $options);
            } catch (\Throwable $e) {
                $this->logger->error('Batch PEP screening failed for party', [
                    'party_id' => $party->getId(),
                    'error' => $e->getMessage(),
                ]);
                // Continue with other parties
            }
        }

        return $results;
    }

    /**
     * Create PEP profile from repository data.
     *
     * @param array $data
     * @return PepProfile
     */
    private function createPepProfile(array $data): PepProfile
    {
        // Parse dates
        $startDate = $this->parseDate($data['start_date'] ?? null);
        $endDate = $this->parseDate($data['end_date'] ?? null);
        $identifiedAt = $this->parseDate($data['identified_at'] ?? null) ?? new \DateTimeImmutable();

        // Determine PEP level
        $level = $this->determinePepLevel($data, $startDate, $endDate);

        return new PepProfile(
            pepId: $data['id'] ?? $data['pep_id'],
            name: $data['name'],
            level: $level,
            position: $data['position'] ?? 'Unknown',
            country: $data['country'] ?? 'Unknown',
            organization: $data['organization'] ?? null,
            startDate: $startDate,
            endDate: $endDate,
            relatedPersons: $data['related_persons'] ?? [],
            additionalInfo: $data,
            identifiedAt: $identifiedAt
        );
    }

    /**
     * Determine PEP level from data.
     *
     * @param array $data
     * @param \DateTimeImmutable|null $startDate
     * @param \DateTimeImmutable|null $endDate
     * @return PepLevel
     */
    private function determinePepLevel(
        array $data,
        ?\DateTimeImmutable $startDate,
        ?\DateTimeImmutable $endDate
    ): PepLevel {
        // Explicit level if provided
        if (isset($data['level'])) {
            return PepLevel::from($data['level']);
        }

        // Check if former PEP (>12 months)
        if ($endDate !== null) {
            $monthsSinceEnd = $this->getMonthsSince($endDate);
            if ($monthsSinceEnd > self::FORMER_PEP_MONTHS) {
                return PepLevel::LOW;
            }
        }

        // Classify by position keywords
        $position = strtolower($data['position'] ?? '');
        
        // High-level positions
        $highKeywords = ['president', 'prime minister', 'minister', 'head of state', 'governor', 'general', 'admiral', 'chief justice'];
        foreach ($highKeywords as $keyword) {
            if (str_contains($position, $keyword)) {
                return PepLevel::HIGH;
            }
        }

        // Medium-level positions
        $mediumKeywords = ['director', 'deputy', 'assistant', 'commissioner', 'colonel', 'ambassador'];
        foreach ($mediumKeywords as $keyword) {
            if (str_contains($position, $keyword)) {
                return PepLevel::MEDIUM;
            }
        }

        // Default to low
        return PepLevel::LOW;
    }

    /**
     * Check if profile meets minimum risk level.
     *
     * @param PepProfile $profile
     * @param string $minRiskLevel
     * @return bool
     */
    private function meetsMinRiskLevel(PepProfile $profile, string $minRiskLevel): bool
    {
        $levelOrder = ['none' => 0, 'low' => 1, 'medium' => 2, 'high' => 3];
        $profileLevel = $levelOrder[$profile->level->value] ?? 0;
        $minLevel = $levelOrder[strtolower($minRiskLevel)] ?? 0;

        return $profileLevel >= $minLevel;
    }

    /**
     * Apply risk modifiers based on party characteristics.
     *
     * @param PartyInterface $party
     * @param PepLevel $baseLevel
     * @param array<PepProfile> $pepProfiles
     * @return PepLevel
     */
    private function applyRiskModifiers(
        PartyInterface $party,
        PepLevel $baseLevel,
        array $pepProfiles
    ): PepLevel {
        // Check if multiple PEP connections
        if (count($pepProfiles) > 2) {
            // Elevate risk if multiple PEP connections
            if ($baseLevel === PepLevel::LOW) {
                return PepLevel::MEDIUM;
            }
            if ($baseLevel === PepLevel::MEDIUM) {
                return PepLevel::HIGH;
            }
        }

        return $baseLevel;
    }

    /**
     * Remove duplicate PEP profiles.
     *
     * @param array<PepProfile> $profiles
     * @return array<PepProfile>
     */
    private function removeDuplicateProfiles(array $profiles): array
    {
        $unique = [];
        $seen = [];

        foreach ($profiles as $profile) {
            if (!isset($seen[$profile->pepId])) {
                $unique[] = $profile;
                $seen[$profile->pepId] = true;
            }
        }

        return $unique;
    }

    /**
     * Parse date string.
     *
     * @param string|null $dateStr
     * @return \DateTimeImmutable|null
     */
    private function parseDate(?string $dateStr): ?\DateTimeImmutable
    {
        if ($dateStr === null || trim($dateStr) === '') {
            return null;
        }

        try {
            return new \DateTimeImmutable($dateStr);
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Get months since date.
     *
     * @param \DateTimeImmutable $date
     * @return int
     */
    private function getMonthsSince(\DateTimeImmutable $date): int
    {
        $now = new \DateTimeImmutable();
        $diff = $date->diff($now);
        return ($diff->y * 12) + $diff->m;
    }

    /**
     * Validate party for PEP screening.
     *
     * @param PartyInterface $party
     * @return void
     * @throws InvalidPartyException
     */
    private function validateParty(PartyInterface $party): void
    {
        if (empty($party->getId())) {
            throw InvalidPartyException::emptyPartyId();
        }

        if (empty(trim($party->getName()))) {
            throw InvalidPartyException::missingRequiredFields($party->getId(), ['name']);
        }
    }
}
