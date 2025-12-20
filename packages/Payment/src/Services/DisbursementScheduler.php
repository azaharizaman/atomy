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
        ScheduleType $type,
        ?\DateTimeImmutable $scheduledDate = null,
        ?RecurrenceFrequency $frequency = null,
        ?\DateTimeImmutable $endDate = null,
        ?int $maxOccurrences = null,
    ): DisbursementSchedule {
        $schedule = match ($type) {
            ScheduleType::IMMEDIATE => DisbursementSchedule::immediate(),
            ScheduleType::SCHEDULED => DisbursementSchedule::scheduled($scheduledDate ?? throw InvalidScheduleException::scheduledDateRequired()),
            ScheduleType::RECURRING => DisbursementSchedule::recurring(
                startDate: $scheduledDate ?? throw InvalidScheduleException::scheduledDateRequired(),
                frequency: $frequency ?? throw InvalidScheduleException::recurrenceFrequencyRequired(),
                endDate: $endDate,
                maxOccurrences: $maxOccurrences,
            ),
        };

        return $schedule;
    }

    /**
     * {@inheritDoc}
     */
    public function scheduleForDate(
        string $disbursementId,
        \DateTimeImmutable $scheduledDate,
    ): DisbursementInterface {
        $schedule = DisbursementSchedule::scheduled($scheduledDate);

        $disbursement = $this->disbursementQuery->findById($disbursementId);

        if ($disbursement === null) {
            throw new DisbursementNotFoundException($disbursementId);
        }

        // Validate and store schedule
        $this->validateSchedule($schedule, $disbursement);
        $this->scheduleStorage->saveSchedule($disbursementId, $schedule);

        // Update disbursement scheduled date
        if ($schedule->scheduledDate !== null) {
            $disbursement->schedule($schedule->scheduledDate);
            $this->disbursementPersist->save($disbursement);
        }

        $this->logger->info('Disbursement scheduled', [
            'disbursement_id' => $disbursementId,
            'scheduled_date' => $schedule->scheduledDate?->format('c'),
        ]);

        return $disbursement;
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
    ): DisbursementInterface {
        $schedule = DisbursementSchedule::recurring(
            startDate: $startDate,
            frequency: $frequency,
            endDate: $endDate,
            maxOccurrences: $maxOccurrences,
        );

        $disbursement = $this->disbursementQuery->findById($disbursementId);

        if ($disbursement === null) {
            throw new DisbursementNotFoundException($disbursementId);
        }

        // Validate and store schedule
        $this->validateSchedule($schedule, $disbursement);
        $this->scheduleStorage->saveSchedule($disbursementId, $schedule);

        // Update disbursement scheduled date
        if ($schedule->scheduledDate !== null) {
            $disbursement->schedule($schedule->scheduledDate);
            $this->disbursementPersist->save($disbursement);
        }

        $this->logger->info('Recurring disbursement scheduled', [
            'disbursement_id' => $disbursementId,
            'start_date' => $schedule->scheduledDate?->format('c'),
            'frequency' => $frequency->value,
        ]);

        return $disbursement;
    }

    /**
     * {@inheritDoc}
     */
    public function cancelSchedule(
        string $disbursementId,
        string $cancelledBy,
        ?string $reason = null,
    ): DisbursementInterface {
        $schedule = $this->getSchedule($disbursementId);

        if ($schedule !== null) {
            $this->scheduleStorage->removeSchedule($disbursementId);

            $this->logger->info('Disbursement schedule cancelled', [
                'disbursement_id' => $disbursementId,
                'cancelled_by' => $cancelledBy,
                'reason' => $reason,
            ]);
        }

        $disbursement = $this->disbursementQuery->findById($disbursementId);

        if ($disbursement === null) {
            throw new DisbursementNotFoundException($disbursementId);
        }

        return $disbursement;
    }

    /**
     * {@inheritDoc}
     */
    public function getDueForProcessing(
        string $tenantId,
        ?\DateTimeImmutable $asOfDate = null,
    ): array {
        $asOfDate ??= new \DateTimeImmutable();
        $dueSchedules = $this->scheduleStorage->findDueForProcessing($tenantId, $asOfDate, 100);

        $result = [];

        foreach ($dueSchedules as $disbursementId => $schedule) {
            if ($schedule->isReadyForProcessing($asOfDate)) {
                $disbursement = $this->disbursementQuery->findById($disbursementId);

                if ($disbursement !== null && $this->canProcess($disbursement)) {
                    $result[$disbursementId] = $disbursement;
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
        int $days = 7,
    ): array {
        $fromDate = new \DateTimeImmutable();
        $toDate = $fromDate->modify('+' . $days . ' days');

        $schedules = $this->scheduleStorage->findUpcoming($tenantId, $fromDate, $toDate, 50);

        $result = [];

        foreach ($schedules as $disbursementId => $schedule) {
            $disbursement = $this->disbursementQuery->findById($disbursementId);

            if ($disbursement !== null) {
                $result[$disbursementId] = $disbursement;
            }
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function processNextOccurrence(string $disbursementId): DisbursementInterface
    {
        $schedule = $this->getSchedule($disbursementId);

        if ($schedule === null) {
            throw new DisbursementNotFoundException($disbursementId);
        }

        if (!$schedule->scheduleType->supportsRecurrence()) {
            throw InvalidScheduleException::notRecurring();
        }

        if (!$schedule->hasMoreOccurrences()) {
            throw InvalidScheduleException::noMoreOccurrences();
        }

        // Get parent disbursement
        $parentDisbursement = $this->disbursementQuery->findById($disbursementId);

        if ($parentDisbursement === null) {
            throw new DisbursementNotFoundException($disbursementId);
        }

        // Calculate and schedule next occurrence
        $nextDate = $schedule->calculateNextOccurrence();

        if ($nextDate === null) {
            throw InvalidScheduleException::noMoreOccurrences();
        }

        // Increment occurrence counter
        $updatedSchedule = $schedule->incrementOccurrence();

        // Update stored schedule
        $this->scheduleStorage->saveSchedule($disbursementId, $updatedSchedule);

        $this->logger->info('Recurring disbursement next occurrence scheduled', [
            'disbursement_id' => $disbursementId,
            'occurrence' => $updatedSchedule->currentOccurrence,
            'next_date' => $nextDate->format('c'),
        ]);

        return $parentDisbursement;
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
     * Get schedule for a disbursement (internal helper).
     */
    private function getSchedule(string $disbursementId): ?DisbursementSchedule
    {
        return $this->scheduleStorage->getSchedule($disbursementId);
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
        $scheduledDate = $schedule->scheduledDate;
        if ($scheduledDate !== null && $scheduledDate < $now) {
            throw InvalidScheduleException::scheduledDateInPast($scheduledDate);
        }

        // End date must be after start date
        $endDate = $schedule->recurrenceEndDate;
        if ($endDate !== null && $scheduledDate !== null && $endDate <= $scheduledDate) {
            throw InvalidScheduleException::endDateBeforeStartDate($scheduledDate, $endDate);
        }

        // Max occurrences must be positive
        $maxOccurrences = $schedule->maxOccurrences;
        if ($maxOccurrences !== null && $maxOccurrences < 1) {
            throw InvalidScheduleException::invalidMaxOccurrences($maxOccurrences);
        }

        // Recurring schedules require frequency
        if ($schedule->scheduleType === ScheduleType::RECURRING) {
            if ($schedule->recurrenceFrequency === null) {
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
