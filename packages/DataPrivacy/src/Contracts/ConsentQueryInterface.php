<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\Contracts;

use Nexus\DataPrivacy\ValueObjects\Consent;
use Nexus\DataPrivacy\Enums\ConsentPurpose;
use Nexus\DataPrivacy\Enums\ConsentStatus;

/**
 * Read operations for consent records (CQRS Query Model).
 */
interface ConsentQueryInterface
{
    /**
     * Find consent by ID.
     */
    public function findById(string $id): ?Consent;

    /**
     * Find all consents for a data subject.
     *
     * @return array<Consent>
     */
    public function findByDataSubject(string $dataSubjectId): array;

    /**
     * Find consent for a specific purpose.
     */
    public function findByDataSubjectAndPurpose(
        string $dataSubjectId,
        ConsentPurpose $purpose
    ): ?Consent;

    /**
     * Find all valid (granted, not expired) consents for a data subject.
     *
     * @return array<Consent>
     */
    public function findValidConsents(string $dataSubjectId): array;

    /**
     * Find consents by status.
     *
     * @return array<Consent>
     */
    public function findByStatus(ConsentStatus $status): array;

    /**
     * Find consents expiring within given days.
     *
     * @return array<Consent>
     */
    public function findExpiringWithinDays(int $days): array;

    /**
     * Check if valid consent exists for data subject and purpose.
     */
    public function hasValidConsent(
        string $dataSubjectId,
        ConsentPurpose $purpose
    ): bool;

    /**
     * Get consent history for audit trail.
     *
     * @return array<Consent>
     */
    public function getConsentHistory(
        string $dataSubjectId,
        ConsentPurpose $purpose
    ): array;

    /**
     * Count consents by status.
     *
     * @return array<string, int> Status => count mapping
     */
    public function countByStatus(): array;
}
