<?php

declare(strict_types=1);

namespace Nexus\ComplianceOperations\DTOs\Sanctions;

/**
 * Context DTO for sanctions screening data.
 *
 * Aggregates all sanctions-related data for compliance workflows.
 */
final readonly class SanctionsScreeningContext
{
    /**
     * @param string $tenantId Tenant identifier
     * @param string $partyId Party identifier
     * @param array<string, mixed> $screeningResult Screening result data
     * @param array<int, array<string, mixed>> $pepProfiles PEP profiles found
     * @param bool $hasMatches Whether sanctions matches were found
     * @param bool $hasPotentialMatches Whether potential matches were found
     * @param bool $isPep Whether party is a PEP
     * @param bool $requiresEnhancedScreening Whether enhanced screening is required
     * @param array<string, mixed>|null $screeningSchedule Screening schedule data
     * @param \DateTimeImmutable $screenedAt Screening timestamp
     */
    public function __construct(
        public string $tenantId,
        public string $partyId,
        public array $screeningResult,
        public array $pepProfiles,
        public bool $hasMatches,
        public bool $hasPotentialMatches,
        public bool $isPep,
        public bool $requiresEnhancedScreening,
        public ?array $screeningSchedule,
        public \DateTimeImmutable $screenedAt,
    ) {}

    /**
     * Check if party is blocked from transactions.
     */
    public function isBlocked(): bool
    {
        return $this->screeningResult['requiresBlocking'] ?? false;
    }

    /**
     * Check if manual review is required.
     */
    public function requiresReview(): bool
    {
        return $this->screeningResult['requiresReview'] ?? false;
    }

    /**
     * Get overall risk level.
     */
    public function getRiskLevel(): string
    {
        return $this->screeningResult['overallRiskLevel'] ?? 'NONE';
    }

    /**
     * Convert to array for serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'tenantId' => $this->tenantId,
            'partyId' => $this->partyId,
            'screeningResult' => $this->screeningResult,
            'pepProfiles' => $this->pepProfiles,
            'hasMatches' => $this->hasMatches,
            'hasPotentialMatches' => $this->hasPotentialMatches,
            'isPep' => $this->isPep,
            'requiresEnhancedScreening' => $this->requiresEnhancedScreening,
            'screeningSchedule' => $this->screeningSchedule,
            'screenedAt' => $this->screenedAt->format('Y-m-d H:i:s'),
        ];
    }
}
