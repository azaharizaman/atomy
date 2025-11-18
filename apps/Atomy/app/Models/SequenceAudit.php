<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eloquent model for sequence audit logs.
 *
 * @property string $id
 * @property string $sequence_id
 * @property string $event_type
 * @property array $event_data
 * @property string|null $performed_by
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read Sequence $sequence
 */
class SequenceAudit extends Model
{
    protected $table = 'sequence_audits';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'sequence_id',
        'event_type',
        'event_data',
        'performed_by',
    ];

    protected $casts = [
        'event_data' => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (SequenceAudit $audit): void {
            if (empty($audit->id)) {
                $audit->id = (string) \Illuminate\Support\Str::ulid();
            }
        });
    }

    public function sequence(): BelongsTo
    {
        return $this->belongsTo(Sequence::class, 'sequence_id');
    }
}
