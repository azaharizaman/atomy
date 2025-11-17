<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Nexus\Workflow\Contracts\TimerInterface;

/**
 * WorkflowTimer Model
 *
 * Eloquent implementation of TimerInterface
 */
class WorkflowTimer extends Model implements TimerInterface
{
    use HasUlids;

    protected $fillable = [
        'workflow_id',
        'type',
        'trigger_at',
        'action',
        'is_fired',
        'fired_at',
    ];

    protected $casts = [
        'action' => 'array',
        'is_fired' => 'boolean',
        'trigger_at' => 'datetime',
        'fired_at' => 'datetime',
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

    public function getType(): string
    {
        return $this->type;
    }

    public function getTriggerAt(): \DateTimeInterface
    {
        return $this->trigger_at;
    }

    public function getAction(): array
    {
        return $this->action ?? [];
    }

    public function isFired(): bool
    {
        return $this->is_fired;
    }

    public function getFiredAt(): ?\DateTimeInterface
    {
        return $this->fired_at;
    }
}
