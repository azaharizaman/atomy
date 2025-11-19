<?php

declare(strict_types=1);

namespace App\Models;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Nexus\Scheduler\Enums\JobStatus;
use Nexus\Scheduler\Enums\JobType;
use Nexus\Scheduler\ValueObjects\JobResult;
use Nexus\Scheduler\ValueObjects\ScheduleRecurrence;
use Nexus\Scheduler\ValueObjects\ScheduledJob as ScheduledJobVO;

/**
 * Scheduled Job Eloquent Model
 *
 * Database representation of a scheduled job.
 * Converts between Eloquent model and package value object.
 */
class ScheduledJob extends Model
{
    use HasUlids;
    
    protected $table = 'scheduled_jobs';
    
    protected $fillable = [
        'job_type',
        'target_id',
        'run_at',
        'status',
        'payload',
        'recurrence',
        'max_retries',
        'retry_count',
        'priority',
        'occurrence_count',
        'last_result',
        'metadata',
    ];
    
    protected $casts = [
        'run_at' => 'datetime:Y-m-d H:i:s',
        'payload' => 'array',
        'recurrence' => 'array',
        'last_result' => 'array',
        'metadata' => 'array',
        'max_retries' => 'integer',
        'retry_count' => 'integer',
        'priority' => 'integer',
        'occurrence_count' => 'integer',
    ];
    
    /**
     * Convert Eloquent model to package value object
     */
    public function toValueObject(): ScheduledJobVO
    {
        return new ScheduledJobVO(
            id: $this->id,
            jobType: JobType::from($this->job_type),
            targetId: $this->target_id,
            runAt: DateTimeImmutable::createFromMutable($this->run_at),
            status: JobStatus::from($this->status),
            payload: $this->payload ?? [],
            recurrence: $this->recurrence ? ScheduleRecurrence::fromArray($this->recurrence) : null,
            maxRetries: $this->max_retries,
            retryCount: $this->retry_count,
            priority: $this->priority,
            occurrenceCount: $this->occurrence_count,
            lastResult: $this->last_result ? JobResult::fromArray($this->last_result) : null,
            metadata: $this->metadata ?? [],
            createdAt: $this->created_at ? DateTimeImmutable::createFromMutable($this->created_at) : null,
            updatedAt: $this->updated_at ? DateTimeImmutable::createFromMutable($this->updated_at) : null,
        );
    }
    
    /**
     * Create Eloquent model from package value object
     */
    public static function fromValueObject(ScheduledJobVO $job): self
    {
        $model = new self();
        $model->id = $job->id;
        $model->job_type = $job->jobType->value;
        $model->target_id = $job->targetId;
        $model->run_at = $job->runAt;
        $model->status = $job->status->value;
        $model->payload = $job->payload;
        $model->recurrence = $job->recurrence?->toArray();
        $model->max_retries = $job->maxRetries;
        $model->retry_count = $job->retryCount;
        $model->priority = $job->priority;
        $model->occurrence_count = $job->occurrenceCount;
        $model->last_result = $job->lastResult?->toArray();
        $model->metadata = $job->metadata;
        
        return $model;
    }
    
    /**
     * Update model from value object (for existing records)
     */
    public function updateFromValueObject(ScheduledJobVO $job): void
    {
        $this->job_type = $job->jobType->value;
        $this->target_id = $job->targetId;
        $this->run_at = $job->runAt;
        $this->status = $job->status->value;
        $this->payload = $job->payload;
        $this->recurrence = $job->recurrence?->toArray();
        $this->max_retries = $job->maxRetries;
        $this->retry_count = $job->retryCount;
        $this->priority = $job->priority;
        $this->occurrence_count = $job->occurrenceCount;
        $this->last_result = $job->lastResult?->toArray();
        $this->metadata = $job->metadata;
    }
}
