<?php

declare(strict_types=1);

namespace Nexus\DataPrivacy\Contracts;

use Nexus\DataPrivacy\ValueObjects\BreachRecord;
use Nexus\DataPrivacy\Enums\BreachSeverity;

/**
 * Read operations for breach records (CQRS Query Model).
 */
interface BreachRecordQueryInterface
{
    /**
     * Find breach by ID.
     */
    public function findById(string $id): ?BreachRecord;

    /**
     * Find all breaches.
     *
     * @return array<BreachRecord>
     */
    public function findAll(): array;

    /**
     * Find breaches by severity.
     *
     * @return array<BreachRecord>
     */
    public function findBySeverity(BreachSeverity $severity): array;

    /**
     * Find unresolved breaches.
     *
     * @return array<BreachRecord>
     */
    public function findUnresolved(): array;

    /**
     * Find breaches requiring regulatory notification.
     *
     * @return array<BreachRecord>
     */
    public function findRequiringRegulatoryNotification(): array;

    /**
     * Find breaches not yet notified to regulator.
     *
     * @return array<BreachRecord>
     */
    public function findPendingRegulatoryNotification(): array;

    /**
     * Find breaches not yet notified to individuals.
     *
     * @return array<BreachRecord>
     */
    public function findPendingIndividualNotification(): array;

    /**
     * Find breaches by date range.
     *
     * @return array<BreachRecord>
     */
    public function findByDateRange(
        \DateTimeImmutable $from,
        \DateTimeImmutable $to
    ): array;

    /**
     * Get breach statistics.
     *
     * @return array{
     *     total: int,
     *     by_severity: array<string, int>,
     *     resolved: int,
     *     unresolved: int,
     *     total_records_affected: int
     * }
     */
    public function getStatistics(): array;

    /**
     * Count breaches by severity.
     *
     * @return array<string, int>
     */
    public function countBySeverity(): array;
}
