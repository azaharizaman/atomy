<?php

declare(strict_types=1);

namespace Nexus\Reporting\Contracts;

/**
 * Repository interface for persisting and retrieving report definitions and execution history.
 */
interface ReportRepositoryInterface
{
    /**
     * Save a report definition to storage.
     *
     * @param array<string, mixed> $data Report definition data
     * @return string The report definition ID (ULID)
     */
    public function save(array $data): string;

    /**
     * Find a report definition by its ID.
     *
     * @return ReportDefinitionInterface|null
     */
    public function findById(string $id): ?ReportDefinitionInterface;

    /**
     * Find all report definitions owned by a specific user or entity.
     *
     * @return array<ReportDefinitionInterface>
     */
    public function findByOwner(string $ownerId): array;

    /**
     * Find all active report definitions that are due for generation at the given time.
     *
     * Used by the scheduler to determine which reports to process.
     *
     * @param \DateTimeImmutable $asOf The reference time to check against
     * @return array<ReportDefinitionInterface>
     */
    public function findDueForGeneration(\DateTimeImmutable $asOf): array;

    /**
     * Archive a report definition (soft delete).
     *
     * Archived reports are excluded from scheduling but execution history is preserved.
     */
    public function archive(string $id): bool;

    /**
     * Update a report definition.
     *
     * @param array<string, mixed> $data Updated fields
     */
    public function update(string $id, array $data): bool;

    /**
     * Store a report generation result.
     *
     * @param array<string, mixed> $data Generation result data
     * @return string The generated report ID (ULID)
     */
    public function storeGeneratedReport(array $data): string;

    /**
     * Find a generated report by its ID.
     *
     * @return array<string, mixed>|null
     */
    public function findGeneratedReportById(string $id): ?array;

    /**
     * Get generation history for a specific report definition.
     *
     * @return array<array<string, mixed>>
     */
    public function getGenerationHistory(string $reportDefinitionId, int $limit = 50): array;

    /**
     * Store a distribution log entry.
     *
     * @param array<string, mixed> $data Distribution log data
     * @return string The log entry ID (ULID)
     */
    public function storeDistributionLog(array $data): string;

    /**
     * Get distribution logs for a generated report.
     *
     * @return array<array<string, mixed>>
     */
    public function getDistributionLogs(string $reportGeneratedId): array;

    /**
     * Find reports in a specific retention tier that are ready for transition.
     *
     * @param string $tier The current tier (active, archived)
     * @param \DateTimeImmutable $olderThan Reports generated before this date
     * @return array<array<string, mixed>>
     */
    public function findReportsForRetentionTransition(string $tier, \DateTimeImmutable $olderThan): array;

    /**
     * Update the retention tier of a generated report.
     */
    public function updateRetentionTier(string $reportGeneratedId, string $newTier): bool;
}
