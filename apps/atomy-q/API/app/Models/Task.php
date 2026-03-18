<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    use HasUlids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $table = 'tasks';

    protected $fillable = [
        'tenant_id',
        'title',
        'description',
        'status',
        'priority',
        'due_date',
        'completed_at',
        'project_id',
        'assignee_ids',
        'predecessor_ids',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'completed_at' => 'datetime',
        'assignee_ids' => 'array',
        'predecessor_ids' => 'array',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
}
