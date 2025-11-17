<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

/**
 * Password History model
 * 
 * @property string $id
 * @property string $user_id
 * @property string $password_hash
 * @property \Illuminate\Support\Carbon $created_at
 */
class PasswordHistory extends Model
{
    use HasUlids;

    protected $table = 'password_histories';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'password_hash',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    protected $hidden = [
        'password_hash',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->created_at = now();
        });
    }
}
