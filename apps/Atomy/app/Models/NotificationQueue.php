<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class NotificationQueue extends Model
{
    use HasUlids;

    protected $table = 'notification_queue';

    protected $fillable = [
        'notification_id',
        'recipient_id',
        'channel',
        'priority',
        'status',
        'payload',
        'scheduled_at',
        'processed_at',
        'attempts',
    ];

    protected $casts = [
        'payload' => 'array',
        'scheduled_at' => 'datetime',
        'processed_at' => 'datetime',
        'attempts' => 'integer',
    ];
}
