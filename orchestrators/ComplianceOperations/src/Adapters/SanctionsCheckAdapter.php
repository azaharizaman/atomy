<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\Adapters;

use Nexus\Sanctions\Contracts\SanctionsScreenerInterface;
use Nexus\Sanctions\Contracts\PepScreenerInterface;
use Nexus\Sanctions\Enums\SanctionsList;
use Nexus\Sanctions\ValueObjects\ScreeningResult;
use Nexus\ComplianceOperations\Contracts\SanctionsCheckAdapterInterface;

/**
 * Adapter for sanctions screening package interface.
 *
 * Adapts the Sanctions package to the ComplianceOperations orchestrator's
 * interface requirements. This adapter implements the orchestrator's own contract
 * and delegates to the atomic package's interfaces.
 *
 * Following the Interface Segregation principle from ARCHITECTURE.md:
 * Orchestrators define their own interfaces and adapters implement them using
 * atomic package interfaces.
 */
final readonly class SanctionsCheckAdapter implements SanctionsCheckAdapterInterface
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
        private SanctionsScreenerInterface $screener,
        private PepScreenerInterface $pepScreener,
    ) {}

    /**
     * Screen a party against sanctions lists.
     *
     * @param string $partyId Party identifier
     * @param array<string, mixed> $partyData Party data for screening
     * @param array<string>|null $lists Specific lists to screen (null for default)
     * @return array<string, mixed> Screening result
     */
    public function screen(string $partyId, array $partyData, ?array $lists = null): array
    {
        $party = $this->buildPartyAdapter($partyId, $partyData);
        $listsToScreen = $lists !== null 
            ? array_map(fn($l) => SanctionsList::from($l), $lists)
            : self::DEFAULT_LISTS;

        $result = $this->screener->screen($party, $listsToScreen);

        return $this->buildScreeningResultData($result);
    }

    /**
     * Screen a party for PEP status.
     *
     * @param string $partyId Party identifier
     * @param array<string, mixed> $partyData Party data for screening
     * @return array<int, array<string, mixed>> PEP profiles found
     */
    public function screenForPep(string $partyId, array $partyData): array
    {
        $party = $this->buildPartyAdapter($partyId, $partyData);
        $profiles = $this->pepScreener->screenForPep($party);

        return array_map(fn($profile) => [
            'profileId' => $profile->pepId,
            'name' => $profile->name,
            'position' => $profile->position,
            'country' => $profile->country,
            'pepLevel' => $profile->level->value,
            'startDate' => $profile->startDate?->format('Y-m-d'),
            'endDate' => $profile->endDate?->format('Y-m-d'),
            'isActive' => $profile->isActive(),
            'relationshipType' => null,
        ], $profiles);
    }

    /**
     * Check if party has sanctions matches.
     *
     * @param string $partyId Party identifier
     */
    public function hasMatches(string $partyId): bool
    {
        // This would require a stored result lookup
        return false;
    }

    /**
     * Check if party is a PEP.
     *
     * @param string $partyId Party identifier
     */
    public function isPep(string $partyId): bool
    {
        // This would require a stored result lookup
        return false;
    }

    /**
     * Get screening result for a party.
     *
     * @param string $partyId Party identifier
     * @return array<string, mixed>|null Screening result or null if not screened
     */
    public function getScreeningResult(string $partyId): ?array
    {
        // This would require a stored result lookup
        return null;
    }

    /**
     * Get recommended screening frequency.
     *
     * @param string $partyId Party identifier
     * @param array<string, mixed> $partyData Party data
     * @return string Recommended frequency
     */
    public function getRecommendedFrequency(string $partyId, array $partyData): string
    {
        $party = $this->buildPartyAdapter($partyId, $partyData);
        $frequency = $this->screener->getRecommendedFrequency($party);
        return $frequency->value;
    }

    /**
     * Calculate similarity between two names.
     *
     * @param string $name1 First name
     * @param string $name2 Second name
     * @return float Similarity score (0.0-1.0)
     */
    public function calculateSimilarity(string $name1, string $name2): float
    {
        return $this->screener->calculateSimilarity($name1, $name2);
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
            public function getType(): string { return strtoupper($this->data['type'] ?? 'INDIVIDUAL'); }
            public function getDateOfBirth(): ?\DateTimeImmutable { return $this->data['dateOfBirth'] ?? null; }
            public function getNationality(): ?string { return $this->data['nationality'] ?? null; }
            public function getCountryOfIncorporation(): ?string { return $this->data['countryOfIncorporation'] ?? null; }
            public function getPassportNumber(): ?string { return $this->data['passportNumber'] ?? null; }
            public function getNationalId(): ?string { return $this->data['nationalId'] ?? null; }
            public function getAddress(): ?string { return $this->data['address'] ?? null; }
            public function getEmailAddress(): ?string { return $this->data['email'] ?? null; }
            public function getAliases(): array { return $this->data['aliases'] ?? []; }
            public function getRiskRating(): ?string { return $this->data['riskRating'] ?? null; }
            public function isIndividual(): bool { return strtoupper($this->data['type'] ?? 'INDIVIDUAL') === 'INDIVIDUAL'; }
            public function isOrganization(): bool { return strtoupper($this->data['type'] ?? 'INDIVIDUAL') === 'ORGANIZATION'; }
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
            'partyName' => $result->partyName,
            'hasMatches' => $result->hasMatches,
            'requiresBlocking' => $result->requiresBlocking,
            'requiresReview' => $result->requiresReview,
            'overallRiskLevel' => $result->overallRiskLevel,
            'matchCount' => count($result->matches),
            'matches' => array_map(fn($match) => [
                'matchId' => $match->listEntryId,
                'listName' => $match->list->getName(),
                'matchedName' => $match->matchedName,
                'matchStrength' => $match->matchStrength->value,
                'similarityScore' => $match->similarityScore,
            ], $result->matches),
            'pepProfileCount' => count($result->pepProfiles),
            'screenedAt' => $result->screenedAt->format('Y-m-d H:i:s'),
            'processingTimeMs' => $result->processingTimeMs,
        ];
    }
}
