<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\Contracts;

use Nexus\DataPrivacy\ValueObjects\BreachRecord;
use Nexus\DataPrivacy\Enums\BreachSeverity;
use Nexus\DataPrivacy\Enums\DataCategory;

/**
 * Manager interface for data breach operations.
 */
interface BreachRecordManagerInterface
{
    /**
     * Report a new data breach.
     *
     * @param array<DataCategory> $affectedCategories
     */
    public function reportBreach(
        string $description,
        BreachSeverity $severity,
        int $recordsAffected,
        array $affectedCategories,
        \DateTimeImmutable $detectedAt,
        ?string $reportedBy = null
    ): BreachRecord;

    /**
     * Get breach by ID.
     */
    public function getBreach(string $breachId): BreachRecord;

    /**
     * Get all breaches.
     *
     * @return array<BreachRecord>
     */
    public function getAllBreaches(): array;

    /**
     * Get unresolved breaches.
     *
     * @return array<BreachRecord>
     */
    public function getUnresolvedBreaches(): array;

    /**
     * Update breach severity.
     */
    public function updateSeverity(
        string $breachId,
        BreachSeverity $newSeverity,
        string $reason
    ): BreachRecord;

    /**
     * Update records affected count.
     */
    public function updateRecordsAffected(
        string $breachId,
        int $recordsAffected
    ): BreachRecord;

    /**
     * Notify regulatory authority about breach.
     *
     * @return string Reference number from authority
     */
    public function notifyRegulatoryAuthority(
        string $breachId,
        string $notificationDetails
    ): string;

    /**
     * Mark breach as notified to regulator.
     */
    public function markRegulatoryNotified(
        string $breachId,
        string $referenceNumber
    ): BreachRecord;

    /**
     * Notify affected individuals.
     *
     * @return int Number of individuals notified
     */
    public function notifyAffectedIndividuals(
        string $breachId,
        string $notificationTemplate,
        array $additionalContext = []
    ): int;

    /**
     * Mark breach as notified to individuals.
     */
    public function markIndividualsNotified(
        string $breachId,
        int $individualsNotified
    ): BreachRecord;

    /**
     * Resolve a breach.
     */
    public function resolveBreach(
        string $breachId,
        string $resolutionDetails
    ): BreachRecord;

    /**
     * Add evidence to breach record.
     */
    public function addEvidence(
        string $breachId,
        string $evidenceContent,
        string $filename
    ): string;

    /**
     * Get breaches requiring regulatory notification.
     *
     * @return array<BreachRecord>
     */
    public function getBreachesRequiringNotification(): array;

    /**
     * Get breaches with approaching notification deadline.
     *
     * @param int $withinHours Hours until deadline
     * @return array<BreachRecord>
     */
    public function getBreachesApproachingDeadline(int $withinHours = 24): array;

    /**
     * Get breach statistics for reporting.
     *
     * @return array{
     *     total: int,
     *     by_severity: array<string, int>,
     *     resolved: int,
     *     unresolved: int,
     *     average_resolution_days: float,
     *     total_records_affected: int
     * }
     */
    public function getBreachStatistics(): array;
}
