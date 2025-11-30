<?php

declare(strict_types=1);

namespace Nexus\Scheduler\ValueObjects;

use DateTimeImmutable;
use Nexus\Scheduler\Contracts\ClockInterface;
use Nexus\Scheduler\Enums\JobStatus;
use Nexus\Scheduler\Enums\JobType;

/**
 * Scheduled Job Value Object
 *
 * Represents a scheduled job with its complete state.
 * Immutable value object with business logic methods.
 */
final readonly class ScheduledJob
{
    /**
     * @param string $id Unique job identifier (ULID)
     * @param JobType $jobType Type of job to execute
     * @param string $targetId ULID of the target entity
     * @param DateTimeImmutable $runAt When to execute the job
     * @param JobStatus $status Current job status
     * @param array<string, mixed> $payload Job-specific data
     * @param ScheduleRecurrence|null $recurrence How the job repeats
     * @param int $maxRetries Maximum retry attempts
     * @param int $retryCount Current retry count
     * @param int $priority Job priority
     * @param int $occurrenceCount Number of times this recurring job has run
     * @param JobResult|null $lastResult Result of last execution
     * @param array<string, mixed> $metadata Additional metadata
     * @param DateTimeImmutable $createdAt When the job was created
     * @param DateTimeImmutable|null $updatedAt Last update timestamp
     */
    public function __construct(
        public string $id,
        public JobType $jobType,
        public string $targetId,
        public DateTimeImmutable $runAt,
        public JobStatus $status,
        public array $payload = [],
        public ?ScheduleRecurrence $recurrence = null,
        public int $maxRetries = 3,
        public int $retryCount = 0,
        public int $priority = 0,
        public int $occurrenceCount = 0,
        public ?JobResult $lastResult = null,
        public array $metadata = [],
        public ?DateTimeImmutable $createdAt = null,
        public ?DateTimeImmutable $updatedAt = null,
    ) {
        if (strlen($id) !== 26 || !ctype_alnum($id)) {
            throw new \InvalidArgumentException('Job ID must be a valid ULID');
        }
        
        if (strlen($targetId) !== 26 || !ctype_alnum($targetId)) {
            throw new \InvalidArgumentException('Target ID must be a valid ULID');
        }
    }
    
    /**
     * Create from schedule definition
     */
    public static function fromDefinition(string $id, ScheduleDefinition $definition): self
    {
        return new self(
            id: $id,
            jobType: $definition->jobType,
            targetId: $definition->targetId,
            runAt: $definition->runAt,
            status: JobStatus::PENDING,
            payload: $definition->payload,
            recurrence: $definition->recurrence,
            maxRetries: $definition->maxRetries,
            priority: $definition->priority,
            metadata: $definition->metadata,
            createdAt: new DateTimeImmutable(),
        );
    }
    
    /**
     * Check if the job is due to run
     */
    public function isDue(ClockInterface $clock): bool
    {
        if (!$this->status->canExecute()) {
            return false;
        }
        
        return $clock->now() >= $this->runAt;
    }
    
    /**
     * Check if the job is overdue
     */
    public function isOverdue(ClockInterface $clock): bool
    {
        if (!$this->status->canExecute()) {
            return false;
        }
        
        $now = $clock->now();
        $overdueThreshold = $this->runAt->modify('+5 minutes');
        
        return $now >= $overdueThreshold;
    }
    
    /**
     * Check if the job is nearing its execution time
     */
    public function isNearingExpiry(ClockInterface $clock, int $minutesBefore = 5): bool
    {
        if (!$this->status->canExecute()) {
            return false;
        }
        
        $now = $clock->now();
        $threshold = $this->runAt->modify("-{$minutesBefore} minutes");
        
        return $now >= $threshold && $now < $this->runAt;
    }
    
    /**
     * Get time interval until execution
     */
    public function getIntervalToExpiry(ClockInterface $clock): int
    {
        $now = $clock->now();
        return $this->runAt->getTimestamp() - $now->getTimestamp();
    }
    
    /**
     * Calculate the next run time based on recurrence
     */
    public function getNextRunTime(ClockInterface $clock): ?DateTimeImmutable
    {
        if ($this->recurrence === null || !$this->recurrence->isRepeating()) {
            return null;
        }
        
        // Check if recurrence has ended
        if ($this->recurrence->hasEnded($clock->now(), $this->occurrenceCount)) {
            return null;
        }
        
        // For cron expressions, this would be calculated by RecurrenceEngine
        // For simple intervals, calculate from current runAt
        $intervalSeconds = $this->recurrence->getIntervalSeconds();
        if ($intervalSeconds === null) {
            return null; // Cron-based, needs RecurrenceEngine
        }
        
        return $this->runAt->modify("+{$intervalSeconds} seconds");
    }
    
    /**
     * Check if the job can be executed
     */
    public function canExecute(): bool
    {
        return $this->status->canExecute();
    }
    
    /**
     * Check if the job can be retried
     */
    public function canRetry(): bool
    {
        return $this->status->canRetry() && $this->retryCount < $this->maxRetries;
    }
    
    /**
     * Check if max retries have been reached
     */
    public function hasExceededMaxRetries(): bool
    {
        return $this->retryCount >= $this->maxRetries;
    }
    
    /**
     * Check if this is a recurring job
     */
    public function isRecurring(): bool
    {
        return $this->recurrence !== null && $this->recurrence->isRepeating();
    }
    
    /**
     * Create a new job with updated status
     */
    public function withStatus(JobStatus $newStatus): self
    {
        if (!$this->status->canTransitionTo($newStatus)) {
            throw new \LogicException(
                "Cannot transition from {$this->status->value} to {$newStatus->value}"
            );
        }
        
        return new self(
            id: $this->id,
            jobType: $this->jobType,
            targetId: $this->targetId,
            runAt: $this->runAt,
            status: $newStatus,
            payload: $this->payload,
            recurrence: $this->recurrence,
            maxRetries: $this->maxRetries,
            retryCount: $this->retryCount,
            priority: $this->priority,
            occurrenceCount: $this->occurrenceCount,
            lastResult: $this->lastResult,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable(),
        );
    }
    
    /**
     * Create a new job with incremented retry count
     */
    public function withIncrementedRetry(): self
    {
        return new self(
            id: $this->id,
            jobType: $this->jobType,
            targetId: $this->targetId,
            runAt: $this->runAt,
            status: $this->status,
            payload: $this->payload,
            recurrence: $this->recurrence,
            maxRetries: $this->maxRetries,
            retryCount: $this->retryCount + 1,
            priority: $this->priority,
            occurrenceCount: $this->occurrenceCount,
            lastResult: $this->lastResult,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable(),
        );
    }
    
    /**
     * Create a new job with updated run time
     */
    public function withRunAt(DateTimeImmutable $runAt): self
    {
        return new self(
            id: $this->id,
            jobType: $this->jobType,
            targetId: $this->targetId,
            runAt: $runAt,
            status: $this->status,
            payload: $this->payload,
            recurrence: $this->recurrence,
            maxRetries: $this->maxRetries,
            retryCount: $this->retryCount,
            priority: $this->priority,
            occurrenceCount: $this->occurrenceCount,
            lastResult: $this->lastResult,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable(),
        );
    }
    
    /**
     * Create a new job with execution result
     */
    public function withResult(JobResult $result): self
    {
        return new self(
            id: $this->id,
            jobType: $this->jobType,
            targetId: $this->targetId,
            runAt: $this->runAt,
            status: $this->status,
            payload: $this->payload,
            recurrence: $this->recurrence,
            maxRetries: $this->maxRetries,
            retryCount: $this->retryCount,
            priority: $this->priority,
            occurrenceCount: $this->occurrenceCount,
            lastResult: $result,
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable(),
        );
    }
    
    /**
     * Create a new job for the next occurrence (recurring jobs)
     */
    public function forNextOccurrence(DateTimeImmutable $nextRunAt): self
    {
        return new self(
            id: $this->id,
            jobType: $this->jobType,
            targetId: $this->targetId,
            runAt: $nextRunAt,
            status: JobStatus::PENDING,
            payload: $this->payload,
            recurrence: $this->recurrence,
            maxRetries: $this->maxRetries,
            retryCount: 0, // Reset retry count
            priority: $this->priority,
            occurrenceCount: $this->occurrenceCount + 1,
            lastResult: null, // Clear last result
            metadata: $this->metadata,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable(),
        );
    }
    
    /**
     * Convert to array for serialization
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'jobType' => $this->jobType->value,
            'targetId' => $this->targetId,
            'runAt' => $this->runAt->format('c'),
            'status' => $this->status->value,
            'payload' => $this->payload,
            'recurrence' => $this->recurrence?->toArray(),
            'maxRetries' => $this->maxRetries,
            'retryCount' => $this->retryCount,
            'priority' => $this->priority,
            'occurrenceCount' => $this->occurrenceCount,
            'lastResult' => $this->lastResult?->toArray(),
            'metadata' => $this->metadata,
            'createdAt' => $this->createdAt?->format('c'),
            'updatedAt' => $this->updatedAt?->format('c'),
        ];
    }
}
