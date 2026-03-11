<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Scenario extends Model
{
    use HasUlids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $table = 'scenarios';

    protected $fillable = [
        'tenant_id',
        'rfq_id',
        'comparison_run_id',
        'name',
        'description',
        'config',
        'status',
    ];

    protected $casts = [
        'config' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Rfq, $this>
     */
    public function rfq(): BelongsTo
    {
        return $this->belongsTo(Rfq::class, 'rfq_id');
    }

    /**
     * @return BelongsTo<ComparisonRun, $this>
     */
    public function comparisonRun(): BelongsTo
    {
        return $this->belongsTo(ComparisonRun::class, 'comparison_run_id');
    }
}
