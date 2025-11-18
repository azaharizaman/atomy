<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

/**
 * Financial Statement model.
 *
 * Stores generated financial statements with versioning support.
 *
 * @property string $id
 * @property string $statement_type
 * @property string $entity_id
 * @property string $period_id
 * @property array $data
 * @property int $version
 * @property string|null $compliance_standard
 * @property \DateTimeImmutable $generated_at
 * @property string $generated_by
 * @property bool $locked
 * @property \DateTimeImmutable $created_at
 * @property \DateTimeImmutable $updated_at
 */
final class FinancialStatement extends Model
{
    use HasUlids;

    protected $table = 'financial_statements';

    protected $fillable = [
        'statement_type',
        'entity_id',
        'period_id',
        'data',
        'version',
        'compliance_standard',
        'generated_at',
        'generated_by',
        'locked',
    ];

    protected $casts = [
        'data' => 'array',
        'version' => 'integer',
        'locked' => 'boolean',
        'generated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope to get statements by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('statement_type', $type);
    }

    /**
     * Scope to get statements by entity.
     */
    public function scopeForEntity($query, string $entityId)
    {
        return $query->where('entity_id', $entityId);
    }

    /**
     * Scope to get statements by period.
     */
    public function scopeForPeriod($query, string $periodId)
    {
        return $query->where('period_id', $periodId);
    }

    /**
     * Scope to get only locked statements.
     */
    public function scopeLocked($query)
    {
        return $query->where('locked', true);
    }

    /**
     * Scope to get latest version.
     */
    public function scopeLatestVersion($query)
    {
        return $query->orderBy('version', 'desc');
    }

    /**
     * Check if statement is editable.
     */
    public function isEditable(): bool
    {
        return !$this->locked;
    }

    /**
     * Lock the statement.
     */
    public function lock(): void
    {
        $this->locked = true;
        $this->save();
    }
}
