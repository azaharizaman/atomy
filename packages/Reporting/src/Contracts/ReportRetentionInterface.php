<?php

declare(strict_types=1);

namespace Nexus\Reporting\Contracts;

/**
 * Manages the tiered retention lifecycle of generated reports.
 *
 * Implements the 3-tier policy:
 * - Tier 1 (Active): 90 days in hot storage
 * - Tier 2 (Archive): 7 years in deep archive (S3 Glacier)
 * - Tier 3 (Purged): Irreversible deletion
 */
interface ReportRetentionInterface
{
    /**
     * Apply retention policy to all generated reports.
     *
     * Transitions reports between tiers based on age and current tier.
     * Should be executed daily via scheduled job.
     *
     * @return array{
     *     active_to_archived: int,
     *     archived_to_purged: int,
     *     errors: array<string>
     * } Statistics of transitions performed
     */
    public function applyRetentionPolicy(): array;

    /**
     * Transition a specific report to the archive tier.
     *
     * Moves the file from hot storage to deep archive (e.g., S3 Glacier)
     * and updates the retention_tier field.
     *
     * @param string $reportGeneratedId
     * @return bool Success status
     */
    public function archiveReport(string $reportGeneratedId): bool;

    /**
     * Purge an archived report permanently.
     *
     * Deletes the file from storage and marks the record as purged.
     * This action is irreversible.
     *
     * @param string $reportGeneratedId
     * @return bool Success status
     */
    public function purgeReport(string $reportGeneratedId): bool;

    /**
     * Get retention statistics.
     *
     * @return array{
     *     total_active: int,
     *     total_archived: int,
     *     total_purged: int,
     *     total_size_bytes: int,
     *     oldest_active: ?\DateTimeImmutable,
     *     oldest_archived: ?\DateTimeImmutable
     * }
     */
    public function getRetentionStatistics(): array;

    /**
     * Check if a report is due for tier transition.
     *
     * @param string $reportGeneratedId
     * @return array{
     *     current_tier: string,
     *     should_transition: bool,
     *     next_tier: ?string,
     *     transition_date: ?\DateTimeImmutable
     * }
     */
    public function checkTransitionStatus(string $reportGeneratedId): array;
}
