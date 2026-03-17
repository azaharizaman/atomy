<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasUlids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $table = 'projects';

    protected $fillable = [
        'tenant_id',
        'name',
        'client_id',
        'start_date',
        'end_date',
        'project_manager_id',
        'status',
        'budget_type',
        'completion_percentage',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'completion_percentage' => 'float',
    ];

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'project_id');
    }

    public function rfqs(): HasMany
    {
        return $this->hasMany(Rfq::class, 'project_id');
    }
}
