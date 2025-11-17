<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

/**
 * Login Attempt model
 * 
 * @property string $id
 * @property string|null $user_id
 * @property string $email
 * @property bool $successful
 * @property string $ip_address
 * @property string|null $user_agent
 * @property string|null $failure_reason
 * @property \Illuminate\Support\Carbon $attempted_at
 */
class LoginAttempt extends Model
{
    use HasUlids;

    protected $table = 'login_attempts';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'email',
        'successful',
        'ip_address',
        'user_agent',
        'failure_reason',
        'attempted_at',
    ];

    protected $casts = [
        'successful' => 'boolean',
        'attempted_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->attempted_at = now();
        });
    }
}
