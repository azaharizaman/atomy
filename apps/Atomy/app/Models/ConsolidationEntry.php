<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Consolidation Entry model.
 *
 * Stores elimination entries for consolidated statements.
 *
 * @property string $id
 * @property string $parent_statement_id
 * @property string $rule_type
 * @property string $source_entity_id
 * @property string $target_entity_id
 * @property float $amount
 * @property string|null $account_code
 * @property array|null $metadata
 * @property \DateTimeImmutable $created_at
 * @property \DateTimeImmutable $updated_at
 */
final class ConsolidationEntry extends Model
{
    use HasUlids;

    protected $table = 'consolidation_entries';

    protected $fillable = [
        'parent_statement_id',
        'rule_type',
        'source_entity_id',
        'target_entity_id',
        'amount',
        'account_code',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'float',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the parent financial statement.
     */
    public function financialStatement(): BelongsTo
    {
        return $this->belongsTo(FinancialStatement::class, 'parent_statement_id');
    }

    /**
     * Scope to get entries by rule type.
     */
    public function scopeOfRuleType($query, string $ruleType)
    {
        return $query->where('rule_type', $ruleType);
    }

    /**
     * Scope to get entries by source entity.
     */
    public function scopeFromEntity($query, string $entityId)
    {
        return $query->where('source_entity_id', $entityId);
    }

    /**
     * Scope to get entries by target entity.
     */
    public function scopeToEntity($query, string $entityId)
    {
        return $query->where('target_entity_id', $entityId);
    }

    /**
     * Scope to get intercompany entries between two entities.
     */
    public function scopeIntercompany($query, string $entity1, string $entity2)
    {
        return $query->where(function ($q) use ($entity1, $entity2) {
            $q->where('source_entity_id', $entity1)
              ->where('target_entity_id', $entity2);
        })->orWhere(function ($q) use ($entity1, $entity2) {
            $q->where('source_entity_id', $entity2)
              ->where('target_entity_id', $entity1);
        });
    }

    /**
     * Get the absolute amount.
     */
    public function getAbsoluteAmount(): float
    {
        return abs($this->amount);
    }

    /**
     * Check if this is an elimination entry.
     */
    public function isElimination(): bool
    {
        return str_starts_with($this->rule_type, 'elimination_');
    }
}
