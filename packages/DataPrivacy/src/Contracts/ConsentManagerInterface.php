<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\Contracts;

use Nexus\DataPrivacy\ValueObjects\Consent;
use Nexus\DataPrivacy\ValueObjects\DataSubjectId;
use Nexus\DataPrivacy\Enums\ConsentPurpose;
use Nexus\DataPrivacy\Enums\ConsentStatus;

/**
 * Manager interface for consent operations.
 */
interface ConsentManagerInterface
{
    /**
     * Grant consent for a data subject and purpose.
     *
     * @param array{
     *     version?: int,
     *     ipAddress?: string|null,
     *     userAgent?: string|null,
     *     expiresAt?: \DateTimeImmutable|null
     * } $options Additional consent options
     */
    public function grantConsent(
        DataSubjectId $dataSubjectId,
        ConsentPurpose $purpose,
        array $options = []
    ): Consent;

    /**
     * Withdraw consent.
     */
    public function withdrawConsent(
        DataSubjectId $dataSubjectId,
        ConsentPurpose $purpose
    ): Consent;

    /**
     * Check if valid consent exists.
     */
    public function hasValidConsent(
        DataSubjectId $dataSubjectId,
        ConsentPurpose $purpose
    ): bool;

    /**
     * Get all consents for a data subject.
     *
     * @return array<Consent>
     */
    public function getConsents(DataSubjectId $dataSubjectId): array;

    /**
     * Get valid (granted, not expired) consents for a data subject.
     *
     * @return array<Consent>
     */
    public function getValidConsents(DataSubjectId $dataSubjectId): array;

    /**
     * Renew consent with new expiry date.
     */
    public function renewConsent(
        DataSubjectId $dataSubjectId,
        ConsentPurpose $purpose,
        \DateTimeImmutable $newExpiresAt
    ): Consent;

    /**
     * Withdraw all consents for a data subject (e.g., on account deletion).
     *
     * @return int Number of consents withdrawn
     */
    public function withdrawAllConsents(DataSubjectId $dataSubjectId): int;

    /**
     * Export consent records for a data subject (for portability requests).
     *
     * @return array<array<string, mixed>>
     */
    public function exportConsentRecords(DataSubjectId $dataSubjectId): array;

    /**
     * Process expired consents (mark as expired).
     *
     * @return int Number of consents marked as expired
     */
    public function processExpiredConsents(): int;
}
