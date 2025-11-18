<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class NotificationHistory extends Model
{
    use HasUlids;

    protected $table = 'notification_history';

    protected $fillable = [
        'notification_id',
        'recipient_id',
        'notification_type',
        'channel',
        'priority',
        'category',
        'status',
        'content',
        'recipient_data',
        'metadata',
        'tracking_external_id',
        'retry_count',
        'scheduled_at',
        'sent_at',
        'delivered_at',
        'failed_at',
    ];

    protected $casts = [
        'content' => 'array',
        'recipient_data' => 'array',
        'metadata' => 'array',
        'retry_count' => 'integer',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'failed_at' => 'datetime',
    ];
}
