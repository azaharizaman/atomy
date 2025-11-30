<?php

declare(strict_types=1);

namespace Nexus\Scheduler\ValueObjects;

use DateTimeImmutable;
use Nexus\Scheduler\Enums\JobType;

/**
 * Schedule Definition Value Object
 *
 * Data transfer object for creating a new scheduled job.
 * Immutable value object used as input to ScheduleManager::schedule().
 */
final readonly class ScheduleDefinition
{
    /**
     * @param JobType $jobType Type of job to execute
     * @param string $targetId ULID of the target entity
     * @param DateTimeImmutable $runAt When to execute the job
     * @param array<string, mixed> $payload Job-specific data
     * @param ScheduleRecurrence|null $recurrence How the job should repeat
     * @param int $maxRetries Maximum retry attempts
     * @param int $priority Job priority (higher = more important)
     * @param array<string, mixed> $metadata Additional metadata
     */
    public function __construct(
        public JobType $jobType,
        public string $targetId,
        public DateTimeImmutable $runAt,
        public array $payload = [],
        public ?ScheduleRecurrence $recurrence = null,
        public int $maxRetries = 3,
        public int $priority = 0,
        public array $metadata = [],
    ) {
        if (trim($targetId) === '') {
            throw new \InvalidArgumentException('Target ID cannot be empty');
        }
        
        if ($maxRetries < 0) {
            throw new \InvalidArgumentException('Maximum retries must be non-negative');
        }
        
        // Validate target ID format (ULID - 26 characters)
        if (strlen($targetId) !== 26 || !ctype_alnum($targetId)) {
            throw new \InvalidArgumentException('Target ID must be a valid ULID (26 alphanumeric characters)');
        }
    }
    
    /**
     * Create a one-time job definition
     */
    public static function once(
        JobType $jobType,
        string $targetId,
        DateTimeImmutable $runAt,
        array $payload = []
    ): self {
        return new self(
            jobType: $jobType,
            targetId: $targetId,
            runAt: $runAt,
            payload: $payload,
            recurrence: ScheduleRecurrence::once(),
        );
    }
    
    /**
     * Create a recurring job definition
     */
    public static function recurring(
        JobType $jobType,
        string $targetId,
        DateTimeImmutable $runAt,
        ScheduleRecurrence $recurrence,
        array $payload = []
    ): self {
        return new self(
            jobType: $jobType,
            targetId: $targetId,
            runAt: $runAt,
            payload: $payload,
            recurrence: $recurrence,
        );
    }
    
    /**
     * Check if this is a recurring job
     */
    public function isRecurring(): bool
    {
        return $this->recurrence !== null && $this->recurrence->isRepeating();
    }
    
    /**
     * Create a new definition with different run time
     */
    public function withRunAt(DateTimeImmutable $runAt): self
    {
        return new self(
            jobType: $this->jobType,
            targetId: $this->targetId,
            runAt: $runAt,
            payload: $this->payload,
            recurrence: $this->recurrence,
            maxRetries: $this->maxRetries,
            priority: $this->priority,
            metadata: $this->metadata,
        );
    }
    
    /**
     * Create a new definition with additional metadata
     *
     * @param array<string, mixed> $additionalMetadata
     */
    public function withMetadata(array $additionalMetadata): self
    {
        return new self(
            jobType: $this->jobType,
            targetId: $this->targetId,
            runAt: $this->runAt,
            payload: $this->payload,
            recurrence: $this->recurrence,
            maxRetries: $this->maxRetries,
            priority: $this->priority,
            metadata: array_merge($this->metadata, $additionalMetadata),
        );
    }
}
