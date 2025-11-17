<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Nexus\Workflow\Contracts\DelegationInterface;

/**
 * WorkflowDelegation Model
 *
 * Eloquent implementation of DelegationInterface
 */
class WorkflowDelegation extends Model implements DelegationInterface
{
    use HasUlids;

    protected $fillable = [
        'delegator_id',
        'delegatee_id',
        'starts_at',
        'ends_at',
        'is_active',
        'chain_depth',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
        'chain_depth' => 'integer',
    ];

    // Interface implementations
    public function getId(): string
    {
        return $this->id;
    }

    public function getDelegatorId(): string
    {
        return $this->delegator_id;
    }

    public function getDelegateeId(): string
    {
        return $this->delegatee_id;
    }

    public function getStartsAt(): \DateTimeInterface
    {
        return $this->starts_at;
    }

    public function getEndsAt(): \DateTimeInterface
    {
        return $this->ends_at;
    }

    public function isActive(): bool
    {
        $now = now();
        return $this->is_active 
            && $this->starts_at <= $now 
            && $this->ends_at >= $now;
    }

    public function getChainDepth(): int
    {
        return $this->chain_depth;
    }
}
