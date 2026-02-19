<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\DataProviders;

use Nexus\Sanctions\Contracts\SanctionsScreenerInterface;
use Nexus\Sanctions\Contracts\PepScreenerInterface;
use Nexus\Sanctions\Contracts\PeriodicScreeningManagerInterface;
use Nexus\Sanctions\Enums\SanctionsList;
use Nexus\Sanctions\ValueObjects\ScreeningResult;
use Nexus\Sanctions\ValueObjects\PepProfile;
use Nexus\ComplianceOperations\DTOs\Sanctions\SanctionsScreeningContext;
use Nexus\ComplianceOperations\DTOs\Sanctions\SanctionsSummaryData;
use Nexus\ComplianceOperations\Exceptions\SanctionsDataException;
use Psr\Log\LoggerInterface;

/**
 * DataProvider for sanctions screening data aggregation.
 *
 * Aggregates sanctions data from the Sanctions package to provide
 * comprehensive context for compliance workflows including:
 * - Sanctions list screening results
 * - PEP (Politically Exposed Person) screening
 * - Match analysis and risk assessment
 * - Periodic screening schedules
 *
 * Following Advanced Orchestrator Pattern v1.1:
 * DataProviders abstract data fetching from Coordinators.
 */
final readonly class SanctionsDataProvider
{
    /**
     * Default sanctions lists to screen against.
     */
    private const DEFAULT_LISTS = [
        SanctionsList::OFAC,
        SanctionsList::UN,
        SanctionsList::EU,
        SanctionsList::UK_HMT,
    ];

    public function __construct(
        private SanctionsScreenerInterface $sanctionsScreener,
        private PepScreenerInterface $pepScreener,
        private PeriodicScreeningManagerInterface $screeningManager,
        private LoggerInterface $logger,
    ) {}

    /**
     * Get comprehensive sanctions context for a party.
     *
     * @param string $tenantId Tenant context
     * @param string $partyId Party identifier
     * @param array<string, mixed> $partyData Party data for screening
     * @param array<SanctionsList>|null $lists Specific lists to screen
     * @throws SanctionsDataException If data cannot be retrieved
     */
    public function getSanctionsContext(
        string $tenantId,
        string $partyId,
        array $partyData = [],
        ?array $lists = null
    ): SanctionsScreeningContext {
        $this->logger->info('Fetching sanctions context', [
            'tenant_id' => $tenantId,
            'party_id' => $partyId,
        ]);

        $party = $this->buildPartyAdapter($partyId, $partyData);
        $listsToScreen = $lists ?? self::DEFAULT_LISTS;

        // Perform sanctions screening
        $screeningResult = $this->sanctionsScreener->screen($party, $listsToScreen);

        // Perform PEP screening
        $pepProfiles = $this->pepScreener->screenForPep($party);

        // Get screening schedule - use getScheduleDetails instead of getScreeningSchedule
        $scheduleDetails = $this->screeningManager->getScheduleDetails($partyId);

        return new SanctionsScreeningContext(
            tenantId: $tenantId,
            partyId: $partyId,
            screeningResult: $this->buildScreeningResultData($screeningResult),
            pepProfiles: $this->buildPepProfilesData($pepProfiles),
            hasMatches: $screeningResult->hasMatches,
            hasPotentialMatches: $screeningResult->requiresReview,
            isPep: $screeningResult->hasPepMatches(),
            requiresEnhancedScreening: $this->requiresEnhancedScreening($screeningResult, $pepProfiles),
            screeningSchedule: $scheduleDetails !== null ? $this->buildScheduleData($scheduleDetails) : null,
            screenedAt: new \DateTimeImmutable(),
        );
    }

    /**
     * Get sanctions summary for dashboard display.
     *
     * @param string $tenantId Tenant context
     */
    public function getSanctionsSummary(string $tenantId): SanctionsSummaryData
    {
        $this->logger->info('Fetching sanctions summary', [
            'tenant_id' => $tenantId,
        ]);

        try {
            // Use PeriodicScreeningManager::getScreeningMetrics() for aggregate data
            $metrics = $this->screeningManager->getScreeningMetrics();

            // Get pending reviews using PeriodicScreeningManager::getPendingReviews()
            $pendingReviews = $this->screeningManager->getPendingReviews(1000);

            return new SanctionsSummaryData(
                tenantId: $tenantId,
                totalScreenings: $metrics['total_screenings'] ?? 0,
                matchesFound: $metrics['confirmed_matches'] ?? 0,
                potentialMatches: $metrics['potential_matches'] ?? 0,
                pepIdentified: $metrics['pep_identified'] ?? 0,
                pendingReviews: count($pendingReviews),
                generatedAt: new \DateTimeImmutable(),
            );
        } catch (\Throwable $e) {
            $this->logger->error('Failed to fetch sanctions summary', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);

            throw new SanctionsDataException('Failed to fetch sanctions summary: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Screen a party against sanctions lists.
     *
     * @param string $partyId Party identifier
     * @param array<string, mixed> $partyData Party data
     * @param array<SanctionsList>|null $lists Lists to screen against
     */
    public function screenParty(
        string $partyId,
        array $partyData,
        ?array $lists = null
    ): ScreeningResult {
        $this->logger->info('Screening party against sanctions lists', [
            'party_id' => $partyId,
        ]);

        $party = $this->buildPartyAdapter($partyId, $partyData);
        $listsToScreen = $lists ?? self::DEFAULT_LISTS;

        return $this->sanctionsScreener->screen($party, $listsToScreen);
    }

    /**
     * Screen a party for PEP status.
     *
     * @param string $partyId Party identifier
     * @param array<string, mixed> $partyData Party data
     * @return array<PepProfile>
     */
    public function screenForPep(string $partyId, array $partyData): array
    {
        $this->logger->info('Screening party for PEP status', [
            'party_id' => $partyId,
        ]);

        $party = $this->buildPartyAdapter($partyId, $partyData);
        return $this->pepScreener->screenForPep($party);
    }

    /**
     * Check if party has sanctions matches.
     *
     * @param string $partyId Party identifier
     */
    public function hasSanctionsMatches(string $partyId): bool
    {
        $this->logger->info('Checking for sanctions matches', [
            'party_id' => $partyId,
        ]);

        try {
            // Use PeriodicScreeningManager::hasActiveMatches() to check for matches
            return $this->screeningManager->hasActiveMatches($partyId);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to check sanctions matches', [
                'party_id' => $partyId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get parties with pending sanctions reviews.
     *
     * @param string $tenantId Tenant context
     * @return array<string> Party IDs with pending reviews
     */
    public function getPendingReviews(string $tenantId): array
    {
        $this->logger->info('Fetching pending sanctions reviews', [
            'tenant_id' => $tenantId,
        ]);

        try {
            // Use PeriodicScreeningManager::getPendingReviews() to get pending reviews
            $pendingReviews = $this->screeningManager->getPendingReviews(1000);
            
            // Extract party IDs from the pending reviews
            return array_map(fn($review) => $review['party_id'] ?? $review->partyId, $pendingReviews);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to fetch pending sanctions reviews', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);

            throw new SanctionsDataException('Failed to fetch pending reviews: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Check if enhanced screening is required.
     */
    private function requiresEnhancedScreening(ScreeningResult $result, array $pepProfiles): bool
    {
        return $result->hasMatches || count($pepProfiles) > 0;
    }

    /**
     * Build party adapter for Sanctions interfaces.
     */
    private function buildPartyAdapter(string $partyId, array $partyData): object
    {
        return new class($partyId, $partyData) implements \Nexus\Sanctions\Contracts\PartyInterface {
            public function __construct(
                private string $partyId,
                private array $data,
            ) {}

            public function getId(): string { return $this->partyId; }
            public function getName(): string { return $this->data['name'] ?? ''; }
            public function getType(): string { return $this->data['type'] ?? 'individual'; }
            public function getDateOfBirth(): ?\DateTimeImmutable { return $this->data['dateOfBirth'] ?? null; }
            public function getNationality(): ?string { return $this->data['nationality'] ?? null; }
            public function getIdentificationNumbers(): array { return $this->data['identificationNumbers'] ?? []; }
            public function getAddresses(): array { return $this->data['addresses'] ?? []; }
            public function getAliases(): array { return $this->data['aliases'] ?? []; }
        };
    }

    /**
     * Build screening result data array.
     *
     * @return array<string, mixed>
     */
    private function buildScreeningResultData(ScreeningResult $result): array
    {
        return [
            'screeningId' => $result->screeningId,
            'partyId' => $result->partyId,
            'hasMatches' => $result->hasMatches,
            'hasPotentialMatches' => $result->requiresReview,
            'matchCount' => count($result->matches),
            'matches' => array_map(fn($match) => [
                'matchId' => $match->listEntryId,
                'listName' => $match->list->value,
                'matchedName' => $match->matchedName,
                'matchStrength' => $match->matchStrength->value,
                'similarityScore' => $match->similarityScore,
                'listEntryId' => $match->listEntryId,
                'requiresReview' => $match->requiresReview(),
            ], $result->matches),
            'screenedAt' => $result->screenedAt->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Build PEP profiles data array.
     *
     * @param array<PepProfile> $profiles
     * @return array<int, array<string, mixed>>
     */
    private function buildPepProfilesData(array $profiles): array
    {
        return array_map(fn($profile) => [
            'profileId' => $profile->pepId,
            'name' => $profile->name,
            'position' => $profile->position,
            'country' => $profile->country,
            'pepLevel' => $profile->level->value,
            'startDate' => $profile->startDate?->format('Y-m-d'),
            'endDate' => $profile->endDate?->format('Y-m-d'),
            'isActive' => $profile->isActive(),
            'relationshipType' => $profile->additionalInfo['relationship_type'] ?? 'direct',
        ], $profiles);
    }

    /**
     * Build screening schedule data array.
     *
     * @param array<string, mixed> $schedule
     * @return array<string, mixed>
     */
    private function buildScheduleData(array $schedule): array
    {
        return [
            'nextScreeningDate' => isset($schedule['next_screening_date']) 
                ? $schedule['next_screening_date']->format('Y-m-d') 
                : null,
            'frequency' => isset($schedule['frequency']) 
                ? $schedule['frequency']->value 
                : null,
            'lastScreeningDate' => isset($schedule['last_screening_date']) 
                ? $schedule['last_screening_date']?->format('Y-m-d') 
                : null,
        ];
    }
}
