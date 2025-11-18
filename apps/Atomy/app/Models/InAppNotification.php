<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class InAppNotification extends Model
{
    use HasUlids;

    protected $table = 'in_app_notifications';

    protected $fillable = [
        'recipient_id',
        'title',
        'message',
        'link',
        'icon',
        'priority',
        'category',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];
}
