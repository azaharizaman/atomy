<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Checklist Response Eloquent Model
 *
 * @property string $id
 * @property string $work_order_id
 * @property string $checklist_template_id
 * @property array $responses
 * @property \DateTimeInterface $completed_at
 */
class ChecklistResponse extends Model
{
    use HasUlids;

    protected $fillable = [
        'work_order_id',
        'checklist_template_id',
        'responses',
        'completed_at',
    ];

    protected $casts = [
        'responses' => 'array',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Business Logic

    public function getResponseValue(string $itemLabel): mixed
    {
        return $this->responses[$itemLabel] ?? null;
    }

    public function isItemCompleted(string $itemLabel): bool
    {
        return isset($this->responses[$itemLabel]);
    }

    public function getCompletionPercentage(): float
    {
        $template = $this->template;
        $totalItems = count($template->items);
        
        if ($totalItems === 0) {
            return 100.0;
        }

        $completedItems = count($this->responses);
        return ($completedItems / $totalItems) * 100;
    }

    // Eloquent Relationships

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ChecklistTemplate::class, 'checklist_template_id');
    }
}
