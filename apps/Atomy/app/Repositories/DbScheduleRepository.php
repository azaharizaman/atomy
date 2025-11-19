<?php

declare(strict_types=1);

namespace App\Repositories;

use DateTimeImmutable;
use App\Models\ScheduledJob as ScheduledJobModel;
use Nexus\Scheduler\Contracts\ScheduleRepositoryInterface;
use Nexus\Scheduler\Enums\JobStatus;
use Nexus\Scheduler\Enums\JobType;
use Nexus\Scheduler\ValueObjects\ScheduledJob;

/**
 * Database Schedule Repository
 *
 * Eloquent implementation of ScheduleRepositoryInterface.
 * Manages job persistence with MySQL/PostgreSQL.
 */
final readonly class DbScheduleRepository implements ScheduleRepositoryInterface
{
    /**
     * Find a job by ID
     */
    public function find(string $id): ?ScheduledJob
    {
        $model = ScheduledJobModel::find($id);
        
        return $model?->toValueObject();
    }
    
    /**
     * Find all due jobs at the given time
     */
    public function findDue(DateTimeImmutable $asOf): array
    {
        $models = ScheduledJobModel::query()
            ->where('status', JobStatus::PENDING->value)
            ->where('run_at', '<=', $asOf)
            ->orderBy('priority', 'desc')
            ->orderBy('run_at', 'asc')
            ->get();
        
        return $models->map(fn($model) => $model->toValueObject())->all();
    }
    
    /**
     * Find jobs by type
     */
    public function findByType(JobType $jobType): array
    {
        $models = ScheduledJobModel::query()
            ->where('job_type', $jobType->value)
            ->orderBy('run_at', 'desc')
            ->get();
        
        return $models->map(fn($model) => $model->toValueObject())->all();
    }
    
    /**
     * Find jobs by target entity ID
     */
    public function findByTarget(string $targetId): array
    {
        $models = ScheduledJobModel::query()
            ->where('target_id', $targetId)
            ->orderBy('run_at', 'desc')
            ->get();
        
        return $models->map(fn($model) => $model->toValueObject())->all();
    }
    
    /**
     * Find jobs by status
     */
    public function findByStatus(JobStatus $status, int $limit = 100): array
    {
        $models = ScheduledJobModel::query()
            ->where('status', $status->value)
            ->orderBy('run_at', 'desc')
            ->limit($limit)
            ->get();
        
        return $models->map(fn($model) => $model->toValueObject())->all();
    }
    
    /**
     * Save a scheduled job
     */
    public function save(ScheduledJob $job): void
    {
        $model = ScheduledJobModel::find($job->id);
        
        if ($model === null) {
            // Create new record
            $model = ScheduledJobModel::fromValueObject($job);
            $model->save();
        } else {
            // Update existing record
            $model->updateFromValueObject($job);
            $model->save();
        }
    }
    
    /**
     * Delete a scheduled job
     */
    public function delete(string $id): bool
    {
        $model = ScheduledJobModel::find($id);
        
        if ($model === null) {
            return false;
        }
        
        return (bool)$model->delete();
    }
    
    /**
     * Count jobs by status
     */
    public function count(?JobStatus $status = null): int
    {
        $query = ScheduledJobModel::query();
        
        if ($status !== null) {
            $query->where('status', $status->value);
        }
        
        return $query->count();
    }
}
