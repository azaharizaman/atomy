<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

/**
 * Period Close model.
 *
 * Tracks period close history and status.
 *
 * @property string $id
 * @property string $period_id
 * @property string $close_type
 * @property string $status
 * @property \DateTimeImmutable|null $closed_at
 * @property string|null $closed_by
 * @property \DateTimeImmutable|null $reopened_at
 * @property string|null $reopened_by
 * @property string|null $reason
 * @property array|null $validation_results
 * @property array|null $closing_entries
 * @property \DateTimeImmutable $created_at
 * @property \DateTimeImmutable $updated_at
 */
final class PeriodClose extends Model
{
    use HasUlids;

    protected $table = 'period_closes';

    protected $fillable = [
        'period_id',
        'close_type',
        'status',
        'closed_at',
        'closed_by',
        'reopened_at',
        'reopened_by',
        'reason',
        'validation_results',
        'closing_entries',
    ];

    protected $casts = [
        'validation_results' => 'array',
        'closing_entries' => 'array',
        'closed_at' => 'datetime',
        'reopened_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope to get closes by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('close_type', $type);
    }

    /**
     * Scope to get closes by status.
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get closed periods.
     */
    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    /**
     * Scope to get reopened periods.
     */
    public function scopeReopened($query)
    {
        return $query->where('status', 'reopened');
    }

    /**
     * Check if period is closed.
     */
    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    /**
     * Check if period can be reopened.
     */
    public function canBeReopened(): bool
    {
        return $this->status === 'closed';
    }

    /**
     * Mark as closed.
     */
    public function markAsClosed(string $closedBy): void
    {
        $this->status = 'closed';
        $this->closed_at = now();
        $this->closed_by = $closedBy;
        $this->save();
    }

    /**
     * Mark as reopened.
     */
    public function markAsReopened(string $reopenedBy, string $reason): void
    {
        $this->status = 'reopened';
        $this->reopened_at = now();
        $this->reopened_by = $reopenedBy;
        $this->reason = $reason;
        $this->save();
    }
}
