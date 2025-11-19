<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ImportError Model
 * 
 * @property string $id
 * @property string $import_id
 * @property int|null $row_number
 * @property string|null $field
 * @property string $severity
 * @property string $code
 * @property string $message
 * @property array|null $context
 * @property \DateTimeImmutable $created_at
 * @property \DateTimeImmutable $updated_at
 */
final class ImportError extends Model
{
    use HasUlids;

    protected $table = 'import_errors';

    protected $fillable = [
        'id',
        'import_id',
        'row_number',
        'field',
        'severity',
        'code',
        'message',
        'context',
    ];

    protected $casts = [
        'context' => 'array',
    ];

    public function import(): BelongsTo
    {
        return $this->belongsTo(Import::class, 'import_id');
    }
}
