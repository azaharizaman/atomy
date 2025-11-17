<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

/**
 * MFA Enrollment model
 * 
 * @property string $id
 * @property string $user_id
 * @property string $method
 * @property string|null $secret
 * @property array|null $backup_codes
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $enrolled_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class MfaEnrollment extends Model
{
    use HasUlids;

    protected $table = 'mfa_enrollments';

    protected $fillable = [
        'user_id',
        'method',
        'secret',
        'backup_codes',
        'is_active',
        'enrolled_at',
    ];

    protected $casts = [
        'backup_codes' => 'array',
        'is_active' => 'boolean',
        'enrolled_at' => 'datetime',
    ];

    protected $hidden = [
        'secret',
        'backup_codes',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
