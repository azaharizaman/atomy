<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Nexus\Workflow\Contracts\WorkflowDefinitionInterface;

/**
 * WorkflowDefinition Model
 *
 * Eloquent implementation of WorkflowDefinitionInterface
 */
class WorkflowDefinition extends Model implements WorkflowDefinitionInterface
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'name',
        'version',
        'states',
        'transitions',
        'initial_state',
        'data_schema',
        'is_active',
        'description',
    ];

    protected $casts = [
        'states' => 'array',
        'transitions' => 'array',
        'data_schema' => 'array',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function instances(): HasMany
    {
        return $this->hasMany(WorkflowInstance::class, 'definition_id');
    }

    // Interface implementations
    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getStates(): array
    {
        // Transform array data to StateInterface objects
        return $this->states ?? [];
    }

    public function getState(string $name): \Nexus\Workflow\Contracts\StateInterface
    {
        // Find and return state by name
        throw new \Nexus\Workflow\Exceptions\StateNotFoundException::withName($name);
    }

    public function getTransitions(): array
    {
        // Transform array data to TransitionInterface objects
        return $this->transitions ?? [];
    }

    public function getAvailableTransitions(string $fromState): array
    {
        // Filter transitions available from given state
        return [];
    }

    public function getInitialState(): string
    {
        return $this->initial_state;
    }

    public function getDataSchema(): array
    {
        return $this->data_schema ?? [];
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }
}
