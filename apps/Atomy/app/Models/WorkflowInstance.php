<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
use Nexus\Workflow\Contracts\WorkflowInterface;

/**
 * WorkflowInstance Model
 *
 * Eloquent implementation of WorkflowInterface
 */
class WorkflowInstance extends Model implements WorkflowInterface
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'definition_id',
        'current_state',
        'subject_type',
        'subject_id',
        'data',
        'status',
        'is_locked',
        'completed_at',
    ];

    protected $casts = [
        'data' => 'array',
        'is_locked' => 'boolean',
        'completed_at' => 'datetime',
    ];

    // Relationships
    public function definition(): BelongsTo
    {
        return $this->belongsTo(WorkflowDefinition::class, 'definition_id');
    }

    public function history(): HasMany
    {
        return $this->hasMany(WorkflowHistory::class, 'workflow_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(WorkflowTask::class, 'workflow_id');
    }

    public function timers(): HasMany
    {
        return $this->hasMany(WorkflowTimer::class, 'workflow_id');
    }

    public function subject()
    {
        return $this->morphTo();
    }

    // Interface implementations
    public function getId(): string
    {
        return $this->id;
    }

    public function getDefinitionId(): string
    {
        return $this->definition_id;
    }

    public function getCurrentState(): string
    {
        return $this->current_state;
    }

    public function getSubjectType(): string
    {
        return $this->subject_type;
    }

    public function getSubjectId(): string
    {
        return $this->subject_id;
    }

    public function getData(): array
    {
        return $this->data ?? [];
    }

    public function setData(array $data): void
    {
        $this->data = $data;
    }

    public function getDataValue(string $key, mixed $default = null): mixed
    {
        return data_get($this->data, $key, $default);
    }

    public function isLocked(): bool
    {
        return $this->is_locked;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->created_at;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updated_at;
    }

    public function getCompletedAt(): ?\DateTimeInterface
    {
        return $this->completed_at;
    }
}
