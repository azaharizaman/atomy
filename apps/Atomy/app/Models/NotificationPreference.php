<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class NotificationPreference extends Model
{
    use HasUlids;

    protected $fillable = [
        'recipient_id',
        'preferred_channels',
        'category_preferences',
        'quiet_hours',
        'global_opt_out',
    ];

    protected $casts = [
        'preferred_channels' => 'array',
        'category_preferences' => 'array',
        'quiet_hours' => 'array',
        'global_opt_out' => 'boolean',
    ];
}
