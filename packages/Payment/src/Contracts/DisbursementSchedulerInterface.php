<?php

declare(strict_types=1);

namespace Nexus\Payment\Contracts;

use Nexus\Payment\Enums\RecurrenceFrequency;
use Nexus\Payment\Enums\ScheduleType;
use Nexus\Payment\ValueObjects\DisbursementSchedule;

/**
 * Contract for disbursement scheduler service.
 *
 * Handles scheduling of immediate, one-time, and recurring disbursements.
 * Used for PAY-034: Disbursement scheduling support.
 */
interface DisbursementSchedulerInterface
{
    /**
     * Create a schedule for a disbursement.
     *
     * @param ScheduleType $type Schedule type
     * @param \DateTimeImmutable|null $scheduledDate Scheduled date (required for SCHEDULED and RECURRING)
     * @param RecurrenceFrequency|null $frequency Recurrence frequency (required for RECURRING)
     * @param \DateTimeImmutable|null $endDate End date for recurrence
     * @param int|null $maxOccurrences Maximum occurrences for recurrence
     *
     * @return DisbursementSchedule The created schedule
     *
     * @throws \Nexus\Payment\Exceptions\InvalidScheduleException If schedule configuration is invalid
     */
    public function createSchedule(
        ScheduleType $type,
        ?\DateTimeImmutable $scheduledDate = null,
        ?RecurrenceFrequency $frequency = null,
        ?\DateTimeImmutable $endDate = null,
        ?int $maxOccurrences = null,
    ): DisbursementSchedule;

    /**
     * Schedule a disbursement for one-time future processing.
     *
     * @param string $disbursementId Disbursement ID
     * @param \DateTimeImmutable $scheduledDate Date to process
     *
     * @return DisbursementInterface Updated disbursement
     */
    public function scheduleForDate(
        string $disbursementId,
        \DateTimeImmutable $scheduledDate,
    ): DisbursementInterface;

    /**
     * Schedule a recurring disbursement.
     *
     * @param string $disbursementId Disbursement ID
     * @param \DateTimeImmutable $startDate First occurrence date
     * @param RecurrenceFrequency $frequency How often to recur
     * @param \DateTimeImmutable|null $endDate End date for recurrence
     * @param int|null $maxOccurrences Maximum occurrences
     *
     * @return DisbursementInterface Updated disbursement
     */
    public function scheduleRecurring(
        string $disbursementId,
        \DateTimeImmutable $startDate,
        RecurrenceFrequency $frequency,
        ?\DateTimeImmutable $endDate = null,
        ?int $maxOccurrences = null,
    ): DisbursementInterface;

    /**
     * Cancel a scheduled disbursement.
     *
     * @param string $disbursementId Disbursement ID
     * @param string $cancelledBy User ID
     * @param string|null $reason Cancellation reason
     *
     * @return DisbursementInterface Updated disbursement
     */
    public function cancelSchedule(
        string $disbursementId,
        string $cancelledBy,
        ?string $reason = null,
    ): DisbursementInterface;

    /**
     * Get all disbursements due for processing.
     *
     * @param string $tenantId Tenant ID
     * @param \DateTimeImmutable|null $asOfDate Reference date (defaults to now)
     *
     * @return array<DisbursementInterface> Disbursements ready for processing
     */
    public function getDueForProcessing(
        string $tenantId,
        ?\DateTimeImmutable $asOfDate = null,
    ): array;

    /**
     * Get upcoming scheduled disbursements.
     *
     * @param string $tenantId Tenant ID
     * @param int $days Number of days to look ahead
     *
     * @return array<DisbursementInterface> Upcoming scheduled disbursements
     */
    public function getUpcoming(string $tenantId, int $days = 7): array;

    /**
     * Process the next occurrence of a recurring disbursement.
     *
     * Creates a new disbursement instance for the current occurrence
     * and updates the schedule for the next one.
     *
     * @param string $disbursementId Parent recurring disbursement ID
     *
     * @return DisbursementInterface The newly created occurrence disbursement
     */
    public function processNextOccurrence(string $disbursementId): DisbursementInterface;

    /**
     * Check if a recurring disbursement has more occurrences.
     *
     * @param string $disbursementId Disbursement ID
     *
     * @return bool True if more occurrences are pending
     */
    public function hasMoreOccurrences(string $disbursementId): bool;
}
