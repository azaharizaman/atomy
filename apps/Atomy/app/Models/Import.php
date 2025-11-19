<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Import Model
 * 
 * @property string $id
 * @property string $handler_type
 * @property string $mode
 * @property string $status
 * @property string $original_file_name
 * @property int $file_size
 * @property string $mime_type
 * @property \DateTimeImmutable $uploaded_at
 * @property string $uploaded_by
 * @property string $tenant_id
 * @property \DateTimeImmutable|null $started_at
 * @property \DateTimeImmutable|null $completed_at
 * @property \DateTimeImmutable $created_at
 * @property \DateTimeImmutable $updated_at
 */
final class Import extends Model
{
    use HasUlids;

    protected $table = 'imports';

    protected $fillable = [
        'id',
        'handler_type',
        'mode',
        'status',
        'original_file_name',
        'file_size',
        'mime_type',
        'uploaded_at',
        'uploaded_by',
        'tenant_id',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'uploaded_at' => 'immutable_datetime',
        'started_at' => 'immutable_datetime',
        'completed_at' => 'immutable_datetime',
    ];

    public function errors(): HasMany
    {
        return $this->hasMany(ImportError::class, 'import_id');
    }
}
