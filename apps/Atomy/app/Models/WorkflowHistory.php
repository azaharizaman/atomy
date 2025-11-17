<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nexus\Workflow\Contracts\HistoryInterface;

/**
 * WorkflowHistory Model
 *
 * Eloquent implementation of HistoryInterface
 */
class WorkflowHistory extends Model implements HistoryInterface
{
    use HasUlids;

    public $timestamps = false;

    protected $table = 'workflow_history';

    protected $fillable = [
        'workflow_id',
        'transition',
        'from_state',
        'to_state',
        'actor_id',
        'comment',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
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

    public function getTransition(): ?string
    {
        return $this->transition;
    }

    public function getFromState(): string
    {
        return $this->from_state;
    }

    public function getToState(): string
    {
        return $this->to_state;
    }

    public function getActorId(): ?string
    {
        return $this->actor_id;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function getMetadata(): array
    {
        return $this->metadata ?? [];
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->created_at;
    }
}
