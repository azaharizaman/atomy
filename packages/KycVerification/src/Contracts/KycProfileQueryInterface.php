<?php

declare(strict_types=1);

namespace Nexus\KycVerification\Contracts;

use Nexus\KycVerification\ValueObjects\KycProfile;
use Nexus\KycVerification\ValueObjects\RiskAssessment;
use Nexus\KycVerification\ValueObjects\ReviewSchedule;

/**
 * Interface for KYC profile persistence (Query operations).
 */
interface KycProfileQueryInterface
{
    /**
     * Find KYC profile by party ID
     */
    public function findByPartyId(string $partyId): ?KycProfile;

    /**
     * Check if KYC profile exists
     */
    public function exists(string $partyId): bool;

    /**
     * Get all profiles with pending verification
     * 
     * @return array<KycProfile>
     */
    public function findPending(?int $limit = null): array;

    /**
     * Get all profiles expiring within days
     * 
     * @return array<KycProfile>
     */
    public function findExpiring(int $withinDays = 30): array;

    /**
     * Get all high-risk profiles
     * 
     * @return array<KycProfile>
     */
    public function findHighRisk(): array;

    /**
     * Get profiles needing review
     * 
     * @return array<KycProfile>
     */
    public function findNeedingReview(): array;

    /**
     * Get profiles by verification status
     * 
     * @return array<KycProfile>
     */
    public function findByStatus(string $status): array;

    /**
     * Count profiles by status
     * 
     * @return array<string, int>
     */
    public function countByStatus(): array;

    /**
     * Search profiles
     * 
     * @param array<string, mixed> $criteria
     * @return array<KycProfile>
     */
    public function search(array $criteria): array;
}
