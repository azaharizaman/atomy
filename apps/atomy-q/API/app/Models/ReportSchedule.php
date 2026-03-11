<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReportSchedule extends Model
{
    use HasUlids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $table = 'report_schedules';

    protected $fillable = [
        'tenant_id',
        'name',
        'report_type',
        'schedule_cron',
        'parameters',
        'status',
        'last_run_at',
        'next_run_at',
        'created_by',
    ];

    protected $casts = [
        'parameters' => 'array',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<ReportRun>
     */
    public function runs(): HasMany
    {
        return $this->hasMany(ReportRun::class, 'schedule_id');
    }
}
