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
        return $this->createScheduleObject($type, $scheduledDate, $frequency, $endDate, $maxOccurrences);
    }

    /**
     * {@inheritDoc}
     */
    public function scheduleForDate(
        string $disbursementId,
        \DateTimeImmutable $scheduledDate,
    ): DisbursementInterface {
        $schedule = $this->createScheduleObject(ScheduleType::SCHEDULED, $scheduledDate);

        return $this->persistSchedule($disbursementId, $schedule, [
            'message' => 'One-time disbursement scheduled',
            'schedule_type' => ScheduleType::SCHEDULED->value,
            'scheduled_date' => $schedule->scheduledDate?->format('c'),
        ]);
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
        $schedule = $this->createScheduleObject(
            ScheduleType::RECURRING,
            $startDate,
            $frequency,
            $endDate,
            $maxOccurrences
        );

        return $this->persistSchedule($disbursementId, $schedule, [
            'message' => 'Recurring disbursement scheduled',
            'schedule_type' => ScheduleType::RECURRING->value,
            'start_date' => $schedule->scheduledDate?->format('c'),
            'frequency' => $frequency->value,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function cancelSchedule(
        string $disbursementId,
        string $cancelledBy,
        ?string $reason = null,
    ): DisbursementInterface {
        // Check disbursement exists first before modifying state
        $disbursement = $this->disbursementQuery->findById($disbursementId);

        if ($disbursement === null) {
            throw new DisbursementNotFoundException($disbursementId);
        }

        $schedule = $this->getSchedule($disbursementId);

        if ($schedule !== null) {
            $this->scheduleStorage->removeSchedule($disbursementId);

            $this->logger->info('Disbursement schedule cancelled', [
                'disbursement_id' => $disbursementId,
                'cancelled_by' => $cancelledBy,
                'reason' => $reason,
            ]);
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
     *
     * NOTE: This method is not fully implemented.
     *
     * Proper implementation requires creating a new disbursement instance for each
     * occurrence using a factory/manager (e.g. DisbursementFactoryInterface or
     * DisbursementManagerInterface) injected into this service.
     *
     * Until that dependency is wired in and the implementation completed, this
     * method will throw a LogicException to prevent incorrect behaviour in
     * production.
     *
     * @throws \LogicException Always thrown - method not implemented
     */
    public function processNextOccurrence(string $disbursementId): DisbursementInterface
    {
        throw new \LogicException(
            'processNextOccurrence is not implemented: recurring disbursement ' .
            'processing requires a disbursement factory/manager to create ' .
            'new disbursement instances for each occurrence.'
        );
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
     *
     * @return DisbursementSchedule|null The schedule if found, null otherwise
     */
    private function getSchedule(string $disbursementId): ?DisbursementSchedule
    {
        return $this->scheduleStorage->getSchedule($disbursementId);
    }

    /**
     * Create a schedule value object (factory method).
     */
    private function createScheduleObject(
        ScheduleType $type,
        ?\DateTimeImmutable $scheduledDate = null,
        ?RecurrenceFrequency $frequency = null,
        ?\DateTimeImmutable $endDate = null,
        ?int $maxOccurrences = null,
    ): DisbursementSchedule {
        return match ($type) {
            ScheduleType::IMMEDIATE => DisbursementSchedule::immediate(),
            ScheduleType::SCHEDULED => DisbursementSchedule::scheduled($scheduledDate ?? throw InvalidScheduleException::scheduledDateRequired()),
            ScheduleType::RECURRING => DisbursementSchedule::recurring(
                startDate: $scheduledDate ?? throw InvalidScheduleException::scheduledDateRequired(),
                frequency: $frequency ?? throw InvalidScheduleException::recurrenceFrequencyRequired(),
                endDate: $endDate,
                maxOccurrences: $maxOccurrences,
            ),
        };
    }

    /**
     * Persist a schedule and update the disbursement.
     *
     * @param array<string, mixed> $logContext
     */
    private function persistSchedule(
        string $disbursementId,
        DisbursementSchedule $schedule,
        array $logContext,
    ): DisbursementInterface {
        $disbursement = $this->disbursementQuery->findById($disbursementId);

        if ($disbursement === null) {
            throw new DisbursementNotFoundException($disbursementId);
        }

        // Store the schedule
        $this->scheduleStorage->saveSchedule($disbursementId, $schedule);

        // Update disbursement scheduled date
        if ($schedule->scheduledDate !== null) {
            $disbursement->schedule($schedule->scheduledDate);
            $this->disbursementPersist->save($disbursement);
        }

        // Log with provided context
        $message = $logContext['message'] ?? 'Disbursement scheduled';
        unset($logContext['message']);

        $this->logger->info($message, array_merge([
            'disbursement_id' => $disbursementId,
        ], $logContext));

        return $disbursement;
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
