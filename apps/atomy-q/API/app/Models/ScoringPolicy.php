<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScoringPolicy extends Model
{
    use HasUlids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $table = 'scoring_policies';

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'status',
        'dimensions',
        'rules',
        'version',
        'published_at',
        'created_by',
    ];

    protected $casts = [
        'dimensions' => 'array',
        'rules' => 'array',
        'version' => 'integer',
        'published_at' => 'datetime',
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
}
