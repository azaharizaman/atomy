<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nexus\Workflow\Contracts\TaskInterface;

/**
 * WorkflowTask Model
 *
 * Eloquent implementation of TaskInterface
 */
class WorkflowTask extends Model implements TaskInterface
{
    use HasUlids;

    protected $fillable = [
        'workflow_id',
        'state_name',
        'title',
        'description',
        'assigned_user_id',
        'assigned_role',
        'status',
        'priority',
        'due_at',
        'completed_at',
        'completed_by',
        'action',
        'comment',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'due_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // Relationships
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class, 'workflow_id');
    }

    // Interface implementations
    public function getId(): string
    {
        return $this->id;
    }

    public function getWorkflowId(): string
    {
        return $this->workflow_id;
    }

    public function getStateName(): string
    {
        return $this->state_name;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getAssignedUserId(): ?string
    {
        return $this->assigned_user_id;
    }

    public function getAssignedRole(): ?string
    {
        return $this->assigned_role;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getPriority(): string
    {
        return $this->priority;
    }

    public function getDueAt(): ?\DateTimeInterface
    {
        return $this->due_at;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->created_at;
    }

    public function getCompletedAt(): ?\DateTimeInterface
    {
        return $this->completed_at;
    }

    public function getCompletedBy(): ?string
    {
        return $this->completed_by;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function getMetadata(): array
    {
        return $this->metadata ?? [];
    }
}
