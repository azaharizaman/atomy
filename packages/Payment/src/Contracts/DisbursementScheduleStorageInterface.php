<?php

declare(strict_types=1);

namespace Nexus\Payment\Contracts;

use Nexus\Payment\ValueObjects\DisbursementSchedule;

/**
 * Interface for disbursement schedule storage.
 *
 * This interface must be implemented by the consuming application
 * to provide persistence for disbursement schedules (PAY-034).
 *
 * Example implementation using a database:
 * ```php
 * final readonly class EloquentDisbursementScheduleStorage implements DisbursementScheduleStorageInterface
 * {
 *     public function getSchedule(string $disbursementId): ?DisbursementSchedule
 *     {
 *         $record = DisbursementScheduleModel::where('disbursement_id', $disbursementId)->first();
 *         return $record ? DisbursementSchedule::fromArray($record->schedule_data) : null;
 *     }
 * }
 * ```
 */
interface DisbursementScheduleStorageInterface
{
    /**
     * Get schedule for a disbursement.
     *
     * @param string $disbursementId The disbursement identifier
     * @return DisbursementSchedule|null The schedule or null if none exists
     */
    public function getSchedule(string $disbursementId): ?DisbursementSchedule;

    /**
     * Save schedule for a disbursement.
     *
     * @param string $disbursementId The disbursement identifier
     * @param DisbursementSchedule $schedule The schedule to save
     */
    public function saveSchedule(string $disbursementId, DisbursementSchedule $schedule): void;

    /**
     * Remove schedule for a disbursement.
     *
     * @param string $disbursementId The disbursement identifier
     */
    public function removeSchedule(string $disbursementId): void;

    /**
     * Check if a disbursement has a schedule.
     *
     * @param string $disbursementId The disbursement identifier
     * @return bool True if schedule exists
     */
    public function hasSchedule(string $disbursementId): bool;

    /**
     * Find all schedules due for processing.
     *
     * Returns schedules where the scheduled date is on or before the given time.
     *
     * @param string $tenantId The tenant identifier
     * @param \DateTimeImmutable $asOf The time to check against
     * @param int $limit Maximum number of schedules to return
     * @return array<string, DisbursementSchedule> Map of disbursement ID to schedule
     */
    public function findDueForProcessing(
        string $tenantId,
        \DateTimeImmutable $asOf,
        int $limit = 100,
    ): array;

    /**
     * Find upcoming scheduled disbursements.
     *
     * Returns schedules with dates between the given range.
     *
     * @param string $tenantId The tenant identifier
     * @param \DateTimeImmutable $fromDate Start of date range
     * @param \DateTimeImmutable $toDate End of date range
     * @param int $limit Maximum number of schedules to return
     * @return array<string, DisbursementSchedule> Map of disbursement ID to schedule
     */
    public function findUpcoming(
        string $tenantId,
        \DateTimeImmutable $fromDate,
        \DateTimeImmutable $toDate,
        int $limit = 50,
    ): array;

    /**
     * Find all recurring schedules.
     *
     * @param string $tenantId The tenant identifier
     * @return array<string, DisbursementSchedule> Map of disbursement ID to schedule
     */
    public function findRecurring(string $tenantId): array;

    /**
     * Count schedules by type.
     *
     * @param string $tenantId The tenant identifier
     * @return array<string, int> Map of schedule type to count
     */
    public function countByType(string $tenantId): array;
}
