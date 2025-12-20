<?php

declare(strict_types=1);

namespace Nexus\Payment\Services;

use Nexus\Payment\Contracts\DisbursementInterface;
use Nexus\Payment\Contracts\DisbursementPersistInterface;
use Nexus\Payment\Contracts\DisbursementQueryInterface;
use Nexus\Payment\Contracts\DisbursementSchedulerInterface;
use Nexus\Payment\Contracts\DisbursementScheduleStorageInterface;
use Nexus\Payment\Enums\DisbursementStatus;
use Nexus\Payment\Enums\RecurrenceFrequency;
use Nexus\Payment\Enums\ScheduleType;
use Nexus\Payment\Exceptions\DisbursementNotFoundException;
use Nexus\Payment\Exceptions\InvalidScheduleException;
use Nexus\Payment\ValueObjects\DisbursementSchedule;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Disbursement scheduler service.
 *
 * Manages disbursement scheduling and recurrence (PAY-034).
 * Supports immediate, scheduled, and recurring disbursements.
 */
final readonly class DisbursementScheduler implements DisbursementSchedulerInterface
{
    public function __construct(
        private DisbursementQueryInterface $disbursementQuery,
        private DisbursementPersistInterface $disbursementPersist,
        private DisbursementScheduleStorageInterface $scheduleStorage,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * {@inheritDoc}
     */
    public function createSchedule(
        string $disbursementId,
        DisbursementSchedule $schedule,
    ): DisbursementSchedule {
        $disbursement = $this->disbursementQuery->findById($disbursementId);

        if ($disbursement === null) {
            throw new DisbursementNotFoundException($disbursementId);
        }

        // Validate schedule
        $this->validateSchedule($schedule, $disbursement);

        // Store the schedule
        $this->scheduleStorage->saveSchedule($disbursementId, $schedule);

        // Update disbursement scheduled date if applicable
        if ($schedule->getScheduledDate() !== null) {
            $disbursement->schedule($schedule->getScheduledDate());
            $this->disbursementPersist->save($disbursement);
        }

        $this->logger->info('Disbursement schedule created', [
            'disbursement_id' => $disbursementId,
            'schedule_type' => $schedule->getType()->value,
            'scheduled_date' => $schedule->getScheduledDate()?->format('c'),
        ]);

        return $schedule;
    }

    /**
     * {@inheritDoc}
     */
    public function scheduleForDate(
        string $disbursementId,
        \DateTimeImmutable $scheduledDate,
    ): DisbursementSchedule {
        $schedule = DisbursementSchedule::scheduled($scheduledDate);

        return $this->createSchedule($disbursementId, $schedule);
    }

    /**
     * {@inheritDoc}
     */
    public function scheduleRecurring(
        string $disbursementId,
        \DateTimeImmutable $startDate,
        RecurrenceFrequency $frequency,
        ?\DateTimeImmutable $endDate = null,
        ?int $maxOccurrences = null,
    ): DisbursementSchedule {
        $schedule = DisbursementSchedule::recurring(
            startDate: $startDate,
            frequency: $frequency,
            endDate: $endDate,
            maxOccurrences: $maxOccurrences,
        );

        return $this->createSchedule($disbursementId, $schedule);
    }

    /**
     * {@inheritDoc}
     */
    public function cancelSchedule(string $disbursementId): void
    {
        $schedule = $this->getSchedule($disbursementId);

        if ($schedule === null) {
            return;
        }

        $this->scheduleStorage->removeSchedule($disbursementId);

        $this->logger->info('Disbursement schedule cancelled', [
            'disbursement_id' => $disbursementId,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function getSchedule(string $disbursementId): ?DisbursementSchedule
    {
        return $this->scheduleStorage->getSchedule($disbursementId);
    }

    /**
     * {@inheritDoc}
     */
    public function getDueForProcessing(
        string $tenantId,
        ?\DateTimeImmutable $asOf = null,
        int $limit = 100,
    ): array {
        $asOf ??= new \DateTimeImmutable();
        $dueSchedules = $this->scheduleStorage->findDueForProcessing($tenantId, $asOf, $limit);

        $result = [];

        foreach ($dueSchedules as $disbursementId => $schedule) {
            if ($schedule->isReadyForProcessing($asOf)) {
                $disbursement = $this->disbursementQuery->findById($disbursementId);

                if ($disbursement !== null && $this->canProcess($disbursement)) {
                    $result[$disbursementId] = $schedule;
                }
            }
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function getUpcoming(
        string $tenantId,
        \DateTimeImmutable $fromDate,
        \DateTimeImmutable $toDate,
        int $limit = 50,
    ): array {
        return $this->scheduleStorage->findUpcoming($tenantId, $fromDate, $toDate, $limit);
    }

    /**
     * {@inheritDoc}
     */
    public function processNextOccurrence(string $disbursementId): ?DisbursementSchedule
    {
        $schedule = $this->getSchedule($disbursementId);

        if ($schedule === null) {
            return null;
        }

        if (!$schedule->getType()->supportsRecurrence()) {
            return null;
        }

        if (!$schedule->hasMoreOccurrences()) {
            $this->logger->info('Recurring schedule completed - no more occurrences', [
                'disbursement_id' => $disbursementId,
            ]);

            return null;
        }

        // Calculate and schedule next occurrence
        $nextDate = $schedule->calculateNextOccurrence();

        if ($nextDate === null) {
            return null;
        }

        // Increment occurrence counter
        $updatedSchedule = $schedule->incrementOccurrence();

        // Update stored schedule
        $this->scheduleStorage->saveSchedule($disbursementId, $updatedSchedule);

        $this->logger->info('Recurring disbursement next occurrence scheduled', [
            'disbursement_id' => $disbursementId,
            'occurrence' => $updatedSchedule->getCurrentOccurrence(),
            'next_date' => $nextDate->format('c'),
        ]);

        return $updatedSchedule;
    }

    /**
     * {@inheritDoc}
     */
    public function hasMoreOccurrences(string $disbursementId): bool
    {
        $schedule = $this->getSchedule($disbursementId);

        return $schedule !== null && $schedule->hasMoreOccurrences();
    }

    /**
     * {@inheritDoc}
     */
    public function reschedule(
        string $disbursementId,
        \DateTimeImmutable $newDate,
    ): DisbursementSchedule {
        $existingSchedule = $this->getSchedule($disbursementId);

        if ($existingSchedule === null) {
            return $this->scheduleForDate($disbursementId, $newDate);
        }

        // Create new schedule preserving recurrence settings if applicable
        $newSchedule = match ($existingSchedule->getType()) {
            ScheduleType::RECURRING => DisbursementSchedule::recurring(
                startDate: $newDate,
                frequency: $existingSchedule->getRecurrenceFrequency(),
                endDate: $existingSchedule->getEndDate(),
                maxOccurrences: $existingSchedule->getMaxOccurrences(),
            ),
            default => DisbursementSchedule::scheduled($newDate),
        };

        return $this->createSchedule($disbursementId, $newSchedule);
    }

    /**
     * Validate schedule configuration.
     *
     * @throws InvalidScheduleException If schedule is invalid
     */
    private function validateSchedule(
        DisbursementSchedule $schedule,
        DisbursementInterface $disbursement,
    ): void {
        $now = new \DateTimeImmutable();

        // Scheduled date must be in the future
        $scheduledDate = $schedule->getScheduledDate();
        if ($scheduledDate !== null && $scheduledDate <= $now) {
            throw InvalidScheduleException::scheduledDateInPast($scheduledDate);
        }

        // End date must be after start date
        $endDate = $schedule->getEndDate();
        if ($endDate !== null && $scheduledDate !== null && $endDate <= $scheduledDate) {
            throw InvalidScheduleException::endDateBeforeStartDate($scheduledDate, $endDate);
        }

        // Max occurrences must be positive
        $maxOccurrences = $schedule->getMaxOccurrences();
        if ($maxOccurrences !== null && $maxOccurrences < 1) {
            throw InvalidScheduleException::invalidMaxOccurrences($maxOccurrences);
        }

        // Recurring schedules require frequency
        if ($schedule->getType() === ScheduleType::RECURRING) {
            if ($schedule->getRecurrenceFrequency() === null) {
                throw InvalidScheduleException::recurrenceFrequencyRequired();
            }
        }
    }

    /**
     * Check if disbursement can be processed.
     */
    private function canProcess(DisbursementInterface $disbursement): bool
    {
        return in_array($disbursement->getStatus(), [
            DisbursementStatus::DRAFT,
            DisbursementStatus::PENDING_APPROVAL,
            DisbursementStatus::APPROVED,
        ], true);
    }
}
